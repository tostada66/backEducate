<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\PerfilUsuario;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EditarProfileController extends Controller
{
    /**
     * Mostrar perfil logueado (para edición).
     */
    public function show(Request $request)
    {
        $user = $request->user()
            ->load(['perfil','estudiante.categorias','rolRel']);

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUserResponse($user)
        ]);
    }

    /**
     * Editar datos de perfil logueado.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nombres'        => ['sometimes','string','max:100'],
            'apellidos'      => ['sometimes','string','max:100'],
            'correo'         => ['sometimes','email','max:191', Rule::unique('usuarios','correo')->ignore($user->idusuario,'idusuario')],
            'nombreusuario'  => ['sometimes','string','max:60', Rule::unique('usuarios','nombreusuario')->ignore($user->idusuario,'idusuario')],
            'telefono'       => ['sometimes','nullable','string','max:30', Rule::unique('usuarios','telefono')->ignore($user->idusuario,'idusuario')],
            'linkedin_url'   => ['sometimes','nullable','url','max:255'],
            'github_url'     => ['sometimes','nullable','url','max:255'],
            'web_url'        => ['sometimes','nullable','url','max:255'],
            'bio'            => ['sometimes','nullable','string'],
            'nivelacademico' => ['sometimes','nullable','string','max:80'],
            'categorias'     => ['sometimes','array'],
            'categorias.*'   => ['integer','exists:categorias,idcategoria'],
        ]);

        // actualizar datos básicos
        $user->fill(collect($data)->only([
            'nombres','apellidos','correo','nombreusuario','telefono'
        ])->toArray());
        $user->save();

        // actualizar perfil extendido
        $perfil = PerfilUsuario::firstOrCreate(['idusuario' => $user->idusuario]);
        $perfil->fill(collect($data)->only([
            'linkedin_url','github_url','web_url','bio'
        ])->toArray());
        $perfil->save();

        // actualizar estudiante
        if ($user->rolRel && $user->rolRel->nombre === 'Estudiante') {
            $estudiante = Estudiante::firstOrCreate(['idusuario' => $user->idusuario]);
            if (isset($data['nivelacademico'])) {
                $estudiante->nivelacademico = $data['nivelacademico'];
                $estudiante->save();
            }
            if (isset($data['categorias'])) {
                $estudiante->categorias()->sync($data['categorias']);
            }
        }

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUserResponse($user->fresh(['perfil','estudiante.categorias','rolRel']))
        ]);
    }

    /**
     * Cambiar contraseña.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'new_password'     => ['required','string','min:8','confirmed'], // necesita new_password_confirmation
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'ok' => false,
                'message' => 'La contraseña actual no es correcta.'
            ], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'Contraseña actualizada correctamente.'
        ]);
    }

    /**
     * Subir/reemplazar foto de perfil.
     */
    public function updateFoto(Request $request)
    {
        $request->validate([
            'foto' => ['required','image','max:2048'],
        ]);

        $user = $request->user();

        if ($user->foto && Storage::disk('public')->exists($user->foto)) {
            Storage::disk('public')->delete($user->foto);
        }

        $path = $request->file('foto')->store('usuarios', 'public');
        $user->foto = $path;
        $user->save();

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUserResponse($user->fresh(['perfil','estudiante.categorias','rolRel']))
        ]);
    }

    /**
     * Dar formato uniforme al perfil.
     */
    private function formatUserResponse($user)
    {
        return [
            'idusuario'      => $user->idusuario,
            'nombres'        => $user->nombres,
            'apellidos'      => $user->apellidos,
            'correo'         => $user->correo,
            'nombreusuario'  => $user->nombreusuario,
            'telefono'       => $user->telefono,
            'foto_url'       => $user->foto_url,

            // perfil extendido
            'linkedin_url'   => $user->perfil->linkedin_url ?? null,
            'github_url'     => $user->perfil->github_url ?? null,
            'web_url'        => $user->perfil->web_url ?? null,
            'bio'            => $user->perfil->bio ?? null,

            // estudiante
            'nivelacademico' => $user->estudiante->nivelacademico ?? null,
            'categorias'     => $user->estudiante
                ? $user->estudiante->categorias->map(fn($c) => [
                    'idcategoria' => $c->idcategoria,
                    'nombre'      => $c->nombre,
                ])
                : [],
        ];
    }
}
