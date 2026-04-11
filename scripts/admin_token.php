<?php

declare(strict_types=1);

/**
 * Script CLI para gestionar tokens de recuperación física.
 * Uso: php scripts/admin_token.php admin@ejemplo.com
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Repositories\UserRepository;
use App\Services\AdminTokenService;

if ($argc < 2) {
    echo "Uso: php scripts/admin_token.php <email>\n";
    exit(1);
}

$email = strtolower(trim($argv[1]));

try {
    $pdo = db()->pdo();
    $repo = new UserRepository($pdo);
    $user = $repo->findByEmail($email);

    if (! $user) {
        echo "Error: El usuario con email '$email' no existe en la base de datos locals.\n";
        exit(1);
    }

    if ($user['role'] !== 'admin' && $user['role'] !== 'superadmin') {
        echo "Error: Solo se pueden generar tokens para roles administrativos.\n";
        exit(1);
    }

    $tokenService = new AdminTokenService();
    $token = $tokenService->generateForEmail($email);

    echo "\n------------------------------------------------------------\n";
    echo " REGENERACIÓN DE TOKEN EXITOSA\n";
    echo "------------------------------------------------------------\n";
    echo " Usuario: " . $user['email'] . " (" . $user['role'] . ")\n";
    echo " Token:   $token\n";
    echo " Ruta:    " . (BASE_PATH . '/storage/secure_tokens/' . sha1($email) . '.token') . "\n";
    echo "------------------------------------------------------------\n";
    echo " ¡AVISO! El token se ha escrito en el servidor con permisos 0600.\n";

} catch (Throwable $e) {
    echo "Error crítico: " . $e->getMessage() . "\n";
    exit(1);
}
