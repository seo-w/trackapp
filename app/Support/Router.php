<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    /**
     * @var array<string, array<string, array{0: class-string, 1: string}>>
     */
    private array $routes = [];

    public function get(string $path, string $controllerClass, string $action): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = [$controllerClass, $action];
    }

    public function post(string $path, string $controllerClass, string $action): void
    {
        $this->routes['POST'][$this->normalizePath($path)] = [$controllerClass, $action];
    }

    public function dispatch(string $method, string $requestPath): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($requestPath);
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>404</title></head><body><p>Página no encontrada.</p></body></html>';
            return;
        }

        [$class, $actionName] = $handler;
        $instance = new $class();
        if (! method_exists($instance, $actionName)) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Acción no disponible.';
            return;
        }
        $instance->{$actionName}();
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }
}
