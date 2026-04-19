<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\ConsultasController;
use App\Controllers\HistorialController;
use App\Controllers\HomeController;
use App\Controllers\SettingsController;
use App\Controllers\StatsController;
use App\Controllers\UserController;
use App\Support\Router;

$router = new Router();
$router->get('/login', AuthController::class, 'showLogin');
$router->post('/login', AuthController::class, 'login');
$router->post('/logout', AuthController::class, 'logout');
$router->get('/waiting-approval', AuthController::class, 'showWaitingApproval');
$router->get('/usuarios', UserController::class, 'index');
$router->post('/usuarios/approve', UserController::class, 'approve');
$router->post('/usuarios/unapprove', UserController::class, 'unapprove');
$router->post('/usuarios/promote', UserController::class, 'promote');
$router->post('/usuarios/demote', UserController::class, 'demote');
$router->post('/usuarios/delete', UserController::class, 'delete');
$router->get('/recovery', AuthController::class, 'showRecovery');
$router->post('/recovery', AuthController::class, 'verifyRecovery');
$router->post('/reset-password', AuthController::class, 'resetPassword');
$router->get('/', HomeController::class, 'index');
$router->get('/consultas', ConsultasController::class, 'index');
$router->post('/consultas', ConsultasController::class, 'consultar');
$router->get('/consultas/novedades', ConsultasController::class, 'novedades');
$router->get('/configuracion', SettingsController::class, 'index');
$router->post('/configuracion', SettingsController::class, 'save');
$router->post('/configuracion/sync', SettingsController::class, 'syncMerkaweb');
$router->post('/configuracion/probar-conexion', SettingsController::class, 'testConnection');
$router->get('/historial', HistorialController::class, 'index');
$router->get('/estadisticas', StatsController::class, 'index');
$router->post('/estadisticas/pauta', StatsController::class, 'savePauta');

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
