<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Unidad;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    /**
     * 📂 Listar unidades de un curso (profesor/admin)
     */
    public function index($idcurso)
    {
        $curso = Curso::findOrFail($idcurso);

        $unidades = $curso->unidades()
            ->with(['clases.contenidos', 'examen'])
            ->orderBy('idunidad')
            ->get();

        $unidades->transform(fn ($unidad) => $this->mapUrls($unidad));

        return response()->json($unidades);
    }

    /**
     * ➕ Crear unidad en un curso
     */
    public function store(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);

        if (in_array($curso->estado, [
            'publicado', 'en_revision', 'oferta_enviada', 'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes crear unidades mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'objetivos'   => 'nullable|string',
            'imagen'      => 'nullable|file|image|max:2048',
        ]);

        $unidad = new Unidad();
        $unidad->idcurso     = $curso->idcurso;
        $unidad->titulo      = $data['titulo'];
        $unidad->descripcion = $data['descripcion'] ?? null;
        $unidad->objetivos   = $data['objetivos'] ?? null;
        $unidad->estado      = 'borrador';

        if ($request->hasFile('imagen')) {
            $unidad->imagen = $request->file('imagen')->store('unidades', 'public');
        }

        $unidad->save();

        return response()->json($this->mapUrls($unidad), 201);
    }

    /**
     * 👁 Mostrar una unidad específica (con matrícula y examen)
     */
    public function show(Request $request, $idcurso, $idunidad)
    {
        $user = $request->user();
        $curso = Curso::findOrFail($idcurso);

        // 🧩 Incluimos clases, contenidos y examen
        $unidad = $curso->unidades()
            ->with(['clases.contenidos', 'examen'])
            ->findOrFail($idunidad);

        $unidad = $this->mapUrls($unidad);

        // 🧠 Verificar si el usuario está matriculado (solo si es estudiante)
        $matriculado = false;
        if ($user && strtolower($user->rolRel?->nombre) === 'estudiante') {
            $matriculado = Matricula::where('idestudiante', $user->estudiante->idestudiante)
                ->where('idcurso', $curso->idcurso)
                ->exists();
        }

        // ⚙️ Bandera de examen activo
        $tieneExamen = $unidad->examen && $unidad->examen->activo ? true : false;

        // 📦 Respuesta completa para frontend
        return response()->json([
            'idunidad' => $unidad->idunidad,
            'titulo' => $unidad->titulo,
            'descripcion' => $unidad->descripcion,
            'duracion_total' => $unidad->duracion_total,
            'imagen_url' => $unidad->imagen_url,
            'clases' => $unidad->clases,
            'examen' => $unidad->examen,
            'tiene_examen' => $tieneExamen,
            'matriculado' => $matriculado,
            'curso' => [
                'idcurso' => $curso->idcurso,
                'nombre' => $curso->nombre
            ],
        ]);
    }

    /**
     * ✏️ Actualizar unidad
     */
    public function update(Request $request, $idcurso, $idunidad)
    {
        $curso  = Curso::findOrFail($idcurso);
        $unidad = $curso->unidades()->findOrFail($idunidad);

        if (in_array($curso->estado, [
            'publicado', 'en_revision', 'oferta_enviada', 'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes modificar unidades mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'objetivos'   => 'nullable|string',
            'imagen'      => 'nullable|file|image|max:2048',
        ]);

        if (isset($data['titulo'])) {
            $unidad->titulo      = $data['titulo'];
        }
        if (isset($data['descripcion'])) {
            $unidad->descripcion = $data['descripcion'];
        }
        if (isset($data['objetivos'])) {
            $unidad->objetivos   = $data['objetivos'];
        }

        if ($request->hasFile('imagen')) {
            $unidad->imagen = $request->file('imagen')->store('unidades', 'public');
        }

        $unidad->save();

        return response()->json($this->mapUrls($unidad));
    }

    /**
     * 🗑 Eliminar unidad
     */
    public function destroy($idcurso, $idunidad)
    {
        $curso = Curso::findOrFail($idcurso);

        if (in_array($curso->estado, [
            'publicado', 'en_revision', 'oferta_enviada', 'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar unidades mientras el curso esté en revisión o publicado'
            ], 403);
        }

        $unidad = $curso->unidades()->findOrFail($idunidad);
        $unidad->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Unidad eliminada correctamente'
        ]);
    }

    /**
     * 🎓 Unidades visibles para estudiante (solo publicadas)
     */
    public function catalogo(Request $request, $idcurso)
    {
        $user = $request->user();
        $curso = Curso::where('estado', 'publicado')->findOrFail($idcurso);

        $unidades = $curso->unidades()
            ->where('estado', 'publicado')
            ->with(['clases' => function ($q) {
                $q->where('estado', 'publicado')->with('contenidos');
            }, 'examen'])
            ->orderBy('idunidad')
            ->get();

        $unidades->transform(function ($unidad) use ($user, $curso) {
            $unidad = $this->mapUrls($unidad);

            // 🧠 Verificar si el estudiante está matriculado
            $matriculado = false;
            if ($user && strtolower($user->rolRel?->nombre) === 'estudiante') {
                $matriculado = Matricula::where('idestudiante', $user->estudiante->idestudiante)
                    ->where('idcurso', $curso->idcurso)
                    ->exists();
            }

            $unidad->matriculado = $matriculado;
            $unidad->tiene_examen = $unidad->examen && $unidad->examen->activo ? true : false;

            return $unidad;
        });

        return response()->json($unidades);
    }

    /**
     * 🔧 Mapear URLs de imágenes y contenidos
     */
    private function mapUrls($unidad)
    {
        $unidad->imagen_url = $unidad->imagen
            ? asset('storage/' . ltrim($this->cleanPath($unidad->imagen), '/'))
            : asset('storage/default_image.png');

        if ($unidad->relationLoaded('clases')) {
            foreach ($unidad->clases as $clase) {
                if ($clase->relationLoaded('contenidos')) {
                    foreach ($clase->contenidos as $contenido) {
                        $path = $this->cleanPath($contenido->url);
                        $urlPublica = $path ? asset('storage/' . ltrim($path, '/')) : null;
                        $contenido->archivo = $urlPublica;
                        $contenido->url_publica = $urlPublica;
                    }
                }
            }
        }

        return $unidad;
    }

    private function cleanPath($path)
    {
        return str_replace([url('storage') . '/', config('app.url') . '/storage/'], '', $path);
    }
}
