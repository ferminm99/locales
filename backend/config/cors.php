<?php

return [
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie', 'check-auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://elmensual.vercel.app','http://localhost:5173','https://locales-one.vercel.app'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];