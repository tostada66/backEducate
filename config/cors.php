<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths donde aplica CORS
    |--------------------------------------------------------------------------
    |
    | Para evitar problemas raros, aplicamos CORS a TODAS las rutas.
    | Más adelante, si quieres, se puede restringir a 'api/*'.
    |
    */

    'paths' => ['*'],  // <- antes tenías solo 'api/*' etc.

    /*
    |--------------------------------------------------------------------------
    | Métodos permitidos
    |--------------------------------------------------------------------------
    */

    'allowed_methods' => ['*'], // GET, POST, PUT, DELETE, OPTIONS, etc.

    /*
    |--------------------------------------------------------------------------
    | Orígenes permitidos
    |--------------------------------------------------------------------------
    |
    | Mientras estás en desarrollo / pruebas, lo dejamos en '*'.
    | Luego se puede cambiar a una lista:
    |   - 'http://localhost:9000'
    |   - 'https://backeducate.onrender.com'
    |   - etc.
    */

    'allowed_origins' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Patrones de orígenes permitidos
    |--------------------------------------------------------------------------
    */

    'allowed_origins_patterns' => [
        // Si luego quieres limitar por IP de tu LAN, podrías usar un regex aquí.
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers permitidos
    |--------------------------------------------------------------------------
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Headers expuestos al navegador
    |--------------------------------------------------------------------------
    */

    'exposed_headers' => ['Authorization'],

    /*
    |--------------------------------------------------------------------------
    | Tiempo en cache del preflight
    |--------------------------------------------------------------------------
    */

    'max_age' => 86400,

    /*
    |--------------------------------------------------------------------------
    | Cookies / credenciales
    |--------------------------------------------------------------------------
    |
    | Estás usando Bearer Token en Authorization, NO cookies,
    | así que esto debe ir en false.
    */

    'supports_credentials' => false,
];
