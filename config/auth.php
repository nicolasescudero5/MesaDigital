<?php

return [
    'driver' => $_ENV['AUTH_DRIVER'] ?? 'simulado',
    'google' => [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost:8080/auth/google/callback',
        'hosted_domain' => $_ENV['GOOGLE_HOSTED_DOMAIN'] ?? '',
    ],
];
