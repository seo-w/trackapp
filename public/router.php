<?php

declare(strict_types=1);

/**
 * Router para el servidor incorporado de PHP (php -S).
 * Uso: php -S localhost:8080 -t public public/router.php
 */
if (PHP_SAPI !== 'cli-server') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Este script solo aplica en el servidor de desarrollo de PHP.';
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
