<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CursoJuego;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CursoJuegoController extends Controller
{
    /**
     * 📋 Listar juegos de una unidad
     */
    public function index($idunidad)
    {
        $juegos = CursoJuego::with(['juego:idjuego,nombre', 'unidad:idunidad,titulo'])
            ->where('idunidad', $idunidad)
            ->orderBy('idcursojuego', 'desc')
            ->get()
            ->map(function ($juego) {
                $juego->imagen_url = $juego->imagen
                    ? asset('storage/' . ltrim($juego->imagen, '/'))
                    : null;
                return $juego;
            });

        return response()->json([
            'ok'    => true,
            'total' => $juegos->count(),
            'data'  => $juegos,
        ]);
    }

    /**
     * 👁 Mostrar detalle de un juego dentro de una unidad
     */
    public function show($idcursojuego)
    {
        $cursoJuego = CursoJuego::with('juego:idjuego,nombre')->find($idcursojuego);

        if (!$cursoJuego) {
            return response()->json([
                'ok'      => false,
                'message' => 'Juego no encontrado en esta unidad.',
            ], 404);
        }

        $cursoJuego->imagen_url = $cursoJuego->imagen
            ? asset('storage/' . ltrim($cursoJuego->imagen, '/'))
            : null;

        return response()->json([
            'ok'   => true,
            'data' => $cursoJuego,
        ]);
    }

    /**
     * ➕ Crear juego en una unidad
     * 📍 POST /api/juegos/unidad/{idunidad}
     */
    public function store(Request $request, $idunidad)
    {
        $validated = $request->validate([
            'idjuego'     => 'required|exists:juegos,idjuego',
            'nombre_tema' => 'nullable|string|max:150',
            'nivel'       => 'nullable|integer|min:1|max:10',
            'imagen'      => 'nullable|file|image|max:2048',
        ]);

        $cursoJuego = new CursoJuego();
        $cursoJuego->idunidad    = $idunidad;
        $cursoJuego->idjuego     = $validated['idjuego'];
        $cursoJuego->nombre_tema = $validated['nombre_tema'] ?? null;
        $cursoJuego->nivel       = $validated['nivel'] ?? 1;
        $cursoJuego->activo      = true;

        if ($request->hasFile('imagen')) {
            $cursoJuego->imagen = $request->file('imagen')->store('unidad_juegos', 'public');
        }

        $cursoJuego->save();

        $cursoJuego->imagen_url = $cursoJuego->imagen
            ? asset('storage/' . ltrim($cursoJuego->imagen, '/'))
            : null;

        return response()->json([
            'ok'      => true,
            'message' => 'Juego agregado a la unidad correctamente.',
            'data'    => $cursoJuego,
        ]);
    }

    /**
     * 🧩 Asignar un juego base a una unidad (simple)
     * 📍 POST /api/juegos/unidad/{idunidad}/asignar
     */
    public function asignarJuegoUnidad(Request $request, $idunidad)
    {
        $request->validate([
            'idjuego' => 'required|exists:juegos,idjuego',
        ]);

        // 🔍 Verificar si ya existe
        $existe = CursoJuego::where('idunidad', $idunidad)
            ->where('idjuego', $request->idjuego)
            ->first();

        if ($existe) {
            $existe->imagen_url = $existe->imagen
                ? asset('storage/' . ltrim($existe->imagen, '/'))
                : null;

            return response()->json([
                'ok'      => false,
                'message' => 'Este juego ya está asignado a la unidad.',
                'data'    => $existe,
            ], 200);
        }

        $cursoJuego = CursoJuego::create([
            'idunidad' => $idunidad,
            'idjuego'  => $request->idjuego,
            'activo'   => true,
        ]);

        $cursoJuego->imagen_url = null;

        return response()->json([
            'ok'      => true,
            'message' => 'Juego asignado correctamente a la unidad.',
            'data'    => $cursoJuego,
        ], 201);
    }

    /**
     * ✏️ Actualizar configuración o imagen del juego
     */
    public function update(Request $request, $idcursojuego)
    {
        $cursoJuego = CursoJuego::find($idcursojuego);

        if (!$cursoJuego) {
            return response()->json([
                'ok'      => false,
                'message' => 'Juego no encontrado en esta unidad.',
            ], 404);
        }

        $validated = $request->validate([
            'nombre_tema' => 'nullable|string|max:150',
            'nivel'       => 'nullable|integer|min:1|max:10',
            'imagen'      => 'nullable|file|image|max:2048',
            'activo'      => 'boolean',
        ]);

        if ($request->hasFile('imagen')) {
            if ($cursoJuego->imagen) {
                Storage::disk('public')->delete($cursoJuego->imagen);
            }
            $cursoJuego->imagen = $request->file('imagen')->store('unidad_juegos', 'public');
        }

        $cursoJuego->fill([
            'nombre_tema' => $validated['nombre_tema'] ?? $cursoJuego->nombre_tema,
            'nivel'       => $validated['nivel'] ?? $cursoJuego->nivel,
            'activo'      => $validated['activo'] ?? $cursoJuego->activo,
        ]);

        $cursoJuego->save();

        $cursoJuego->imagen_url = $cursoJuego->imagen
            ? asset('storage/' . ltrim($cursoJuego->imagen, '/'))
            : null;

        return response()->json([
            'ok'      => true,
            'message' => 'Juego actualizado correctamente.',
            'data'    => $cursoJuego,
        ]);
    }

    /**
     * 🟥 Dar de baja un juego de la unidad (marcar como inactivo)
     * PATCH /api/curso-juego/{idcursojuego}/baja
     */
    public function darDeBaja($idcursojuego)
    {
        $cursoJuego = CursoJuego::find($idcursojuego);

        if (!$cursoJuego) {
            return response()->json([
                'ok'      => false,
                'message' => 'Juego no encontrado.',
            ], 404);
        }

        $cursoJuego->activo = false;
        $cursoJuego->save();

        $cursoJuego->imagen_url = $cursoJuego->imagen
            ? asset('storage/' . ltrim($cursoJuego->imagen, '/'))
            : null;

        return response()->json([
            'ok'      => true,
            'message' => 'Juego dado de baja correctamente.',
            'data'    => $cursoJuego,
        ]);
    }

    /**
     * 🟢 Reactivar juego de la unidad
     * PATCH /api/curso-juego/{idcursojuego}/reactivar
     */
    public function reactivar($idcursojuego)
    {
        $cursoJuego = CursoJuego::find($idcursojuego);

        if (!$cursoJuego) {
            return response()->json([
                'ok'      => false,
                'message' => 'Juego no encontrado.',
            ], 404);
        }

        $cursoJuego->activo = true;
        $cursoJuego->save();

        $cursoJuego->imagen_url = $cursoJuego->imagen
            ? asset('storage/' . ltrim($cursoJuego->imagen, '/'))
            : null;

        return response()->json([
            'ok'      => true,
            'message' => 'Juego reactivado correctamente.',
            'data'    => $cursoJuego,
        ]);
    }

    /**
     * ❌ Eliminar juego de la unidad
     */
    public function destroy($idcursojuego)
    {
        $cursoJuego = CursoJuego::find($idcursojuego);

        if (!$cursoJuego) {
            return response()->json([
                'ok'      => false,
                'message' => 'Juego no encontrado.',
            ], 404);
        }

        if ($cursoJuego->imagen) {
            Storage::disk('public')->delete($cursoJuego->imagen);
        }

        $cursoJuego->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Juego eliminado correctamente de la unidad.',
        ]);
    }
}
