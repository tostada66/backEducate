<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Notificacion; // 👈 IMPORTANTE: importar el modelo de notificaciones
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
            'idrol'         => ['required','integer', Rule::exists('roles', 'idrol')],
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

        /**
         * 🔔 Si el usuario registrado es PROFESOR (idrol = 2),
         *     crear notificaciones para todos los ADMIN.
         */
        if ((int) $user->idrol === 2) { // 👈 ajusta si el id del rol profesor es otro
            // Buscar admins por relación de rol
            $admins = Usuario::whereHas('rolRel', function ($q) {
                $q->where('nombre', 'admin'); // 👈 ajusta al nombre real del rol en tu tabla roles
            })->get();

            $nombreProfe = trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? ''));

            foreach ($admins as $admin) {
                Notificacion::crear(
                    idusuario: $admin->idusuario,
                    categoria: 'solicitudes', // se suma en resumenNotis.solicitudes
                    tipo:      'nueva_solicitud_profesor',
                    titulo:    'Nueva solicitud de profesor',
                    mensaje:   "El usuario {$nombreProfe} se registró como profesor y requiere revisión.",
                    url:       '/admin/solicitudes', // 👉 al hacer clic, lo lleva a la pantalla de solicitudes
                    datos:     [
                        'idusuario_profesor' => $user->idusuario,
                        'nombre_profesor'    => $nombreProfe,
                    ]
                );
            }
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Usuario registrado correctamente.',
            'user'    => [
                'idusuario' => $user->idusuario, // 👈 necesario para paso 2 (nivel académico)
                'nombre'    => $user->nombres . ' ' . $user->apellidos,
                'correo'    => $user->correo,
                'rol'       => $user->rolRel->nombre ?? null, // relación en modelo Usuario
            ]
        ], 201);
    }
}
