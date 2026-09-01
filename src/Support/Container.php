<?php

declare(strict_types=1);

namespace App\Support;

use App\Auth\AuthProviderInterface;
use App\Auth\GoogleAuthProvider;
use App\Auth\SimulatedAuthProvider;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RbacMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Repositories\CaracterRemitenteRepository;
use App\Repositories\CategoriaRepository;
use App\Repositories\DocumentoAdjuntoRepository;
use App\Repositories\DocumentoComentarioRepository;
use App\Repositories\DocumentoHistorialRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\NotificacionRepository;
use App\Repositories\SedeRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\DocumentoService;
use App\Services\ExportService;
use App\Services\LocalStorageAdapter;
use App\Services\MailerInterface;
use App\Services\NotificacionService;
use App\Services\PhpMailerAdapter;
use App\Services\RecordatorioService;
use App\Services\StorageInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Container
{
    private static ?ContainerInterface $instance = null;

    public static function build(): ContainerInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);

        $builder->addDefinitions([
            // Configuración
            'config.app' => fn() => require __DIR__ . '/../../config/app.php',
            'config.db' => fn() => require __DIR__ . '/../../config/database.php',
            'config.mail' => fn() => require __DIR__ . '/../../config/mail.php',
            'config.auth' => fn() => require __DIR__ . '/../../config/auth.php',

            // PDO Singleton
            PDO::class => function (ContainerInterface $c) {
                $config = $c->get('config.db');
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );
                if (!empty($config['socket'])) {
                    $dsn .= ";unix_socket={$config['socket']}";
                }
                return new PDO($dsn, $config['username'], $config['password'], $config['options']);
            },

            // Logger Singleton
            LoggerInterface::class => function () {
                $logger = new Logger('mesa_digital');
                $logPath = __DIR__ . '/../../storage/logs/app.log';
                if (!is_dir(dirname($logPath))) {
                    mkdir(dirname($logPath), 0755, true);
                }
                $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
                return $logger;
            },

            // View Renderer
            View::class => fn() => new View(__DIR__ . '/../../resources/views'),

            // Mailer
            MailerInterface::class => function (ContainerInterface $c) {
                return new PhpMailerAdapter($c->get('config.mail'), $c->get(LoggerInterface::class));
            },

            // Storage
            StorageInterface::class => function (ContainerInterface $c) {
                $basePath = __DIR__ . '/../../' . ($_ENV['STORAGE_LOCAL_PATH'] ?? 'storage/documentos');
                $maxMb = (int)($_ENV['UPLOAD_MAX_SIZE_MB'] ?? 10);
                return new LocalStorageAdapter($basePath, $maxMb);
            },

            // Auth Provider (Dual)
            AuthProviderInterface::class => function (ContainerInterface $c) {
                $authConfig = $c->get('config.auth');
                $appConfig = $c->get('config.app');
                $driver = $authConfig['driver'] ?? 'simulado';

                if ($driver === 'google') {
                    return new GoogleAuthProvider(
                        $c->get(UsuarioRepository::class),
                        $authConfig['google'] ?? []
                    );
                }

                // Default: simulado
                return new SimulatedAuthProvider(
                    $c->get(UsuarioRepository::class),
                    $appConfig['env'] ?? 'local',
                    $appConfig['login_simulado_habilitado'] ?? true
                );
            },

            // NotificacionService
            NotificacionService::class => function (ContainerInterface $c) {
                $appConfig = $c->get('config.app');
                return new NotificacionService(
                    $c->get(NotificacionRepository::class),
                    $c->get(CategoriaRepository::class),
                    $c->get(DocumentoRepository::class),
                    $c->get(UsuarioRepository::class),
                    $c->get(MailerInterface::class),
                    $c->get(LoggerInterface::class),
                    $appConfig['url'] ?? 'http://localhost:8080'
                );
            },

            // Middlewares
            SecurityHeadersMiddleware::class => \DI\autowire(),
            CsrfMiddleware::class => \DI\autowire(),
            AuthMiddleware::class => \DI\autowire(),
            RbacMiddleware::class => \DI\autowire(),
            RateLimitMiddleware::class => \DI\autowire(),
        ]);

        self::$instance = $builder->build();
        return self::$instance;
    }
}
