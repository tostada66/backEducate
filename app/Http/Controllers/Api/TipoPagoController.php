<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoPago;
use Illuminate\Http\Request;

class TipoPagoController extends Controller
{
    /**
     * Listar todos los tipos de pago
     */
    public function index()
    {
        return response()->json(TipoPago::all(), 200);
    }

    /**
     * Crear un nuevo tipo de pago
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:80|unique:tipos_pagos,nombre',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $pago = TipoPago::create($validated);

        return response()->json([
            'message' => 'Tipo de pago creado correctamente',
            'data' => $pago
        ], 201);
    }

    /**
     * Mostrar un tipo de pago específico
     */
    public function show($idpago)
    {
        $pago = TipoPago::find($idpago);

        if (!$pago) {
            return response()->json(['message' => 'Tipo de pago no encontrado'], 404);
        }

        return response()->json($pago, 200);
    }

    /**
     * Actualizar un tipo de pago
     */
    public function update(Request $request, $idpago)
    {
        $pago = TipoPago::find($idpago);

        if (!$pago) {
            return response()->json(['message' => 'Tipo de pago no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:80|unique:tipos_pagos,nombre,' . $idpago . ',idpago',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $pago->update($validated);

        return response()->json([
            'message' => 'Tipo de pago actualizado correctamente',
            'data' => $pago
        ], 200);
    }

    /**
     * Eliminar un tipo de pago
     */
    public function destroy($idpago)
    {
        $pago = TipoPago::find($idpago);

        if (!$pago) {
            return response()->json(['message' => 'Tipo de pago no encontrado'], 404);
        }

        $pago->delete();

        return response()->json(['message' => 'Tipo de pago eliminado correctamente'], 200);
    }
}
