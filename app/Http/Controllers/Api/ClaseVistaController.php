<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClaseVista;
use App\Models\Matricula;
use App\Models\Clase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaseVistaController extends Controller
{
    /**
     * 🔹 Registrar o actualizar el progreso de un video
     * PATCH /api/vistas
     */
    public function updateProgreso(Request $request)
    {
        $data = $request->validate([
            'idclase'        => 'required|integer|exists:clases,idclase',
            'idcontenido'    => 'required|integer|exists:contenidos,idcontenido',
            'segundo_actual' => 'required|integer|min:0',
            'duracion'       => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $estudiante = $user->estudiante ?? null;

        // 🔒 Solo estudiantes
        if (!$estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo estudiantes pueden registrar progreso.',
            ], 403);
        }

        // 🎯 Buscar el curso al que pertenece la clase
        $clase = Clase::with('unidad.curso')->findOrFail($data['idclase']);
        $idcurso = $clase->unidad->idcurso ?? null;

        if (!$idcurso) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo determinar el curso de la clase.',
            ], 422);
        }

        // 🎓 Buscar matrícula activa del estudiante para este curso
        $matricula = Matricula::where('idestudiante', $estudiante->idestudiante)
            ->where('idcurso', $idcurso)
            ->where('estado', 'activa')
            ->first();

        if (!$matricula) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes estar matriculado en este curso para registrar progreso.',
            ], 422);
        }

        // ✅ Buscar o crear registro de vista
        $vista = ClaseVista::firstOrNew([
            'idclase'      => $data['idclase'],
            'idcontenido'  => $data['idcontenido'],
            'idestudiante' => $estudiante->idestudiante,
        ]);

        // Asociar matrícula activa
        $vista->idmatricula = $matricula->idmatricula;

        // 🔄 Aplicar y guardar progreso
        $vista->applySample(
            $data['segundo_actual'],
            $data['duracion']
        )->syncAndSave();

        // ✅ Si el video se completó, actualizar el progreso global del curso
        if ($vista->completado && $vista->idmatricula && $vista->matricula) {
            $vista->matricula->actualizarProgresoCurso();
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'porcentaje'      => $vista->porcentaje,
                'completado'      => $vista->completado,
                'ultimo_segundo'  => $vista->ultimo_segundo,
            ],
        ]);
    }

    /**
     * 🔹 Obtener progreso actual del video
     * GET /api/vistas/{idcontenido}
     */
    public function getProgreso($idcontenido)
    {
        $user = Auth::user();
        $estudiante = $user->estudiante ?? null;

        if (!$estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo estudiantes pueden consultar progreso.',
            ], 403);
        }

        $vista = ClaseVista::where('idcontenido', $idcontenido)
            ->where('idestudiante', $estudiante->idestudiante)
            ->first();

        return response()->json([
            'ok' => true,
            'data' => $vista
                ? [
                    'porcentaje'      => $vista->porcentaje,
                    'ultimo_segundo'  => $vista->ultimo_segundo,
                    'completado'      => $vista->completado,
                ]
                : null,
        ]);
    }
}
