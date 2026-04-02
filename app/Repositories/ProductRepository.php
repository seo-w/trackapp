<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Acceso a la tabla products (caché local de detalles del API Merkaweb).
 */
final class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByRemoteId(string $remoteId): ?array
    {
        $this->ensureTableExists();

        $stmt = $this->pdo->prepare(
            'SELECT id, remote_id, name, description, warehouse, image_url, raw_json, updated_at
             FROM products WHERE remote_id = :remoteId LIMIT 1'
        );
        $stmt->execute(['remoteId' => $remoteId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array{remote_id: string, name: ?string, description: ?string, warehouse: ?string, image_url: ?string, raw_json: ?string} $data
     */
    public function save(array $data): void
    {
        $this->ensureTableExists();

        $stmt = $this->pdo->prepare(
            'INSERT INTO products (remote_id, name, description, warehouse, image_url, raw_json, updated_at)
             VALUES (:remote_id, :name, :description, :warehouse, :image_url, :raw_json, CURRENT_TIMESTAMP)
             ON CONFLICT(remote_id) DO UPDATE SET
                name = EXCLUDED.name,
                description = EXCLUDED.description,
                warehouse = EXCLUDED.warehouse,
                image_url = EXCLUDED.image_url,
                raw_json = EXCLUDED.raw_json,
                updated_at = CURRENT_TIMESTAMP'
        );

        $stmt->execute([
            'remote_id'   => $data['remote_id'],
            'name'        => $data['name'],
            'description' => $data['description'],
            'warehouse'   => $data['warehouse'],
            'image_url'   => $data['image_url'],
            'raw_json'    => $data['raw_json'],
        ]);
    }

    private function ensureTableExists(): void
    {
        try {
            // Check if remote_id is already a string (column type)
            // If it is integer, we drop and recreate
            $res = $this->pdo->query('PRAGMA table_info(products)');
            $cols = $res->fetchAll();
            $drop = false;
            foreach ($cols as $col) {
                if ($col['name'] === 'remote_id' && (str_contains(strtoupper($col['type']), 'INT'))) {
                    $drop = true;
                    break;
                }
            }
            if ($drop) {
                $this->pdo->exec('DROP TABLE products');
                throw new PDOException('Forcing recreation');
            }
            $this->pdo->query('SELECT 1 FROM products LIMIT 1');
        } catch (PDOException $e) {
            if ($e->getMessage() === 'Forcing recreation' || str_contains($e->getMessage(), 'no such table')) {
                $this->pdo->exec('
                    CREATE TABLE IF NOT EXISTS products (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        remote_id VARCHAR(255) UNIQUE NOT NULL,
                        name VARCHAR(255) NULL,
                        description TEXT NULL,
                        warehouse VARCHAR(255) NULL,
                        image_url TEXT NULL,
                        raw_json TEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    );
                    CREATE INDEX IF NOT EXISTS idx_products_remote_id ON products (remote_id);
                ');
            } else {
                throw $e;
            }
        }
    }

}
