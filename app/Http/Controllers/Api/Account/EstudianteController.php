<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    /**
     * Guardar nivel académico en registro (sin login)
     */
    public function guardarNivelRegistro(Request $request)
    {
        $request->validate([
            'idusuario' => 'required|exists:usuarios,idusuario',
            'nivelacademico' => 'required|string|max:80',
        ]);

        $estudiante = Estudiante::firstOrCreate(
            ['idusuario' => $request->idusuario],
            ['nivelacademico' => $request->nivelacademico]
        );

        // si ya existía, actualizamos
        $estudiante->nivelacademico = $request->nivelacademico;
        $estudiante->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Nivel académico guardado correctamente (registro).',
            'estudiante' => $estudiante
        ]);
    }

    /**
     * Guardar o actualizar nivel académico del estudiante (con login)
     */
    public function guardarNivel(Request $request)
    {
        $request->validate([
            'nivelacademico' => 'required|string|max:80',
        ]);

        $user = $request->user(); // usuario autenticado

        $estudiante = Estudiante::firstOrCreate(
            ['idusuario' => $user->idusuario]
        );

        $estudiante->nivelacademico = $request->nivelacademico;
        $estudiante->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Nivel académico guardado correctamente (con login).',
            'nivelacademico' => $estudiante->nivelacademico,
        ]);
    }

    /**
     * Obtener datos del estudiante autenticado
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $estudiante = Estudiante::where('idusuario', $user->idusuario)
            ->with('categorias')
            ->first();

        return response()->json($estudiante);
    }
}
