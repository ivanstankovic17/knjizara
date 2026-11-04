<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE'],

    // Klijent knjižare je na istom poreklu kao API, pa mu CORS ne treba.
    // Ovde se navode druga porekla kojima je pristup dozvoljen, odvojena
    // zarezom, na primer CORS_ALLOWED_ORIGINS=https://admin.knjizara.rs
    'allowed_origins' => array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'Authorization', 'X-Request-Id'],

    // Zaglavlja koja skript sa drugog porekla sme da pročita iz odgovora.
    'exposed_headers' => ['Location', 'Retry-After', 'X-Request-Id', 'X-RateLimit-Remaining'],

    'max_age' => 600,

    'supports_credentials' => false,

];
