<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use App\Models\Curso;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    /**
     * 📂 Listar clases de un curso
     */
    public function index($idcurso)
    {
        $curso = Curso::findOrFail($idcurso);

        $clases = $curso->clases()
            ->orderBy('orden')
            ->get();

        return response()->json($clases);
    }

    /**
     * ➕ Crear nueva clase en un curso
     */
    public function store(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'orden'       => 'nullable|integer',
            'duracion'    => 'nullable|integer',
            'estado'      => 'in:borrador,publicado'
        ]);

        // Forzar idcurso y valores por defecto
        $clase = Clase::create([
            'idcurso'     => $curso->idcurso,
            'titulo'      => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'orden'       => $data['orden'] ?? 1,
            'duracion'    => $data['duracion'] ?? null,
            'estado'      => $data['estado'] ?? 'borrador',
        ]);

        return response()->json($clase, 201);
    }

    /**
     * 👁 Mostrar una clase de un curso
     */
    public function show($idcurso, $idclase)
    {
        $curso = Curso::findOrFail($idcurso);

        $clase = $curso->clases()
            ->findOrFail($idclase);

        return response()->json($clase);
    }

    /**
     * ✏️ Actualizar clase de un curso
     */
    public function update(Request $request, $idcurso, $idclase)
    {
        $curso = Curso::findOrFail($idcurso);
        $clase = $curso->clases()->findOrFail($idclase);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'orden'       => 'nullable|integer',
            'duracion'    => 'nullable|integer',
            'estado'      => 'in:borrador,publicado'
        ]);

        $clase->update($data);

        return response()->json($clase);
    }

    /**
     * 🗑 Eliminar (SoftDelete) clase de un curso
     */
    public function destroy($idcurso, $idclase)
    {
        $curso = Curso::findOrFail($idcurso);
        $clase = $curso->clases()->findOrFail($idclase);

        $clase->delete(); // 👈 soft delete

        return response()->json([
            'ok'      => true,
            'message' => 'Clase eliminada correctamente'
        ]);
    }
}
