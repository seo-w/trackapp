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

    /**
     * Calculates the matrix of City x Carrier success rates for the Heatmap.
     */
    public function calculateCarrierCityMatrix(array $orders, int $limitCities = 10): array
    {
        $matrix = [];
        $couriers = [];
        $cities = [];

        foreach ($orders as $order) {
            $city = (string) ($order['cityName'] ?? $order['city'] ?? 'Otras');
            $carrier = (string) ($order['carrierName'] ?? 'No indicada');
            $status = (int) ($order['statusCode'] ?? 0);

            if (!isset($matrix[$city])) $matrix[$city] = [];
            if (!isset($matrix[$city][$carrier])) $matrix[$city][$carrier] = ['total' => 0, 'success' => 0];

            $matrix[$city][$carrier]['total']++;
            if ($status === 3 || $status === 5) {
                $matrix[$city][$carrier]['success']++;
            }

            $couriers[$carrier] = true;
            $cities[$city] = ($cities[$city] ?? 0) + 1;
        }

        arsort($cities);
        $topCities = array_slice(array_keys($cities), 0, $limitCities);
        $allCouriers = array_keys($couriers);

        $finalData = [];
        foreach ($topCities as $cIdx => $city) {
            foreach ($allCouriers as $crIdx => $carrier) {
                $stats = $matrix[$city][$carrier] ?? ['total' => 0, 'success' => 0];
                $pct = $stats['total'] > 0 ? round(($stats['success'] / $stats['total']) * 100, 1) : null;
                $finalData[] = [$crIdx, $cIdx, $pct];
            }
        }

        return [
            'matrix' => $finalData,
            'cities' => $topCities,
            'carriers' => $allCouriers
        ];
    }

    /**
     * Prepares data for a Pareto Chart (Product vs Profit).
     * @param array<string, array<string, mixed>> $productStats
     * @param array<string, array<string, mixed>> $productNames Full product details indexed by ID
     */
    public function calculateProductPareto(array $productStats, array $productNames = []): array
    {
        $data = [];
        foreach ($productStats as $pid => $stats) {
            $profit = (float)($stats['ingresos'] - $stats['costos']);
            
            // For Pareto analysis of concentration, we focus on positive contributions.
            // Losses skew the cumulative line and the 80/20 observation.
            if ($profit <= 0) continue;

            $pData = $productNames[(string)$pid] ?? null;
            $name = (string)($pData['name'] ?? $stats['fallback_name'] ?: "P-$pid");

            $data[] = [
                'name' => $name,
                'profit' => $profit
            ];
        }

        // Sort by profit descending
        usort($data, fn($a, $b) => $b['profit'] <=> $a['profit']);

        $totalProfit = array_sum(array_column($data, 'profit'));
        $runningSum = 0;
        foreach ($data as &$item) {
            if ($totalProfit > 0) {
                $runningSum += $item['profit'];
                $item['percentage'] = round(($runningSum / $totalProfit) * 100, 1);
            } else {
                $item['percentage'] = 0;
            }
        }

        return [
            'chartData' => array_slice($data, 0, 15),
            'totalProfit' => $totalProfit
        ];
    }


    /**
     * Calculates monthly geographic distribution for the Map.
     */
    /**
     * Calculates monthly geographic distribution for the Map.
     * Aggregates by Department to match the GeoJSON features.
     */
    public function calculateGeographicPoints(array $orders): array
    {
        $deptMap = [
            'BOGOTA' => 'SANTAFE DE BOGOTA D.C',
            'ANTIOQUIA' => 'ANTIOQUIA',
            'VALLE DEL CAUCA' => 'VALLE DEL CAUCA',
            'ATLANTICO' => 'ATLANTICO',
            'BOLIVAR' => 'BOLIVAR',
            'SANTANDER' => 'SANTANDER',
            'NORTE DE SANTANDER' => 'NORTE DE SANTANDER',
            'RISARALDA' => 'RISARALDA',
            'TOLIMA' => 'TOLIMA',
            'MAGDALENA' => 'MAGDALENA',
            'HUILA' => 'HUILA',
            'META' => 'META',
            'QUINDIO' => 'QUINDIO',
            'CESAR' => 'CESAR',
            'CORDOBA' => 'CORDOBA',
            'SUCRE' => 'SUCRE',
            'CAUCA' => 'CAUCA',
            'BOYACA' => 'BOYACA',
            'CHOCO' => 'CHOCO',
            'CAQUETA' => 'CAQUETA',
            'CASANARE' => 'CASANARE',
            'LA GUAJIRA' => 'LA GUAJIRA',
            'PUTUMAYO' => 'PUTUMAYO',
            'AMAZONAS' => 'AMAZONAS',
            'ARAUCA' => 'ARAUCA',
            'GUAVIARE' => 'GUAVIARE',
            'VICHADA' => 'VICHADA',
            'GUAINIA' => 'GUAINIA',
            'VAUPES' => 'VAUPES'
        ];

        $counts = [];
        foreach ($orders as $order) {
            $deptRaw = strtoupper(trim($order['departmentName'] ?? ''));
            if (empty($deptRaw)) {
                $deptRaw = strtoupper(trim($order['city'] ?? ''));
            }
            
            // Normalización robusta de tildes y caracteres especiales
            $search  = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
            $replace = ['A', 'E', 'I', 'O', 'U', 'N', 'U'];
            $deptNormalized = str_replace($search, $replace, $deptRaw);
            
            $dept = $deptMap[$deptNormalized] ?? $deptNormalized;

            if ($dept === '') continue;

            if (!isset($counts[$dept])) {
                $counts[$dept] = 0;
            }
            $counts[$dept]++;
        }

        $result = [];
        foreach ($counts as $name => $val) {
            $result[] = ['name' => $name, 'value' => $val];
        }
        return $result;
    }

    /**
     * Calculates detailed financial KPIs per month.
     * @param list<array<string, mixed>> $orders
     * @param array<string, float> $pautas Monthly ad spend indexed by 'YYYY-MM'
     */
    public function calculateMonthlyFinancials(array $orders, array $pautas): array
    {
        $grouped = [];
        foreach ($orders as $o) {
            $date = $o['mainEventDate'] ?? '';
            if (is_string($date) && preg_match('/^(\d{4}-\d{2})/', $date, $m)) {
                $mes = $m[1];
            } else {
                continue;
            }

            if (!isset($grouped[$mes])) {
                $grouped[$mes] = [
                    'mes' => $mes,
                    'label' => $this->getFormattedMonthLabel($date),
                    'despachadas' => 0,
                    'entregadas' => 0,
                    'devueltas' => 0,
                    'en_proceso' => 0,
                    'ingresos_brutos' => 0.0,
                    'costos_producto' => 0.0,
                    'costos_envio_exito' => 0.0,
                    'costos_devolucion' => 0.0,
                ];
            }

            $st = (int) ($o['statusCode'] ?? 0);
            $grouped[$mes]['despachadas']++;
            $total = (float) ($o['total'] ?? 0.0);
            $costo = (float) ($o['costo'] ?? 0.0);
            $cEnvio = (float) ($o['costoEnvio'] ?? 0.0);

            if ($st === 3 || $st === 5) {
                $grouped[$mes]['entregadas']++;
                $grouped[$mes]['ingresos_brutos'] += $total;
                $grouped[$mes]['costos_producto'] += $costo;
                $grouped[$mes]['costos_envio_exito'] += $cEnvio;
            } elseif ($st === 4) {
                $grouped[$mes]['devueltas']++;
                $grouped[$mes]['costos_devolucion'] += $cEnvio;
            } elseif ($st === 2) {
                $grouped[$mes]['en_proceso']++;
            }
        }

        $months = [];
        foreach ($grouped as $mes => $mdata) {
            $pauta = (float) ($pautas[$mes] ?? 0.0);
            $mdata['pauta'] = $pauta;
            $mdata['utilidad_bruta'] = $mdata['ingresos_brutos'] - $mdata['costos_producto'] - $mdata['costos_envio_exito'];
            $mdata['profit'] = $mdata['utilidad_bruta'] - $mdata['costos_devolucion'] - $pauta;
            $mdata['efectividad_pct'] = $mdata['despachadas'] > 0 ? round(($mdata['entregadas'] / $mdata['despachadas']) * 100, 1) : 0;
            $mdata['devolucion_pct'] = $mdata['despachadas'] > 0 ? round(($mdata['devueltas'] / $mdata['despachadas']) * 100, 1) : 0;
            $mdata['roas'] = $pauta > 0 ? round($mdata['ingresos_brutos'] / $pauta, 2) : 0;
            $mdata['cpa'] = $mdata['entregadas'] > 0 ? round($pauta / $mdata['entregadas'], 0) : 0;
            $mdata['margen_unidad'] = $mdata['entregadas'] > 0 ? round($mdata['profit'] / $mdata['entregadas'], 0) : 0;
            $months[] = $mdata;
        }

        usort($months, fn($a, $b) => strcmp($b['mes'], $a['mes']));
        return $months;
    }
}

