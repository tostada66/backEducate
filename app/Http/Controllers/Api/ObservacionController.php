<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observacion;
use Illuminate\Http\Request;

class ObservacionController extends Controller
{
    /**
     * 📜 Listar observaciones de un curso (admin o profesor)
     */
    public function listarPorCurso($idcurso)
    {
        $observaciones = Observacion::where('idcurso', $idcurso)
            ->with('usuario:idusuario,nombres,apellidos,foto')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($obs) {
                return [
                    'idobservacion' => $obs->idobservacion,
                    'tipo'          => $obs->tipo,
                    'comentario'    => $obs->comentario,
                    'fecha'         => $obs->created_at->format('d/m/Y H:i'),
                    'usuario'       => $obs->usuario
                        ? [
                            'idusuario' => $obs->usuario->idusuario,
                            'nombre'    => $obs->usuario->nombres . ' ' . $obs->usuario->apellidos,
                            'foto'      => $obs->usuario->foto
                                ? asset('storage/' . $obs->usuario->foto)
                                : null,
                        ]
                        : null,
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $observaciones,
        ]);
    }

    /**
     * 💼 Listar observaciones de una oferta (admin o profesor)
     */
    public function listarPorOferta($idoferta)
    {
        $observaciones = Observacion::where('idoferta', $idoferta)
            ->with('usuario:idusuario,nombres,apellidos,foto')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($obs) {
                return [
                    'idobservacion' => $obs->idobservacion,
                    'tipo'          => $obs->tipo,
                    'comentario'    => $obs->comentario,
                    'fecha'         => $obs->created_at->format('d/m/Y H:i'),
                    'usuario'       => $obs->usuario
                        ? [
                            'idusuario' => $obs->usuario->idusuario,
                            'nombre'    => $obs->usuario->nombres . ' ' . $obs->usuario->apellidos,
                            'foto'      => $obs->usuario->foto
                                ? asset('storage/' . $obs->usuario->foto)
                                : null,
                        ]
                        : null,
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $observaciones,
        ]);
    }

    /**
     * 📝 Crear una nueva observación (rechazo o contraoferta)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'idcurso'    => 'nullable|exists:cursos,idcurso',
            'idoferta'   => 'nullable|exists:ofertas,idoferta',
            'tipo'       => 'required|in:rechazo,contraoferta,info',
            'comentario' => 'required|string|min:5|max:1000',
        ]);

        $usuario = $request->user();

        $observacion = Observacion::create([
            'idcurso'    => $data['idcurso'] ?? null,
            'idoferta'   => $data['idoferta'] ?? null,
            'idusuario'  => $usuario->idusuario,
            'tipo'       => $data['tipo'],
            'comentario' => $data['comentario'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Observación registrada correctamente',
            'data' => $observacion,
        ], 201);
    }

    /**
     * 🗑️ Eliminar observación (solo admin o autor)
     */
    public function destroy(Request $request, $id)
    {
        $observacion = Observacion::findOrFail($id);
        $usuario = $request->user();

        // Solo el autor o un admin pueden eliminar
        if ($observacion->idusuario !== $usuario->idusuario && $usuario->rol !== 'admin') {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para eliminar esta observación',
            ], 403);
        }

        $observacion->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Observación eliminada correctamente',
        ]);
    }
}
