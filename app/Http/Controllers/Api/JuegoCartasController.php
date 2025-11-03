<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\JuegoCartasPar;
use App\Models\IntentoJuego;

class JuegoCartasController extends Controller
{
    /* ===========================================================
     * 🎮 PARTE ESTUDIANTES
     * =========================================================== */

    /**
     * 📤 Listar cartas aleatorias (modo estudiante)
     * GET /api/juego-cartas/{idcursojuego}?cantidad=8
     */
    public function listar($idcursojuego, Request $request)
    {
        $cantidad = intval($request->get('cantidad', 8));

        $pares = JuegoCartasPar::where('idcursojuego', $idcursojuego)
            ->activos()
            ->inRandomOrder()
            ->take($cantidad)
            ->get()
            ->map(function ($p) {
                $p->imagen_a_url = $p->imagen_a ? asset('storage/' . ltrim($p->imagen_a, '/')) : null;
                $p->imagen_b_url = $p->imagen_b ? asset('storage/' . ltrim($p->imagen_b, '/')) : null;
                return $p;
            });

        return response()->json([
            'ok' => true,
            'cantidad' => $pares->count(),
            'data' => $pares,
        ]);
    }

    /**
     * 💾 Guardar intento del jugador
     * POST /api/juego-cartas/guardar-intento
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
     * 📋 Listar todas las cartas del juego
     * GET /api/curso-juego/{idcursojuego}/cartas
     */
    public function listarCartas($idcursojuego)
    {
        $pares = JuegoCartasPar::where('idcursojuego', $idcursojuego)
            ->orderBy('idpar', 'desc')
            ->get()
            ->map(function ($p) {
                $p->imagen_a_url = $p->imagen_a ? asset('storage/' . ltrim($p->imagen_a, '/')) : null;
                $p->imagen_b_url = $p->imagen_b ? asset('storage/' . ltrim($p->imagen_b, '/')) : null;
                return $p;
            });

        return response()->json([
            'ok' => true,
            'total' => $pares->count(),
            'data' => $pares,
        ]);
    }

    /**
     * ➕ Guardar una o varias cartas (pares A–B)
     * POST /api/curso-juego/{idcursojuego}/cartas
     */
    public function guardarCarta(Request $request, $idcursojuego)
    {
        $pares = $request->input('pares', []);
        if (empty($pares)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se enviaron pares.',
            ], 400);
        }

        $guardadas = [];
        foreach ($pares as $p) {
            $carta = new JuegoCartasPar();
            $carta->idcursojuego = $idcursojuego;
            $carta->texto_a = $p['texto_a'] ?? null;
            $carta->texto_b = $p['texto_b'] ?? null;
            $carta->activo = true;

            // 📸 Guardar imagenes base64 (si las hay)
            if (!empty($p['imagen_a']) && str_starts_with($p['imagen_a'], 'data:image')) {
                $carta->imagen_a = $this->guardarImagenBase64($p['imagen_a'], 'juego_cartas');
            } elseif (!empty($p['imagen_a'])) {
                $carta->imagen_a = $p['imagen_a'];
            }

            if (!empty($p['imagen_b']) && str_starts_with($p['imagen_b'], 'data:image')) {
                $carta->imagen_b = $this->guardarImagenBase64($p['imagen_b'], 'juego_cartas');
            } elseif (!empty($p['imagen_b'])) {
                $carta->imagen_b = $p['imagen_b'];
            }

            $carta->save();

            $carta->imagen_a_url = $carta->imagen_a ? asset('storage/' . ltrim($carta->imagen_a, '/')) : null;
            $carta->imagen_b_url = $carta->imagen_b ? asset('storage/' . ltrim($carta->imagen_b, '/')) : null;

            $guardadas[] = $carta;
        }

        return response()->json([
            'ok' => true,
            'message' => 'Pares guardados correctamente.',
            'data' => $guardadas,
        ], 201);
    }

    /**
     * ✏️ Actualizar par
     * PUT /api/curso-juego/carta/{idpar}
     */
    public function updateCarta(Request $request, $idpar)
    {
        $par = JuegoCartasPar::find($idpar);
        if (!$par) {
            return response()->json(['ok' => false, 'message' => 'Par no encontrado.'], 404);
        }

        $data = $request->validate([
            'texto_a' => 'nullable|string|max:150',
            'texto_b' => 'nullable|string|max:150',
            'imagen_a' => 'nullable',
            'imagen_b' => 'nullable',
            'activo' => 'nullable|boolean',
        ]);

        // 🔄 Actualizar imágenes (base64 o rutas nuevas)
        if (!empty($data['imagen_a']) && str_starts_with($data['imagen_a'], 'data:image')) {
            if ($par->imagen_a) Storage::disk('public')->delete($par->imagen_a);
            $par->imagen_a = $this->guardarImagenBase64($data['imagen_a'], 'juego_cartas');
        }

        if (!empty($data['imagen_b']) && str_starts_with($data['imagen_b'], 'data:image')) {
            if ($par->imagen_b) Storage::disk('public')->delete($par->imagen_b);
            $par->imagen_b = $this->guardarImagenBase64($data['imagen_b'], 'juego_cartas');
        }

        $par->fill([
            'texto_a' => $data['texto_a'] ?? $par->texto_a,
            'texto_b' => $data['texto_b'] ?? $par->texto_b,
            'activo' => $data['activo'] ?? $par->activo,
        ])->save();

        $par->imagen_a_url = $par->imagen_a ? asset('storage/' . ltrim($par->imagen_a, '/')) : null;
        $par->imagen_b_url = $par->imagen_b ? asset('storage/' . ltrim($par->imagen_b, '/')) : null;

        return response()->json([
            'ok' => true,
            'message' => 'Par actualizado correctamente.',
            'data' => $par,
        ]);
    }

    /**
     * ❌ Eliminar par
     * DELETE /api/curso-juego/carta/{idpar}
     */
    public function eliminarCarta($idpar)
    {
        $par = JuegoCartasPar::find($idpar);
        if (!$par) {
            return response()->json(['ok' => false, 'message' => 'Par no encontrado.'], 404);
        }

        if ($par->imagen_a) Storage::disk('public')->delete($par->imagen_a);
        if ($par->imagen_b) Storage::disk('public')->delete($par->imagen_b);

        $par->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Par eliminado correctamente.',
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
