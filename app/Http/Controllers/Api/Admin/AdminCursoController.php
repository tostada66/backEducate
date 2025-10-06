<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Oferta;
use App\Models\Observacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCursoController extends Controller
{
    /**
     * 📂 Listar cursos en revisión, con oferta enviada o rechazados
     */
    public function pendientes()
    {
        $cursos = Curso::whereIn('estado', ['en_revision', 'oferta_enviada', 'rechazado'])
            ->with([
                'profesor.usuario:idusuario,nombres,apellidos,foto',
                'categoria:idcategoria,nombre'
            ])
            ->withCount(['observaciones as num_observaciones' => function ($q) {
                // ✅ Contar todas las observaciones relacionadas al curso
                $q->whereNotNull('idcurso');
            }])
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
            'ok' => true,
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
        $numClases = $curso->unidades->sum(fn($unidad) => $unidad->clases->count());

        return response()->json([
            'ok' => true,
            'curso' => $curso,
            'num_clases' => $numClases,
            'oferta' => $curso->oferta
        ]);
    }

    /**
     * 📊 Obtener el número total de clases del curso (para el formulario de oferta)
     */
    public function contarClases($idcurso)
    {
        $curso = Curso::with(['unidades.clases'])->findOrFail($idcurso);

        $totalClases = $curso->unidades->sum(fn($unidad) => $unidad->clases->count());

        return response()->json([
            'ok' => true,
            'total_clases' => $totalClases,
            'curso' => [
                'idcurso' => $curso->idcurso,
                'nombre' => $curso->nombre,
            ]
        ]);
    }

    /**
     * 💼 Enviar oferta al profesor (NO publica aún el curso)
     */
    public function aprobar(Request $request, $idcurso)
    {
        $curso = Curso::with(['unidades.clases', 'profesor'])->findOrFail($idcurso);

        if ($curso->estado === 'publicado') {
            return response()->json([
                'ok' => false,
                'message' => 'El curso ya está publicado'
            ], 400);
        }

        $request->validate([
            'tarifa_por_clase' => 'required|numeric|min:0',
            'tarifa_por_mes'   => 'required|numeric|min:0',
            'duracion_meses'   => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($curso, $request) {
            $numClases = $curso->unidades->sum(fn($u) => $u->clases->count());

            if ($numClases === 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se puede enviar una oferta: el curso no tiene clases registradas.'
                ], 400);
            }

            $costo_total = ($numClases * $request->tarifa_por_clase)
                        + ($request->duracion_meses * $request->tarifa_por_mes);

            $oferta = Oferta::updateOrCreate(
                ['idcurso' => $curso->idcurso],
                [
                    'idprofesor'        => $curso->idprofesor,
                    'num_clases'        => $numClases,
                    'tarifa_por_clase'  => $request->tarifa_por_clase,
                    'tarifa_por_mes'    => $request->tarifa_por_mes,
                    'duracion_meses'    => $request->duracion_meses,
                    'costo_total'       => $costo_total,
                    'estado'            => 'pendiente',
                ]
            );

            $curso->update(['estado' => 'oferta_enviada']);

            return response()->json([
                'ok' => true,
                'message' => 'Oferta enviada correctamente al profesor',
                'oferta' => $oferta,
                'curso' => $curso,
            ]);
        });
    }

    /**
     * ❌ Rechazar curso antes de publicación (admin)
     */
    public function rechazar(Request $request, $idcurso)
    {
        $curso = Curso::findOrFail($idcurso);
        $admin = $request->user();

        if ($curso->estado === 'publicado') {
            return response()->json([
                'ok' => false,
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

        return response()->json([
            'ok' => true,
            'message' => 'Curso rechazado correctamente',
            'curso' => $curso,
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
            'ok' => true,
            'cursos' => $cursos
        ]);
    }
}
