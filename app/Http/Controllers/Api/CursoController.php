<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CursoController extends Controller
{
    /**
     * 📂 Listar cursos (profesor/admin)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $idrol = $user->idrol; // Usamos el ID numérico

        if ($idrol === 2) { // 👨‍🏫 Profesor
            $cursos = Curso::where('idprofesor', $user->profesor->idprofesor)
                ->with([
                    'profesor.usuario',
                    'categoria',
                    // 👇 para saber si el curso tiene edición activa (pendiente / en_edicion / en_revision)
                    'edicionActiva',
                ])
                ->latest()
                ->paginate(10);
        } elseif ($idrol === 3) { // 🛠️ Administrador
            $cursos = Curso::with([
                    'profesor.usuario',
                    'categoria',
                    'edicionActiva',
                ])
                ->latest()
                ->paginate(10);
        } else { // 🎓 Estudiante u otro rol
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $cursos->getCollection()->transform(fn ($curso) => $this->mapUrls($curso));

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
            'idcategoria' => 'nullable|exists:categorias,idcategoria',
        ]);

        $curso = new Curso();
        $curso->idprofesor  = $user->profesor->idprofesor;
        $curso->idcategoria = $request->idcategoria ?? null;
        $curso->nombre      = $data['nombre'];
        $curso->slug        = Str::slug($data['nombre']) . '-' . uniqid();
        $curso->descripcion = $data['descripcion'] ?? null;
        $curso->nivel       = $data['nivel'] ?? null;
        $curso->estado      = 'borrador'; // 👈 Estado inicial

        if ($request->hasFile('imagen')) {
            $curso->imagen = $request->file('imagen')->store('cursos', 'public');
        }

        $curso->save();
        $this->mapUrls($curso);

        return response()->json([
            'ok'    => true,
            'curso' => $curso->load('categoria')
        ], 201);
    }

    /**
     * 👁 Mostrar curso (logueado con clases y contenidos)
     */
    public function show(Request $request, $idcurso)
    {
        $user = $request->user();
        $rol  = strtolower($user->rolRel?->nombre);

        // 👇 ahora también traemos la edición activa y el histórico de ediciones
        $curso = Curso::with([
                'profesor',
                'unidades.clases.contenidos',
                'categoria',
                'edicionActiva',
                'ediciones',
            ])
            ->findOrFail($idcurso);

        if ($rol === 'profesor' && $curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $this->mapUrls($curso);

        return response()->json($curso);
    }

    /**
     * ✏️ Actualizar curso
     */
    public function update(Request $request, $idcurso)
    {
        // 👉 Traemos también ediciones para poder consultar si hay ventana de edición
        $curso = Curso::with('ediciones')->findOrFail($idcurso);
        $user  = $request->user();
        $rol   = strtolower($user->rolRel?->nombre);

        // 🔐 1. Validar que el profesor sea dueño del curso
        if ($rol === 'profesor' && $curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 🔒 2. Reglas de bloqueo SOLO para profesor
        if ($rol === 'profesor') {

            // ⛔ Estados de negociación donde NO se puede tocar nada
            if (in_array($curso->estado, ['en_revision', 'oferta_enviada', 'pendiente_aceptacion'])) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No puedes modificar un curso que está en revisión u oferta pendiente'
                ], 403);
            }

            // ⛔ Curso publicado: solo se puede editar si hay una edición aprobada (en_edicion)
            if ($curso->estado === 'publicado') {

                $tieneVentanaEdicion = $curso->ediciones()
                    ->where('estado', 'en_edicion') // 👈 solo cuando el admin ya aprobó la edición
                    ->exists();

                if (!$tieneVentanaEdicion) {
                    return response()->json([
                        'ok'      => false,
                        'message' => 'El curso está publicado. Debes solicitar una edición y esperar la aprobación del administrador antes de poder modificarlo.'
                    ], 403);
                }
            }

            // 👉 Estados "borrador" y "rechazado" sí pueden editarse libremente
        }

        // 🛠 3. (Admin u otros roles pasan directo sin restricciones extra)
        $data = $request->validate([
            'nombre'      => 'sometimes|string|max:150',
            'descripcion' => 'nullable|string',
            'nivel'       => 'nullable|string|max:30',
            'imagen'      => 'nullable|file|image|max:2048',
            'idcategoria' => 'nullable|exists:categorias,idcategoria',
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
        if (isset($data['idcategoria'])) {
            $curso->idcategoria = $data['idcategoria'];
        }

        if ($request->hasFile('imagen')) {
            $curso->imagen = $request->file('imagen')->store('cursos', 'public');
        }

        $curso->save();
        $this->mapUrls($curso);

        return response()->json([
            'ok'    => true,
            'curso' => $curso->load('categoria')
        ]);
    }

    /**
     * 📤 Enviar curso a revisión (profesor)
     */
    public function enviarRevision($idcurso, Request $request)
    {
        $curso = Curso::findOrFail($idcurso);
        $user = $request->user();

        if ($curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($curso->estado !== 'borrador') {
            return response()->json(['ok' => false, 'message' => 'Solo se pueden enviar cursos en borrador'], 400);
        }

        $curso->estado = 'en_revision';
        $curso->save();

        return response()->json([
            'ok' => true,
            'message' => 'Curso enviado a revisión con éxito',
            'curso' => $curso
        ]);
    }

    /**
     * ✅ Profesor acepta oferta del admin
     */
    public function aceptarOferta($idcurso, Request $request)
    {
        $curso = Curso::findOrFail($idcurso);
        $user = $request->user();

        if ($curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($curso->estado !== 'pendiente_aceptacion') {
            return response()->json(['ok' => false, 'message' => 'No hay oferta pendiente para aceptar'], 400);
        }

        $curso->estado = 'publicado';
        $curso->save();

        return response()->json([
            'ok' => true,
            'message' => 'Oferta aceptada. El curso ahora está publicado.',
            'curso' => $curso
        ]);
    }

    /**
     * ❌ Profesor rechaza oferta
     */
    public function rechazarOferta($idcurso, Request $request)
    {
        $curso = Curso::findOrFail($idcurso);
        $user = $request->user();

        if ($curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($curso->estado !== 'pendiente_aceptacion') {
            return response()->json(['ok' => false, 'message' => 'No hay oferta pendiente para rechazar'], 400);
        }

        $curso->estado = 'rechazado';
        $curso->save();

        return response()->json([
            'ok' => true,
            'message' => 'Has rechazado la oferta del administrador.',
            'curso' => $curso
        ]);
    }

    /**
     * 🗑 Eliminar curso
     */
    public function destroy(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);
        $user  = $request->user();
        $rol   = strtolower($user->rolRel?->nombre);

        if ($rol === 'profesor' && $curso->idprofesor !== $user->profesor->idprofesor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (in_array($curso->estado, ['publicado', 'en_revision', 'oferta_enviada', 'pendiente_aceptacion'])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar un curso publicado o en revisión'
            ], 403);
        }

        $curso->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Curso eliminado correctamente'
        ]);
    }

    /**
     * 📖 Catálogo público (solo info básica)
     */
    public function catalogo()
    {
        $cursos = Curso::where('estado', 'publicado')
            ->with([
                'profesor.usuario', // 👈 también cargamos el usuario del profesor
                'categoria'
            ])
            ->latest()
            ->paginate(10);

        // Añadimos URLs y nombre completo del profesor
        $cursos->getCollection()->transform(function ($curso) {
            $this->mapUrls($curso);

            if ($curso->profesor && $curso->profesor->usuario) {
                $usuario = $curso->profesor->usuario;
                $curso->profesor->nombre_completo = trim("{$usuario->nombres} {$usuario->apellidos}");
            } else {
                $curso->profesor->nombre_completo = "Profesor #{$curso->profesor->idprofesor}";
            }

            return $curso;
        });

        return response()->json($cursos);
    }

    /**
     * 👁 Mostrar curso público (con unidades)
     */
    public function showPublic($idcurso)
    {
        $curso = Curso::where('estado', 'publicado')
            ->with(['profesor', 'categoria', 'unidades'])
            ->findOrFail($idcurso);

        $this->mapUrls($curso);

        return response()->json($curso);
    }

    /**
     * 🔧 Mapear URLs públicas
     */
    private function mapUrls($curso)
    {
        $curso->imagen_url = $curso->imagen
            ? asset('storage/' . ltrim($this->cleanPath($curso->imagen), '/'))
            : asset('storage/default_image.png');

        // Si no se han cargado unidades, evitamos error
        if ($curso->relationLoaded('unidades')) {
            foreach ($curso->unidades as $unidad) {
                $unidad->imagen_url = $unidad->imagen
                    ? asset('storage/' . ltrim($this->cleanPath($unidad->imagen), '/'))
                    : asset('storage/default_image.png');
            }
        }

        return $curso;
    }

    private function cleanPath($path)
    {
        return str_replace([url('storage') . '/', config('app.url') . '/storage/'], '', $path);
    }
}
