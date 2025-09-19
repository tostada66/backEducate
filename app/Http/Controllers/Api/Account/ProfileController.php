<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\PerfilUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Obtener el perfil completo del usuario autenticado (requiere login)
     */
    public function show(Request $request)
    {
        $user = $request->user()
            ->load(['estudiante.categorias','perfil','rolRel']);

        return $this->formatUserResponse($user);
    }

    /**
     * Obtener el perfil de un usuario por ID (modo público, durante registro)
     */
    public function showRegistro($idusuario)
    {
        $user = \App\Models\Usuario::with(['estudiante.categorias','perfil','rolRel'])
            ->findOrFail($idusuario);

        return $this->formatUserResponse($user);
    }

    /**
     * Formato de respuesta de perfil
     */
    private function formatUserResponse($user)
    {
        return response()->json([
            'ok'   => true,
            'user' => [
                'idusuario'      => $user->idusuario,
                'nombres'        => $user->nombres,
                'apellidos'      => $user->apellidos,
                'correo'         => $user->correo,
                'nombreusuario'  => $user->nombreusuario,
                'telefono'       => $user->telefono,
                'estado'         => $user->estado,
                'foto_url'       => $user->foto_url,

                // perfil extendido
                'linkedin_url'   => $user->perfil->linkedin_url ?? null,
                'github_url'     => $user->perfil->github_url ?? null,
                'web_url'        => $user->perfil->web_url ?? null,
                'bio'            => $user->perfil->bio ?? null,

                // estudiante
                'nivelacademico' => $user->estudiante->nivelacademico ?? null,

                // intereses/categorías
                'categorias'     => $user->estudiante
                    ? $user->estudiante->categorias->map(fn($cat) => [
                        'idcategoria' => $cat->idcategoria,
                        'nombre'      => $cat->nombre,
                        'descripcion' => $cat->descripcion,
                    ])
                    : [],
            ],
        ]);
    }

    /**
     * Guardar perfil extendido (público, durante registro)
     * 👉 Aquí solo guardamos foto, linkedin, github, web y bio.
     */
    public function guardarProfileRegistro(Request $request)
    {
        $data = $request->validate([
            'idusuario'    => ['required','exists:usuarios,idusuario'],
            'linkedin_url' => ['sometimes','nullable','url','max:255'],
            'github_url'   => ['sometimes','nullable','url','max:255'],
            'web_url'      => ['sometimes','nullable','url','max:255'],
            'bio'          => ['sometimes','nullable','string'],
        ]);

        $user = \App\Models\Usuario::findOrFail($data['idusuario']);

        // actualizar perfil extendido
        $perfil = PerfilUsuario::firstOrCreate(['idusuario' => $user->idusuario]);
        $perfil->fill(collect($data)->only(['linkedin_url','github_url','web_url','bio'])->toArray());
        $perfil->save();

        return $this->formatUserResponse($user->fresh(['estudiante.categorias','perfil','rolRel']));
    }

    /**
     * Guardar foto de perfil (público, durante registro)
     */
    public function guardarFotoRegistro(Request $request)
    {
        $request->validate([
            'idusuario' => ['required','exists:usuarios,idusuario'],
            'foto'      => ['nullable','image','max:2048'],
        ]);

        $user = \App\Models\Usuario::findOrFail($request->idusuario);

        if ($request->hasFile('foto')) {
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $path = $request->file('foto')->store('usuarios', 'public');
            $user->foto = $path;
            $user->save();
        }

        return $this->formatUserResponse($user->fresh(['estudiante.categorias','perfil','rolRel']));
    }
}
