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
        $tab = $_GET['tab'] ?? 'consolidado';
        $validTabs = ['consolidado', 'logistica', 'geografia', 'productos', 'finanzas'];
        if (!in_array($tab, $validTabs)) { $tab = 'consolidado'; }

        try {
            $pdo = db()->pdo();
            $repo = new AppSettingsRepository($pdo);
            $merkaweb = MerkawebService::fromApp(config('app'), $repo);
            $queryService = new OrderListQueryService($merkaweb);
            
            // Siempre traemos las órdenes base (es inevitable para todas las métricas actuales)
            $out = $queryService->fetchAndNormalize([2, 3, 4, 5]);
            $orders = $out['orders'];

            $statsService = new OrderStatisticsService();
            $data = [];
            $products = [];
            $months = [];
            $globalFin = null;

            // Lógica selectiva por pestaña
            switch ($tab) {
                case 'consolidado':
                    $data = $statsService->calculateGlobal($orders);
                    // Necesitamos la historia mensual para las gráficas estratégicas del consolidado
                    $adRepo = new AdSpendRepository($pdo);
                    $tiendaId = (string) session()->get('tienda_id', 'global');
                    $pautas = $adRepo->all($tiendaId);
                    $months = $statsService->calculateMonthlyFinancials($orders, $pautas);
                    break;
                case 'logistica':
                    $data = $statsService->calculateLogistics($orders);
                    // También cargamos meses básicos para la tabla de telemetría si es necesario
                    // (calculateLogistics ya devuelve su propio array de meses pero es diferente)
                    break;
                case 'geografia':
                    $data = $statsService->calculateGeographic($orders);
                    break;
                case 'productos':
                    $data = $statsService->calculateProducts($orders);
                    // Solo cargamos nombres de productos si estamos en esta pestaña
                    $productRepo = new \App\Repositories\ProductRepository($pdo);
                    $productService = new \App\Services\ProductService($merkaweb, $productRepo);
                    $pIds = array_keys($data['productStats'] ?? []);
                    $products = $productService->getMultiple($pIds);
                    break;
                case 'finanzas':
                    $adRepo = new AdSpendRepository($pdo);
                    $tiendaId = (string) session()->get('tienda_id', 'global');
                    $pautas = $adRepo->all($tiendaId);
                    $months = $statsService->calculateMonthlyFinancials($orders, $pautas);

                    $globalFin = ['pauta' => 0.0, 'costos_devolucion' => 0.0, 'profit' => 0.0, 'ingresos_brutos' => 0.0, 'utilidad_bruta' => 0.0, 'entregadas' => 0];
                    foreach ($months as $m) {
                        $globalFin['pauta'] += $m['pauta'];
                        $globalFin['costos_devolucion'] += $m['costos_devolucion'];
                        $globalFin['profit'] += $m['profit'];
                        $globalFin['ingresos_brutos'] += $m['ingresos_brutos'];
                        $globalFin['utilidad_bruta'] += $m['utilidad_bruta'];
                        $globalFin['entregadas'] += $m['entregadas'];
                    }
                    $globalFin['roas'] = $globalFin['pauta'] > 0 ? round($globalFin['ingresos_brutos'] / $globalFin['pauta'], 2) : 0;
                    $globalFin['cpa'] = $globalFin['entregadas'] > 0 ? round($globalFin['pauta'] / $globalFin['entregadas'], 0) : 0;
                    $globalFin['margen_unidad'] = $globalFin['entregadas'] > 0 ? round($globalFin['profit'] / $globalFin['entregadas'], 0) : 0;
                    break;
            }

        } catch (\Throwable $e) {
            $this->view('estadisticas', [
                'title' => 'Estadísticas',
                'error' => 'No se pudieron cargar las estadísticas: ' . $e->getMessage(),
                'activeTab' => $tab,
                'months' => [],
                'apiWarnings' => [],
                'detailedStats' => null,
                'globalFinancials' => null,
                'products' => [],
            ]);
            return;
        }

        // Advanced Analytics for ECharts
        $data['advanced'] = [
            'heatmap' => $tab === 'logistica' ? $statsService->calculateCarrierCityMatrix($orders) : null,
            'pareto' => $tab === 'productos' ? $statsService->calculateProductPareto($data['productStats'] ?? [], $products) : null,
            'geoPoints' => $tab === 'geografia' ? $statsService->calculateGeographicPoints($orders) : null,
        ];

        $this->view('estadisticas', [
            'title' => 'Estadísticas y Finanzas',
            'activeTab' => $tab,
            'months' => $months,
            'apiWarnings' => $out['apiWarnings'] ?? [],
            'detailedStats' => $data,
            'globalFinancials' => $globalFin,
            'products' => $products,
            'error' => null,
            'hasData' => !empty($orders),
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
        $tiendaId = (string) session()->get('tienda_id', 'global');

        if (is_string($mes) && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $amount = (float) $amountRaw;
            $adRepo = new AdSpendRepository(db()->pdo());
            $adRepo->save($tiendaId, $mes, $amount);
        }

        // Redirigir de vuelta a estadísticas
        header('Location: /estadisticas?tab=finanzas');
        exit;
    }
}
