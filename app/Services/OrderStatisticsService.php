<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Calculates global and monthly statistics for a list of orders.
 */
final class OrderStatisticsService
{
    private const MONTHS_ES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * @param list<array<string, mixed>> $orders
     * @return array{
     *   global: array<int, int>,
     *   months: array<string, array{label: string, stats: array<int, int>, total: int}>,
     *   returnsByCity: array<string, int>,
     *   successByCity: array<string, array{delivered: int, total: int, pct: float}>,
     *   productStats: array<string, array{delivered: int, returns: int, total: int, pct: float, ingresos: float, costos: float, en_ruta: int, fallback_name: string}>,
     *   grandTotal: int
     * }
     */
    public function calculate(array $orders): array
    {
        $global = [2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $months = [];
        $returnsByCity = [];
        $cityStats = [];
        $productStats = [];
 // Para calcular el éxito

        foreach ($orders as $order) {
            $status = (int) ($order['statusCode'] ?? 0);
            
            // Increment global totals if status is tracked
            if (array_key_exists($status, $global)) {
                $global[$status]++;
            }

            $dateStr = $order['mainEventDate'] ?? '';
            if ($dateStr === '') {
                continue;
            }

            $time = strtotime($dateStr);
            if ($time === false) {
                continue;
            }

            $yearMonth = date('Y-m', $time);
            $monthNum = (int) date('n', $time);
            $yearStr = date('Y', $time);
            $monthLabel = self::MONTHS_ES[$monthNum] . ' ' . $yearStr;

            if (! isset($months[$yearMonth])) {
                $months[$yearMonth] = [
                    'label' => $monthLabel,
                    'stats' => [2 => 0, 3 => 0, 4 => 0, 5 => 0],
                    'total' => 0,
                ];
            }

            if (array_key_exists($status, $months[$yearMonth]['stats'])) {
                $months[$yearMonth]['stats'][$status]++;
            }
            $months[$yearMonth]['total']++;

            // Datos por ciudad
            $city = (string) ($order['city'] ?? 'No indicada');
            if (!isset($cityStats[$city])) {
                $cityStats[$city] = ['delivered' => 0, 'total' => 0];
            }
            $cityStats[$city]['total']++;
            
            if ($status === 3 || $status === 5) {
                $cityStats[$city]['delivered']++;
            }

            // Track returns by city (Status 4)
            if ($status === 4) {
                $returnsByCity[$city] = ($returnsByCity[$city] ?? 0) + 1;
            }

            // Datos por producto
            $pid = (string) ($order['productId'] ?? '');
            if ($pid !== '' && $pid !== '0') {
                if (! isset($productStats[$pid])) {
                    $productStats[$pid] = [
                        'delivered' => 0, 
                        'returns' => 0, 
                        'total' => 0, 
                        'pct' => 0.0, 
                        'ingresos' => 0.0, 
                        'costos' => 0.0,
                        'en_ruta' => 0,
                        'fallback_name' => (string) ($order['orderProductName'] ?? ''),
                    ];
                }
                $productStats[$pid]['total']++;
                
                // Si el fallback sigue vacío, intentar capturarlo
                if ($productStats[$pid]['fallback_name'] === '' && !empty($order['orderProductName'])) {
                    $productStats[$pid]['fallback_name'] = $order['orderProductName'];
                }

                $t = (float)($order['total'] ?? 0.0);

                $c = (float)($order['costo'] ?? 0.0);
                $e = (float)($order['costoEnvio'] ?? 0.0);

                if ($status === 3 || $status === 5) {
                    $productStats[$pid]['delivered']++;
                    $productStats[$pid]['ingresos'] += $t;
                    $productStats[$pid]['costos'] += ($c + $e);
                } elseif ($status === 4) {
                    $productStats[$pid]['returns']++;
                    $productStats[$pid]['costos'] += $e; // Solo flete en devoluciones
                } elseif ($status === 2) {
                    $productStats[$pid]['en_ruta']++;
                }
            }
        }

        // Calcular porcentajes de éxito por producto
        foreach ($productStats as $pid => $counts) {
            if ($counts['total'] > 0) {
                $productStats[$pid]['pct'] = round(($counts['delivered'] / $counts['total']) * 100, 1);
            }
        }

        // Calcular porcentajes de éxito por ciudad (Filtro: mínimo 3 pedidos para confiabilidad)
        $successByCity = [];
        foreach ($cityStats as $city => $counts) {
            if ($counts['total'] >= 3) {
                $pct = round(($counts['delivered'] / $counts['total']) * 100, 1);
                $successByCity[$city] = [
                    'delivered' => $counts['delivered'],
                    'total' => $counts['total'],
                    'pct' => $pct
                ];
            }
        }

        // Ordenar éxito por porcentaje (descendente) y luego por volumen
        uasort($successByCity, function($a, $b) {
            if ($b['pct'] !== $a['pct']) {
                return $b['pct'] <=> $a['pct'];
            }
            return $b['total'] <=> $a['total'];
        });

        // Sort by month key descending (newest first)
        krsort($months);

        // Sort cities by returns descending
        arsort($returnsByCity);

        return [
            'global' => $global,
            'months' => $months,
            'returnsByCity' => $returnsByCity,
            'successByCity' => $successByCity,
            'productStats' => $productStats,
            'grandTotal' => array_sum($global),
        ];
    }

}
