<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\JuegoReciclajeItem;
use App\Models\IntentoJuego;

class JuegoReciclajeController extends Controller
{
    /* ===========================================================
     * 🎮 PARTE ESTUDIANTES
     * =========================================================== */

    /**
     * 📤 Listar ítems aleatorios (modo estudiante)
     * GET /api/juego-reciclaje/{idcursojuego}?cantidad=8
     */
    public function listar($idcursojuego, Request $request)
    {
        $cantidad = intval($request->get('cantidad', 8));

        $items = JuegoReciclajeItem::where('idcursojuego', $idcursojuego)
            ->activos()
            ->inRandomOrder()
            ->take($cantidad)
            ->get()
            ->map(function ($i) {
                $i->imagen_url = $i->imagen ? asset('storage/' . ltrim($i->imagen, '/')) : null;
                return $i;
            });

        return response()->json([
            'ok' => true,
            'cantidad' => $items->count(),
            'data' => $items,
        ]);
    }

    /**
     * 💾 Guardar intento del jugador
     * POST /api/juego-reciclaje/guardar-intento
     */
    public function guardarIntento(Request $request)
    {
        $data = $request->validate([
            'idestudiante' => 'required|integer|exists:estudiantes,idestudiante',
            'idcursojuego' => 'required|integer|exists:curso_juego,idcursojuego',
            'puntaje'      => 'required|integer|min:0',
            'aciertos'     => 'required|integer|min:0',
            'errores'      => 'required|integer|min:0',
            'tiempo'       => 'required|integer|min:0',
            'detalles'     => 'nullable|array',
        ]);

        $intento = IntentoJuego::create([
            'idestudiante' => $data['idestudiante'],
            'idcursojuego' => $data['idcursojuego'],
            'puntaje'      => $data['puntaje'],
            'aciertos'     => $data['aciertos'],
            'errores'      => $data['errores'],
            'tiempo'       => $data['tiempo'],
            'detalles'     => json_encode($data['detalles'] ?? []),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Intento guardado correctamente.',
            'data' => $intento,
        ], 201);
    }

    /* ===========================================================
     * 🧩 PARTE PROFESOR / ADMIN
     * =========================================================== */

    /**
     * 📋 Listar todos los ítems del juego de reciclaje
     * GET /api/curso-juego/{idcursojuego}/reciclaje
     */
    public function listarItems($idcursojuego)
    {
        $items = JuegoReciclajeItem::where('idcursojuego', $idcursojuego)
            ->orderBy('iditem', 'desc')
            ->get()
            ->map(function ($i) {
                $i->imagen_url = $i->imagen ? asset('storage/' . ltrim($i->imagen, '/')) : null;
                return $i;
            });

        return response()->json([
            'ok' => true,
            'total' => $items->count(),
            'data' => $items,
        ]);
    }

    /**
     * ➕ Guardar uno o varios ítems
     * POST /api/curso-juego/{idcursojuego}/reciclaje
     */
    public function guardarItem(Request $request, $idcursojuego)
    {
        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se enviaron ítems.',
            ], 400);
        }

        $guardados = [];
        foreach ($items as $i) {
            $item = new JuegoReciclajeItem();
            $item->idcursojuego = $idcursojuego;
            $item->nombre = $i['nombre'] ?? null;
            $item->tipo = $i['tipo'] ?? null;
            $item->activo = true;

            // 📸 Guardar imagen (base64 o ruta existente)
            if (!empty($i['imagen']) && str_starts_with($i['imagen'], 'data:image')) {
                $item->imagen = $this->guardarImagenBase64($i['imagen'], 'juego_reciclaje');
            } elseif (!empty($i['imagen'])) {
                $item->imagen = $i['imagen'];
            }

            $item->save();
            $item->imagen_url = $item->imagen ? asset('storage/' . ltrim($item->imagen, '/')) : null;

            $guardados[] = $item;
        }

        return response()->json([
            'ok' => true,
            'message' => 'Ítems guardados correctamente.',
            'data' => $guardados,
        ], 201);
    }

    /**
     * ✏️ Actualizar ítem
     * PUT /api/curso-juego/reciclaje/{iditem}
     */
    public function updateItem(Request $request, $iditem)
    {
        $item = JuegoReciclajeItem::find($iditem);
        if (!$item) {
            return response()->json(['ok' => false, 'message' => 'Ítem no encontrado.'], 404);
        }

        $data = $request->validate([
            'nombre' => 'nullable|string|max:150',
            'tipo' => 'nullable|string|max:50',
            'imagen' => 'nullable',
            'activo' => 'nullable|boolean',
        ]);

        // 🔄 Actualizar imagen (si viene en base64)
        if (!empty($data['imagen']) && str_starts_with($data['imagen'], 'data:image')) {
            if ($item->imagen) Storage::disk('public')->delete($item->imagen);
            $item->imagen = $this->guardarImagenBase64($data['imagen'], 'juego_reciclaje');
        }

        $item->fill([
            'nombre' => $data['nombre'] ?? $item->nombre,
            'tipo' => $data['tipo'] ?? $item->tipo,
            'activo' => $data['activo'] ?? $item->activo,
        ])->save();

        $item->imagen_url = $item->imagen ? asset('storage/' . ltrim($item->imagen, '/')) : null;

        return response()->json([
            'ok' => true,
            'message' => 'Ítem actualizado correctamente.',
            'data' => $item,
        ]);
    }

    /**
     * ❌ Eliminar ítem
     * DELETE /api/curso-juego/reciclaje/{iditem}
     */
    public function eliminarItem($iditem)
    {
        $item = JuegoReciclajeItem::find($iditem);
        if (!$item) {
            return response()->json(['ok' => false, 'message' => 'Ítem no encontrado.'], 404);
        }

        if ($item->imagen) Storage::disk('public')->delete($item->imagen);
        $item->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Ítem eliminado correctamente.',
        ]);
    }

    /* ===========================================================
     * 🧰 MÉTODO AUXILIAR
     * =========================================================== */
    private function guardarImagenBase64($base64, $carpeta = 'uploads')
    {
        $data = explode(',', $base64);
        $mime = explode('/', explode(';', $data[0])[0])[1];
        $contenido = base64_decode($data[1]);
        $nombreArchivo = $carpeta . '/' . uniqid() . '.' . $mime;
        Storage::disk('public')->put($nombreArchivo, $contenido);
        return $nombreArchivo;
    }
}
