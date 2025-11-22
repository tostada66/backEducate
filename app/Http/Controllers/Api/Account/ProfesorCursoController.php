<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Licencia;
use App\Models\Observacion;
use App\Models\Oferta;
use App\Models\PagoProfesor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfesorCursoController extends Controller
{
    /**
     * 📚 Listar cursos del profesor con observaciones y oferta
     */
    public function index(Request $request)
    {
        $profesor = $request->user()->profesor;

        $query = Curso::where('idprofesor', $profesor->idprofesor)
            ->with(['categoria'])
            ->withCount(['observaciones as num_observaciones'])
            ->withExists(['oferta as tiene_oferta']);

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        $cursos = $query->latest()->get();

        return response()->json([
            'ok' => true,
            'data' => $cursos
        ]);
    }

    /**
     * 🔁 Enviar curso a revisión
     */
    public function enviarRevision($idcurso)
    {
        $curso = Curso::findOrFail($idcurso);

        if ($curso->estado !== 'borrador' && $curso->estado !== 'rechazado') {
            return response()->json([
                'ok' => false,
                'message' => 'Solo puedes enviar a revisión cursos en borrador o rechazados.'
            ], 400);
        }

        $curso->update(['estado' => 'en_revision']);

        return response()->json([
            'ok' => true,
            'message' => 'Curso enviado a revisión correctamente.',
            'curso' => $curso
        ]);
    }

    /**
     * 💼 Ver oferta pendiente
     */
    public function verOferta($idcurso)
    {
        $curso = Curso::with('oferta')->findOrFail($idcurso);

        if (!$curso->oferta) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay oferta disponible para este curso'
            ], 404);
        }

        $oferta = $curso->oferta->makeHidden(['created_at', 'updated_at']);
        $oferta->costo = $curso->oferta->costo_total;

        return response()->json([
            'ok' => true,
            'oferta' => $oferta
        ]);
    }

    /**
     * ✅ Aceptar oferta → crea licencia, publica curso y genera pago pendiente
     */
    public function aceptarOferta($idcurso)
    {
        $curso = Curso::with('oferta', 'profesor')->findOrFail($idcurso);
        $oferta = $curso->oferta;

        if (!$oferta) {
            return response()->json(['ok' => false, 'message' => 'No hay oferta para aceptar'], 404);
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

        return response()->json([
            'ok' => true,
            'message' => 'Oferta aceptada, curso publicado y pago pendiente generado correctamente.'
        ]);
    }

    /**
     * ❌ Rechazar o contraofertar (profesor)
     */
    public function rechazarOferta(Request $request, $idcurso)
    {
        $curso = Curso::with('oferta')->findOrFail($idcurso);
        $profesor = $request->user();

        if (!$curso->oferta) {
            return response()->json([
                'ok' => false,
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
            'idcurso'   => $curso->idcurso,
            'idoferta'  => $curso->oferta->idoferta,
            'idusuario' => $profesor->idusuario,
            'tipo'      => $tipo,
            'comentario' => $data['comentario'],
        ]);

        return response()->json([
            'ok' => true,
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
        $curso = Curso::findOrFail($idcurso);

        if ($curso->estado !== 'rechazado') {
            return response()->json([
                'ok' => false,
                'message' => 'Solo puedes volver a enviar cursos que fueron rechazados.'
            ], 400);
        }

        $curso->update(['estado' => 'en_revision']);

        return response()->json([
            'ok' => true,
            'message' => 'Curso reenviado a revisión correctamente.',
            'curso' => $curso
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
            'ok' => true,
            'data' => $cursos
        ]);
    }
}
