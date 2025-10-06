<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoPlan;
use Illuminate\Http\Request;

class TipoPlanController extends Controller
{
    /**
     * Mostrar todos los planes disponibles
     */
    public function index()
    {
        return response()->json(TipoPlan::all(), 200);
    }

    /**
     * Crear un nuevo plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:tipo_planes,nombre',
            'descripcion' => 'nullable|string|max:255',
            'precio' => 'required|numeric|min:0',
        ]);

        $plan = TipoPlan::create($validated);

        return response()->json([
            'message' => 'Plan creado correctamente',
            'data' => $plan
        ], 201);
    }

    /**
     * Mostrar un plan específico
     */
    public function show($idplan)
    {
        $plan = TipoPlan::find($idplan);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        return response()->json($plan, 200);
    }

    /**
     * Actualizar un plan existente
     */
    public function update(Request $request, $idplan)
    {
        $plan = TipoPlan::find($idplan);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100|unique:tipo_planes,nombre,' . $idplan . ',idplan',
            'descripcion' => 'nullable|string|max:255',
            'precio' => 'sometimes|numeric|min:0',
        ]);

        $plan->update($validated);

        return response()->json([
            'message' => 'Plan actualizado correctamente',
            'data' => $plan
        ], 200);
    }

    /**
     * Eliminar un plan
     */
    public function destroy($idplan)
    {
        $plan = TipoPlan::find($idplan);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $plan->delete();

        return response()->json(['message' => 'Plan eliminado correctamente'], 200);
    }
}
