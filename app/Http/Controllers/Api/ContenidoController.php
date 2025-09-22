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
     * 📂 Listar contenidos de una clase (profesor/admin)
     */
    public function index($idcurso, $idunidad, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $contenidos = $clase->contenidos()
            ->orderBy('orden')
            ->get();

        return response()->json($contenidos);
    }

    /**
     * ➕ Crear contenido
     */
    public function store(Request $request, $idcurso, $idunidad, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:10240', // hasta 10MB
            'duracion'    => 'nullable|integer|min:1',
            'estado'      => 'in:borrador,publicado'
        ]);

        $contenido = new Contenido();
        $contenido->idclase     = $clase->idclase;
        $contenido->titulo      = $data['titulo'];
        $contenido->descripcion = $data['descripcion'] ?? null;
        $contenido->duracion    = $data['duracion'] ?? null;
        $contenido->estado      = $data['estado'] ?? 'borrador';

        // 📂 Calcular orden automáticamente
        $maxOrden = Contenido::where('idclase', $clase->idclase)->max('orden');
        $contenido->orden = $maxOrden ? $maxOrden + 1 : 1;

        // 📂 Guardar archivo y detectar tipo
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $path = $file->store('contenidos', 'public');
            $contenido->url = $path;

            $mime = strtolower($file->getClientMimeType());
            $ext  = strtolower($file->getClientOriginalExtension());

            if (str_starts_with($mime, 'image/')) {
                $contenido->tipo = 'imagen';
            } elseif (str_starts_with($mime, 'video/')) {
                $contenido->tipo = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $contenido->tipo = 'audio';
            } elseif ($mime === 'application/pdf' || $ext === 'pdf') {
                $contenido->tipo = 'pdf';
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $contenido->tipo = 'word';
            } elseif (in_array($ext, ['xls', 'xlsx'])) {
                $contenido->tipo = 'excel';
            } elseif (in_array($ext, ['ppt', 'pptx'])) {
                $contenido->tipo = 'powerpoint';
            } else {
                $contenido->tipo = 'otro';
            }
        } else {
            $contenido->url  = $data['url'] ?? null;
            $contenido->tipo = $data['url'] ? 'link' : 'otro';
        }

        $contenido->save();

        return response()->json($contenido, 201);
    }

    /**
     * 👁 Mostrar un contenido
     */
    public function show($idcurso, $idunidad, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        return response()->json($contenido);
    }

    /**
     * ✏️ Actualizar contenido
     */
    public function update(Request $request, $idcurso, $idunidad, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:10240',
            'duracion'    => 'nullable|integer|min:1',
            'estado'      => 'in:borrador,publicado'
        ]);

        if (isset($data['titulo'])) $contenido->titulo = $data['titulo'];
        if (isset($data['descripcion'])) $contenido->descripcion = $data['descripcion'];
        if (isset($data['duracion'])) $contenido->duracion = $data['duracion'];
        if (isset($data['estado'])) $contenido->estado = $data['estado'];

        if ($request->hasFile('archivo')) {
            if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
                Storage::disk('public')->delete($contenido->url);
            }

            $file = $request->file('archivo');
            $path = $file->store('contenidos', 'public');
            $contenido->url = $path;

            $mime = strtolower($file->getClientMimeType());
            $ext  = strtolower($file->getClientOriginalExtension());

            if (str_starts_with($mime, 'image/')) {
                $contenido->tipo = 'imagen';
            } elseif (str_starts_with($mime, 'video/')) {
                $contenido->tipo = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $contenido->tipo = 'audio';
            } elseif ($mime === 'application/pdf' || $ext === 'pdf') {
                $contenido->tipo = 'pdf';
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $contenido->tipo = 'word';
            } elseif (in_array($ext, ['xls', 'xlsx'])) {
                $contenido->tipo = 'excel';
            } elseif (in_array($ext, ['ppt', 'pptx'])) {
                $contenido->tipo = 'powerpoint';
            } else {
                $contenido->tipo = 'otro';
            }
        } elseif (isset($data['url'])) {
            $contenido->url  = $data['url'];
            $contenido->tipo = $data['url'] ? 'link' : 'otro';
        }

        $contenido->save();

        return response()->json($contenido);
    }

    /**
     * 🔄 Cambiar orden de un contenido
     */
    public function cambiarOrden(Request $request, $idcurso, $idunidad, $idclase, $idcontenido)
    {
        $direccion = $request->input('direccion'); // "up" o "down"
        $contenido = Contenido::where('idclase', $idclase)->findOrFail($idcontenido);

        if ($direccion === 'up') {
            $anterior = Contenido::where('idclase', $idclase)
                ->where('orden', $contenido->orden - 1)
                ->first();

            if ($anterior) {
                // Evitar duplicado temporal
                $contenido->orden = -1;
                $contenido->save();

                $anterior->orden++;
                $anterior->save();

                $contenido->orden = $anterior->orden - 1;
                $contenido->save();
            }
        } elseif ($direccion === 'down') {
            $siguiente = Contenido::where('idclase', $idclase)
                ->where('orden', $contenido->orden + 1)
                ->first();

            if ($siguiente) {
                // Evitar duplicado temporal
                $contenido->orden = -1;
                $contenido->save();

                $siguiente->orden--;
                $siguiente->save();

                $contenido->orden = $siguiente->orden + 1;
                $contenido->save();
            }
        }

        $contenidos = Contenido::where('idclase', $idclase)
            ->orderBy('orden')
            ->get();

        return response()->json([
            'ok' => true,
            'contenidos' => $contenidos
        ]);
    }

    /**
     * 🗑 Eliminar contenido
     */
    public function destroy($idcurso, $idunidad, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
            Storage::disk('public')->delete($contenido->url);
        }

        $contenido->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Contenido eliminado correctamente'
        ]);
    }

    /**
     * 🎓 Catálogo de contenidos (solo publicados)
     */
    public function catalogo($idcurso, $idunidad, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $contenidos = $clase->contenidos()
            ->where('estado', 'publicado')
            ->orderBy('orden')
            ->get();

        return response()->json($contenidos);
    }
}
