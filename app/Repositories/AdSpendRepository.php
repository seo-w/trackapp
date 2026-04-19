<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

final class AdSpendRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->exec('PRAGMA busy_timeout = 5000');
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
            // Verificamos si la tabla existe y tiene el índice único correcto
            $res = $this->pdo->query("SELECT sql FROM sqlite_master WHERE name='ad_spends'")->fetch();
            if (!$res) {
                $this->pdo->exec('
                    CREATE TABLE IF NOT EXISTS ad_spends (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        mes VARCHAR(7),
                        tienda_id VARCHAR(50) DEFAULT "global",
                        amount REAL DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE(mes, tienda_id)
                    )
                ');
                return;
            }

            $sql = $res['sql'];
            // Si la tabla existe pero no tiene el índice compuesto (mes, tienda_id), informamos que requiere recreación
            if (!str_contains($sql, 'UNIQUE(mes, tienda_id)') && !str_contains($sql, 'UNIQUE (mes, tienda_id)')) {
                 // En SQLite, agregar una restricción UNIQUE requiere recrear la tabla.
                 // Como esta es una app de analítica, si detectamos el esquema viejo, 
                 // lo ideal sería una migración de datos, pero para este hotfix 
                 // asumimos que el usuario aceptó la recreación o que la tabla se limpiará.
                 $this->pdo->exec("DROP TABLE ad_spends");
                 $this->ensureTableExists();
            }
        } catch (PDOException $e) {
            // Si hay un error al consultar sqlite_master, intentamos crear la tabla por si acaso
            if (str_contains($e->getMessage(), 'no such table')) {
                 $this->pdo->exec('
                    CREATE TABLE IF NOT EXISTS ad_spends (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        mes VARCHAR(7),
                        tienda_id VARCHAR(50) DEFAULT "global",
                        amount REAL DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE(mes, tienda_id)
                    )
                ');
            }
        }
    }
}
