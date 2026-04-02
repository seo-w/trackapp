<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Carga opcional de variables desde BASE_PATH/.env (sin dependencias externas).
 */
final class EnvLoader
{
    public static function load(string $basePath): void
    {
        $file = $basePath . '/.env';
        if (! is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (preg_match('/^"(.*)"$/s', $value, $matches)) {
                $value = str_replace(['\\n', '\\"'], ["\n", '"'], $matches[1]);
            } elseif (preg_match("/^'(.*)'$/s", $value, $matches)) {
                $value = $matches[1];
            }

            if ($name === '') {
                continue;
            }

            if (getenv($name) !== false) {
                continue;
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}
