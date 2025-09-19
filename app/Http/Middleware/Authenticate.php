<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // 🚀 Si es petición API, no redirigir, solo devolver 401
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // 🚀 Si fuese una petición web (Blade), redirige a login
        return route('login');
    }
}
