<?php

return [

    // Rutas donde aplica CORS (API + cookie CSRF si algún día usas Sanctum SPA)
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 🔒 Agrega tus orígenes reales de DEV (incluye 9001) y los de PROD
    'allowed_origins' => [
        'capacitor://localhost',     // App Android/iOS (Capacitor WebView)
        'http://localhost',          // Dev
        'http://127.0.0.1',          // Dev

        // Quasar dev (puertos típicos)
        'http://localhost:9000',
        'http://127.0.0.1:9000',
        'http://localhost:9001',     // 👈 tu caso actual
        'http://127.0.0.1:9001',     // 👈 tu caso actual

        // Si usas Vite puro alguna vez:
        // 'http://localhost:5173',
        // 'http://127.0.0.1:5173',

        // Producción (ejemplos; cámbialos por los tuyos)
        // 'https://app.tu-dominio.com',
        // 'https://api.tu-dominio.com',
    ],

    // Opcional: permitir tu LAN en dev (192.168.x.x:puerto)
    'allowed_origins_patterns' => [
        '#^http://192\.168\.\d{1,3}\.\d{1,3}(:\d+)?$#',
    ],

    'allowed_headers'   => ['*'],
    'exposed_headers'   => ['Authorization'],
    'max_age'           => 86400,

    // ❗ Usas Bearer tokens (no cookies) → debe ser false
    'supports_credentials' => false,
];
