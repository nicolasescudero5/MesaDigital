<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CaracterRemitenteController;
use App\Controllers\CategoriaController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentoController;
use App\Controllers\HealthController;
use App\Controllers\ReporteController;
use App\Controllers\SedeController;
use App\Controllers\TipoDocumentoController;
use App\Controllers\UsuarioController;
use App\Middleware\AuthMiddleware;
use App\Support\Router;

/** @var Router $router */

// Rutas Públicas / Salud
$router->get('/health', [HealthController::class, 'check']);

// Autenticación
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/login/simulado', [AuthController::class, 'simulatedLogin']);
$router->get('/auth/google/callback', [AuthController::class, 'googleCallback']);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// Inicio / Redirección
$router->get('/', function() {
    header("Location: /dashboard");
    exit;
}, [AuthMiddleware::class]);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Documentos
$router->get('/documentos', [DocumentoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/documentos/nuevo', [DocumentoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/documentos', [DocumentoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/documentos/{id:\d+}', [DocumentoController::class, 'show'], [AuthMiddleware::class]);
$router->post('/documentos/{id:\d+}/tomar', [DocumentoController::class, 'tomar'], [AuthMiddleware::class]);
$router->post('/documentos/{id:\d+}/reclasificar', [DocumentoController::class, 'reclasificar'], [AuthMiddleware::class]);
$router->post('/documentos/{id:\d+}/plazo-legal', [DocumentoController::class, 'modificarPlazo'], [AuthMiddleware::class]);
$router->post('/documentos/{id:\d+}/resolver', [DocumentoController::class, 'resolver'], [AuthMiddleware::class]);
$router->post('/documentos/{id:\d+}/cerrar', [DocumentoController::class, 'cerrar'], [AuthMiddleware::class]);
$router->post('/documentos/{id:\d+}/anular', [DocumentoController::class, 'anular'], [AuthMiddleware::class], ['administrador']);
$router->post('/documentos/{id:\d+}/comentarios', [DocumentoController::class, 'comentar'], [AuthMiddleware::class]);
$router->post('/documentos/{id:\d+}/adjuntos', [DocumentoController::class, 'adjuntar'], [AuthMiddleware::class]);
$router->get('/documentos/{id:\d+}/adjuntos/{adjunto_id:\d+}/descargar', [DocumentoController::class, 'serveAdjunto'], [AuthMiddleware::class]);

// Reportes y Exportación
$router->get('/reportes/documentos/csv', [ReporteController::class, 'exportCsv'], [AuthMiddleware::class]);
$router->get('/reportes/documentos/excel', [ReporteController::class, 'exportExcel'], [AuthMiddleware::class]);
$router->get('/reportes/documentos/pdf', [ReporteController::class, 'exportPdf'], [AuthMiddleware::class]);

// Administración — Sedes (Solo Administrador)
$router->get('/sedes', [SedeController::class, 'index'], [AuthMiddleware::class], ['administrador']);
$router->post('/sedes', [SedeController::class, 'store'], [AuthMiddleware::class], ['administrador']);
$router->post('/sedes/{id:\d+}/actualizar', [SedeController::class, 'update'], [AuthMiddleware::class], ['administrador']);
$router->post('/sedes/{id:\d+}/desactivar', [SedeController::class, 'deactivate'], [AuthMiddleware::class], ['administrador']);

// Administración — Categorías y Responsables (Solo Administrador)
$router->get('/categorias', [CategoriaController::class, 'index'], [AuthMiddleware::class], ['administrador']);
$router->post('/categorias', [CategoriaController::class, 'store'], [AuthMiddleware::class], ['administrador']);
$router->post('/categorias/{id:\d+}/actualizar', [CategoriaController::class, 'update'], [AuthMiddleware::class], ['administrador']);
$router->post('/categorias/{id:\d+}/desactivar', [CategoriaController::class, 'deactivate'], [AuthMiddleware::class], ['administrador']);
$router->post('/categorias/{id:\d+}/responsables', [CategoriaController::class, 'addResponsable'], [AuthMiddleware::class], ['administrador']);
$router->post('/categorias/{id:\d+}/responsables/eliminar', [CategoriaController::class, 'removeResponsable'], [AuthMiddleware::class], ['administrador']);

// Administración — Tipos de Documento (Solo Administrador)
$router->get('/tipos-documento', [TipoDocumentoController::class, 'index'], [AuthMiddleware::class], ['administrador']);
$router->post('/tipos-documento', [TipoDocumentoController::class, 'store'], [AuthMiddleware::class], ['administrador']);
$router->post('/tipos-documento/{id:\d+}/actualizar', [TipoDocumentoController::class, 'update'], [AuthMiddleware::class], ['administrador']);
$router->post('/tipos-documento/{id:\d+}/desactivar', [TipoDocumentoController::class, 'deactivate'], [AuthMiddleware::class], ['administrador']);

// Administración — Caracteres de Remitente (Solo Administrador)
$router->get('/caracteres-remitente', [CaracterRemitenteController::class, 'index'], [AuthMiddleware::class], ['administrador']);
$router->post('/caracteres-remitente', [CaracterRemitenteController::class, 'store'], [AuthMiddleware::class], ['administrador']);
$router->post('/caracteres-remitente/{id:\d+}/actualizar', [CaracterRemitenteController::class, 'update'], [AuthMiddleware::class], ['administrador']);
$router->post('/caracteres-remitente/{id:\d+}/desactivar', [CaracterRemitenteController::class, 'deactivate'], [AuthMiddleware::class], ['administrador']);

// Administración — Usuarios (Solo Administrador)
$router->get('/usuarios', [UsuarioController::class, 'index'], [AuthMiddleware::class], ['administrador']);
$router->post('/usuarios', [UsuarioController::class, 'store'], [AuthMiddleware::class], ['administrador']);
$router->post('/usuarios/{id:\d+}/actualizar', [UsuarioController::class, 'update'], [AuthMiddleware::class], ['administrador']);
$router->post('/usuarios/{id:\d+}/estado', [UsuarioController::class, 'toggleStatus'], [AuthMiddleware::class], ['administrador']);
