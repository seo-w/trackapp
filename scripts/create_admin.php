<?php

declare(strict_types=1);

/**
 * Script para registrar al primer administrador local del sistema basándose en su correo.
 * Uso: php scripts/create_admin.php info@personalbliss.org MiPasswordSegura123
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Repositories\UserRepository;
use App\Services\AdminTokenService;

if ($argc < 3) {
    echo "Uso: php scripts/create_admin.php <email> <password>\n";
    exit(1);
}

$email = strtolower(trim($argv[1]));
$pass = trim($argv[2]);

try {
    $pdo = db()->pdo();
    $repo = new UserRepository($pdo);
    
    $existing = $repo->findByEmail($email);
    if ($existing) {
        $repo->update((int) $existing['id'], [
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_approved' => 1
        ]);
        echo "Usuario actualizado a Admin y Aprobado.\n";
    } else {
        $repo->create([
            'email' => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_approved' => 1
        ]);
        echo "Nuevo Administrador Creado con Éxito.\n";
    }

    // Generar token físico inicial
    $tokenService = new AdminTokenService();
    $token = $tokenService->generateForEmail($email);

    echo "\n------------------------------------------------------------\n";
    echo " DETALLES DEL ADMINISTRADOR\n";
    echo "------------------------------------------------------------\n";
    echo " Email: $email\n";
    echo " Token: $token\n";
    echo "------------------------------------------------------------\n";
    echo " ¡AVISO! El token se requiere para recuperaciones de emergencia.\n";

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
