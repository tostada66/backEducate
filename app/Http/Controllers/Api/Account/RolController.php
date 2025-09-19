<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rol;

class RolController extends Controller
{
    public function choose(Request $request)
    {
        $request->validate([
            'rol' => 'required|in:estudiante,profesor',
        ]);

        $user = $request->user();

        $rol = Rol::where('nombre', $request->rol)->first();
        if (!$rol) {
            return response()->json(['message' => 'Rol no existe.'], 422);
        }

        $user->idrol = $rol->idrol;
        $user->save();

        // si es estudiante/ profesor crea ficha si hace falta (opcional)
        if ($rol->nombre === 'estudiante' && !$user->estudiante) {
            \App\Models\Estudiante::create(['idusuario' => $user->idusuario]);
        }
        if ($rol->nombre === 'profesor' && !$user->profesor) {
            \App\Models\Profesor::create(['idusuario' => $user->idusuario]);
        }

        return response()->json(['ok' => true, 'rol' => $rol->nombre]);
    }
}
