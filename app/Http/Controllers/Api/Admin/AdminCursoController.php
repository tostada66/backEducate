<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Observacion;
use App\Models\Oferta;
use App\Models\CursoEdicion; // 👈 IMPORTANTE
use App\Models\Notificacion; // 👈 NUEVO
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCursoController extends Controller
{
    /**
     * 📂 Listar cursos en revisión, con oferta enviada, rechazados
     *    y publicados que tengan una edición activa.
     */
    public function pendientes()
    {
        $cursos = Curso::with([
                'profesor.usuario:idusuario,nombres,apellidos,foto',
                'categoria:idcategoria,nombre',
                // 👇 Traer la edición activa (si existe)
                'edicionActiva',
            ])
            ->withCount([
                // ✅ Contar todas las observaciones relacionadas al curso
                'observaciones as num_observaciones' => function ($q) {
                    $q->whereNotNull('idcurso');
                },
                // 👇 Contador de ediciones activas:
                //    pendiente / en_edicion / en_revision
                'ediciones as ediciones_activas_count' => function ($q) {
                    $q->whereIn('estado', ['pendiente', 'en_edicion', 'en_revision']);
                },
            ])
            ->where(function ($q) {
                // Cursos normales en flujo de aprobación
                $q->whereIn('estado', ['en_revision', 'oferta_enviada', 'rechazado'])
                  // O cursos (normalmente publicados) que tengan una edición activa
                  ->orWhereHas('edicionActiva');
            })
            ->orderByDesc('updated_at')
            ->get([
                'idcurso',
                'idprofesor',
                'idcategoria',
                'nombre',
                'descripcion',
                'imagen',
                'estado',
                'created_at',
                'updated_at'
            ]);

        return response()->json([
            'ok'     => true,
            'cursos' => $cursos
        ]);
    }

    /**
     * 🔍 Preview: obtener número de clases antes de enviar oferta
     */
    public function aprobarPreview($idcurso)
    {
        $curso = Curso::with(['unidades.clases', 'oferta'])->findOrFail($idcurso);

        // Contar clases totales
        $numClases = $curso->unidades->sum(fn ($unidad) => $unidad->clases->count());

        return response()->json([
            'ok'         => true,
            'curso'      => $curso,
            'num_clases' => $numClases,
            'oferta'     => $curso->oferta
        ]);
    }

    /**
     * 📊 Obtener el número total de clases del curso (para el formulario de oferta)
     */
    public function contarClases($idcurso)
    {
        $curso = Curso::with(['unidades.clases'])->findOrFail($idcurso);

        $totalClases = $curso->unidades->sum(fn ($unidad) => $unidad->clases->count());

        return response()->json([
            'ok'           => true,
            'total_clases' => $totalClases,
            'curso'        => [
                'idcurso' => $curso->idcurso,
                'nombre'  => $curso->nombre,
            ]
        ]);
    }

    /**
     * 💼 Enviar oferta al profesor (NO publica aún el curso)
     */
    public function aprobar(Request $request, $idcurso)
    {
        // 👈 Traemos también el usuario del profesor
        $curso = Curso::with(['unidades.clases', 'profesor.usuario'])->findOrFail($idcurso);

        if ($curso->estado === 'publicado') {
            return response()->json([
                'ok'      => false,
                'message' => 'El curso ya está publicado'
            ], 400);
        }

        $request->validate([
            'tarifa_por_clase' => 'required|numeric|min:0',
            'tarifa_por_mes'   => 'required|numeric|min:0',
            'duracion_meses'   => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($curso, $request) {
            $numClases = $curso->unidades->sum(fn ($u) => $u->clases->count());

            if ($numClases === 0) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se puede enviar una oferta: el curso no tiene clases registradas.'
                ], 400);
            }

            $costo_total = ($numClases * $request->tarifa_por_clase)
                + ($request->duracion_meses * $request->tarifa_por_mes);

            $oferta = Oferta::updateOrCreate(
                ['idcurso' => $curso->idcurso],
                [
                    'idprofesor'       => $curso->idprofesor,
                    'num_clases'       => $numClases,
                    'tarifa_por_clase' => $request->tarifa_por_clase,
                    'tarifa_por_mes'   => $request->tarifa_por_mes,
                    'duracion_meses'   => $request->duracion_meses,
                    'costo_total'      => $costo_total,
                    'estado'           => 'pendiente',
                ]
            );

            $curso->update(['estado' => 'oferta_enviada']);

            // 🔔 Notificar al PROFESOR que tiene una nueva oferta
            $profesor      = optional($curso->profesor);
            $usuarioProf   = optional($profesor->usuario);
            $idusuarioProf = $usuarioProf->idusuario;

            if ($idusuarioProf) {
                Notificacion::crearParaUsuario($idusuarioProf, [
                    'categoria' => 'cursos',
                    'tipo'      => 'oferta_enviada',
                    'titulo'    => 'Nueva oferta para tu curso',
                    'mensaje'   => 'Se ha generado una oferta para tu curso «' . $curso->nombre . '». Revisa los detalles y acepta o rechaza la propuesta.',
                    'url'       => '/profesor/cursos/' . $curso->idcurso,
                    'datos'     => [
                        'idcurso' => $curso->idcurso,
                        'idoferta'=> $oferta->idoferta,
                    ],
                ]);
            }

            return response()->json([
                'ok'      => true,
                'message' => 'Oferta enviada correctamente al profesor',
                'oferta'  => $oferta,
                'curso'   => $curso,
            ]);
        });
    }

    /**
     * ❌ Rechazar curso antes de publicación (admin)
     */
    public function rechazar(Request $request, $idcurso)
    {
        $curso = Curso::with('profesor.usuario')->findOrFail($idcurso);
        $admin = $request->user();

        if ($curso->estado === 'publicado') {
            return response()->json([
                'ok'      => false,
                'message' => 'No puedes rechazar un curso ya publicado'
            ], 400);
        }

        $data = $request->validate([
            'comentario' => 'required|string|min:5|max:1000',
        ]);

        // Cambiar estado a rechazado
        $curso->update(['estado' => 'rechazado']);

        // Registrar observación (comentario del admin)
        Observacion::create([
            'idcurso'   => $curso->idcurso,
            'idusuario' => $admin->idusuario,
            'tipo'      => 'rechazo',
            'comentario'=> $data['comentario'],
        ]);

        // 🔔 Notificar al PROFESOR que su curso fue rechazado
        $profesor      = optional($curso->profesor);
        $usuarioProf   = optional($profesor->usuario);
        $idusuarioProf = $usuarioProf->idusuario;

        if ($idusuarioProf) {
            Notificacion::crearParaUsuario($idusuarioProf, [
                'categoria' => 'cursos',
                'tipo'      => 'curso_rechazado',
                'titulo'    => 'Tu curso fue rechazado',
                'mensaje'   => 'El curso «' . $curso->nombre . '» fue rechazado por el equipo de revisión. Revisa los comentarios para realizar los cambios necesarios.',
                'url'       => '/profesor/cursos/' . $curso->idcurso,
                'datos'     => [
                    'idcurso'    => $curso->idcurso,
                    'comentario' => $data['comentario'],
                ],
            ]);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Curso rechazado correctamente',
            'curso'   => $curso,
        ]);
    }

    /**
     * 🔴 Listar solo cursos rechazados (para auditorías)
     */
    public function rechazados()
    {
        $cursos = Curso::where('estado', 'rechazado')
            ->with([
                'profesor.usuario:idusuario,nombres,apellidos,foto',
                'categoria:idcategoria,nombre'
            ])
            ->withCount(['observaciones as num_observaciones'])
            ->orderByDesc('updated_at')
            ->get([
                'idcurso',
                'idprofesor',
                'idcategoria',
                'nombre',
                'descripcion',
                'imagen',
                'estado',
                'created_at',
                'updated_at'
            ]);

        return response()->json([
            'ok'     => true,
            'cursos' => $cursos
        ]);
    }

    /**
     * ✅ Aprobar solicitud de edición de un curso
     * El profesor podrá editar el curso mientras la edición esté "en_edicion".
     */
    public function aprobarEdicion($idcursoEdicion, Request $request)
    {
        // Traemos la edición con su curso asociado y el usuario del profesor
        $edicion = CursoEdicion::with('curso.profesor.usuario')->findOrFail($idcursoEdicion);

        // Solo tiene sentido aprobar si está pendiente
        if ($edicion->estado !== 'pendiente') {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo se pueden aprobar solicitudes en estado pendiente.',
            ], 400);
        }

        // (Opcional) asegurar que el curso esté publicado
        if ($edicion->curso && $edicion->curso->estado !== 'publicado') {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo puedes abrir edición para cursos publicados.',
            ], 400);
        }

        // Cambiamos estado a en_edicion y guardamos fecha
        $edicion->estado      = 'en_edicion';
        $edicion->aprobado_at = now();
        $edicion->save();

        // 🔔 Notificar al PROFESOR que su solicitud de edición fue aprobada
        $curso        = $edicion->curso;
        $profesor     = optional($curso?->profesor);
        $usuarioProf  = optional($profesor->usuario);
        $idusuarioProf = $usuarioProf->idusuario;

        if ($idusuarioProf && $curso) {
            Notificacion::crearParaUsuario($idusuarioProf, [
                'categoria' => 'ediciones',
                'tipo'      => 'edicion_aprobada',
                'titulo'    => 'Solicitud de edición aprobada',
                'mensaje'   => 'Tu solicitud de edición para el curso «' . $curso->nombre . '» fue aprobada. Ya puedes realizar los cambios.',
                'url'       => '/profesor/cursos/' . $curso->idcurso,
                'datos'     => [
                    'idcurso'        => $curso->idcurso,
                    'idcursoEdicion' => $edicion->id,
                ],
            ]);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Solicitud de edición aprobada. El profesor ya puede modificar el curso.',
            'edicion' => $edicion,
        ]);
    }

    /**
     * ✅ Cerrar una edición (ADMIN)
     * La edición pasa de 'en_revision' → 'cerrada' y el curso queda publicado.
     * Sirve para que deje de aparecer como "edición activa".
     */
    public function cerrarEdicion($idcursoEdicion, Request $request)
    {
        $edicion = CursoEdicion::with('curso.profesor.usuario')->findOrFail($idcursoEdicion);

        // Solo tiene sentido cerrar si ya está en revisión (el profe ya terminó)
        if ($edicion->estado !== 'en_revision') {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo puedes cerrar ediciones que están en estado "en_revision".',
            ], 400);
        }

        return DB::transaction(function () use ($edicion) {
            $curso = $edicion->curso;

            // Por si acaso, aseguramos que el curso quede publicado
            if ($curso && $curso->estado !== 'publicado') {
                $curso->estado = 'publicado';
                $curso->save();
            }

            // Marcamos la edición como cerrada (solo histórico)
            $edicion->estado     = 'cerrada';
            $edicion->cerrado_at = now();
            $edicion->save();

            // 🔔 Notificar al PROFESOR que la revisión de edición terminó
            $profesor      = optional($curso?->profesor);
            $usuarioProf   = optional($profesor->usuario);
            $idusuarioProf = $usuarioProf->idusuario;

            if ($idusuarioProf && $curso) {
                Notificacion::crearParaUsuario($idusuarioProf, [
                    'categoria' => 'ediciones',
                    'tipo'      => 'edicion_cerrada',
                    'titulo'    => 'Edición revisada y cerrada',
                    'mensaje'   => 'La edición del curso «' . $curso->nombre . '» fue revisada y cerrada. El curso permanece publicado con los cambios aprobados.',
                    'url'       => '/profesor/cursos/' . $curso->idcurso,
                    'datos'     => [
                        'idcurso'        => $curso->idcurso,
                        'idcursoEdicion' => $edicion->id,
                    ],
                ]);
            }

            return response()->json([
                'ok'      => true,
                'message' => 'Edición revisada y cerrada correctamente. El curso queda publicado.',
                'edicion' => $edicion,
            ]);
        });
    }
}
