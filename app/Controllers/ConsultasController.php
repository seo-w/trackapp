<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\StatusMapper;
use App\Repositories\AppSettingsRepository;
use App\Repositories\QueryLogRepository;
use App\Services\MerkawebService;
use App\Services\OrderListQueryService;
use App\Services\OrderStatisticsService;

final class ConsultasController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->renderConsultas([
            'selectedStates' => [2, 3, 4, 5],
            'showResults' => false,
        ]);
    }

    public function consultar(): void
    {
        $this->requireAuth();
        
        $selectedFromPost = $this->parseEstadosFromPost();
        $fechaDesde = $this->parseDateFromPost('fecha_desde');
        $fechaHasta = $this->parseDateFromPost('fecha_hasta');

        if (! csrf_validate()) {
            $this->renderConsultas([
                'selectedStates' => $selectedFromPost !== [] ? $selectedFromPost : [2, 3, 4, 5],
                'fechaDesde' => $fechaDesde,
                'fechaHasta' => $fechaHasta,
                'formError' => 'La sesión de seguridad expiró o el formulario no es válido. Vuelve a intentarlo.',
                'showResults' => false,
            ]);

            return;
        }

        if ($selectedFromPost === []) {
            $this->renderConsultas([
                'selectedStates' => [],
                'fechaDesde' => $fechaDesde,
                'fechaHasta' => $fechaHasta,
                'formError' => 'Selecciona al menos un estado (2, 3, 4 o 5).',
                'showResults' => false,
                'products' => [],
            ]);

            return;
        }

        sort($selectedFromPost);
 
        try {
            $pdo = db()->pdo();
            $repo = new AppSettingsRepository($pdo);
            $merkaweb = MerkawebService::fromApp(config('app'), $repo);
            $queryService = new OrderListQueryService($merkaweb);
            $out = $queryService->fetchAndNormalize($selectedFromPost, $fechaDesde, $fechaHasta);
            $filterSummary = $queryService->describeEstados($selectedFromPost, $fechaDesde, $fechaHasta);

            // Cargar detalles de productos
            $productRepo = new \App\Repositories\ProductRepository($pdo);
            $productService = new \App\Services\ProductService($merkaweb, $productRepo);
            $productIds = array_column($out['orders'], 'productId');
            $products = $productService->getMultiple($productIds);

        } catch (\Throwable $e) {
            $this->logConsultaSafe(
                implode(',', $selectedFromPost),
                0,
                false,
                $this->truncateLogMessage($e->getMessage()),
            );
            $this->renderConsultas([
                'selectedStates' => $selectedFromPost,
                'fechaDesde' => $fechaDesde,
                'fechaHasta' => $fechaHasta,
                'filterSummary' => $this->describeEstadosSelection($selectedFromPost, $fechaDesde, $fechaHasta),
                'queryError' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo ejecutar la consulta.',
                'showResults' => true,
            ]);

            return;
        }

        $this->logConsultaSafe(
            implode(',', $selectedFromPost),
            count($out['orders']),
            $out['allApisOk'],
            $this->truncateLogMessage($out['errorSummary']),
        );

        $this->renderConsultas([
            'selectedStates' => $selectedFromPost,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'filterSummary' => $filterSummary,
            'orders' => $out['orders'],
            'products' => $products ?? [],
            'apiWarnings' => $out['apiWarnings'],
            'allApisOk' => $out['allApisOk'],
            'showResults' => true,
        ]);
    }


    /**
     * @param array<string, mixed> $overrides
     */
    private function renderConsultas(array $overrides = []): void
    {
        $defaults = [
            'title' => 'Consultas',
            'heading' => 'Consultar órdenes',
            'subtitle' => 'Elige uno o varios estados logísticos (2 = Despachado, 3 = Entregado, 4 = Devuelto, 5 = Legalizado). Los datos se obtienen del API Merkaweb en el servidor y se muestran aquí sin exponer el token.',
            'selectedStates' => [2, 3, 4, 5],
            'fechaDesde' => null,
            'fechaHasta' => null,
            'filterSummary' => null,
            'orders' => [],
            'apiWarnings' => [],
            'allApisOk' => true,
            'detailedStats' => null,
            'products' => [],
            'queryError' => null,
            'formError' => null,
            'showResults' => false,
        ];

        $this->view('consultas', array_merge($defaults, $overrides));
    }

    /**
     * @return list<int>
     */
    private function parseEstadosFromPost(): array
    {
        $raw = $_POST['estados'] ?? [];
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $value) {
            $i = (int) $value;
            if (in_array($i, [2, 3, 4, 5], true)) {
                $out[] = $i;
            }
        }

        return array_values(array_unique($out));
    }

    private function parseDateFromPost(string $key): ?string
    {
        $raw = $_POST[$key] ?? '';
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }
        $val = trim($raw);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) === 1) {
            return $val;
        }
        return null;
    }

    private function logConsultaSafe(string $requestedStates, int $resultCount, bool $success, ?string $errorMessage): void
    {
        try {
            $repo = new QueryLogRepository(db()->pdo());
            $repo->insert($requestedStates, $resultCount, $success, $errorMessage);
        } catch (\Throwable) {
            // La consulta ya se mostró; el log no debe romper la UI.
        }
    }

    private function truncateLogMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return $message;
        }
        if (strlen($message) <= 2000) {
            return $message;
        }

        return substr($message, 0, 1997) . '...';
    }

    /**
     * @param list<int> $estados
     */
    private function describeEstadosSelection(array $estados, ?string $fechaDesde = null, ?string $fechaHasta = null): string
    {
        $mapper = new StatusMapper();
        $parts = [];
        foreach ($estados as $e) {
            $parts[] = sprintf('%d — %s', $e, $mapper->label((int) $e));
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
