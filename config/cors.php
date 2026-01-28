<?php

return [

    'paths' => [
        'api/*',
        'files/*',
        'execute',
        'execute/stream',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // 'http://localhost:5173', // Vite
        // 'http://127.0.0.1:5173',
        '*',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Content-Type',
        'Cache-Control',
    ],

    'max_age' => 0,

    'supports_credentials' => true,
];

