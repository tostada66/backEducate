<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Matricula;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    /**
     * 📌 Inscribir al estudiante logueado en un curso
     */
    public function inscribir(Request $request, $idcurso)
    {
        $user = $request->user();

        if (!$user->estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo estudiantes pueden inscribirse'
            ], 403);
        }

        $curso = Curso::where('estado', 'publicado')->findOrFail($idcurso);

        $matricula = Matricula::where('idestudiante', $user->estudiante->idestudiante)
            ->where('idcurso', $curso->idcurso)
            ->first();

        if ($matricula) {
            // 🔁 Reactivar matrícula existente
            $matricula->estado = 'activa';
            $matricula->fecha = today();
            $matricula->porcentaje_avance = $matricula->porcentaje_avance ?? 0; // 👈 asegurar campo
            $matricula->save();
        } else {
            // 🆕 Crear nueva matrícula con avance inicial 0
            $matricula = Matricula::create([
                'idestudiante'       => $user->estudiante->idestudiante,
                'idcurso'            => $curso->idcurso,
                'fecha'              => today(),
                'estado'             => 'activa',
                'porcentaje_avance'  => 0, // 👈 nuevo campo
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Inscripción exitosa',
            'matricula' => $matricula->load('curso'),
        ], 201);
    }

    /**
     * 📌 Desuscribir al estudiante de un curso
     */
    public function desuscribir(Request $request, $idcurso)
    {
        $user = $request->user();

        if (!$user->estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo estudiantes pueden desuscribirse'
            ], 403);
        }

        $matricula = Matricula::where('idestudiante', $user->estudiante->idestudiante)
            ->where('idcurso', $idcurso)
            ->first();

        if (!$matricula) {
            return response()->json([
                'ok' => false,
                'message' => 'No estás inscrito en este curso'
            ], 404);
        }

        $matricula->estado = 'cancelada';
        $matricula->fecha = today();
        $matricula->save();

        return response()->json([
            'ok' => true,
            'message' => 'Te desuscribiste del curso correctamente',
            'matricula' => $matricula
        ]);
    }

    /**
     * 📊 Listar cursos inscritos del estudiante (con información completa)
     */
    public function misCursos(Request $request)
    {
        $user = $request->user();

        if (!$user->estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo estudiantes'
            ], 403);
        }

        $matriculas = Matricula::with([
            'curso.categoria',
            'curso.profesor.usuario', // 👨‍🏫 mostrar nombre completo
        ])
            ->where('idestudiante', $user->estudiante->idestudiante)
            ->where('estado', 'activa')
            ->get();

        // 🔹 Reestructuramos salida para el frontend
        $result = $matriculas->map(function ($m) {
            $curso = $m->curso;
            $profesorUsuario = $curso->profesor?->usuario;

            return [
                'idmatricula'       => $m->idmatricula,
                'idcurso'           => $curso->idcurso,
                'nombre'            => $curso->nombre,
                'nivel'             => $curso->nivel ?: 'General',
                'descripcion'       => $curso->descripcion ?: 'Sin descripción',
                'duracion_total'    => $curso->duracion_total ?: 'No definida',
                'categoria'         => $curso->categoria,
                'imagen'            => $curso->imagen,
                'imagen_url'        => $curso->imagen
                    ? asset('storage/' . ltrim($curso->imagen, '/'))
                    : asset('storage/default_image.png'),
                'profesor' => [
                    'idprofesor'      => $curso->profesor?->idprofesor,
                    'nombre_completo' => $profesorUsuario
                        ? trim("{$profesorUsuario->nombres} {$profesorUsuario->apellidos}")
                        : 'No asignado',
                ],
                // 📊 Ahora siempre numérico (float)
                'porcentaje_avance' => (float) $m->porcentaje_avance,
            ];
        });

        return response()->json($result);
    }
}
