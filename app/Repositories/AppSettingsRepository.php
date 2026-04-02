<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Acceso a la tabla app_settings (configuración persistida: API, tienda, token cifrado).
 */
final class AppSettingsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, api_base_url, tienda_id, access_token_encrypted, created_at, updated_at
             FROM app_settings WHERE id = :id LIMIT 1',
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null Primer registro por id ascendente (patrón singleton de configuración).
     */
    public function first(): ?array
    {
        $stmt = $this->pdo->query(
            'SELECT id, api_base_url, tienda_id, access_token_encrypted, created_at, updated_at
             FROM app_settings ORDER BY id ASC LIMIT 1',
        );
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed> Crea un registro por defecto si la tabla está vacía.
     */
    public function firstOrCreateEmpty(): array
    {
        try {
            $existing = $this->first();
        } catch (\PDOException $e) {
            // Si la tabla no existe (SQLite recién creado), intentamos auto-migrar
            if (str_contains($e->getMessage(), 'no such table')) {
                $sqlPath = BASE_PATH . '/database/schema_sqlite.sql';
                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);
                    if (is_string($sql) && $sql !== '') {
                        \App\Support\Database::runSqlFile($this->pdo, $sql);
                    }
                }
                $existing = $this->first();
            } else {
                throw $e;
            }
        }

        if ($existing !== null) {
            return $existing;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO app_settings (api_base_url, tienda_id, access_token_encrypted)
             VALUES (:api_base_url, :tienda_id, :access_token_encrypted)',
        );
        $stmt->execute([
            'api_base_url' => '',
            'tienda_id' => '',
            'access_token_encrypted' => null,
        ]);

        $created = $this->first();
        if ($created === null) {
            throw new \RuntimeException('No se pudo crear el registro inicial de app_settings.');
        }

        return $created;
    }

    /**
     * Actualiza un registro existente por id.
     *
     * @param array{api_base_url: string, tienda_id: string, access_token_encrypted: ?string} $data
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE app_settings
             SET api_base_url = :api_base_url,
                 tienda_id = :tienda_id,
                 access_token_encrypted = :access_token_encrypted,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
        );
        $stmt->execute([
            'id' => $id,
            'api_base_url' => $data['api_base_url'],
            'tienda_id' => $data['tienda_id'],
            'access_token_encrypted' => $data['access_token_encrypted'],
        ]);

        if ($stmt->rowCount() === 0 && $this->findById($id) === null) {
            throw new \InvalidArgumentException(sprintf('No existe app_settings con id %d.', $id));
        }
    }
}
