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
     */
    public function calculateGlobal(array $orders): array
    {
        $global = [2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($orders as $order) {
            $status = (int) ($order['statusCode'] ?? 0);
            if (array_key_exists($status, $global)) {
                $global[$status]++;
            }
        }
        return [
            'global' => $global,
            'grandTotal' => array_sum($global),
        ];
    }

    /**
     * @param list<array<string, mixed>> $orders
     */
    public function calculateLogistics(array $orders): array
    {
        $months = [];
        $courierStats = [];
        foreach ($orders as $order) {
            $status = (int) ($order['statusCode'] ?? 0);
            $dateStr = $order['mainEventDate'] ?? '';
            $courier = (string) ($order['carrierName'] ?? 'No indicada');

            // Courier
            if (!isset($courierStats[$courier])) {
                $courierStats[$courier] = ['total' => 0, 'returns' => 0, 'pct' => 0.0];
            }
            $courierStats[$courier]['total']++;
            if ($status === 4) { $courierStats[$courier]['returns']++; }

            // Monthly breakdown (Logistics only)
            if ($dateStr !== '') {
                $yearMonth = substr($dateStr, 0, 7);
                if (preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
                    if (!isset($months[$yearMonth])) {
                        $months[$yearMonth] = [
                            'label' => $this->getFormattedMonthLabel($dateStr),
                            'stats' => [2 => 0, 3 => 0, 4 => 0, 5 => 0],
                            'total' => 0,
                        ];
                    }
                    if (array_key_exists($status, $months[$yearMonth]['stats'])) {
                        $months[$yearMonth]['stats'][$status]++;
                    }
                    $months[$yearMonth]['total']++;
                }
            }
        }
        
        foreach ($courierStats as $c => $d) {
            if ($d['total'] > 0) $courierStats[$c]['pct'] = round(($d['returns'] / $d['total']) * 100, 1);
        }
        uasort($courierStats, fn($a, $b) => $b['returns'] <=> $a['returns']);
        krsort($months);

        return [
            'months' => $months,
            'courierStats' => $courierStats
        ];
    }

    /**
     * @param list<array<string, mixed>> $orders
     */
    public function calculateGeographic(array $orders): array
    {
        $returnsByCity = [];
        $cityStats = [];
        foreach ($orders as $order) {
            $status = (int) ($order['statusCode'] ?? 0);
            $city = (string) ($order['city'] ?? 'No indicada');
            
            if (!isset($cityStats[$city])) {
                $cityStats[$city] = ['delivered' => 0, 'total' => 0];
            }
            $cityStats[$city]['total']++;
            if ($status === 3 || $status === 5) {
                $cityStats[$city]['delivered']++;
            }
            if ($status === 4) {
                $returnsByCity[$city] = ($returnsByCity[$city] ?? 0) + 1;
            }
        }

        $successByCity = [];
        foreach ($cityStats as $city => $counts) {
            if ($counts['total'] >= 3) {
                $pct = round(($counts['delivered'] / $counts['total']) * 100, 1);
                $successByCity[$city] = ['delivered' => $counts['delivered'], 'total' => $counts['total'], 'pct' => $pct];
            }
        }
        uasort($successByCity, fn($a, $b) => ($b['pct'] <=> $a['pct']) ?: ($b['total'] <=> $a['total']));
        arsort($returnsByCity);

        return [
            'returnsByCity' => $returnsByCity,
            'successByCity' => $successByCity
        ];
    }

    /**
     * @param list<array<string, mixed>> $orders
     */
    public function calculateProducts(array $orders): array
    {
        $productStats = [];
        foreach ($orders as $order) {
            $status = (int) ($order['statusCode'] ?? 0);
            $pid = (string) ($order['productId'] ?? '');
            if ($pid === '' || $pid === '0') continue;

            if (!isset($productStats[$pid])) {
                $productStats[$pid] = [
                    'delivered' => 0, 'returns' => 0, 'total' => 0, 'pct' => 0.0, 
                    'ingresos' => 0.0, 'costos' => 0.0, 'en_ruta' => 0,
                    'fallback_name' => (string) ($order['orderProductName'] ?? ''),
                ];
            }
            $productStats[$pid]['total']++;
            
            $t = (float)($order['total'] ?? 0.0);
            $c = (float)($order['costo'] ?? 0.0);
            $e = (float)($order['costoEnvio'] ?? 0.0);

            if ($status === 3 || $status === 5) {
                $productStats[$pid]['delivered']++;
                $productStats[$pid]['ingresos'] += $t;
                $productStats[$pid]['costos'] += ($c + $e);
            } elseif ($status === 4) {
                $productStats[$pid]['returns']++;
                $productStats[$pid]['costos'] += $e;
            } elseif ($status === 2) {
                $productStats[$pid]['en_ruta']++;
            }
        }

        foreach ($productStats as $pid => $counts) {
            if ($counts['total'] > 0) $productStats[$pid]['pct'] = round(($counts['delivered'] / $counts['total']) * 100, 1);
        }
        return ['productStats' => $productStats];
    }

    private function getFormattedMonthLabel(string $dateStr): string
    {
        $time = strtotime($dateStr);
        if (!$time) return 'Desconocido';
        $monthNum = (int) date('n', $time);
        $yearStr = date('Y', $time);
        return self::MONTHS_ES[$monthNum] . ' ' . $yearStr;
    }

}
