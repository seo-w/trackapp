<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\OrderNormalizer;
use App\Domain\StatusMapper;

/**
 * Consulta el API por uno o varios estados, normaliza, deduplica y ordena.
 */
final class OrderListQueryService
{
    public function __construct(
        private MerkawebService $merkaweb,
        private OrderNormalizer $normalizer = new OrderNormalizer(),
        private StatusMapper $statusMapper = new StatusMapper(),
    ) {
    }

    /**
     * @param list<int> $estados Solo 2, 3 y 4 (ya filtrados)
     * @param string|null $fechaDesde Fecha inicio formato YYYY-MM-DD
     * @param string|null $fechaHasta Fecha fin formato YYYY-MM-DD
     * @return array{
     *   orders: list<array<string, mixed>>,
     *   apiWarnings: list<string>,
     *   allApisOk: bool,
     *   errorSummary: ?string
     * }
     */
    public function fetchAndNormalize(array $estados, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $apiWarnings = [];
        $allOk = true;
        $normalizedAccumulator = [];

        foreach ($estados as $estado) {
            $result = $this->merkaweb->findOrdenesByEstado($estado, $fechaDesde, $fechaHasta);
            if (! $result->ok) {
                $allOk = false;
                $apiWarnings[] = sprintf('Estado %d: %s', $estado, $result->message);
                continue;
            }

            $rows = MerkawebOrderPayload::extractOrderRows($result->data);
            foreach ($rows as $raw) {
                if (! is_array($raw)) {
                    continue;
                }
                /** @var array<string, mixed> $raw */
                $normalized = $this->normalizer->normalize($raw);
                
                // Aplicar filtro de fecha local si vienen definidos
                if ($this->passesDateFilter($normalized, $fechaDesde, $fechaHasta)) {
                    $normalizedAccumulator[] = $normalized;
                }
            }
        }

        $deduped = $this->dedupeByOrderId($normalizedAccumulator);
        $sorted = $this->sortByMainEventDateDesc($deduped);

        $errorSummary = $allOk ? null : implode(' ', $apiWarnings);

        return [
            'orders' => $sorted,
            'apiWarnings' => $apiWarnings,
            'allApisOk' => $allOk,
            'errorSummary' => $errorSummary,
        ];
    }

    /**
     * @param array<string, mixed> $normalized
     */
    private function passesDateFilter(array $normalized, ?string $fechaDesde, ?string $fechaHasta): bool
    {
        if ($fechaDesde === null && $fechaHasta === null) {
            return true;
        }

        $d = $normalized['mainEventDate'] ?? null;
        if (! is_string($d) || trim($d) === '') {
            return false;
        }
        
        $time = strtotime($d);
        if ($time === false) {
            return false;
        }
        $orderDate = date('Y-m-d', $time);
        
        if ($fechaDesde !== null && $fechaDesde !== '' && $orderDate < $fechaDesde) {
            return false;
        }

        if ($fechaHasta !== null && $fechaHasta !== '' && $orderDate > $fechaHasta) {
            return false;
        }

        return true;
    }

    /**
     * @param list<array<string, mixed>> $orders
     * @return list<array<string, mixed>>
     */
    private function dedupeByOrderId(array $orders): array
    {
        $byId = [];
        foreach ($orders as $row) {
            $id = (string) ($row['orderId'] ?? '');
            if ($id === '') {
                $payload = json_encode($row);
                $id = '_sin_id_' . md5($payload !== false ? $payload : serialize($row));
            }
            if (! isset($byId[$id])) {
                $byId[$id] = $row;
                continue;
            }
            if ($this->compareMainEventDate($row, $byId[$id]) > 0) {
                $byId[$id] = $row;
            }
        }

        return array_values($byId);
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private function compareMainEventDate(array $a, array $b): int
    {
        return self::mainEventTimestamp($a) <=> self::mainEventTimestamp($b);
    }

    /** @param array<string, mixed> $row */
    private static function mainEventTimestamp(array $row): int
    {
        $d = $row['mainEventDate'] ?? null;
        if (! is_string($d) || trim($d) === '') {
            return 0;
        }
        $t = strtotime($d);

        return $t !== false ? $t : 0;
    }

    /**
     * @param list<array<string, mixed>> $orders
     * @return list<array<string, mixed>>
     */
    private function sortByMainEventDateDesc(array $orders): array
    {
        usort($orders, static function (array $x, array $y): int {
            return self::mainEventTimestamp($y) <=> self::mainEventTimestamp($x);
        });

        return $orders;
    }

    /**
     * Texto de resumen de filtros para la UI.
     *
     * @param list<int> $estados
     */
    public function describeEstados(array $estados, ?string $fechaDesde = null, ?string $fechaHasta = null): string
    {
        $parts = [];
        foreach ($estados as $e) {
            $parts[] = sprintf('%d — %s', $e, $this->statusMapper->label((int) $e));
        }

        $base = implode(', ', $parts);
        
        $fechas = [];
        if ($fechaDesde !== null && $fechaDesde !== '') {
            $fechas[] = 'Desde: ' . $fechaDesde;
        }
        if ($fechaHasta !== null && $fechaHasta !== '') {
            $fechas[] = 'Hasta: ' . $fechaHasta;
        }
        
        if ($fechas !== []) {
            $base .= ' (' . implode(', ', $fechas) . ')';
        }

        return $base;
    }
}
