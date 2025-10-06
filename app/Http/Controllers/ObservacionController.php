<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observacion;
use Illuminate\Http\Request;

class ObservacionController extends Controller
{
    /**
     * 📋 Listar observaciones de un curso específico.
     */
    public function listarPorCurso($idcurso)
    {
        $observaciones = Observacion::where('idcurso', $idcurso)
            ->with('usuario:idusuario,nombres,apellidos,foto,rol')
            ->orderByDesc('created_at')
            ->get([
                'idobservacion',
                'idcurso',
                'idoferta',
                'idusuario',
                'tipo',
                'comentario',
                'created_at'
            ]);

        return response()->json([
            'ok' => true,
            'data' => $observaciones
        ]);
    }

    /**
     * 📋 Listar observaciones de una oferta específica.
     */
    public function listarPorOferta($idoferta)
    {
        $observaciones = Observacion::where('idoferta', $idoferta)
            ->with('usuario:idusuario,nombres,apellidos,foto,rol')
            ->orderByDesc('created_at')
            ->get([
                'idobservacion',
                'idcurso',
                'idoferta',
                'idusuario',
                'tipo',
                'comentario',
                'created_at'
            ]);

        return response()->json([
            'ok' => true,
            'data' => $observaciones
        ]);
    }

    /**
     * 📝 Crear nueva observación
     * Detecta automáticamente si la crea un profesor (contraoferta)
     * o un administrador (rechazo/revisión/sistema)
     */
    public function store(Request $request)
    {
        $request->validate([
            'comentario' => 'required|string|max:1000',
            'tipo' => 'nullable|in:rechazo,revision,contraoferta,sistema',
            'idcurso' => 'nullable|exists:cursos,idcurso',
            'idoferta' => 'nullable|exists:ofertas,idoferta',
        ]);

        $usuario = $request->user();
        $tipo = $request->input('tipo');

        // 🔹 Si no se envía tipo, decidir automáticamente según rol del usuario
        if (!$tipo) {
            if ($usuario->rol === 'profesor') {
                $tipo = 'contraoferta';
            } else {
                $tipo = 'rechazo';
            }
        }

        $observacion = Observacion::create([
            'idcurso' => $request->idcurso,
            'idoferta' => $request->idoferta,
            'idusuario' => $usuario->idusuario,
            'tipo' => $tipo,
            'comentario' => $request->comentario,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Observación registrada correctamente',
            'data' => $observacion->load('usuario:idusuario,nombres,apellidos,foto,rol')
        ]);
    }

    /**
     * 🔍 Mostrar una observación específica.
     */
    public function show($id)
    {
        $observacion = Observacion::with('usuario:idusuario,nombres,apellidos,foto,rol')
            ->findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => $observacion
        ]);
    }

    /**
     * ❌ Eliminar observación
     */
    public function destroy($id)
    {
        $observacion = Observacion::findOrFail($id);
        $observacion->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Observación eliminada correctamente'
        ]);
    }
}
