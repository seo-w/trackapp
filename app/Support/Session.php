<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Sesión PHP con mensajes flash entre peticiones (patrón cola → mostrar en la siguiente).
 */
final class Session
{
    private const FLASH_KEY = '__trackapp_flash';

    private bool $flashPrepared = false;

    /** @param array<string, mixed> $options Opciones bajo config app.session */
    public function __construct(private array $options = [])
    {
    }

    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $name = (string) ($this->options['name'] ?? 'PHPSESSID');
            session_name($name);

            $lifetime = (int) ($this->options['cookie_lifetime'] ?? 0);
            $path = (string) ($this->options['path'] ?? '/');
            $domain = (string) ($this->options['domain'] ?? '');
            $secure = (bool) ($this->options['secure'] ?? false);
            $httponly = (bool) ($this->options['httponly'] ?? true);
            $sameSite = (string) ($this->options['samesite'] ?? 'Lax');

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => $path,
                'domain' => $domain !== '' ? $domain : '',
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $sameSite,
            ]);

            session_start();
        }

        if (! $this->flashPrepared) {
            $this->ensureFlash();
            $this->rotateFlash();
            $this->flashPrepared = true;
        }
    }

    private function ensureFlash(): void
    {
        if (! isset($_SESSION[self::FLASH_KEY]) || ! is_array($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [
                'queued' => [],
                'display' => [],
            ];
        }
    }

    /**
     * Mueve lo encolado al final de la petición anterior a la bolsa visible en esta petición.
     */
    private function rotateFlash(): void
    {
        $flash = &$_SESSION[self::FLASH_KEY];
        if (! is_array($flash)) {
            $_SESSION[self::FLASH_KEY] = ['queued' => [], 'display' => []];
            $flash = &$_SESSION[self::FLASH_KEY];
        }
        if (! isset($flash['queued']) || ! is_array($flash['queued'])) {
            $flash['queued'] = [];
        }
        $flash['display'] = $flash['queued'];
        $flash['queued'] = [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Disponible en la siguiente petición (p. ej. tras un redirect).
     *
     * @param mixed $value Debe ser serializable en sesión
     */
    public function flash(string $key, mixed $value): void
    {
        $this->ensureFlash();
        $flash = &$_SESSION[self::FLASH_KEY];
        if (! isset($flash['queued']) || ! is_array($flash['queued'])) {
            $flash['queued'] = [];
        }
        $flash['queued'][$key] = $value;
    }

    /**
     * Lee un flash de la petición actual y lo elimina de la bolsa de visualización.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureFlash();
        $flash = &$_SESSION[self::FLASH_KEY];
        if (! isset($flash['display']) || ! is_array($flash['display'])) {
            return $default;
        }
        if (! array_key_exists($key, $flash['display'])) {
            return $default;
        }
        $value = $flash['display'][$key];
        unset($flash['display'][$key]);

        return $value;
    }

    public function hasFlash(string $key): bool
    {
        $this->ensureFlash();
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        if (! is_array($flash) || ! isset($flash['display']) || ! is_array($flash['display'])) {
            return false;
        }

        return array_key_exists($key, $flash['display']);
    }

    /**
     * Lectura no destructiva de todos los flashes visibles en esta petición.
     *
     * @return array<string, mixed>
     */
    public function peekFlashAll(): array
    {
        $this->ensureFlash();
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        if (! is_array($flash) || ! isset($flash['display']) || ! is_array($flash['display'])) {
            return [];
        }

        return $flash['display'];
    }

    public function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }
}
