<?php

return [
    'host' => $_ENV['MAIL_HOST'] ?? '127.0.0.1',
    'port' => (int)($_ENV['MAIL_PORT'] ?? 1025),
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? null,
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-responder@reditinere.com',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Mesa Digital — Red Itínere',
];
