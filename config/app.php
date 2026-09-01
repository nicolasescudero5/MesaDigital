<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Mesa Digital',
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
    'key' => $_ENV['APP_KEY'] ?? 'secret_app_key_32_bytes_default',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Buenos_Aires',
    'locale' => $_ENV['APP_LOCALE'] ?? 'es_AR',
    'session_secure' => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'login_simulado_habilitado' => filter_var($_ENV['LOGIN_SIMULADO_HABILITADO'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'auto_cierre_dias' => (int)($_ENV['AUTO_CIERRE_DIAS'] ?? 0),
    'recordatorio_horas_sin_tomar' => (int)($_ENV['RECORDATORIO_HORAS_SIN_TOMAR'] ?? 48),
    'alerta_vencimiento_dias' => array_map('intval', explode(',', $_ENV['ALERTA_VENCIMIENTO_DIAS'] ?? '3,1')),
    'upload_max_size_mb' => (int)($_ENV['UPLOAD_MAX_SIZE_MB'] ?? 10),
];
