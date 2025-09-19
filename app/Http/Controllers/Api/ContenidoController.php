<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use App\Models\Contenido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContenidoController extends Controller
{
    /**
     * 📂 Listar contenidos de una clase
     */
    public function index($idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $contenidos = $clase->contenidos()
            ->orderBy('orden')
            ->get();

        // Agregar URL pública si hay archivo
        $contenidos->transform(function ($c) {
            if ($c->url && !filter_var($c->url, FILTER_VALIDATE_URL)) {
                $c->url_publica = Storage::url($c->url);
            }
            return $c;
        });

        return response()->json($contenidos);
    }

    /**
     * ➕ Crear contenido
     */
    public function store(Request $request, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'tipo'        => 'required|string|max:50',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:5120', // hasta 5MB
            'orden'       => 'nullable|integer',
            'estado'      => 'in:borrador,publicado'
        ]);

        $contenido = new Contenido();
        $contenido->idclase     = $clase->idclase;
        $contenido->titulo      = $data['titulo'];
        $contenido->descripcion = $data['descripcion'] ?? null;
        $contenido->tipo        = $data['tipo'];
        $contenido->orden       = $data['orden'] ?? 1;
        $contenido->estado      = $data['estado'] ?? 'borrador';

        // Guardar archivo si se envía
        if ($request->hasFile('archivo')) {
            $path = $request->file('archivo')->store('contenidos', 'public');
            $contenido->url = $path;
        } else {
            $contenido->url = $data['url'] ?? null;
        }

        $contenido->save();

        // Agregar URL pública si es archivo
        if ($contenido->url && !filter_var($contenido->url, FILTER_VALIDATE_URL)) {
            $contenido->url_publica = Storage::url($contenido->url);
        }

        return response()->json($contenido, 201);
    }

    /**
     * 👁 Mostrar un contenido
     */
    public function show($idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)->findOrFail($idcontenido);

        if ($contenido->url && !filter_var($contenido->url, FILTER_VALIDATE_URL)) {
            $contenido->url_publica = Storage::url($contenido->url);
        }

        return response()->json($contenido);
    }

    /**
     * ✏️ Actualizar contenido
     */
    public function update(Request $request, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)->findOrFail($idcontenido);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'tipo'        => 'sometimes|string|max:50',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:5120',
            'orden'       => 'nullable|integer',
            'estado'      => 'in:borrador,publicado'
        ]);

        if (isset($data['titulo'])) {
            $contenido->titulo = $data['titulo'];
        }
        if (isset($data['descripcion'])) {
            $contenido->descripcion = $data['descripcion'];
        }
        if (isset($data['tipo'])) {
            $contenido->tipo = $data['tipo'];
        }
        if (isset($data['orden'])) {
            $contenido->orden = $data['orden'];
        }
        if (isset($data['estado'])) {
            $contenido->estado = $data['estado'];
        }

        // Actualizar archivo si viene
        if ($request->hasFile('archivo')) {
            if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
                Storage::disk('public')->delete($contenido->url);
            }
            $path = $request->file('archivo')->store('contenidos', 'public');
            $contenido->url = $path;
        } elseif (isset($data['url'])) {
            $contenido->url = $data['url'];
        }

        $contenido->save();

        if ($contenido->url && !filter_var($contenido->url, FILTER_VALIDATE_URL)) {
            $contenido->url_publica = Storage::url($contenido->url);
        }

        return response()->json($contenido);
    }

    /**
     * 🗑 Eliminar contenido
     */
    public function destroy($idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)->findOrFail($idcontenido);

        if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
            Storage::disk('public')->delete($contenido->url);
        }

        $contenido->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Contenido eliminado correctamente'
        ]);
    }
}
