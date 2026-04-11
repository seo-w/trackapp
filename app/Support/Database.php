<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

/**
 * Conexión PDO reutilizable con sentencias preparadas como práctica recomendada en repositorios.
 */
final class Database
{
    /** @var PDO|null */
    private $pdo = null;

    /** @var array */
    private $config;

    /**
     * @param array<string, mixed> $config Típicamente config/database.php
     */
    private function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Instancia ligada a la configuración de la aplicación (respeta database.enabled en capas superiores).
     *
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config)
    {
        return new self($config);
    }

    /**
     * Alias semántico para scripts CLI (migrate) frente a fromConfig() en runtime.
     *
     * @param array<string, mixed> $config
     */
    public static function connectForMigration(array $config)
    {
        return self::fromConfig($config);
    }

    public function pdo()
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $this->pdo = $this->open($this->config);

        return $this->pdo;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function open(array $config)
    {
        $driver = (string) (isset($config['driver']) ? $config['driver'] : 'mysql');
        if ($driver !== 'mysql' && $driver !== 'sqlite') {
            throw new \InvalidArgumentException(sprintf(
                'Driver no soportado: %s. Usa mysql o sqlite en config/database.php.',
                $driver
            ));
        }

        $host = (string) (isset($config['host']) ? $config['host'] : '127.0.0.1');
        $port = (int) (isset($config['port']) ? $config['port'] : 3306);
        $database = (string) (isset($config['database']) ? $config['database'] : '');
        $charset = (string) (isset($config['charset']) ? $config['charset'] : 'utf8mb4');
        $username = (string) (isset($config['username']) ? $config['username'] : '');
        $password = (string) (isset($config['password']) ? $config['password'] : '');

        if ($database === '') {
            throw new \InvalidArgumentException('Falta database.name (DB_DATABASE) en la configuración.');
        }

        if ($driver === 'sqlite') {
            // Asegurarse de crear el archivo o usar el existente
            $dsn = 'sqlite:' . $database;
            $username = null; // SQLite no usa estos credenciales típicamente
            $password = null;
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $host,
                $port,
                $database,
                $charset
            );
        }

        /** @var array<int, int|bool> $baseOptions */
        $baseOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        /** @var array<int, mixed> $extra */
        $extra = isset($config['options']) ? $config['options'] : [];
        /** @var array<int, mixed> $options */
        $options = $extra + $baseOptions;

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException(
                'No se pudo conectar a la base de datos. Revisa host, puerto, credenciales y que el servicio esté en ejecución.',
                0,
                $e
            );
        }
    }

    /**
     * Ejecuta un fichero SQL dividido por punto y coma (sin ORM).
     * Omite comentarios de línea `--` y bloques vacíos.
     */
    public static function runSqlFile(PDO $pdo, $sql)
    {
        $clean = preg_replace('/^\s*--.*$/m', '', $sql);
        if ($clean === null) {
            $clean = '';
        }
        $chunks = array_filter(
            array_map('trim', explode(';', $clean)),
            function ($chunk) {
                return $chunk !== '';
            }
        );

        $count = 0;
        foreach ($chunks as $statement) {
            $pdo->exec($statement);
            ++$count;
        }

        return $count;
    }
}
