<?php

declare(strict_types=1);

namespace App\Support;

use App\Application;

/**
 * Redirecciones HTTP sin dependencias externas.
 */
final class Redirect
{
    public static function to(string $url, int $status = 302)
    {
        $location = self::normalizeUrl($url);
        http_response_code($status);
        header('Location: ' . $location);

        exit;
    }

    public static function back(int $status = 302)
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

        $isRelative = (isset($trimmed[0]) && $trimmed[0] === '/') && ! (isset($trimmed[1]) && $trimmed[1] === '/');
        if ($base !== '' && $isRelative) {
            return $base . $trimmed;
        }

        return $trimmed;
    }
}
