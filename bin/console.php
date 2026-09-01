<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$command = $argv[1] ?? 'help';

if ($command === 'help' || $command === '-h' || $command === '--help') {
    echo "=================================================\n";
    echo " Consola de Comandos — Mesa Digital (Red Itínere)\n";
    echo "=================================================\n\n";
    echo "Uso: php bin/console.php <comando>\n\n";
    echo "Comandos disponibles:\n";
    echo "  notificaciones:procesar-cola   Procesa notificaciones pendientes en cola\n";
    echo "  documentos:recordatorio-48h    Encola recordatorios para docs >48h en Recibido\n";
    echo "  documentos:alerta-vencimiento  Encola alertas para docs a vencer a 3 y 1 día\n";
    echo "  documentos:auto-cierre         Ejecuta auto-cierre de resueltos si está activo\n";
    echo "  reportes:resumen-semanal       Envía resumen semanal a Admin y Supervisión\n";
    echo "  schedule:run                   Ejecuta tareas programadas del cron\n";
    exit(0);
}

// 1. Variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'America/Argentina/Buenos_Aires');

// 2. Contenedor DI
$container = \App\Support\Container::build();

$notificacionService = $container->get(\App\Services\NotificacionService::class);
$recordatorioService = $container->get(\App\Services\RecordatorioService::class);
$documentoRepository = $container->get(\App\Repositories\DocumentoRepository::class);
$usuarioRepository = $container->get(\App\Repositories\UsuarioRepository::class);
$mailer = $container->get(\App\Services\MailerInterface::class);

switch ($command) {
    case 'notificaciones:procesar-cola':
        echo "[" . date('Y-m-d H:i:s') . "] Procesando cola de notificaciones...\n";
        $res = $notificacionService->procesarCola(50);
        echo "Resultado: {$res['enviadas']} enviadas, {$res['fallidas']} fallidas de {$res['procesadas']} procesadas.\n";
        break;

    case 'documentos:recordatorio-48h':
        echo "[" . date('Y-m-d H:i:s') . "] Verificando documentos sin tomar hace más de 48h...\n";
        $horas = (int)($appConfig['recordatorio_horas_sin_tomar'] ?? 48);
        $count = $recordatorioService->procesarRecordatorios48h($horas);
        echo "Se encolaron {$count} recordatorios de 48h.\n";
        break;

    case 'documentos:alerta-vencimiento':
        echo "[" . date('Y-m-d H:i:s') . "] Verificando alertas de vencimiento legal...\n";
        $dias = $appConfig['alerta_vencimiento_dias'] ?? [3, 1];
        $res = $recordatorioService->procesarAlertasVencimiento($dias);
        foreach ($res as $d => $c) {
            echo "Alertas a {$d} día(s): {$c} encoladas.\n";
        }
        break;

    case 'documentos:auto-cierre':
        $dias = (int)($appConfig['auto_cierre_dias'] ?? 0);
        echo "[" . date('Y-m-d H:i:s') . "] Verificando auto-cierre de documentos resueltos (Config: {$dias} días)...\n";
        $count = $recordatorioService->procesarAutoCierre($dias);
        echo "Se cerraron automáticamente {$count} documentos.\n";
        break;

    case 'reportes:resumen-semanal':
        echo "[" . date('Y-m-d H:i:s') . "] Generando resumen semanal de KPIs...\n";
        $admins = array_filter($usuarioRepository->findAll(true), fn($u) => $u->isAdministrador() || $u->isSupervision());
        if (!empty($admins)) {
            $adminUser = reset($admins);
            $kpis = $documentoRepository->getKpis($adminUser);
            $subject = "Resumen semanal de Mesa Digital — " . date('d/m/Y');
            $body = "<h2>Resumen semanal de Mesa Digital</h2>"
                  . "<p>Total activos: <strong>{$kpis['total']}</strong> | Recibidos: <strong>{$kpis['recibidos']}</strong> | En curso: <strong>{$kpis['en_curso']}</strong> | Vencidos: <strong>{$kpis['vencidos']}</strong></p>";
            foreach ($admins as $admin) {
                $mailer->send($admin->email, $subject, $body);
            }
            echo "Resumen semanal enviado a " . count($admins) . " administradores/supervisores.\n";
        }
        break;

    case 'schedule:run':
        echo "[" . date('Y-m-d H:i:s') . "] Ejecutando tareas programadas...\n";
        // 1. Siempre procesar cola de notificaciones
        $notificacionService->procesarCola(50);

        // 2. Si es las 08:00 hora local o --force-daily, ejecutar recordatorios diarios
        $currentHourMin = date('H:i');
        if ($currentHourMin === '08:00' || in_array('--force-daily', $argv, true)) {
            $recordatorioService->procesarRecordatorios48h((int)($appConfig['recordatorio_horas_sin_tomar'] ?? 48));
            $recordatorioService->procesarAlertasVencimiento($appConfig['alerta_vencimiento_dias'] ?? [3, 1]);
            $recordatorioService->procesarAutoCierre((int)($appConfig['auto_cierre_dias'] ?? 0));
        }
        echo "Scheduler finalizado con éxito.\n";
        break;

    default:
        echo "Comando no reconocido: '{$command}'. Ejecutá 'php bin/console.php help' para ver la lista.\n";
        exit(1);
}
