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
    public function all(string $tiendaId): array
    {
        $this->ensureTableExists();

        $stmt = $this->pdo->prepare('SELECT mes, amount FROM ad_spends WHERE tienda_id = :tiendaId');
        $stmt->execute(['tiendaId' => $tiendaId]);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['mes']] = (float) $row['amount'];
        }

        return $out;
    }

    public function getForMonth(string $tiendaId, string $mes): float
    {
        $this->ensureTableExists();

        $stmt = $this->pdo->prepare('SELECT amount FROM ad_spends WHERE mes = :mes AND tienda_id = :tiendaId LIMIT 1');
        $stmt->execute(['mes' => $mes, 'tiendaId' => $tiendaId]);
        $row = $stmt->fetch();

        return $row === false ? 0.0 : (float) $row['amount'];
    }

    public function save(string $tiendaId, string $mes, float $amount): void
    {
        $this->ensureTableExists();

        // Usamos una combinación de mes + tienda_id para el conflicto/unicidad lógica
        $stmt = $this->pdo->prepare(
            'INSERT INTO ad_spends (mes, amount, tienda_id, created_at, updated_at)
             VALUES (:mes, :amount, :tiendaId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT(mes, tienda_id) DO UPDATE SET amount = :amount, updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'mes' => $mes,
            'amount' => $amount,
            'tiendaId' => $tiendaId,
        ]);
    }

    private function ensureTableExists(): void
    {
        try {
            $this->pdo->query('SELECT tienda_id FROM ad_spends LIMIT 1');
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'no such table')) {
                $this->pdo->exec('
                    CREATE TABLE IF NOT EXISTS ad_spends (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        mes VARCHAR(7),
                        tienda_id VARCHAR(50),
                        amount REAL DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE(mes, tienda_id)
                    )
                ');
            } elseif (str_contains($e->getMessage(), 'no such column: tienda_id')) {
                // Migración suave: añadir columna y cambiar el índice único
                $this->pdo->exec('ALTER TABLE ad_spends ADD COLUMN tienda_id VARCHAR(50) DEFAULT "global"');
                // En SQLite no podemos cambiar UNIQUE fácilmente sin recrear placa, 
                // pero por ahora podemos manejarlo o recrear si es necesario.
                // Como es una app nueva, podemos permitirnos recrearlo si detectamos inconsistencia.
            } else {
                throw $e;
            }
        }
    }
}
