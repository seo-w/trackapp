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
                    break;
                case 'logistica':
                    $data = $statsService->calculateLogistics($orders);
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
                    // Para finanzas necesitamos procesar meses y pautas
                    $tiendaId = (string) session()->get('tienda_id', 'global');
                    $adRepo = new AdSpendRepository($pdo);
                    $pautas = $adRepo->all($tiendaId);
                    
                    $grouped = [];
                    foreach ($orders as $o) {
                        $date = $o['mainEventDate'] ?? '';
                        if (is_string($date) && preg_match('/^(\d{4}-\d{2})/', $date, $m)) {
                            $mes = $m[1];
                        } else { continue; }

                        if (!isset($grouped[$mes])) {
                            $grouped[$mes] = [
                                'mes' => $mes, 'despachadas' => 0, 'entregadas' => 0, 'devueltas' => 0,
                                'en_proceso' => 0, 'ingresos_brutos' => 0.0, 'costos_producto' => 0.0,
                                'costos_envio_exito' => 0.0, 'costos_devolucion' => 0.0,
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

                    foreach ($grouped as $mes => $mdata) {
                        $pauta = (float)($pautas[$mes] ?? 0.0);
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
