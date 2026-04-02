<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

final class AdSpendRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string, float> array con llave mes 'YYYY-MM' y valor la pauta
     */
    public function all(): array
    {
        $this->ensureTableExists();

        $stmt = $this->pdo->query('SELECT mes, amount FROM ad_spends');
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['mes']] = (float) $row['amount'];
        }

        return $out;
    }

    public function getForMonth(string $mes): float
    {
        $this->ensureTableExists();

        $stmt = $this->pdo->prepare('SELECT amount FROM ad_spends WHERE mes = :mes LIMIT 1');
        $stmt->execute(['mes' => $mes]);
        $row = $stmt->fetch();

        return $row === false ? 0.0 : (float) $row['amount'];
    }

    public function save(string $mes, float $amount): void
    {
        $this->ensureTableExists();

        $stmt = $this->pdo->prepare(
            'INSERT INTO ad_spends (mes, amount, created_at, updated_at)
             VALUES (:mes, :amount, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT(mes) DO UPDATE SET amount = :amount, updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'mes' => $mes,
            'amount' => $amount,
        ]);
    }

    private function ensureTableExists(): void
    {
        try {
            $this->pdo->query('SELECT 1 FROM ad_spends LIMIT 1');
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'no such table')) {
                $this->pdo->exec('
                    CREATE TABLE IF NOT EXISTS ad_spends (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        mes VARCHAR(7) UNIQUE,
                        amount REAL DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ');
            } else {
                throw $e;
            }
        }
    }
}
