<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CursoController extends Controller
{
    /**
     * Este es un comentario de prueba para github
     * 📂 Listar cursos
     * - Profesor: solo sus cursos
     * - Admin: todos
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $rol  = strtolower($user->rolRel?->nombre);

        if ($rol === 'profesor') {
            // ✅ Solo cursos del profesor logueado
            $cursos = Curso::where('idprofesor', $user->profesor->idprofesor)
                ->with('profesor')
                ->latest()
                ->paginate(10);
        } elseif ($rol === 'administrador') {
            // ✅ Admin puede ver todos
            $cursos = Curso::with('profesor')->latest()->paginate(10);
        } else {
            // 🚫 Estudiante u otro rol no autorizado
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Agregar URL de imagen
        $cursos->getCollection()->transform(function ($curso) {
            $curso->imagen_url = $curso->imagen
                ? asset('storage/' . $curso->imagen)
                : null;
            return $curso;
        });

        return response()->json($cursos);
    }

    /**
     * ➕ Crear curso (solo profesor)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $rol  = strtolower($user->rolRel?->nombre);

        if ($rol !== 'profesor') {
            return response()->json(['message' => 'Solo los profesores pueden crear cursos'], 403);
        }

        $data = $request->validate([
            'nombre'      => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'nivel'       => 'nullable|string|max:30',
            'imagen'      => 'nullable|file|image|max:2048',
        ]);

        $curso = new Curso();
        $curso->idprofesor     = $user->profesor->idprofesor;
        $curso->nombre         = $data['nombre'];
        $curso->slug           = Str::slug($data['nombre']) . '-' . uniqid();
        $curso->descripcion    = $data['descripcion'] ?? null;
        $curso->nivel          = $data['nivel'] ?? null;
        $curso->estado         = 'borrador';
        $curso->fecha_creacion = now()->toDateString();

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('cursos', 'public');
            $curso->imagen = $path;
        }

        $curso->save();

        $curso->imagen_url = $curso->imagen
            ? asset('storage/' . $curso->imagen)
            : null;

        return response()->json([
            'ok'    => true,
            'curso' => $curso
        ], 201);
    }

    /**
     * 👁 Mostrar un curso
     */
    public function show(Request $request, $idcurso)
    {
        $user = $request->user();
        $rol  = strtolower($user->rolRel?->nombre);

        $curso = Curso::with(['profesor', 'clases', 'categorias'])->findOrFail($idcurso);

        if ($rol === 'profesor' && $curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $curso->imagen_url = $curso->imagen
            ? asset('storage/' . $curso->imagen)
            : null;

        return response()->json($curso);
    }

    /**
     * ✏️ Actualizar curso
     */
    public function update(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);
        $user  = $request->user();
        $rol   = strtolower($user->rolRel?->nombre);

        if ($rol === 'profesor' && $curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'nombre'      => 'sometimes|string|max:150',
            'descripcion' => 'nullable|string',
            'nivel'       => 'nullable|string|max:30',
            'imagen'      => 'nullable|file|image|max:2048',
            'estado'      => 'in:borrador,publicado,archivado',
        ]);

        if (isset($data['nombre'])) {
            $curso->nombre = $data['nombre'];
            $curso->slug   = Str::slug($data['nombre']) . '-' . uniqid();
        }
        if (isset($data['descripcion'])) {
            $curso->descripcion = $data['descripcion'];
        }
        if (isset($data['nivel'])) {
            $curso->nivel = $data['nivel'];
        }
        if (isset($data['estado'])) {
            $curso->estado = $data['estado'];
        }

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('cursos', 'public');
            $curso->imagen = $path;
        }

        $curso->save();

        $curso->imagen_url = $curso->imagen
            ? asset('storage/' . $curso->imagen)
            : null;

        return response()->json([
            'ok'    => true,
            'curso' => $curso
        ]);
    }

    /**
     * 🗑 Eliminar curso (soft delete)
     */
    public function destroy(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);
        $user  = $request->user();
        $rol   = strtolower($user->rolRel?->nombre);

        if ($rol === 'profesor' && $curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $curso->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Curso dado de baja correctamente'
        ]);
    }

    /**
     * ♻️ Restaurar curso eliminado
     */
    public function restore(Request $request, $idcurso)
    {
        $curso = Curso::withTrashed()->findOrFail($idcurso);
        $user  = $request->user();
        $rol   = strtolower($user->rolRel?->nombre);

        if ($rol === 'profesor' && $curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($curso->trashed()) {
            $curso->restore();
            return response()->json([
                'ok'      => true,
                'message' => 'Curso restaurado correctamente',
                'curso'   => $curso
            ]);
        }

        return response()->json([
            'ok'      => false,
            'message' => 'El curso no estaba eliminado'
        ], 400);
    }

    /**
     * 📖 Catálogo público (solo publicados y activos)
     */
    public function catalogo()
    {
        $cursos = Curso::where('estado', 'publicado')
            ->with('profesor')
            ->latest()
            ->paginate(10);

        $cursos->getCollection()->transform(function ($curso) {
            $curso->imagen_url = $curso->imagen
                ? asset('storage/' . $curso->imagen)
                : null;
            return $curso;
        });

        return response()->json($cursos);
    }
}
