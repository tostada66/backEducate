<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Licencia;
use App\Models\Observacion;
use App\Models\Oferta;
use App\Models\PagoProfesor;
use App\Models\CursoEdicion;
use App\Models\Notificacion; // 👈 Notificaciones
use App\Models\Usuario;      // 👈 Para buscar admins
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfesorCursoController extends Controller
{
    /**
     * 📚 Listar cursos del profesor con observaciones, oferta y edición activa
     */
    public function index(Request $request)
    {
        $profesor = $request->user()->profesor;

        $query = Curso::where('idprofesor', $profesor->idprofesor)
            ->with(['categoria'])
            ->withCount(['observaciones as num_observaciones'])
            ->withExists(['oferta as tiene_oferta'])
            // 👇 Para que el front vea si tiene edición activa
            ->with(['edicionActiva']);

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        $cursos = $query->latest()->get();

        return response()->json([
            'ok'   => true,
            'data' => $cursos
        ]);
    }

    /**
     * 🔁 Enviar curso a revisión (borrador o rechazado)
     */
    public function enviarRevision(Request $request, $idcurso)
    {
        $user = $request->user();

        if (!$user->profesor) {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo los profesores pueden enviar cursos a revisión.'
            ], 403);
        }

        // Aseguramos que el curso sea del profesor logueado
        $curso = Curso::where('idcurso', $idcurso)
            ->where('idprofesor', $user->profesor->idprofesor)
            ->firstOrFail();

        if ($curso->estado !== 'borrador' && $curso->estado !== 'rechazado') {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo puedes enviar a revisión cursos en borrador o rechazados.'
            ], 400);
        }

        $curso->update(['estado' => 'en_revision']);

        // 🔔 NOTIFICAR A TODOS LOS ADMIN: nuevo curso en revisión
        $admins = Usuario::whereHas('rolRel', function ($q) {
            $q->where('nombre', 'admin'); // ⚠️ Ajusta si el rol se llama distinto
        })->get();

        $nombreProf = trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? ''));

        foreach ($admins as $admin) {
            Notificacion::crear(
                idusuario: $admin->idusuario,
                categoria: 'cursos',
                tipo:      'curso_en_revision',
                titulo:    'Nuevo curso enviado a revisión',
                mensaje:   "El profesor {$nombreProf} envió el curso «{$curso->nombre}» para revisión.",
                url:       "/admin/cursos/{$curso->idcurso}",
                datos:     [
                    'idcurso'    => $curso->idcurso,
                    'idprofesor' => $user->profesor->idprofesor,
                ]
            );
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Curso enviado a revisión correctamente.',
            'curso'   => $curso
        ]);
    }

    /**
     * ✏️ Profesor solicita edición de un curso publicado
     */
    public function solicitarEdicion(Request $request, $idcurso)
    {
        $user = $request->user();

        // Aseguramos que tenga perfil de profesor
        if (!$user->profesor) {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo los profesores pueden solicitar edición de cursos.'
            ], 403);
        }

        $profesor = $user->profesor;

        $data = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        // 1️⃣ Verificamos que el curso exista y pertenezca a ese profesor
        $curso = Curso::where('idcurso', $idcurso)
            ->where('idprofesor', $profesor->idprofesor)
            ->firstOrFail();

        // 2️⃣ Solo permitir si está publicado
        if ($curso->estado !== 'publicado') {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo puedes solicitar edición de cursos publicados.'
            ], 400);
        }

        // 3️⃣ Verificar que NO tenga una solicitud de edición activa
        $tieneActiva = CursoEdicion::where('idcurso', $curso->idcurso)
            ->whereIn('estado', ['pendiente', 'en_edicion', 'en_revision'])
            ->exists();

        if ($tieneActiva) {
            return response()->json([
                'ok'      => false,
                'message' => 'Ya existe una solicitud de edición activa para este curso.'
            ], 400);
        }

        // 4️⃣ Crear la solicitud
        $edicion = CursoEdicion::create([
            'idcurso'    => $curso->idcurso,
            'idprofesor' => $profesor->idprofesor,
            'motivo'     => $data['motivo'] ?? null,
            'estado'     => 'pendiente',
        ]);

        // 🔔 NOTIFICAR A ADMIN: solicitud de edición
        $admins = Usuario::whereHas('rolRel', function ($q) {
            $q->where('nombre', 'admin');
        })->get();

        $nombreProf = trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? ''));

        foreach ($admins as $admin) {
            Notificacion::crear(
                idusuario: $admin->idusuario,
                categoria: 'cursos',
                tipo:      'solicitud_edicion',
                titulo:    'Solicitud de edición de curso',
                mensaje:   "El profesor {$nombreProf} pidió editar el curso «{$curso->nombre}».",
                url:       "/admin/cursos/{$curso->idcurso}",
                datos:     [
                    'idcurso'        => $curso->idcurso,
                    'idcursoEdicion' => $edicion->id,
                ]
            );
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Solicitud de edición enviada. Un administrador revisará tu pedido.',
            'edicion' => $edicion,
        ], 201);
    }

    /**
     * 💼 Ver oferta pendiente
     */
    public function verOferta($idcurso)
    {
        $curso = Curso::with('oferta')->findOrFail($idcurso);

        if (!$curso->oferta) {
            return response()->json([
                'ok'      => false,
                'message' => 'No hay oferta disponible para este curso'
            ], 404);
        }

        $oferta = $curso->oferta->makeHidden(['created_at', 'updated_at']);
        $oferta->costo = $curso->oferta->costo_total;

        return response()->json([
            'ok'     => true,
            'oferta' => $oferta
        ]);
    }

    /**
     * ✅ Aceptar oferta → crea licencia, publica curso y genera pago pendiente
     *    + 🔔 Notificar a los ADMIN que el profesor aceptó
     */
    public function aceptarOferta(Request $request, $idcurso)
    {
        $user = $request->user();

        if (!$user->profesor) {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo los profesores pueden aceptar ofertas.'
            ], 403);
        }

        // Aseguramos que el curso sea del profesor logueado
        $curso = Curso::with('oferta')
            ->where('idcurso', $idcurso)
            ->where('idprofesor', $user->profesor->idprofesor)
            ->firstOrFail();

        $oferta = $curso->oferta;

        if (!$oferta) {
            return response()->json([
                'ok'      => false,
                'message' => 'No hay oferta para aceptar'
            ], 404);
        }

        DB::transaction(function () use ($curso, $oferta) {
            // 1️⃣ Crear licencia
            $licencia = Licencia::create([
                'idcurso'          => $curso->idcurso,
                'idprofesor'       => $curso->idprofesor,
                'num_clases'       => $oferta->num_clases,
                'tarifa_por_clase' => $oferta->tarifa_por_clase,
                'duracion_meses'   => $oferta->duracion_meses,
                'costo'            => $oferta->costo_total,
                'fechainicio'      => Carbon::today(),
                'fechafin'         => Carbon::today()->addMonths($oferta->duracion_meses),
                'estado'           => 'activa',
            ]);

            // 2️⃣ Generar pago pendiente automáticamente
            PagoProfesor::create([
                'idprofesor'       => $curso->idprofesor,
                'idlicencia'       => $licencia->idlicencia,
                'monto'            => $oferta->costo_total,
                'estado'           => 'pendiente',
                'fecha_generacion' => now(),
            ]);

            // 3️⃣ Actualizar estados
            $curso->update(['estado' => 'publicado']);
            $oferta->update(['estado' => 'aceptada']);
        });

        // 🔔 Notificar a los ADMIN que el profesor aceptó la oferta
        $admins = Usuario::whereHas('rolRel', function ($q) {
            $q->where('nombre', 'admin');
        })->get();

        $nombreProf = trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? ''));

        foreach ($admins as $admin) {
            Notificacion::crear(
                idusuario: $admin->idusuario,
                categoria: 'cursos',
                tipo:      'oferta_aceptada',
                titulo:    'Oferta aceptada por profesor',
                mensaje:   "El profesor {$nombreProf} aceptó la oferta del curso «{$curso->nombre}».",
                url:       "/admin/cursos/pendientes",
                datos:     [
                    'idcurso'    => $curso->idcurso,
                    'idprofesor' => $curso->idprofesor,
                ]
            );
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Oferta aceptada, curso publicado y pago pendiente generado correctamente.'
        ]);
    }

    /**
     * ❌ Rechazar o contraofertar (profesor)
     *    + 🔔 Notificar también a los ADMIN
     */
    public function rechazarOferta(Request $request, $idcurso)
    {
        $usuario = $request->user();

        if (!$usuario->profesor) {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo los profesores pueden responder ofertas.'
            ], 403);
        }

        // Aseguramos que el curso sea del profesor logueado
        $curso = Curso::with('oferta')
            ->where('idcurso', $idcurso)
            ->where('idprofesor', $usuario->profesor->idprofesor)
            ->firstOrFail();

        if (!$curso->oferta) {
            return response()->json([
                'ok'      => false,
                'message' => 'No hay oferta para rechazar o responder'
            ], 404);
        }

        $data = $request->validate([
            'comentario' => 'required|string|min:5|max:1000',
            'tipo'       => 'nullable|in:rechazo,contraoferta',
        ]);

        $tipo = $data['tipo'] ?? 'rechazo';

        if ($tipo === 'rechazo') {
            $curso->update(['estado' => 'rechazado']);
        }

        Observacion::create([
            'idcurso'    => $curso->idcurso,
            'idoferta'   => $curso->oferta->idoferta,
            'idusuario'  => $usuario->idusuario,
            'tipo'       => $tipo,
            'comentario' => $data['comentario'],
        ]);

        // 🔔 Notificar a los ADMIN que el profesor respondió la oferta
        $admins = Usuario::whereHas('rolRel', function ($q) {
            $q->where('nombre', 'admin');
        })->get();

        $nombreProf = trim(($usuario->nombres ?? '') . ' ' . ($usuario->apellidos ?? ''));

        $mensajeBase = "El profesor {$nombreProf} ha " .
            ($tipo === 'rechazo' ? 'rechazado' : 'realizado una contraoferta para') .
            " el curso «{$curso->nombre}».";

        $tipoNoti = $tipo === 'rechazo'
            ? 'oferta_rechazada'
            : 'oferta_contraoferta';

        $tituloNoti = $tipo === 'rechazo'
            ? 'Oferta rechazada por profesor'
            : 'Contraoferta de profesor';

        foreach ($admins as $admin) {
            Notificacion::crear(
                idusuario: $admin->idusuario,
                categoria: 'cursos',
                tipo:      $tipoNoti,
                titulo:    $tituloNoti,
                mensaje:   $mensajeBase,
                url:       "/admin/cursos/pendientes",
                datos:     [
                    'idcurso'    => $curso->idcurso,
                    'idprofesor' => $curso->idprofesor,
                    'comentario' => $data['comentario'],
                ]
            );
        }

        return response()->json([
            'ok'      => true,
            'message' => $tipo === 'rechazo'
                ? 'Has rechazado la oferta del curso.'
                : 'Tu contraoferta ha sido enviada correctamente.',
            'curso' => $curso
        ]);
    }

    /**
     * 🔄 Volver a enviar un curso rechazado a revisión
     */
    public function volverAEnviar(Request $request, $idcurso)
    {
        $user = $request->user();

        if (!$user->profesor) {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo los profesores pueden reenviar cursos a revisión.'
            ], 403);
        }

        // Aseguramos que el curso sea del profesor logueado
        $curso = Curso::where('idcurso', $idcurso)
            ->where('idprofesor', $user->profesor->idprofesor)
            ->firstOrFail();

        if ($curso->estado !== 'rechazado') {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo puedes volver a enviar cursos que fueron rechazados.'
            ], 400);
        }

        $curso->update(['estado' => 'en_revision']);

        // 🔔 NOTIFICAR A ADMIN: curso rechazado reenviado a revisión
        $admins = Usuario::whereHas('rolRel', function ($q) {
            $q->where('nombre', 'admin');
        })->get();

        $nombreProf = trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? ''));

        foreach ($admins as $admin) {
            Notificacion::crear(
                idusuario: $admin->idusuario,
                categoria: 'cursos',
                tipo:      'curso_reenviado',
                titulo:    'Curso reenviado a revisión',
                mensaje:   "El profesor {$nombreProf} volvió a enviar el curso «{$curso->nombre}» a revisión.",
                url:       "/admin/cursos/{$curso->idcurso}",
                datos:     [
                    'idcurso'    => $curso->idcurso,
                    'idprofesor' => $user->profesor->idprofesor,
                ]
            );
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Curso reenviado a revisión correctamente.',
            'curso'   => $curso
        ]);
    }

    /**
     * 👑 ADMIN: Listar cursos de un profesor específico
     */
    public function cursosPorProfesor($idprofesor)
    {
        $cursos = Curso::where('idprofesor', $idprofesor)
            ->with(['categoria'])
            ->withCount(['observaciones as num_observaciones'])
            ->withExists(['oferta as tiene_oferta'])
            ->latest()
            ->get();

        return response()->json([
            'ok'   => true,
            'data' => $cursos
        ]);
    }

    /**
     * ✅ Profesor finaliza la edición de un curso
     * Pasa la edición de 'en_edicion' → 'en_revision'
     */
    public function finalizarEdicion(Request $request, $idcursoEdicion)
    {
        $user = $request->user();

        if (!$user->profesor) {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo los profesores pueden finalizar ediciones.'
            ], 403);
        }

        $profesor = $user->profesor;

        // Traer la edición y asegurar que le pertenece a ese profesor
        $edicion = CursoEdicion::with('curso')
            ->where('id', $idcursoEdicion)
            ->where('idprofesor', $profesor->idprofesor)
            ->firstOrFail();

        if ($edicion->estado !== 'en_edicion') {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo puedes finalizar ediciones que están en estado "en_edicion".'
            ], 400);
        }

        // Pasamos a "en_revision" para que el admin la revise
        $edicion->estado = 'en_revision';
        $edicion->save();

        // 🔔 NOTIFICAR A ADMIN: edición lista para revisión
        $curso = $edicion->curso;

        $admins = Usuario::whereHas('rolRel', function ($q) {
            $q->where('nombre', 'admin');
        })->get();

        $nombreProf = trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? ''));

        foreach ($admins as $admin) {
            Notificacion::crear(
                idusuario: $admin->idusuario,
                categoria: 'cursos',
                tipo:      'edicion_en_revision',
                titulo:    'Edición de curso lista para revisión',
                mensaje:   "El profesor {$nombreProf} finalizó la edición del curso «{$curso->nombre}».",
                url:       "/admin/cursos/{$curso->idcurso}",
                datos:     [
                    'idcurso'        => $curso->idcurso,
                    'idcursoEdicion' => $edicion->id,
                ]
            );
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Edición finalizada. El administrador revisará los cambios.',
            'edicion' => $edicion,
        ]);
    }
}
