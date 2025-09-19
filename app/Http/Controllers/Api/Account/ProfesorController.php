<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Profesor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfesorController extends Controller
{
    /**
     * Registro de profesor (público, paso después de usuario base).
     */
    public function guardarRegistro(Request $request)
    {
        $data = $request->validate([
            'idusuario'    => ['required','exists:usuarios,idusuario'],
            'especialidad' => ['sometimes','nullable','string','max:120'],
            'bio'          => ['sometimes','nullable','string'],
        ]);

        $profesor = Profesor::firstOrCreate(['idusuario' => $data['idusuario']]);
        $profesor->fill(collect($data)->only(['especialidad','bio'])->toArray());
        $profesor->save();

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUserResponse(
                Usuario::with(['profesor','perfil','rolRel'])
                    ->findOrFail($data['idusuario'])
            )
        ]);
    }

    /**
     * Guardar foto de profesor durante el registro (público).
     */
    public function guardarFotoRegistro(Request $request)
    {
        $request->validate([
            'idusuario' => ['required','exists:usuarios,idusuario'],
            'foto'      => ['nullable','image','max:2048'],
        ]);

        $user = Usuario::findOrFail($request->idusuario);

        if ($request->hasFile('foto')) {
            // eliminar foto anterior si existe
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }

            $path = $request->file('foto')->store('usuarios', 'public');
            $user->foto = $path;
            $user->save();
        }

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUserResponse(
                $user->fresh(['profesor','perfil','rolRel'])
            )
        ]);
    }

    /**
     * Ver perfil de profesor durante el registro (público).
     */
    public function showRegistro($idusuario)
    {
        $user = Usuario::with(['profesor','perfil','rolRel'])
            ->findOrFail($idusuario);

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUserResponse($user)
        ]);
    }

    /**
     * Dar formato unificado al perfil de profesor (respuesta pública).
     */
    private function formatUserResponse($user)
    {
        return [
            'idusuario'     => $user->idusuario,
            'nombres'       => $user->nombres,
            'apellidos'     => $user->apellidos,
            'correo'        => $user->correo,
            'nombreusuario' => $user->nombreusuario,
            'telefono'      => $user->telefono,
            'estado'        => $user->estado,
            'foto_url'      => $user->foto_url,

            // Perfil extendido
            'linkedin_url'  => $user->perfil->linkedin_url ?? null,
            'github_url'    => $user->perfil->github_url ?? null,
            'web_url'       => $user->perfil->web_url ?? null,

            // Profesor
            'especialidad'  => $user->profesor->especialidad ?? null,
            'bio'           => $user->profesor->bio
                ?? $user->perfil->bio
                ?? null,
        ];
    }
}
