<?php

declare(strict_types=1);

namespace App;

use App\Support\Csrf;
use App\Support\Database;
use App\Support\Session;

/**
 * Contenedor mínimo de la aplicación: configuración y servicios básicos.
 */
final class Application
{
    private static ?self $instance = null;

    /** @var array<string, mixed> */
    private array $config;

    private string $basePath;

    private ?Session $session = null;

    private ?Csrf $csrf = null;

    private ?Database $database = null;

    /**
     * @param array<string, mixed> $config Árbol con claves de primer nivel al menos: app, database
     */
    private function __construct(string $basePath, array $config)
    {
        $this->basePath = $basePath;
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $configRoot
     */
    public static function boot(string $basePath, array $configRoot): self
    {
        self::$instance = new self($basePath, $configRoot);
        self::$instance->session()->start();

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('La aplicación no ha sido inicializada. Ejecuta Application::boot() primero.');
        }

        return self::$instance;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /** @return array<string, mixed> */
    public function allConfig(): array
    {
        return $this->config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function session(): Session
    {
        return $this->session ??= new Session($this->get('app.session', []));
    }

    public function csrf(): Csrf
    {
        return $this->csrf ??= new Csrf(
            $this->session(),
            $this->get('app.csrf', []),
        );
    }

    public function database(): Database
    {
        if (! $this->get('database.enabled', false)) {
            throw new \RuntimeException(
                'La base de datos está deshabilitada. Activa database.enabled o define DB_ENABLED=true en el entorno.',
            );
        }

        return $this->database ??= Database::fromConfig($this->get('database', []));
    }
}
