<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Suscripcion;

class AuthController extends Controller
{
    /**
     * POST /api/login
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => 'nullable|string|required_without:email',
            'email'    => 'nullable|email|required_without:login',
            'password' => 'required|string|min:4',
        ]);

        $identifier = trim($data['login'] ?? $data['email'] ?? '');
        $identifierLower = strtolower($identifier);

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        if ($isEmail || isset($data['email'])) {
            $user = Usuario::whereRaw('LOWER(correo) = ?', [$identifierLower])->first();
        } else {
            $user = Usuario::where('nombreusuario', $identifier)->first();
        }

        // ❌ Usuario no encontrado o contraseña incorrecta
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        // ❌ Usuario inactivo
        if (isset($user->estado) && (int) $user->estado === 0) {
            return response()->json([
                'message' => 'Usuario inactivo. Contacta al administrador.',
            ], 403);
        }

        // ✅ Validar profesor aprobado
        if ($user->idrol === 2 && $user->profesor) {
            if ($user->profesor->estado_aprobacion !== 'aprobado') {
                return response()->json([
                    'message' => 'Su cuenta de profesor aún no fue aprobada.',
                ], 403);
            }
        }

        // 🔑 Generar token
        $token = $user->createToken('api')->plainTextToken;

        // 🔍 Buscar suscripción activa (solo si es estudiante)
        $suscripcionActiva = null;
        if ($user->idrol === 1 && $user->estudiante) {
            $suscripcionActiva = Suscripcion::where('idestudiante', $user->estudiante->idestudiante)
                ->where('estado', true)
                ->where(function ($q) {
                    $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
                })
                ->orderByDesc('fecha_fin')
                ->first();
        }

        return response()->json([
            'user' => [
                'idusuario'          => $user->idusuario,
                'idrol'              => $user->idrol,
                'nombres'            => $user->nombres,
                'correo'             => $user->correo,
                'nombreusuario'      => $user->nombreusuario,

                // Estado de aprobación profesor (si aplica)
                'estado_aprobacion'  => $user->profesor->estado_aprobacion ?? null,

                // 👉 Info de suscripción (solo estudiantes)
                'suscripcion_activa' => $suscripcionActiva ? true : false,
                'plan_id'            => $suscripcionActiva?->idplan,
                'fecha_fin'          => $suscripcionActiva?->fecha_fin,
            ],
            'token'      => $token,
            'needs_role' => is_null($user->idrol),
        ], 200);
    }

    /**
     * GET /api/me  (auth:sanctum)
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $suscripcionActiva = null;

        // Solo aplica para estudiantes
        if ($user->idrol === 1 && $user->estudiante) {
            $suscripcionActiva = Suscripcion::where('idestudiante', $user->estudiante->idestudiante)
                ->where('estado', true)
                ->where(function ($q) {
                    $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
                })
                ->orderByDesc('fecha_fin')
                ->first();
        }

        return response()->json([
            'idusuario'          => $user->idusuario,
            'idrol'              => $user->idrol,
            'nombres'            => $user->nombres,
            'correo'             => $user->correo,
            'nombreusuario'      => $user->nombreusuario,

            // Extra para el frontend
            'estado_aprobacion'  => $user->profesor->estado_aprobacion ?? null,
            'suscripcion_activa' => $suscripcionActiva ? true : false,
            'plan_id'            => $suscripcionActiva?->idplan,
            'fecha_fin'          => $suscripcionActiva?->fecha_fin,
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada',
        ], 200);
    }
}
