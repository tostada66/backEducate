<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    /**
     * POST /api/password/reset/by-email
     *
     * Body:
     *  - email               (required, email)
     *  - current_password    (required, string, min:4)
     *  - password            (required, confirmed, min:8)
     *  - password_confirmation (required)
     *
     * Respuestas:
     *  - 200 OK
     *  - 404 Not Found (correo no existe)
     *  - 401 Unauthorized (current_password no coincide)
     *  - 422 Unprocessable Entity (validación)
     */
    public function resetByEmail(Request $request)
    {
        $data = $request->validate([
            'email'            => ['required', 'email'],
            'current_password' => ['required', 'string', 'min:4'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        // tu columna real de email es "correo"
        $email = strtolower(trim($data['email']));

        $user = Usuario::whereRaw('LOWER(correo) = ?', [$email])->first();

        if (! $user) {
            return response()->json([
                'message' => 'No encontramos un usuario con ese correo.',
            ], 404);
        }

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no coincide.',
            ], 401);
        }

        // Evitar que la nueva sea igual a la actual
        if (Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'La nueva contraseña no puede ser igual a la actual.',
                'errors'  => [
                    'password' => ['La nueva contraseña no puede ser igual a la actual.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        // (Opcional) invalidar otros tokens activos para forzar re-login
        // $user->tokens()->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ], 200);
    }
}
