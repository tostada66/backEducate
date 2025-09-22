<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unidad;
use App\Models\Curso;
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
            ->with('clases')
            ->orderBy('idunidad')
            ->get();

        $unidades->transform(function ($unidad) {
            $unidad->imagen_url = $unidad->imagen
                ? asset('storage/' . $unidad->imagen)
                : null;
            $unidad->duracion_total = $unidad->duracion_total;
            return $unidad;
        });

        return response()->json($unidades);
    }

    /**
     * ➕ Crear unidad en un curso
     */
    public function store(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'objetivos'   => 'nullable|string',
            'estado'      => 'in:borrador,publicado',
            'imagen'      => 'nullable|file|image|max:2048',
        ]);

        $unidad = new Unidad();
        $unidad->idcurso     = $curso->idcurso;
        $unidad->titulo      = $data['titulo'];
        $unidad->descripcion = $data['descripcion'] ?? null;
        $unidad->objetivos   = $data['objetivos'] ?? null;
        $unidad->estado      = $data['estado'] ?? 'borrador';

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('unidades', 'public');
            $unidad->imagen = $path;
        }

        $unidad->save();

        $unidad->imagen_url = $unidad->imagen
            ? asset('storage/' . $unidad->imagen)
            : null;
        $unidad->duracion_total = $unidad->duracion_total;

        return response()->json($unidad, 201);
    }

    /**
     * 👁 Mostrar una unidad específica
     */
    public function show($idcurso, $idunidad)
    {
        $curso  = Curso::findOrFail($idcurso);
        $unidad = $curso->unidades()->with('clases')->findOrFail($idunidad);

        $unidad->imagen_url = $unidad->imagen
            ? asset('storage/' . $unidad->imagen)
            : null;
        $unidad->duracion_total = $unidad->duracion_total;

        return response()->json($unidad);
    }

    /**
     * ✏️ Actualizar unidad
     */
    public function update(Request $request, $idcurso, $idunidad)
    {
        $curso  = Curso::findOrFail($idcurso);
        $unidad = $curso->unidades()->findOrFail($idunidad);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'objetivos'   => 'nullable|string',
            'estado'      => 'in:borrador,publicado',
            'imagen'      => 'nullable|file|image|max:2048',
        ]);

        if (isset($data['titulo'])) {
            $unidad->titulo = $data['titulo'];
        }
        if (isset($data['descripcion'])) {
            $unidad->descripcion = $data['descripcion'];
        }
        if (isset($data['objetivos'])) {
            $unidad->objetivos = $data['objetivos'];
        }
        if (isset($data['estado'])) {
            $unidad->estado = $data['estado'];
        }

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('unidades', 'public');
            $unidad->imagen = $path;
        }

        $unidad->save();

        $unidad->imagen_url = $unidad->imagen
            ? asset('storage/' . $unidad->imagen)
            : null;
        $unidad->duracion_total = $unidad->duracion_total;

        return response()->json($unidad);
    }

    /**
     * 🗑 Eliminar (SoftDelete) unidad
     */
    public function destroy($idcurso, $idunidad)
    {
        $curso  = Curso::findOrFail($idcurso);
        $unidad = $curso->unidades()->findOrFail($idunidad);

        $unidad->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Unidad eliminada correctamente'
        ]);
    }

    /**
     * 🎓 Unidades visibles para estudiante
     */
    public function catalogo($idcurso)
    {
        $curso = Curso::where('estado', 'publicado')->findOrFail($idcurso);

        $unidades = $curso->unidades()
            ->where('estado', 'publicado')
            ->with(['clases' => function ($q) {
                $q->where('estado', 'publicado');
            }])
            ->orderBy('idunidad')
            ->get();

        $unidades->transform(function ($unidad) {
            $unidad->imagen_url = $unidad->imagen
                ? asset('storage/' . $unidad->imagen)
                : null;
            $unidad->duracion_total = $unidad->duracion_total;
            return $unidad;
        });

        return response()->json($unidades);
    }
}
