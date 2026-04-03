<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AdSpendRepository;
use App\Repositories\AppSettingsRepository;
use App\Services\MerkawebService;
use App\Services\OrderListQueryService;
use App\Services\OrderStatisticsService;

final class StatsController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        try {
            $pdo = db()->pdo();
            $repo = new AppSettingsRepository($pdo);
            $merkaweb = MerkawebService::fromApp(config('app'), $repo);
            $queryService = new OrderListQueryService($merkaweb);
            // Traemos todos los estados importantes (2, 3, 4, 5) sin filtro de fecha para agrupar por mes
            $out = $queryService->fetchAndNormalize([2, 3, 4, 5]);
            $orders = $out['orders'];

            $statsService = new OrderStatisticsService();
            $detailedStats = $statsService->calculate($orders);

            // Cargar detalles de productos únicos
            $productRepo = new \App\Repositories\ProductRepository($pdo);
            $productService = new \App\Services\ProductService($merkaweb, $productRepo);
            $pIds = array_keys($detailedStats['productStats'] ?? []);
            $products = $productService->getMultiple($pIds);

            // Cargar gastos de publicidad
            $adRepo = new AdSpendRepository($pdo);
            $pautas = $adRepo->all();

            /** @var array<string, array<string, mixed>> $grouped */
            $grouped = [];

            foreach ($orders as $o) {
                $date = $o['mainEventDate'] ?? '';
                // Extraer YYYY-MM
                $mes = '';
                if (is_string($date) && preg_match('/^(\d{4}-\d{2})/', $date, $m)) {
                    $mes = $m[1];
                } else {
                    continue; // Ignorar si no hay mes parseable
                }

                if (!isset($grouped[$mes])) {
                    $grouped[$mes] = [
                        'mes' => $mes,
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
                    // La devolución solo cobra el envío (un trayecto), según aclaración del usuario
                    $grouped[$mes]['costos_devolucion'] += $cEnvio;
                } elseif ($st === 2) {
                    $grouped[$mes]['en_proceso']++;
                }
            }

            // Procesar y ordenar de mes más reciente a más viejo
            $months = [];
            foreach ($grouped as $mes => $data) {
                $pauta = (float)($pautas[$mes] ?? 0.0);
                $data['pauta'] = $pauta;
                
                $data['utilidad_bruta'] = $data['ingresos_brutos'] - $data['costos_producto'] - $data['costos_envio_exito'];
                $data['profit'] = $data['utilidad_bruta'] - $data['costos_devolucion'] - $pauta;

                $d = $data['despachadas'];
                $e = $data['entregadas'];
                $data['efectividad_pct'] = $d > 0 ? round(($e / $d) * 100, 1) : 0;
                $data['devolucion_pct'] = $d > 0 ? round(($data['devueltas'] / $d) * 100, 1) : 0;

                // Nuevas métricas: ROAS, CPA, Margen x Unidad
                $data['roas'] = $pauta > 0 ? round($data['ingresos_brutos'] / $pauta, 2) : 0;
                $data['cpa'] = $e > 0 ? round($pauta / $e, 0) : 0;
                $data['margen_unidad'] = $e > 0 ? round($data['profit'] / $e, 0) : 0;
                
                $months[] = $data;
            }

            usort($months, function($a, $b) {
                return strcmp($b['mes'], $a['mes']); // orden descendente YYYY-MM
            });

            // Calcular acumulados globales financieros
            $globalFin = [
                'pauta' => 0.0,
                'costos_devolucion' => 0.0,
                'profit' => 0.0,
                'ingresos_brutos' => 0.0,
                'utilidad_bruta' => 0.0,
                'entregadas' => 0,
            ];

            foreach ($months as $m) {
                $globalFin['pauta'] += (float)$m['pauta'];
                $globalFin['costos_devolucion'] += (float)$m['costos_devolucion'];
                $globalFin['profit'] += (float)$m['profit'];
                $globalFin['ingresos_brutos'] += (float)$m['ingresos_brutos'];
                $globalFin['utilidad_bruta'] += (float)$m['utilidad_bruta'];
                $globalFin['entregadas'] += (int)$m['entregadas'];
            }

            // KPIs Globales
            $globalFin['roas'] = $globalFin['pauta'] > 0 ? round($globalFin['ingresos_brutos'] / $globalFin['pauta'], 2) : 0;
            $globalFin['cpa'] = $globalFin['entregadas'] > 0 ? round($globalFin['pauta'] / $globalFin['entregadas'], 0) : 0;
            $globalFin['margen_unidad'] = $globalFin['entregadas'] > 0 ? round($globalFin['profit'] / $globalFin['entregadas'], 0) : 0;

        } catch (\Throwable $e) {
            $this->view('estadisticas', [
                'error' => 'No se pudieron cargar las estadísticas: ' . $e->getMessage(),
                'months' => [],
                'apiWarnings' => [],
                'detailedStats' => null,
                'globalFinancials' => null,
                'products' => [],
            ]);
            return;
        }

        $this->view('estadisticas', [
            'title' => 'Estadísticas y Finanzas',
            'months' => $months,
            'apiWarnings' => $out['apiWarnings'] ?? [],
            'detailedStats' => $detailedStats ?? null,
            'globalFinancials' => $globalFin,
            'products' => $products ?? [],
            'error' => null,
        ]);

    }


    public function savePauta(): void
    {
        $this->requireAuth();
        if (! csrf_validate()) {
            http_response_code(400);
            echo "Sesión expirada.";
            return;
        }

        $mes = $_POST['mes'] ?? '';
        $amountRaw = $_POST['amount'] ?? '0';

        if (is_string($mes) && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $amount = (float) $amountRaw;
            $adRepo = new AdSpendRepository(db()->pdo());
            $adRepo->save($mes, $amount);
        }

        // Redirigir de vuelta a estadísticas
        header('Location: /estadisticas');
        exit;
    }
}
