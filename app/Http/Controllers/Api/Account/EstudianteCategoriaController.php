<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Estudiante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstudianteCategoriaController extends Controller
{
    /**
     * Guardar intereses del estudiante durante el registro (sin login).
     * Sobrescribe todos los intereses con los que marque el usuario.
     */
    public function guardarInteresesRegistro(Request $request)
    {
        $request->validate([
            'idusuario'    => 'required|exists:usuarios,idusuario',
            'categorias'   => 'required|array|min:1',
            'categorias.*' => 'integer|exists:categorias,idcategoria',
        ]);

        try {
            $estudiante = Estudiante::firstOrCreate(
                ['idusuario' => $request->idusuario]
            );

            // 🔹 Sobrescribe lo que seleccione
            $estudiante->categorias()->sync($request->categorias);

            // 🔹 Traer categorías sin ambigüedad
            $categorias = DB::table('categorias')
                ->join('estudiante_categoria', 'categorias.idcategoria', '=', 'estudiante_categoria.idcategoria')
                ->where('estudiante_categoria.idestudiante', $estudiante->idestudiante)
                ->select('categorias.idcategoria', 'categorias.nombre')
                ->get();

            return response()->json([
                'ok'         => true,
                'message'    => 'Intereses guardados correctamente (registro).',
                'categorias' => $categorias,
            ], 201);
        } catch (\Throwable $e) {
            Log::error("❌ Error guardarInteresesRegistro", [
                'exception' => $e,
                'payload'   => $request->all()
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Error al guardar intereses',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Actualizar intereses del estudiante (requiere login).
     * Aquí no eliminamos lo que ya tenía, solo agregamos los nuevos.
     */
    public function updateIntereses(Request $request)
    {
        $request->validate([
            'categorias'   => 'required|array|min:1',
            'categorias.*' => 'integer|exists:categorias,idcategoria',
        ]);

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $estudiante = Estudiante::firstOrCreate(
                ['idusuario' => $user->idusuario]
            );

            $estudiante->categorias()->syncWithoutDetaching($request->categorias);

            $categorias = DB::table('categorias')
                ->join('estudiante_categoria', 'categorias.idcategoria', '=', 'estudiante_categoria.idcategoria')
                ->where('estudiante_categoria.idestudiante', $estudiante->idestudiante)
                ->select('categorias.idcategoria', 'categorias.nombre')
                ->get();

            return response()->json([
                'ok'         => true,
                'message'    => 'Intereses actualizados correctamente.',
                'categorias' => $categorias,
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Error updateIntereses", [
                'exception' => $e,
                'payload'   => $request->all()
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Error al actualizar intereses',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Obtener intereses del estudiante (requiere login o idusuario explícito).
     */
    public function getIntereses($idusuario)
    {
        try {
            $estudiante = Estudiante::where('idusuario', $idusuario)->firstOrFail();

            $categorias = DB::table('categorias')
                ->join('estudiante_categoria', 'categorias.idcategoria', '=', 'estudiante_categoria.idcategoria')
                ->where('estudiante_categoria.idestudiante', $estudiante->idestudiante)
                ->select('categorias.idcategoria', 'categorias.nombre')
                ->get();

            return response()->json($categorias);
        } catch (\Throwable $e) {
            Log::error("❌ Error getIntereses", [
                'exception' => $e,
                'idusuario' => $idusuario,
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Error al obtener intereses',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }
}
