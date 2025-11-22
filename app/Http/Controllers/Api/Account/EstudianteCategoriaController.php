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

            // 🔹 Solo las seleccionadas del estudiante
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
            ], 500);
        }
    }

    /**
     * Obtener SOLO los intereses del estudiante (vista normal).
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

            return response()->json([
                'ok'         => true,
                'categorias' => $categorias,
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Error getIntereses", [
                'exception' => $e,
                'idusuario' => $idusuario,
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Error al obtener intereses',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener TODAS las categorías y marcar cuáles tiene el estudiante (modo edición).
     */
    public function getTodasConEstado($idusuario)
    {
        try {
            $estudiante = Estudiante::where('idusuario', $idusuario)->firstOrFail();

            // todas las categorías
            $todas = DB::table('categorias')->select('idcategoria', 'nombre')->get();

            // categorías seleccionadas por el estudiante
            $seleccionadas = DB::table('estudiante_categoria')
                ->where('idestudiante', $estudiante->idestudiante)
                ->pluck('idcategoria')
                ->toArray();

            // respuesta con flag seleccionado
            $categorias = $todas->map(function ($cat) use ($seleccionadas) {
                return [
                    'idcategoria'  => $cat->idcategoria,
                    'nombre'       => $cat->nombre,
                    'seleccionado' => in_array($cat->idcategoria, $seleccionadas),
                ];
            });

            return response()->json([
                'ok'         => true,
                'categorias' => $categorias,
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Error getTodasConEstado", [
                'exception' => $e,
                'idusuario' => $idusuario,
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Error al obtener todas las categorías',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
