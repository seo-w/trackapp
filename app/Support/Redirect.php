<?php

declare(strict_types=1);

namespace App\Support;

use App\Application;

/**
 * Redirecciones HTTP sin dependencias externas.
 */
final class Redirect
{
    /**
     * @param int $status 302 por defecto; 303 recomendable tras POST explícito
     */
    public static function to(string $url, int $status = 302): never
    {
        $location = self::normalizeUrl($url);
        http_response_code($status);
        header('Location: ' . $location);

        exit;
    }

    public static function back(int $status = 302): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $target = is_string($referer) && $referer !== '' ? $referer : '/';

        self::to($target, $status);
    }

    /**
     * Antepone app.url si la ruta es relativa al sitio.
     */
    private static function normalizeUrl(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return '/';
        }

        try {
            $base = (string) Application::getInstance()->get('app.url', '');
        } catch (\Throwable) {
            $base = '';
        }

        $isRelative = str_starts_with($trimmed, '/') && ! str_starts_with($trimmed, '//');
        if ($base !== '' && $isRelative) {
            return $base . $trimmed;
        }

        return $trimmed;
    }
}
