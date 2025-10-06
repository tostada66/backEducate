<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaseController extends Controller
{
    /**
     * 📂 Listar clases de una unidad (profesor/admin)
     */
    public function index($idcurso, $idunidad)
    {
        $curso = Curso::findOrFail($idcurso);
        $unidad = $curso->unidades()->findOrFail($idunidad);

        $clases = $unidad->clases()
            ->with('contenidos')
            ->orderBy('orden')
            ->get();

        $clases->transform(fn($c) => $this->mapUrls($c));

        return response()->json($clases);
    }

    /**
     * ➕ Crear nueva clase en una unidad
     */
    public function store(Request $request, $idcurso, $idunidad)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si el curso no se puede editar
        if (in_array($curso->estado, [
            'publicado',
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes añadir clases mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $unidad = $curso->unidades()->findOrFail($idunidad);

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'orden'       => 'nullable|integer',
            'estado'      => 'in:borrador,publicado'
        ]);

        $nextOrden = ($unidad->clases()->max('orden') ?? 0) + 1;

        $clase = $unidad->clases()->create([
            'titulo'      => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'orden'       => $data['orden'] ?? $nextOrden,
            'estado'      => $data['estado'] ?? 'borrador',
        ]);

        return response()->json($this->mapUrls($clase->load('contenidos')), 201);
    }

    /**
     * 👁 Mostrar una clase de una unidad
     */
    public function show($idcurso, $idunidad, $idclase)
    {
        $curso = Curso::findOrFail($idcurso);
        $unidad = $curso->unidades()->findOrFail($idunidad);

        $clase = $unidad->clases()
            ->with('contenidos')
            ->findOrFail($idclase);

        return response()->json($this->mapUrls($clase));
    }

    /**
     * ✏️ Actualizar clase de una unidad
     */
    public function update(Request $request, $idcurso, $idunidad, $idclase)
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
                'message' => 'No puedes modificar clases mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $unidad = $curso->unidades()->findOrFail($idunidad);
        $clase = $unidad->clases()->findOrFail($idclase);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'orden'       => 'nullable|integer',
            'estado'      => 'in:borrador,publicado'
        ]);

        $clase->update($data);

        return response()->json($this->mapUrls($clase->load('contenidos')));
    }

    /**
     * 🗑 Eliminar clase de una unidad
     */
    public function destroy($idcurso, $idunidad, $idclase)
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
                'message' => 'No puedes eliminar clases mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $unidad = $curso->unidades()->findOrFail($idunidad);
        $clase = $unidad->clases()->findOrFail($idclase);

        $clase->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Clase eliminada correctamente'
        ]);
    }

    /**
     * 🔄 Cambiar orden de una clase (subir/bajar)
     */
    public function cambiarOrden(Request $request, $idcurso, $idunidad, $idclase)
    {
        $curso  = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable
        if (in_array($curso->estado, [
            'publicado',
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes reordenar clases mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $unidad = $curso->unidades()->findOrFail($idunidad);
        $clase  = $unidad->clases()->findOrFail($idclase);

        $direccion = $request->input('direccion'); // "up" o "down"

        if (!in_array($direccion, ['up', 'down'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Dirección inválida'
            ], 422);
        }

        DB::transaction(function () use ($unidad, $clase, $direccion) {
            if ($direccion === 'up') {
                $swap = $unidad->clases()
                    ->where('orden', '<', $clase->orden)
                    ->orderBy('orden', 'desc')
                    ->first();
            } else {
                $swap = $unidad->clases()
                    ->where('orden', '>', $clase->orden)
                    ->orderBy('orden', 'asc')
                    ->first();
            }

            if ($swap) {
                $tempOrden    = -1;
                $oldOrden     = $clase->orden;
                $swapOrden    = $swap->orden;

                $clase->update(['orden' => $tempOrden]);
                $swap->update(['orden' => $oldOrden]);
                $clase->update(['orden' => $swapOrden]);
            }
        });

        $clases = $unidad->clases()->with('contenidos')->orderBy('orden')->get();
        $clases->transform(fn($c) => $this->mapUrls($c));

        return response()->json([
            'ok' => true,
            'message' => 'Orden actualizado correctamente',
            'clases' => $clases
        ]);
    }

    /**
     * 🎓 Listar clases para estudiantes (solo publicadas)
     */
    public function catalogo($idcurso, $idunidad)
    {
        $curso  = Curso::where('estado', 'publicado')->findOrFail($idcurso);
        $unidad = $curso->unidades()
            ->where('estado', 'publicado')
            ->findOrFail($idunidad);

        $clases = $unidad->clases()
            ->where('estado', 'publicado')
            ->with(['contenidos' => function ($q) {
                $q->where('estado', 'publicado');
            }])
            ->orderBy('orden')
            ->get();

        $clases->transform(fn($c) => $this->mapUrls($c));

        return response()->json($clases);
    }

    /**
     * 🔧 Mapear URLs públicas de los contenidos y asignar portada de clase
     */
    private function mapUrls($clase)
    {
        foreach ($clase->contenidos as $contenido) {
            $contenido->archivo = $contenido->url_publica;
            $contenido->miniatura_publica = $contenido->miniatura_publica;
        }

        // 👉 Portada de la clase = miniatura del primer video publicado
        $video = $clase->contenidos->firstWhere('tipo', 'video');
        if ($video && $video->miniatura_publica) {
            $clase->miniatura_publica = $video->miniatura_publica;
        } else {
            $imagen = $clase->contenidos->firstWhere('tipo', 'imagen');
            $clase->miniatura_publica = $imagen ? $imagen->url_publica : null;
        }

        return $clase;
    }
}
