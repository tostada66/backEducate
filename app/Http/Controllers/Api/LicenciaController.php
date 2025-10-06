<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Licencia;
use Illuminate\Http\Request;

class LicenciaController extends Controller
{
    /**
     * 📂 ADMIN: Ver todas las licencias de todos los cursos
     */
    public function indexAdmin()
    {
        $licencias = Licencia::with([
                'curso.profesor.usuario', // curso -> profesor -> usuario
                'curso.categoria'
            ])
            ->latest()
            ->get();

        return response()->json([
            'ok' => true,
            'licencias' => $licencias
        ]);
    }

    /**
     * 📂 PROFESOR: Ver solo las licencias de sus cursos
     */
    public function indexProfesor(Request $request)
    {
        $idprofesor = $request->user()->profesor->idprofesor;

        $licencias = Licencia::with(['curso.categoria'])
            ->where('idprofesor', $idprofesor)
            ->latest()
            ->get();

        return response()->json([
            'ok' => true,
            'licencias' => $licencias
        ]);
    }
}
