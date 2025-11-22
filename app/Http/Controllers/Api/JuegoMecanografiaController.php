<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CursoJuego;
use App\Models\JuegoMecanografiaPalabra;
use Illuminate\Http\Request;

class JuegoMecanografiaController extends Controller
{
    /**
     * 🧩 Listar palabras de mecanografía por idcursojuego
     */
    public function listarPalabras($idcursojuego)
    {
        if (!CursoJuego::whereKey($idcursojuego)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró la unidad-juego solicitada.'
            ], 404);
        }

        $palabras = JuegoMecanografiaPalabra::where('idcursojuego', $idcursojuego)
            ->orderBy('idpalabra')
            ->get(['idpalabra', 'idcursojuego', 'palabra', 'tiempo', 'dificultad', 'activo', 'created_at']);

        $niveles = $palabras->pluck('dificultad')->unique()->values();

        return response()->json([
            'ok' => true,
            'cursojuego' => (int) $idcursojuego,
            'data' => $palabras,
            'niveles' => $niveles
        ], 200);
    }

    /**
     * 💾 Guardar o reemplazar todas las palabras
     */
    public function guardarPalabras(Request $request, $idcursojuego)
    {
        if (!CursoJuego::whereKey($idcursojuego)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Juego no encontrado en esta unidad.'
            ], 404);
        }

        $palabras = $request->input('palabras', []);
        if (!is_array($palabras)) {
            return response()->json([
                'ok' => false,
                'message' => 'Formato inválido: "palabras" debe ser un arreglo.'
            ], 422);
        }

        // 🧹 Eliminar todas las palabras anteriores del juego
        JuegoMecanografiaPalabra::where('idcursojuego', $idcursojuego)->delete();

        // ➕ Crear nuevas
        foreach ($palabras as $p) {
            JuegoMecanografiaPalabra::create([
                'idcursojuego' => $idcursojuego,
                'palabra'      => $p['palabra'] ?? '',
                'tiempo'       => $p['tiempo'] ?? 10, // segundos por defecto
                'dificultad'   => $p['dificultad'] ?? 'fácil',
                'activo'       => $p['activo'] ?? true,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Palabras guardadas correctamente.'
        ], 201);
    }

    /**
     * ✏️ Actualizar palabra individual
     */
    public function update(Request $request, $idpalabra)
    {
        $palabra = JuegoMecanografiaPalabra::find($idpalabra);
        if (!$palabra) {
            return response()->json([
                'ok' => false,
                'message' => 'Palabra no encontrada.'
            ], 404);
        }

        $validated = $request->validate([
            'palabra'     => 'sometimes|string|max:255',
            'tiempo'      => 'nullable|integer|min:1|max:120',
            'dificultad'  => 'nullable|in:fácil,medio,difícil',
            'activo'      => 'boolean',
        ]);

        $palabra->update($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Palabra actualizada correctamente.',
            'data' => $palabra
        ], 200);
    }

    /**
     * ❌ Eliminar palabra individual
     */
    public function destroy($idpalabra)
    {
        $palabra = JuegoMecanografiaPalabra::find($idpalabra);
        if (!$palabra) {
            return response()->json([
                'ok' => false,
                'message' => 'Palabra no encontrada.'
            ], 404);
        }

        $palabra->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Palabra eliminada correctamente.'
        ], 200);
    }
}
