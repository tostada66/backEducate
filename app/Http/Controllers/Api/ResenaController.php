<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Resena;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class ResenaController extends Controller
{
    /**
     * 📋 Listar reseñas de un curso (público o autenticado)
     */
    public function listarPorCurso(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);
        $puedeComentar = null; // Por defecto: null si no está logueado
        $yaComento = false;
        $matriculado = false;

        $usuario = auth()->user();

        // 🟢 Intentar obtener usuario desde el token si no está autenticado aún
        if (!$usuario && $request->bearerToken()) {
            $token = $request->bearerToken();
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $usuario = $accessToken->tokenable;
            }
        }

        // 🔹 Si hay usuario autenticado, verificamos si puede comentar
        if ($usuario) {
            // Buscar si el usuario es estudiante
            $estudiante = Estudiante::where('idusuario', $usuario->idusuario)->first();

            if ($estudiante) {
                // Verificar matrícula activa
                $matriculado = Matricula::where('idcurso', $curso->idcurso)
                    ->where('idestudiante', $estudiante->idestudiante)
                    ->where('estado', 'activa')
                    ->exists();

                // Verificar si ya dejó reseña
                $yaComento = Resena::where('idcurso', $curso->idcurso)
                    ->where('idestudiante', $estudiante->idestudiante)
                    ->exists();

                // ✅ Puede comentar si está matriculado y no ha comentado aún
                $puedeComentar = $matriculado && !$yaComento;
            } else {
                // Si es profesor o admin, no puede comentar
                $puedeComentar = false;
            }
        }

        // 🔹 Obtener reseñas con info del estudiante y usuario
        $resenas = Resena::where('idcurso', $curso->idcurso)
            ->with(['estudiante.usuario:idusuario,nombres,apellidos,foto'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                return [
                    'idresena' => $r->idresena,
                    'idusuario' => $r->estudiante?->usuario?->idusuario,
                    'autor_nombre' => trim(
                        ($r->estudiante?->usuario?->nombres ?? '') . ' ' .
                        ($r->estudiante?->usuario?->apellidos ?? '')
                    ),
                    'foto_url' => $r->estudiante?->usuario?->foto
                        ? asset('storage/' . $r->estudiante->usuario->foto)
                        : '/images/avatar-default.png',
                    'puntuacion' => $r->puntuacion,
                    'comentario' => $r->comentario,
                    'created_at' => $r->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $resenas,
            'promedio' => round($resenas->avg('puntuacion') ?? 0, 1),
            'total' => $resenas->count(),
            'puedeComentar' => $puedeComentar,
            'yaComento' => $yaComento, // 👈 nuevo campo para el frontend
        ]);
    }

    /**
     * 💬 Crear una nueva reseña
     */
    public function store(Request $request, $idcurso)
    {
        $request->validate([
            'puntuacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:2000',
        ]);

        $curso = Curso::findOrFail($idcurso);
        $usuario = auth()->user();

        // 🔍 Obtener estudiante asociado al usuario
        $estudiante = Estudiante::where('idusuario', $usuario->idusuario)->first();
        if (!$estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo los estudiantes pueden dejar reseñas.'
            ], 403);
        }

        // ✅ Verificar si está matriculado en el curso
        $matriculado = Matricula::where('idcurso', $curso->idcurso)
            ->where('idestudiante', $estudiante->idestudiante)
            ->where('estado', 'activa')
            ->exists();

        if (!$matriculado) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo puedes dejar reseña en cursos en los que estés matriculado.'
            ], 403);
        }

        // 🚫 Evitar duplicados (una reseña por estudiante por curso)
        $yaExiste = Resena::where('idcurso', $curso->idcurso)
            ->where('idestudiante', $estudiante->idestudiante)
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya has dejado una reseña para este curso.'
            ], 409);
        }

        // 💾 Crear reseña
        $resena = Resena::create([
            'idcurso' => $curso->idcurso,
            'idestudiante' => $estudiante->idestudiante,
            'puntuacion' => $request->puntuacion,
            'comentario' => $request->comentario,
        ]);

        $resena->load('estudiante.usuario:idusuario,nombres,apellidos,foto');

        return response()->json([
            'ok' => true,
            'message' => 'Reseña publicada correctamente.',
            'data' => [
                'idresena' => $resena->idresena,
                'autor_nombre' => trim(
                    ($resena->estudiante?->usuario?->nombres ?? '') . ' ' .
                    ($resena->estudiante?->usuario?->apellidos ?? '')
                ),
                'foto_url' => $resena->estudiante?->usuario?->foto
                    ? asset('storage/' . $resena->estudiante->usuario->foto)
                    : '/images/avatar-default.png',
                'puntuacion' => $resena->puntuacion,
                'comentario' => $resena->comentario,
                'created_at' => $resena->created_at->diffForHumans(),
            ]
        ]);
    }

    /**
     * ❌ Eliminar reseña (autor, profesor o admin)
     */
    public function destroy($idresena)
    {
        $resena = Resena::findOrFail($idresena);
        $usuario = auth()->user();

        // 🔹 Autor de la reseña
        $estudiante = Estudiante::where('idusuario', $usuario->idusuario)->first();
        if ($estudiante && $estudiante->idestudiante === $resena->idestudiante) {
            $resena->delete();

            return response()->json([
                'ok' => true,
                'message' => 'Reseña eliminada por el autor.'
            ]);
        }

        // 🔹 Profesor del curso
        $profesorAuth = DB::table('profesores')
            ->where('idusuario', $usuario->idusuario)
            ->value('idprofesor');

        $profesorCurso = DB::table('cursos')
            ->where('idcurso', $resena->idcurso)
            ->value('idprofesor');

        if ($profesorAuth && $profesorAuth == $profesorCurso) {
            $resena->delete();

            return response()->json([
                'ok' => true,
                'message' => 'Reseña eliminada por el profesor del curso.'
            ]);
        }

        // 🔹 Admin
        if ($usuario->rolRel && $usuario->rolRel->nombre === 'admin') {
            $resena->delete();

            return response()->json([
                'ok' => true,
                'message' => 'Reseña eliminada por el administrador.'
            ]);
        }

        // 🚫 Sin permisos
        return response()->json([
            'ok' => false,
            'message' => 'No tienes permiso para eliminar esta reseña.'
        ], 403);
    }
}
