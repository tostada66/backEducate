<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgresoClase;
use App\Models\Matricula;
use Illuminate\Http\Request;

class ProgresoClaseController extends Controller
{
    /**
     * Marcar una clase como completada
     */
    public function completar(Request $request, $idmatricula, $idclase)
    {
        $matricula = Matricula::findOrFail($idmatricula);

        // Verificar que pertenece al estudiante logueado
        if ($matricula->idestudiante !== $request->user()->estudiante->idestudiante) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $progreso = ProgresoClase::updateOrCreate(
            [
                'idmatricula' => $matricula->idmatricula,
                'idclase' => $idclase,
            ],
            [
                'completado' => true,
                'progreso' => 100,
                'ultima_vista_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'progreso' => $progreso,
            'avance_curso' => $matricula->porcentaje_avance,
        ]);
    }
}
