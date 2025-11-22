<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\PerfilUsuario;
use App\Models\Profesor;
use App\Models\Usuario;
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
            'idusuario'     => ['required','exists:usuarios,idusuario'],
            'especialidad'  => ['sometimes','nullable','string','max:120'],
            'bio'           => ['sometimes','nullable','string'],
            'direccion'     => ['sometimes','nullable','string','max:150'],
            'pais'          => ['sometimes','nullable','string','max:100'],
            'empresa'       => ['sometimes','nullable','string','max:150'],
            'cargo'         => ['sometimes','nullable','string','max:120'],
            'fecha_inicio'  => ['sometimes','nullable','date'],
            'fecha_fin'     => ['sometimes','nullable','date'],
            'detalles'      => ['sometimes','nullable','string'],

            // ✅ Campos perfil_usuarios
            'linkedin_url'  => ['sometimes','nullable','url','max:255'],
            'github_url'    => ['sometimes','nullable','url','max:255'],
            'web_url'       => ['sometimes','nullable','url','max:255'],
        ]);

        $profesor = Profesor::firstOrCreate(['idusuario' => $data['idusuario']]);
        $profesor->fill(collect($data)->only([
            'especialidad','bio','direccion','pais','empresa','cargo','fecha_inicio','fecha_fin','detalles'
        ])->toArray());
        $profesor->estado_aprobacion = 'pendiente';
        $profesor->save();

        $perfil = PerfilUsuario::firstOrCreate(['idusuario' => $data['idusuario']]);
        $perfil->fill(collect($data)->only([
            'linkedin_url','github_url','web_url','bio'
        ])->toArray());
        $perfil->save();

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
     * 👑 ADMIN: listar solicitudes pendientes
     */
    public function solicitudesPendientes()
    {
        $profesores = Profesor::with('usuario')
            ->where('estado_aprobacion', 'pendiente')
            ->get();

        return response()->json($profesores);
    }

    /**
     * 👑 ADMIN: cambiar estado de aprobación (aprobado/rechazado)
     */
    public function cambiarEstado(Request $request, $idprofesor)
    {
        $request->validate([
            'estado' => 'required|in:aprobado,rechazado',
        ]);

        $profesor = Profesor::findOrFail($idprofesor);
        $profesor->estado_aprobacion = $request->estado;
        $profesor->save();

        return response()->json([
            'ok' => true,
            'message' => "Profesor {$request->estado} correctamente."
        ]);
    }

    /**
     * 👑 ADMIN: ver detalle de un profesor
     */
    public function detalle($idprofesor)
    {
        $profesor = Profesor::with(['usuario.profesor','usuario.perfil','usuario.rolRel'])
            ->findOrFail($idprofesor);

        $user = $profesor->usuario->fresh(['profesor','perfil','rolRel']);

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUserResponse($user)
        ]);
    }

    /**
     * 👑 ADMIN: listar todos los profesores registrados
     */
    public function listarProfesores()
    {
        $profesores = Profesor::with('usuario')
            ->get()
            ->map(function ($p) {
                return [
                    'idprofesor'   => $p->idprofesor,
                    'idusuario'    => $p->usuario->idusuario ?? null,
                    'nombres'      => $p->usuario->nombres ?? '—',
                    'apellidos'    => $p->usuario->apellidos ?? '',
                    'correo'       => $p->usuario->correo ?? '',
                    'estado'       => $p->usuario->estado ?? 0,
                    'total_cursos' => Curso::where('idprofesor', $p->idprofesor)->count(),
                    'created_at'   => $p->usuario->created_at ?? null,
                ];
            });

        return response()->json($profesores);
    }

    /**
     * Dar formato unificado al perfil de profesor (respuesta pública/admin).
     */
    private function formatUserResponse($user)
    {
        return [
            'idusuario'         => $user->idusuario,
            'nombres'           => $user->nombres,
            'apellidos'         => $user->apellidos,
            'correo'            => $user->correo,
            'nombreusuario'     => $user->nombreusuario,
            'telefono'          => $user->telefono,
            'estado'            => $user->estado,
            'foto_url'          => $user->foto_url,

            // Perfil extendido
            'linkedin_url'      => $user->perfil->linkedin_url ?? null,
            'github_url'        => $user->perfil->github_url ?? null,
            'web_url'           => $user->perfil->web_url ?? null,

            // Profesor
            'especialidad'      => $user->profesor->especialidad ?? null,
            'bio'               => $user->profesor->bio
                                    ?? $user->perfil->bio
                                    ?? null,
            'direccion'         => $user->profesor->direccion ?? null,
            'pais'              => $user->profesor->pais ?? null,
            'empresa'           => $user->profesor->empresa ?? null,
            'cargo'             => $user->profesor->cargo ?? null,
            'fecha_inicio'      => $user->profesor->fecha_inicio ?? null,
            'fecha_fin'         => $user->profesor->fecha_fin ?? null,
            'detalles'          => $user->profesor->detalles ?? null,

            // Estado aprobación
            'estado_aprobacion' => $user->profesor->estado_aprobacion ?? 'pendiente',
        ];
    }
}
