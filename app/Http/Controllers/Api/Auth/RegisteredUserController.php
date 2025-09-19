<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisteredUserController extends Controller
{
    /**
     * Registrar nuevo usuario
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'idrol'         => ['required','integer', Rule::exists('roles','idrol')],
            'nombres'       => ['required','string','max:100'],
            'apellidos'     => ['required','string','max:100'],
            'correo'        => ['required','email:rfc,dns','max:191','unique:usuarios,correo'],
            'nombreusuario' => ['required','string','max:60','unique:usuarios,nombreusuario'],
            'telefono'      => ['required','string','max:30','unique:usuarios,telefono'],
            'password'      => ['required','string','min:8','confirmed'],
        ]);

        $user = Usuario::create([
            'idrol'         => $data['idrol'], // 👈 se guarda el rol elegido
            'nombres'       => $data['nombres'],
            'apellidos'     => $data['apellidos'],
            'correo'        => $data['correo'],
            'nombreusuario' => $data['nombreusuario'],
            'telefono'      => $data['telefono'],
            'password'      => Hash::make($data['password']),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Usuario registrado correctamente.',
            'user'    => [
                'idusuario' => $user->idusuario, // 👈 necesario para paso 2 (nivel académico)
                'nombre'    => $user->nombres.' '.$user->apellidos,
                'correo'    => $user->correo,
                'rol'       => $user->rolRel->nombre ?? null, // relación en modelo Usuario
            ]
        ], 201);
    }
}
