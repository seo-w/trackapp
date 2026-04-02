<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Registro de consultas (auditoría / historial) en query_logs.
 */
final class QueryLogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return int id autogenerado
     */
    public function insert(
        string $requestedStates,
        int $resultCount,
        bool $success,
        ?string $errorMessage = null,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO query_logs (requested_states, result_count, success, error_message)
             VALUES (:requested_states, :result_count, :success, :error_message)',
        );
        $stmt->execute([
            'requested_states' => $requestedStates,
            'result_count' => $resultCount,
            'success' => $success ? 1 : 0,
            'error_message' => $errorMessage,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        if ($id <= 0) {
            throw new \RuntimeException('No se obtuvo lastInsertId tras insertar en query_logs.');
        }

        return $id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(int $limit = 50): array
    {
        $safeLimit = (int) max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT id, requested_states, result_count, success, error_message, created_at, updated_at
             FROM query_logs
             ORDER BY id DESC
             LIMIT {$safeLimit}",
        );
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, requested_states, result_count, success, error_message, created_at, updated_at
             FROM query_logs WHERE id = :id LIMIT 1',
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
