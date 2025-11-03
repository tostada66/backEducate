<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\PerfilUsuario;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * 🔹 Obtener el perfil del usuario autenticado
     */
    public function show(Request $request)
    {
        $user = $request->user()
            ->load(['estudiante.categorias', 'perfil', 'rolRel']);

        return $this->formatUserResponse($user);
    }

    /**
     * 🔹 Obtener el perfil de un usuario por ID (modo público, durante registro)
     */
    public function showRegistro($idusuario)
    {
        $user = Usuario::with(['estudiante.categorias', 'perfil', 'rolRel'])
            ->findOrFail($idusuario);

        return $this->formatUserResponse($user);
    }

    /**
     * 🔹 Obtener el perfil de un usuario (modo admin)
     */
    public function showAdmin($idusuario)
    {
        $user = Usuario::with(['estudiante.categorias', 'perfil', 'rolRel'])
            ->findOrFail($idusuario);

        // ✅ Marcar categorías seleccionadas (para que se muestren en frontend)
        if ($user->estudiante && $user->estudiante->categorias) {
            $user->estudiante->categorias->transform(function ($cat) {
                $cat->seleccionado = true;

                return $cat;
            });
        }

        return $this->formatUserResponse($user);
    }

    /**
     * 🔹 Formato unificado de respuesta del perfil
     */
    private function formatUserResponse($user)
    {
        // ⚙️ Aseguramos que las categorías estén marcadas como seleccionadas
        if ($user->estudiante && $user->estudiante->categorias) {
            $user->estudiante->categorias->transform(function ($cat) {
                $cat->seleccionado = $cat->seleccionado ?? true;

                return $cat;
            });
        }

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
                    ? $user->estudiante->categorias->map(fn ($cat) => [
                        'idcategoria' => $cat->idcategoria,
                        'nombre'      => $cat->nombre,
                        'descripcion' => $cat->descripcion,
                        'seleccionado' => true, // 👈 clave para mostrar en frontend
                    ])
                    : [],
            ],
        ]);
    }

    /**
     * 🔹 Guardar perfil extendido (modo registro)
     */
    public function guardarProfileRegistro(Request $request)
    {
        $data = $request->validate([
            'idusuario'    => ['required', 'exists:usuarios,idusuario'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'github_url'   => ['sometimes', 'nullable', 'url', 'max:255'],
            'web_url'      => ['sometimes', 'nullable', 'url', 'max:255'],
            'bio'          => ['sometimes', 'nullable', 'string'],
        ]);

        $user = Usuario::findOrFail($data['idusuario']);

        // Actualizar perfil extendido
        $perfil = PerfilUsuario::firstOrCreate(['idusuario' => $user->idusuario]);
        $perfil->fill(collect($data)->only(['linkedin_url', 'github_url', 'web_url', 'bio'])->toArray());
        $perfil->save();

        return $this->formatUserResponse($user->fresh(['estudiante.categorias', 'perfil', 'rolRel']));
    }

    /**
     * 🔹 Guardar foto de perfil (modo registro)
     */
    public function guardarFotoRegistro(Request $request)
    {
        $request->validate([
            'idusuario' => ['required', 'exists:usuarios,idusuario'],
            'foto'      => ['nullable', 'image', 'max:2048'],
        ]);

        $user = Usuario::findOrFail($request->idusuario);

        if ($request->hasFile('foto')) {
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $path = $request->file('foto')->store('usuarios', 'public');
            $user->foto = $path;
            $user->save();
        }

        return $this->formatUserResponse($user->fresh(['estudiante.categorias', 'perfil', 'rolRel']));
    }
}
