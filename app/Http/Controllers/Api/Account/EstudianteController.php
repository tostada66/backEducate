<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    /**
     * Guardar datos del estudiante en el registro (sin login)
     * - nivelacademico (requerido)
     * - escuela (opcional)
     * - bio (opcional)
     */
    public function guardarNivelRegistro(Request $request)
    {
        $data = $request->validate([
            'idusuario'      => 'required|exists:usuarios,idusuario',
            'nivelacademico' => 'required|string|max:80',
            'escuela'        => 'nullable|string|max:255',
            'bio'            => 'nullable|string',
        ]);

        // Crear o recuperar el estudiante
        $estudiante = Estudiante::firstOrCreate(
            ['idusuario' => $data['idusuario']]
        );

        // Actualizar campos
        $estudiante->nivelacademico = $data['nivelacademico'];
        $estudiante->escuela        = $data['escuela'] ?? null;
        $estudiante->bio            = $data['bio'] ?? null;
        $estudiante->save();

        return response()->json([
            'ok'        => true,
            'message'   => 'Datos de estudiante guardados correctamente (registro).',
            'estudiante'=> $estudiante,
        ]);
    }

    /**
     * Guardar o actualizar datos del estudiante (con login)
     * - nivelacademico (requerido)
     * - escuela (opcional)
     * - bio (opcional)
     */
    public function guardarNivel(Request $request)
    {
        $data = $request->validate([
            'nivelacademico' => 'required|string|max:80',
            'escuela'        => 'nullable|string|max:255',
            'bio'            => 'nullable|string',
        ]);

        $user = $request->user(); // usuario autenticado

        $estudiante = Estudiante::firstOrCreate(
            ['idusuario' => $user->idusuario]
        );

        $estudiante->nivelacademico = $data['nivelacademico'];
        $estudiante->escuela        = $data['escuela'] ?? $estudiante->escuela;
        $estudiante->bio            = $data['bio'] ?? $estudiante->bio;
        $estudiante->save();

        return response()->json([
            'ok'            => true,
            'message'       => 'Datos del estudiante guardados correctamente (con login).',
            'nivelacademico'=> $estudiante->nivelacademico,
            'escuela'       => $estudiante->escuela,
            'bio'           => $estudiante->bio,
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
