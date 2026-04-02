<?php

declare(strict_types=1);

use App\Application;
use App\Support\Database;
use App\Support\Redirect;
use App\Support\View;

function app(): Application
{
    return Application::getInstance();
}

/**
 * @param mixed $default
 * @return ($key is null ? array<string, mixed> : mixed)
 */
function config(?string $key = null, mixed $default = null): mixed
{
    $instance = Application::getInstance();
    if ($key === null) {
        return $instance->allConfig();
    }

    return $instance->get($key, $default);
}

function session(): App\Support\Session
{
    return app()->session();
}

function db(): Database
{
    return app()->database();
}

/**
 * @param int $status Código HTTP (302 por defecto)
 */
function redirect(string $url, int $status = 302): never
{
    Redirect::to($url, $status);
}

/**
 * @param int $status Código HTTP (302 por defecto)
 */
function redirect_back(int $status = 302): never
{
    Redirect::back($status);
}

/** @param array<string, mixed> $data */
function view(string $name, array $data = []): void
{
    View::render($name, $data);
}

/**
 * Encola un mensaje flash para la siguiente petición HTTP.
 *
 * @param mixed $value Debe poder almacenarse en $_SESSION
 */
function flash(string $key, mixed $value): void
{
    session()->flash($key, $value);
}

/**
 * Obtiene y elimina un mensaje flash de la petición actual.
 */
function flash_take(string $key, mixed $default = null): mixed
{
    return session()->getFlash($key, $default);
}

function flash_has(string $key): bool
{
    return session()->hasFlash($key);
}

/**
 * @return array<string, mixed>
 */
function flash_all(): array
{
    return session()->peekFlashAll();
}

function csrf_token(): string
{
    return app()->csrf()->token();
}

function csrf_field(): string
{
    return app()->csrf()->fieldHtml();
}

/**
 * Valida la petición POST actual contra el token CSRF de sesión.
 */
function csrf_validate(): bool
{
    return app()->csrf()->validateRequest();
}
