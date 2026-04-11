<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Gestión de usuarios locales (Administradores y usuarios).
 */
final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'user',
                is_approved INTEGER NOT NULL DEFAULT 0,
                tienda_id VARCHAR(50),
                tienda_name VARCHAR(255),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);
        ");

        // Auto-migración suave de columnas faltantes
        try {
            $this->pdo->exec("ALTER TABLE users ADD COLUMN tienda_id VARCHAR(50)");
        } catch (\PDOException) {}
        try {
            $this->pdo->exec("ALTER TABLE users ADD COLUMN tienda_name VARCHAR(255)");
        } catch (\PDOException) {}
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, password, role, is_approved, tienda_id, tienda_name, created_at, updated_at
             FROM users WHERE email = :email LIMIT 1',
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array{email: string, password_hash: string, role: string, is_approved?: int, tienda_id?: ?string, tienda_name?: ?string} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password, role, is_approved, tienda_id, tienda_name) 
             VALUES (:email, :password, :role, :is_approved, :tienda_id, :tienda_name)',
        );
        $stmt->execute([
            'email' => $data['email'],
            'password' => $data['password_hash'],
            'role' => $data['role'],
            'is_approved' => (int) ($data['is_approved'] ?? 0),
            'tienda_id' => $data['tienda_id'] ?? null,
            'tienda_name' => $data['tienda_name'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{password_hash?: ?string, role?: ?string, is_approved?: int, tienda_id?: ?string, tienda_name?: ?string} $data
     */
    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        if (array_key_exists('password_hash', $data)) {
            $fields[] = 'password = :password';
            $params['password'] = $data['password_hash'];
        }
        if (array_key_exists('role', $data)) {
            $fields[] = 'role = :role';
            $params['role'] = $data['role'];
        }
        if (array_key_exists('is_approved', $data)) {
            $fields[] = 'is_approved = :is_approved';
            $params['is_approved'] = (int) $data['is_approved'];
        }
        if (array_key_exists('tienda_id', $data)) {
            $fields[] = 'tienda_id = :tienda_id';
            $params['tienda_id'] = $data['tienda_id'];
        }
        if (array_key_exists('tienda_name', $data)) {
            $fields[] = 'tienda_name = :tienda_name';
            $params['tienda_name'] = $data['tienda_name'];
        }

        if ($fields === []) {
            return;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        
        $this->pdo->prepare($sql)->execute($params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT id, email, role, is_approved, tienda_id, tienda_name, created_at FROM users ORDER BY created_at DESC',
        )->fetchAll();
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
}
