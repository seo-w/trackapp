#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Inicializa las tablas definidas en schema.sql.
 *
 * Uso:
 *   php database/migrate.php
 *
 * Variables de entorno recomendadas: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 * (no exige DB_ENABLED; conecta siempre que la configuración sea válida).
 */

$basePath = dirname(__DIR__);
define('BASE_PATH', $basePath);

if (! is_file($basePath . '/config/database.php')) {
    fwrite(STDERR, "No se encontró config/database.php.\n");
    exit(1);
}

/** @var array<string, mixed> $dbConfig */
$dbConfig = require $basePath . '/config/database.php';

require_once $basePath . '/app/Support/Database.php';

try {
    $driver = $dbConfig['driver'] ?? 'mysql';
    $sqlFile = $driver === 'sqlite' ? 'schema_sqlite.sql' : 'schema.sql';
    $sqlPath = $basePath . '/database/' . $sqlFile;
    if (! is_file($sqlPath)) {
        throw new RuntimeException("No existe database/{$sqlFile}");
    }

    $sql = (string) file_get_contents($sqlPath);
    $database = App\Support\Database::connectForMigration($dbConfig);
    $executed = App\Support\Database::runSqlFile($database->pdo(), $sql);

    fwrite(STDOUT, "Migración aplicada: {$executed} sentencias ejecutadas.\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
