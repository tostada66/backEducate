<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Body (flexible):
     *  - { "login": "correo o nombreusuario", "password": "..." }
     *  - { "email": "correo@dominio.com", "password": "..." }
     * Responde: { user, token, needs_role }
     */
    public function login(Request $request)
    {
        // Al menos una de las dos llaves: login o email
        $data = $request->validate([
            'login'    => 'nullable|string|required_without:email',
            'email'    => 'nullable|email|required_without:login',
            'password' => 'required|string|min:4',
        ]);

        // Normaliza el identificador
        $identifier = trim($data['login'] ?? $data['email'] ?? '');
        $identifierLower = strtolower($identifier);

        // Detecta si el identificador tiene forma de email
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        // Búsqueda flexible:
        // - Si viene "email" o el "login" tiene forma de email → busca por "correo" (case-insensitive)
        // - Si viene "login" (no email) → busca por "nombreusuario"
        if ($isEmail || isset($data['email'])) {
            $user = Usuario::whereRaw('LOWER(correo) = ?', [$identifierLower])->first();
        } else {
            $user = Usuario::where('nombreusuario', $identifier)->first();
        }

        // Credenciales inválidas → 401
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        // Usuario inactivo → 403 (si manejas "estado")
        if (isset($user->estado) && (int) $user->estado === 0) {
            return response()->json([
                'message' => 'Usuario inactivo. Contacta al administrador.',
            ], 403);
        }

        // (Opcional) invalidar tokens previos para sesión única:
        // $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user'       => $user,                  // respeta $hidden en el modelo
            'token'      => $token,
            'needs_role' => is_null($user->idrol),  // ajusta a tu lógica
        ], 200);
    }

    /**
     * GET /api/me  (auth:sanctum)
     * Devuelve el usuario autenticado.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * POST /api/logout  (auth:sanctum)
     * Cierra SOLO el token actual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada',
        ], 200);
    }
}
