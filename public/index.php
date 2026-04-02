<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Controllers\ConsultasController;
use App\Controllers\HistorialController;
use App\Controllers\HomeController;
use App\Controllers\SettingsController;
use App\Controllers\StatsController;
use App\Support\Router;

$router = new Router();
$router->get('/', HomeController::class, 'index');
$router->get('/consultas', ConsultasController::class, 'index');
$router->post('/consultas', ConsultasController::class, 'consultar');
$router->get('/configuracion', SettingsController::class, 'index');
$router->post('/configuracion', SettingsController::class, 'save');
$router->post('/configuracion/probar-conexion', SettingsController::class, 'testConnection');
$router->get('/historial', HistorialController::class, 'index');
$router->get('/estadisticas', StatsController::class, 'index');
$router->post('/estadisticas/pauta', StatsController::class, 'savePauta');

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
