<?php

declare(strict_types=1);

namespace App\Support;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RbacMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Container\ContainerInterface;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];

    public function __construct(private ContainerInterface $container) {}

    public function get(string $path, array|callable $handler, array $middlewares = [], array $roles = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares, $roles);
    }

    public function post(string $path, array|callable $handler, array $middlewares = [], array $roles = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares, $roles);
    }

    public function addRoute(string $method, string $path, array|callable $handler, array $middlewares = [], array $roles = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'roles' => $roles,
        ];
    }

    public function dispatch(string $httpMethod, string $uri): void
    {
        // Limpiar query string y trailing slash
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $idx => $route) {
                $r->addRoute($route['method'], $route['path'], $idx);
            }
        });

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                http_response_code(404);
                $view = $this->container->get(View::class);
                try {
                    echo $view->render('errors/404', [], 'auth');
                } catch (\Throwable $e) {
                    echo "<h1>404 — Página no encontrada</h1><p><a href='/'>Volver al inicio</a></p>";
                }
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                http_response_code(405);
                echo "<h1>405 — Método HTTP no permitido</h1>";
                break;

            case Dispatcher::FOUND:
                $routeIndex = $routeInfo[1];
                $vars = $routeInfo[2];
                $route = $this->routes[$routeIndex];

                $this->executeRoutePipeline($route, $vars);
                break;
        }
    }

    private function executeRoutePipeline(array $route, array $vars): void
    {
        // 1. Pipeline de Middlewares globales y de ruta
        $middlewares = array_merge(
            [SecurityHeadersMiddleware::class, CsrfMiddleware::class],
            $route['middlewares']
        );

        $coreAction = function () use ($route, $vars) {
            $handler = $route['handler'];

            if (is_array($handler)) {
                [$controllerClass, $method] = $handler;
                $controller = $this->container->get($controllerClass);
                return $controller->$method($vars);
            }

            return $handler($vars);
        };

        // Crear pipeline en reversa
        $pipeline = $coreAction;

        // Si tiene restricción de roles (RBAC)
        if (!empty($route['roles'])) {
            $next = $pipeline;
            $pipeline = function () use ($next, $route) {
                $rbac = $this->container->get(RbacMiddleware::class);
                return $rbac->handle($next, $route['roles']);
            };
        }

        // Middleware de autenticación si está presente en la ruta
        if (in_array(AuthMiddleware::class, $route['middlewares'], true)) {
            $next = $pipeline;
            $pipeline = function () use ($next) {
                $auth = $this->container->get(AuthMiddleware::class);
                return $auth->handle($next);
            };
        }

        // Middleware de CSRF
        $next = $pipeline;
        $pipeline = function () use ($next) {
            $csrf = $this->container->get(CsrfMiddleware::class);
            return $csrf->handle($next);
        };

        // Middleware de SecurityHeaders
        $next = $pipeline;
        $pipeline = function () use ($next) {
            $sec = $this->container->get(SecurityHeadersMiddleware::class);
            return $sec->handle($next);
        };

        try {
            $pipeline();
        } catch (\Throwable $e) {
            http_response_code(500);

            try {
                $logger = $this->container->get(\Monolog\Logger::class);
                $logger->error("Unhandled Exception: " . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                ]);
            } catch (\Throwable) {
                error_log((string)$e);
            }

            try {
                $view = $this->container->get(View::class);
                echo $view->render('errors/500', [
                    'exception' => $e,
                    'message' => (($_ENV['APP_ENV'] ?? 'local') === 'local') 
                        ? $e->getMessage() 
                        : 'Ha ocurrido un error inesperado en el servidor. Por favor, intente más tarde.'
                ], 'app');
            } catch (\Throwable) {
                echo "<h1>Error 500</h1><p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
            }
        }
    }
}
