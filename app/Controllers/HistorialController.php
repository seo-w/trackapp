<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\QueryLogRepository;

final class HistorialController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        /** @var list<array<string, mixed>> $logs */
        $logs = [];
        $dbUnavailable = false;

        try {
            $repo = new QueryLogRepository(db()->pdo());
            $logs = $repo->latest(150);
        } catch (\Throwable) {
            $dbUnavailable = true;
        }

        $this->view('historial', [
            'title' => 'Historial',
            'heading' => 'Historial de consultas',
            'subtitle' => 'Registro de las consultas masivas a Merkaweb desde la pantalla de órdenes: estados pedidos, cantidad de resultados normalizados y si todas las llamadas al API terminaron correctamente.',
            'logs' => $logs,
            'dbUnavailable' => $dbUnavailable,
        ]);
    }
}
