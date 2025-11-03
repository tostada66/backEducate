<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use App\Models\Contenido;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContenidoController extends Controller
{
    /**
     * 📂 Listar contenidos de una clase
     */
    public function index($idcurso, $idunidad, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $contenidos = $clase->contenidos()
            ->orderBy('orden')
            ->get();

        $contenidos->transform(fn ($c) => $this->mapUrls($c));

        return response()->json($contenidos);
    }

    /**
     * ➕ Crear contenido
     */
    public function store(Request $request, $idcurso, $idunidad, $idclase)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable
        if (in_array($curso->estado, [
            'publicado',
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes agregar contenidos mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $clase = Clase::findOrFail($idclase);

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:204800',
            'miniatura'   => 'nullable|file|max:5120',
            'duracion'    => 'nullable|integer|min:1',
            'estado'      => 'in:borrador,publicado'
        ]);

        $contenido = new Contenido();
        $contenido->idclase     = $clase->idclase;
        $contenido->titulo      = $data['titulo'];
        $contenido->descripcion = $data['descripcion'] ?? null;
        $contenido->duracion    = $data['duracion'] ?? null;
        $contenido->estado      = $data['estado'] ?? 'borrador';

        // 📂 Calcular orden
        $maxOrden = Contenido::where('idclase', $clase->idclase)->max('orden');
        $contenido->orden = $maxOrden ? $maxOrden + 1 : 1;

        // 📂 Guardar archivo principal
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $path = $file->store('contenidos', 'public');
            $contenido->url = $path;

            $mime = strtolower($file->getClientMimeType());

            if (str_starts_with($mime, 'image/')) {
                $contenido->tipo = 'imagen';
            } elseif (str_starts_with($mime, 'video/')) {
                $yaExiste = Contenido::where('idclase', $clase->idclase)
                    ->where('tipo', 'video')
                    ->exists();
                if ($yaExiste) {
                    return response()->json([
                        'error' => '⚠️ Solo se permite un video por clase'
                    ], 422);
                }
                $contenido->tipo = 'video';
            } else {
                $contenido->tipo = 'documento';
            }
        } else {
            $contenido->url  = $data['url'] ?? null;
            $contenido->tipo = $data['url'] ? 'documento' : 'otro';
        }

        // 📌 Miniatura si es video
        if ($contenido->tipo === 'video') {
            if ($request->hasFile('miniatura')) {
                $contenido->miniatura = $request->file('miniatura')
                    ->store('contenidos/miniaturas', 'public');
            } elseif ($request->filled('miniatura')) {
                $contenido->miniatura = $data['miniatura'];
            }
        } else {
            $contenido->miniatura = null;
        }

        $contenido->save();

        return response()->json($this->mapUrls($contenido), 201);
    }

    /**
     * 👁 Mostrar un contenido
     */
    public function show($idcurso, $idunidad, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        return response()->json($this->mapUrls($contenido));
    }

    /**
     * ✏️ Actualizar contenido
     */
    public function update(Request $request, $idcurso, $idunidad, $idclase, $idcontenido)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable
        if (in_array($curso->estado, [
            'publicado',
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes modificar contenidos mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:204800',
            'miniatura'   => 'nullable|file|max:5120',
            'duracion'    => 'nullable|integer|min:1',
            'estado'      => 'in:borrador,publicado'
        ]);

        if (isset($data['titulo'])) {
            $contenido->titulo = $data['titulo'];
        }
        if (isset($data['descripcion'])) {
            $contenido->descripcion = $data['descripcion'];
        }
        if (isset($data['duracion'])) {
            $contenido->duracion = $data['duracion'];
        }
        if (isset($data['estado'])) {
            $contenido->estado = $data['estado'];
        }

        if ($request->hasFile('archivo')) {
            if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
                Storage::disk('public')->delete($contenido->url);
            }

            $file = $request->file('archivo');
            $path = $file->store('contenidos', 'public');
            $contenido->url = $path;

            $mime = strtolower($file->getClientMimeType());

            if (str_starts_with($mime, 'image/')) {
                $contenido->tipo = 'imagen';
                $contenido->miniatura = null;
            } elseif (str_starts_with($mime, 'video/')) {
                $yaExiste = Contenido::where('idclase', $idclase)
                    ->where('tipo', 'video')
                    ->where('idcontenido', '!=', $contenido->idcontenido)
                    ->exists();
                if ($yaExiste) {
                    return response()->json([
                        'error' => '⚠️ Solo se permite un video por clase'
                    ], 422);
                }
                $contenido->tipo = 'video';
            } else {
                $contenido->tipo = 'documento';
                $contenido->miniatura = null;
            }
        } elseif (isset($data['url'])) {
            $contenido->url  = $data['url'];
            $contenido->tipo = $data['url'] ? 'documento' : 'otro';
            if ($contenido->tipo !== 'video') {
                $contenido->miniatura = null;
            }
        }

        if ($contenido->tipo === 'video') {
            if ($request->hasFile('miniatura')) {
                if ($contenido->miniatura && Storage::disk('public')->exists($contenido->miniatura)) {
                    Storage::disk('public')->delete($contenido->miniatura);
                }
                $contenido->miniatura = $request->file('miniatura')
                    ->store('contenidos/miniaturas', 'public');
            } elseif (isset($data['miniatura'])) {
                $contenido->miniatura = $data['miniatura'];
            }
        } else {
            $contenido->miniatura = null;
        }

        $contenido->save();

        return response()->json($this->mapUrls($contenido));
    }

    /**
     * 🔄 Cambiar orden de un contenido
     */
    public function cambiarOrden(Request $request, $idcurso, $idunidad, $idclase, $idcontenido)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable
        if (in_array($curso->estado, [
            'publicado',
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes reordenar contenidos mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $direccion = $request->input('direccion');
        $contenido = Contenido::where('idclase', $idclase)->findOrFail($idcontenido);

        if ($direccion === 'up') {
            $anterior = Contenido::where('idclase', $idclase)
                ->where('orden', $contenido->orden - 1)
                ->first();
            if ($anterior) {
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

        $contenidos->transform(fn ($c) => $this->mapUrls($c));

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
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable
        if (in_array($curso->estado, [
            'publicado',
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar contenidos mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
            Storage::disk('public')->delete($contenido->url);
        }
        if ($contenido->miniatura && Storage::disk('public')->exists($contenido->miniatura)) {
            Storage::disk('public')->delete($contenido->miniatura);
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

        $contenidos->transform(fn ($c) => $this->mapUrls($c));

        return response()->json($contenidos);
    }

    /**
     * 📥 Descargar contenido
     */
    public function descargar($idcurso, $idunidad, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        if (!$contenido->url || !Storage::disk('public')->exists($contenido->url)) {
            return response()->json([
                'ok' => false,
                'message' => 'Archivo no encontrado'
            ], 404);
        }

        $path = Storage::disk('public')->path($contenido->url);
        $ext  = pathinfo($contenido->url, PATHINFO_EXTENSION);
        $nombre = ($contenido->titulo ?: 'contenido') . '.' . $ext;

        return response()->download($path, $nombre, [
            'Content-Type' => mime_content_type($path)
        ]);
    }

    /**
     * 🔧 Mapear URL pública
     */
    private function mapUrls($contenido)
    {
        $path = $this->cleanPath($contenido->url);
        $urlPublica = $path ? asset('storage/' . ltrim($path, '/')) : null;
        $contenido->archivo = $urlPublica;
        $contenido->url_publica = $urlPublica;

        if ($contenido->miniatura) {
            $miniPath = $this->cleanPath($contenido->miniatura);
            $contenido->miniatura_publica = $miniPath
                ? asset('storage/' . ltrim($miniPath, '/'))
                : $contenido->miniatura;
        } else {
            $contenido->miniatura_publica = null;
        }

        return $contenido;
    }

    private function cleanPath($path)
    {
        return str_replace([url('storage') . '/', config('app.url') . '/storage/'], '', $path);
    }
}
