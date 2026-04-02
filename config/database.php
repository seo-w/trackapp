<?php

declare(strict_types=1);

/**
 * Configuración de base de datos (PDO MySQL).
 *
 * Inicializar tablas (sin ORM):
 *   DB_ENABLED pode omitirse en migración; el script conecta siempre.
 *   php database/migrate.php
 *
 * enabled = false: app()->database() lanzará excepción hasta activar DB_ENABLED.
 */
return [
    'enabled' => filter_var(getenv('DB_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN),

    'driver' => getenv('DB_DRIVER') ?: 'sqlite',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_DATABASE') ?: BASE_PATH . '/storage/database.sqlite',
    'username' => getenv('DB_USERNAME') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',

    'options' => [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ],
];
