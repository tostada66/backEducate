<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use App\Models\Comentario;
use App\Models\Profesor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComentarioController extends Controller
{
    /**
     * 📜 Listar comentarios de una clase (tipo YouTube)
     */
    public function index($idclase)
    {
        $clase = Clase::findOrFail($idclase);

        // 🔹 Carga los comentarios principales con usuario y respuestas
        $comentarios = $clase->comentarios()
            ->with(['usuario', 'respuestas.usuario'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $comentarios
        ]);
    }

    /**
     * 💬 Crear un nuevo comentario o respuesta
     */
    public function store(Request $request, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $request->validate([
            'contenido' => 'required|string|max:2000',
            'idpadre'   => 'nullable|exists:comentarios,idcomentario'
        ]);

        $comentario = Comentario::create([
            'idclase'   => $clase->idclase,
            'idusuario' => auth()->id(),
            'idpadre'   => $request->idpadre,
            'contenido' => $request->contenido,
        ]);

        // Cargar usuario relacionado
        $comentario->load('usuario');

        return response()->json([
            'ok' => true,
            'message' => 'Comentario publicado correctamente',
            'data' => $comentario
        ]);
    }

    /**
     * ❌ Eliminar comentario (autor, profesor del curso o admin)
     */
    public function destroy($idcomentario)
    {
        $comentario = Comentario::findOrFail($idcomentario);
        $usuario = auth()->user();

        // 🔹 1️⃣ Autor del comentario
        if ($usuario->idusuario === $comentario->idusuario) {
            $comentario->delete();

            return response()->json([
                'ok' => true,
                'message' => 'Comentario eliminado (autor)',
            ]);
        }

        // 🔹 2️⃣ Profesor dueño del curso
        $profesorAuth = Profesor::where('idusuario', $usuario->idusuario)->first();

        if ($profesorAuth) {
            $idProfesorCurso = DB::table('comentarios')
                ->join('clases', 'comentarios.idclase', '=', 'clases.idclase')
                ->join('unidades', 'clases.idunidad', '=', 'unidades.idunidad')
                ->join('cursos', 'unidades.idcurso', '=', 'cursos.idcurso')
                ->where('comentarios.idcomentario', $comentario->idcomentario)
                ->value('cursos.idprofesor');

            if ($idProfesorCurso && $idProfesorCurso == $profesorAuth->idprofesor) {
                $comentario->delete();

                return response()->json([
                    'ok' => true,
                    'message' => 'Comentario eliminado (profesor del curso)',
                ]);
            }
        }

        // 🔹 3️⃣ Administrador
        if ($usuario->rolRel && $usuario->rolRel->nombre === 'admin') {
            $comentario->delete();

            return response()->json([
                'ok' => true,
                'message' => 'Comentario eliminado (administrador)',
            ]);
        }

        // 🚫 4️⃣ Sin permisos
        return response()->json([
            'ok' => false,
            'message' => 'No tienes permiso para eliminar este comentario',
        ], 403);
    }
}
