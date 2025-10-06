<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Matricula;
use App\Models\Curso;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    /**
     * 📌 Inscribir al estudiante logueado en un curso
     * - Si ya existía y estaba inactiva → se reactiva
     * - Si nunca existió → se crea nueva
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
            // Reactivar matrícula existente
            $matricula->estado = 'activa';
            $matricula->fecha = today(); // 👈 fecha en formato YYYY-MM-DD
            $matricula->save();
        } else {
            // Crear nueva matrícula
            $matricula = Matricula::create([
                'idestudiante' => $user->estudiante->idestudiante,
                'idcurso'      => $curso->idcurso,
                'fecha'        => today(), // 👈 fecha correcta
                'estado'       => 'activa',

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
     * - No elimina el registro, solo lo marca como inactivo
     * - Así el progreso queda guardado
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

    // 👇 Aquí el cambio
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
     * 📌 Listar cursos inscritos del estudiante
     * - Solo muestra los que están activos
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

        $matriculas = Matricula::with(['curso.categoria', 'curso.profesor'])
            ->where('idestudiante', $user->estudiante->idestudiante)
            ->where('estado', 'activa')
            ->get();

        return response()->json($matriculas);
    }
}
