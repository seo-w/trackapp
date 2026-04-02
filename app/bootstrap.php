<?php

declare(strict_types=1);

use App\Application;
use App\Support\EnvLoader;

require_once __DIR__ . '/Support/EnvLoader.php';
EnvLoader::load(BASE_PATH);

$configRoot = [
    'app' => require BASE_PATH . '/config/app.php',
    'database' => require BASE_PATH . '/config/database.php',
];

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$application = Application::boot(BASE_PATH, $configRoot);

require BASE_PATH . '/app/Support/helpers.php';

return $application;
