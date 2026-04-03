<?php

declare(strict_types=1);

namespace App\Support;

use App\Application;

/**
 * Renderizado de vistas PHP con layout principal.
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $name, array $data = []): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $data['currentPath'] = $data['currentPath'] ?? $path;
        $data['app'] = $data['app'] ?? Application::getInstance()->get('app', []);
        
        $tiendaName = '';
        try {
            $row = (new \App\Repositories\AppSettingsRepository(db()->pdo()))->first();
            if ($row && !empty($row['tienda_name'])) {
                $tiendaName = $row['tienda_name'];
            }
        } catch (\Throwable $e) {}
        $data['tienda_name'] = $tiendaName;

        extract($data, EXTR_SKIP);

        $viewFile = BASE_PATH . '/app/Views/' . $name . '.php';
        if (! is_file($viewFile)) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Vista no encontrada.';

            return;
        }

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        $layoutFile = BASE_PATH . '/app/Views/layouts/main.php';
        require $layoutFile;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function partial(string $name, array $data = []): void
    {
        $file = BASE_PATH . '/app/Views/partials/' . $name . '.php';
        if (! is_file($file)) {
            return;
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}
