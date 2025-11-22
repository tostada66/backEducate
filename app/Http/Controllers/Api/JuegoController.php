<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Juego;
use Illuminate\Http\Request;

class JuegoController extends Controller
{
    /**
     * 🎮 Listar todos los juegos base (predefinidos)
     */
    public function index(Request $request)
    {
        $query = Juego::query();

        // 🔹 Filtrar por estado activo/inactivo (opcional)
        if ($request->filled('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        $juegos = $query->orderBy('nombre')->get();

        return response()->json([
            'ok' => true,
            'total' => $juegos->count(),
            'data' => $juegos,
        ]);
    }

    /**
     * 🎮 Mostrar detalle de un juego base
     */
    public function show($idjuego)
    {
        $juego = Juego::find($idjuego);

        if (!$juego) {
            return response()->json([
                'ok' => false,
                'message' => 'Juego no encontrado.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $juego,
        ]);
    }

    /**
     * 🚫 Crear juego base (bloqueado)
     */
    public function store()
    {
        return response()->json([
            'ok' => false,
            'message' => 'No se permite crear nuevos juegos base. Usa los juegos predefinidos.',
        ], 403);
    }

    /**
     * ✏️ Editar juego (solo descripción o estado)
     */
    public function update(Request $request, $idjuego)
    {
        $juego = Juego::find($idjuego);

        if (!$juego) {
            return response()->json([
                'ok' => false,
                'message' => 'Juego no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $juego->fill([
            'descripcion' => $validated['descripcion'] ?? $juego->descripcion,
            'activo' => $validated['activo'] ?? $juego->activo,
        ]);

        $juego->save();

        return response()->json([
            'ok' => true,
            'message' => 'Juego actualizado correctamente.',
            'data' => $juego,
        ]);
    }

    /**
     * 🚫 Eliminar juego base (bloqueado)
     */
    public function destroy()
    {
        return response()->json([
            'ok' => false,
            'message' => 'No se permite eliminar juegos base.',
        ], 403);
    }
}
