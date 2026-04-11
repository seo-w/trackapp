<?php

declare(strict_types=1);

/** @var string|null $error */
/** @var list<array<string, mixed>> $months */
/** @var list<string> $apiWarnings */

?>
<div class="container track-page-header">
    <h1 class="track-page-title h2 mb-2">Estadísticas y Finanzas</h1>
    <p class="track-page-lead mb-0">Rendimiento mensual de tus ventas, costos y entregas (Dropshipping).</p>
</div>

<div class="container pb-5">

    <style>
        :root {
            --track-stats-accent: #00ffff;
            --track-stats-glow: rgba(0, 255, 255, 0.2);
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
        }

        /* Sidebar Navigation Styles */
        .sidebar-stats-wrapper {
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .sidebar-stats-card {
            background: var(--track-surface);
            backdrop-filter: var(--track-blur);
            -webkit-backdrop-filter: var(--track-blur);
            border: 1px solid var(--track-border);
            border-radius: 1.5rem;
            padding: 1.5rem;
        }

        .nav-stats-category {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--track-primary);
            opacity: 0.7;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            padding-left: 0.5rem;
        }

        .nav-stats-category:first-child {
            margin-top: 0;
        }

        .nav-pills-stats-vertical .nav-link {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--track-muted);
            border-radius: 12px;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
            border: 1px solid transparent;
            text-align: left;
        }

        .nav-pills-stats-vertical .nav-link i {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .nav-pills-stats-vertical .nav-link:hover {
            background: rgba(var(--track-primary-rgb, 0, 255, 255), 0.05);
            color: var(--track-primary);
            border-color: rgba(var(--track-primary-rgb, 0, 255, 255), 0.1);
        }

        .nav-pills-stats-vertical .nav-link.active {
            background: rgba(var(--track-primary-rgb, 0, 255, 255), 0.1) !important;
            color: var(--track-primary) !important;
            font-weight: 700;
            border-color: rgba(var(--track-primary-rgb, 0, 255, 255), 0.3);
            box-shadow: 0 0 15px rgba(var(--track-primary-rgb, 0, 255, 255), 0.05);
        }

        /* Mobile Quick Nav FAB */
        .mobile-stats-nav-trigger {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1050;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--track-primary);
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 30px rgba(var(--track-primary-rgb, 0, 255, 255), 0.4);
            border: none;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .mobile-stats-nav-trigger:active {
            transform: scale(0.9);
        }

        /* Theme-specific Overrides */
        [data-theme="light"] .sidebar-stats-card {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
        }

        [data-theme="light"] .nav-pills-stats-vertical .nav-link.active {
            background: var(--track-primary) !important;
            color: white !important;
        }

        .stats-card-premium {
            background: var(--track-surface);
            backdrop-filter: blur(10px);
            border: 1px solid var(--track-border);
            border-radius: 20px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .stats-card-premium:hover {
            transform: translateY(-5px);
            border-color: rgba(var(--track-primary-rgb, 0, 255, 255), 0.3);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .stats-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        /* Alert Enhancements for Dark Mode */
        [data-theme="dark"] .alert-danger {
            background: rgba(255, 68, 68, 0.1) !important;
            border: 1px solid rgba(255, 68, 68, 0.3) !important;
            color: #ff8888 !important;
        }

        [data-theme="dark"] .alert-warning {
            background: rgba(255, 184, 0, 0.1) !important;
            border: 1px solid rgba(255, 184, 0, 0.3) !important;
            color: #ffcc00 !important;
        }
    </style>

    <div x-data="{ 
    activeTab: '<?= $activeTab ?>',
    // Mapeo interno para resaltar ítems si la pestaña tiene sub-secciones
    activeSection: 'section-<?= $activeTab ?>'
}">
        <!-- Mobile Trigger (FAB) -->
        <button class="mobile-stats-nav-trigger d-lg-none" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#statsMobileNav" aria-controls="statsMobileNav">
            <i class="bi bi-list-nested fs-4"></i>
        </button>

        <div class="row g-4 mt-1 mb-5 pb-5">
            <!-- Sidebar Navigation (Desktop) -->
            <aside class="col-lg-3 d-none d-lg-block">
                <div class="sidebar-stats-wrapper">
                    <div class="sidebar-stats-card shadow-sm">
                        <div class="nav flex-column nav-pills-stats-vertical" id="v-pills-tab" role="tablist"
                            aria-orientation="vertical">

                            <div class="nav-stats-category">Resumen</div>
                            <a class="nav-link" href="/estadisticas?tab=consolidado"
                                :class="{ 'active': activeTab === 'consolidado' }">
                                <i class="bi bi-globe2"></i> Consolidado Global
                            </a>

                            <div class="nav-stats-category">Operación</div>
                            <a class="nav-link" href="/estadisticas?tab=logistica"
                                :class="{ 'active': activeTab === 'logistica' }">
                                <i class="bi bi-truck"></i> Logística y Courier
                            </a>
                            <a class="nav-link" href="/estadisticas?tab=geografia"
                                :class="{ 'active': activeTab === 'geografia' }">
                                <i class="bi bi-geo-fill"></i> Análisis Geográfico
                            </a>
                            <a class="nav-link" href="/estadisticas?tab=productos"
                                :class="{ 'active': activeTab === 'productos' }">
                                <i class="bi bi-box-seam"></i> Rendimiento SKUs
                            </a>

                            <div class="nav-stats-category">Económico</div>
                            <a class="nav-link" href="/estadisticas?tab=finanzas"
                                :class="{ 'active': activeTab === 'finanzas' }">
                                <i class="bi bi-cash-stack"></i> Finanzas y Pauta
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <!-- Main Content Area Column -->
            <div class="col-lg-9">



                <?php if (is_string($error) && $error !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                        <i class="bi bi-x-circle mt-1"></i>
                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($apiWarnings) && $apiWarnings !== []): ?>
                    <div class="alert alert-warning d-flex align-items-start gap-2" role="status">
                        <i class="bi bi-cloud-slash mt-1" aria-hidden="true"></i>
                        <div>
                            <strong>Atención:</strong> Algunas órdenes podrían faltar debido a errores del API.
                            <ul class="mb-0 mt-2 small">
                                <?php foreach ($apiWarnings as $w): ?>
                                    <li><?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!isset($hasData) || $hasData === false): ?>
                    <div class="stats-card-premium text-center p-5 border-dashed"
                        style="border: 2px dashed var(--track-border) !important; background: rgba(var(--track-primary-rgb), 0.02);">
                        <div class="mb-4">
                            <i class="bi bi-bar-chart fs-1"
                                style="color: var(--track-primary); filter: drop-shadow(0 0 10px var(--track-stats-glow));"></i>
                        </div>
                        <h4 class="fw-bold mb-2" style="color: var(--track-text);">Sin datos para mostrar</h4>
                        <p class="mb-0 mx-auto opacity-75" style="max-width: 500px; color: var(--track-muted);">No hay
                            información suficiente para generar estadísticas en este periodo. Asegúrate de tener órdenes
                            creadas y el API de Merkaweb configurado correctamente en tu perfil.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $labels = [
                        2 => 'Despachado',
                        3 => 'Entregado',
                        4 => 'Devuelto',
                        5 => 'Legalizado',
                    ];

                    if (!function_exists('stats_status_color')) {
                        function stats_status_color(int $code, string $default = ''): string
                        {
                            // Devolvemos estilos en línea (o variables css) para adaptarse mejor al cyber-theme transparentando el color
                            // 2=Despachado (Primary), 3=Entregado (Success), 4=Devuelto (Warning/Magenta), 5=Legalizado (Info/Teal)
                            switch ($code) {
                                case 2:
                                    return 'color: var(--track-primary) !important; text-shadow: 0 0 10px var(--track-accent-glow);';
                                case 3:
                                    return 'color: var(--track-success) !important; text-shadow: 0 0 10px rgba(32,201,151,0.2);';
                                case 4:
                                    return 'color: var(--track-warning) !important; text-shadow: 0 0 10px rgba(255,184,0,0.2);';
                                case 5:
                                    return 'color: var(--track-info) !important; text-shadow: 0 0 10px rgba(0,210,255,0.2);';
                                default:
                                    return $default;
                            }
                        }
                    }

                    // Formatear pesos
                    $fmt = function ($val) {
                        return '$' . number_format((float) $val, 0, ',', '.');
                    };

                    // Clase dinámica para el profit
                    $profitClass = function ($val, $bgOnly = false) {
                        $val = (float) $val;
                        if ($val > 0)
                            return $bgOnly ? 'bg-success' : 'bg-success text-white';
                        if ($val < 0)
                            return $bgOnly ? 'bg-danger' : 'bg-danger text-white';
                        return $bgOnly ? 'bg-warning' : 'bg-warning text-dark';
                    };
                    ?>

                    <?php if (isset($detailedStats) && $detailedStats !== null): ?>
                        <?php if ($activeTab === 'consolidado'): ?>
                            <!-- Vista Global -->
                            <div class="row g-4 mb-5" id="section-consolidado">
                                <div class="col-12">
                                    <div class="card track-card track-card--emphasis border-0">
                                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="track-icon-circle" style="width:2rem; height:2rem; font-size:1rem;"><i
                                                        class="bi bi-cpu"></i></span>
                                                <h5 class="card-title fw-bold mb-0 text-primary uppercase"
                                                    style="letter-spacing:1px;">Logística Centralizada</h5>
                                            </div>
                                        </div>
                                        <div class="card-body px-4 pb-4">
                                            <div class="row g-3">
                                                <?php foreach ($labels as $code => $label): ?>
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-3 rounded-3"
                                                            style="background: var(--track-card-bg); border: 1px solid var(--track-border);">
                                                            <div class="mb-2 fw-bold text-uppercase opacity-75"
                                                                style="font-size: 0.65rem; letter-spacing: 1px; color: var(--track-table-head);">
                                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                                                            <div class="h2 mb-0 fw-bold"
                                                                style="<?= stats_status_color((int) $code, 'color: var(--track-text);') ?>">
                                                                <?= (int) ($detailedStats['global'][$code] ?? 0) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div
                                                class="mt-4 pt-3 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                                                <span class="text-muted small text-uppercase fw-bold">Nivel de Operación
                                                    Consolidado:</span>
                                                <span class="h3 mb-0 fw-bold"
                                                    style="color: var(--track-text);"><?= (int) $detailedStats['grandTotal'] ?>
                                                    <small class="text-muted fs-6">unidades</small></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($activeTab === 'logistica'): ?>
                            <!-- Resumen Logístico Mensual -->
                            <div class="card track-card border-0 mb-5 overflow-hidden" id="section-logistica">
                                <div class="card-header bg-transparent border-0 pt-4 px-4">
                                    <h5 class="card-title fw-bold mb-0 text-primary"><i class="bi bi-calendar3 me-2"></i>Telemetría
                                        Mensual</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0 table-borderless"
                                            style="--bs-table-bg: transparent; --bs-table-color: var(--track-table-color); --bs-table-hover-bg: var(--track-hover-bg);">
                                            <thead style="border-bottom: 1px solid var(--track-border);">
                                                <tr>
                                                    <th class="px-4 py-3 text-uppercase fw-bold"
                                                        style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                        Mes / Periodo</th>
                                                    <?php foreach ($labels as $label): ?>
                                                        <th class="text-center py-3 text-uppercase fw-bold"
                                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                                                    <?php endforeach; ?>
                                                    <th class="text-center px-4 py-3 text-uppercase fw-bold"
                                                        style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                        Totales</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (($detailedStats['months'] ?? []) as $m): ?>
                                                    <tr>
                                                        <td class="px-4 py-3 fw-bold" style="color: var(--track-text);">
                                                            <?= htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <?php foreach ($labels as $code => $label): ?>
                                                            <td class="text-center py-3">
                                                                <?php $count = (int) ($m['stats'][$code] ?? 0); ?>
                                                                <span class="<?= $count > 0 ? 'fw-bold' : 'fw-medium' ?>"
                                                                    style="<?= $count > 0 ? stats_status_color((int) $code, 'color: var(--track-text);') : 'color: var(--track-text); opacity: 0.5;' ?>">
                                                                    <?= $count ?>
                                                                </span>
                                                            </td>
                                                        <?php endforeach; ?>
                                                        <td class="text-center px-4 py-3 fw-bold"
                                                            style="background: var(--track-hover-bg); color: var(--track-primary);">
                                                            <?= (int) $m['total'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Devoluciones por Transportadora -->
                            <div class="card track-card border-0 mb-5 pb-4">
                                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                                    <h5 class="card-title fw-bold mb-0 text-primary"><i
                                            class="bi bi-shield-exclamation me-2"></i>Devoluciones por Transportadora</h5>
                                    <p class="small mb-0 mt-1" style="color: var(--track-muted);">Eficiencia de entrega por operador
                                        logístico.</p>
                                </div>
                                <div class="card-body px-4 pb-3">
                                    <?php if (!empty($detailedStats['courierStats'] ?? [])): ?>
                                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                            <?php foreach ($detailedStats['courierStats'] as $courier => $cdata): ?>
                                                <div class="col">
                                                    <div class="p-3 rounded"
                                                        style="background: var(--track-card-bg); border: 1px solid var(--track-border);">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="fw-bold text-truncate"
                                                                style="color: var(--track-text); max-width: 70%;"><?= htmlspecialchars($courier, ENT_QUOTES, 'UTF-8') ?></span>
                                                            <span
                                                                class="badge rounded-pill <?= $cdata['pct'] > 15 ? 'text-danger' : 'text-success' ?> bg-opacity-10 border border-<?= $cdata['pct'] > 15 ? 'danger' : 'success' ?> border-opacity-20"><?= $cdata['pct'] ?>%
                                                                devol.</span>
                                                        </div>
                                                        <div class="progress mb-2" style="height: 4px; background: rgba(255,255,255,0.05);">
                                                            <div class="progress-bar <?= $cdata['pct'] > 15 ? 'bg-danger' : 'bg-success' ?>"
                                                                role="progressbar" style="width: <?= $cdata['pct'] ?>%"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.7rem;">
                                                            <span>Retornos: <strong
                                                                    style="color:var(--track-text);"><?= $cdata['returns'] ?></strong></span>
                                                            <span>Total: <strong
                                                                    style="color:var(--track-text);"><?= $cdata['total'] ?></strong></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4 opacity-50">
                                            <i class="bi bi-info-circle fs-4 mb-2"></i>
                                            <p class="small mb-0">No se detectaron transportadoras vinculadas a los pedidos de este
                                                periodo.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($activeTab === 'geografia'): ?>
                            <!-- Ciudades con Mayor Éxito de Entrega -->
                            <?php if (!empty($detailedStats['successByCity'])): ?>
                                <div class="card track-card border-0 shadow-sm mb-4" id="section-geografia">

                                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                                        <h5 class="card-title fw-bold mb-0" style="color: var(--track-text);"><i
                                                class="bi bi-star-fill me-2" style="color: var(--track-primary);"></i>Ciudades con Mayor
                                            Éxito de Entrega</h5>
                                        <p class="small mb-0 mt-1" style="color: var(--track-muted);">Ciudades con más de 3 pedidos,
                                            ordenadas por tasa de entrega efectiva.</p>
                                    </div>
                                    <div class="stats-grid">
                                        <?php
                                        $topSuccess = array_slice($detailedStats['successByCity'], 0, 8, true);
                                        foreach ($topSuccess as $city => $data):
                                            ?>
                                            <div class="card track-card p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <span class="fw-bold text-truncate"
                                                        style="color: var(--track-text); max-width: 70%;"><?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span
                                                        class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-20"><?= $data['pct'] ?>%</span>
                                                </div>
                                                <div class="progress mb-3"
                                                    style="height: 6px; background: rgba(var(--track-success-rgb, 32,201,151), 0.1);">
                                                    <div class="progress-bar bg-success shadow-glow" role="progressbar"
                                                        style="width: <?= $data['pct'] ?>%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                                                    <span>Entregados: <strong
                                                            style="color: var(--track-text);"><?= $data['delivered'] ?></strong></span>
                                                    <span>Total: <strong
                                                            style="color: var(--track-text);"><?= $data['total'] ?></strong></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Ciudades con más Devoluciones -->
                            <?php if (!empty($detailedStats['returnsByCity'])): ?>
                                <div class="card track-card border-0 shadow-sm mb-4">

                                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                                        <h5 class="card-title fw-bold mb-0" style="color: var(--track-text);"><i
                                                class="bi bi-geo-fill me-2" style="color: var(--track-danger);"></i>Ciudades con más
                                            Devoluciones</h5>
                                        <p class="small mb-0 mt-1" style="color: var(--track-muted);">Top de lugares donde se presentan
                                            la mayor cantidad de retornos.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                            <?php
                                            $topCities = array_slice($detailedStats['returnsByCity'], 0, 6, true);
                                            foreach ($topCities as $city => $count):
                                                ?>
                                                <div class="col">
                                                    <div class="d-flex justify-content-between align-items-center p-3 rounded"
                                                        style="background: var(--track-card-bg); border: 1px solid var(--track-border); overflow: hidden;">
                                                        <div class="d-flex align-items-center flex-grow-1 overflow-hidden me-2">
                                                            <div class="rounded-circle p-2 me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                                                style="width: 32px; height: 32px; background: rgba(var(--track-danger-rgb, 255, 36, 228), 0.1);">
                                                                <i class="bi bi-pin-map-fill small" style="color: var(--track-danger);"
                                                                    aria-hidden="true"></i>
                                                            </div>
                                                            <span class="fw-semibold text-truncate text-uppercase"
                                                                style="color: var(--track-text); font-size: 0.8rem; letter-spacing: 0.5px;"><?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?></span>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <span class="badge rounded-pill px-2 py-1"
                                                                style="background: rgba(var(--track-danger-rgb, 255, 36, 228), 0.2); color: var(--track-danger); border: 1px solid var(--track-danger); min-width: 30px;"><?= $count ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($activeTab === 'productos'): ?>
                            <!-- Rendimiento por Producto -->
                            <?php if (!empty($detailedStats['productStats'])): ?>
                                <?php
                                $pItems = [];
                                foreach (($detailedStats['productStats'] ?? []) as $pid => $pStat) {
                                    $pData = $products[(string) $pid] ?? null;
                                    $rawName = (string) ($pData['name'] ?? '');
                                    // Safe string check for "Producto ID"
                                    $isPlaceholder = (substr($rawName, 0, 11) === 'Producto ID');

                                    $pName = ($pData && !empty($rawName) && !$isPlaceholder) ? $rawName : ($pStat['fallback_name'] ?? 'S/N');
                                    $pImg = (string) ($pData['image_url'] ?? '');
                                    $pWarehouse = (string) ($pData['warehouse'] ?? 'Info. del pedido');
                                    $delivered = (int) ($pStat['delivered'] ?? 0);
                                    $total = (int) ($pStat['total'] ?? 0);
                                    $ingr = (float) ($pStat['ingresos'] ?? 0);
                                    $cst = (float) ($pStat['costos'] ?? 0);
                                    $margen = $delivered > 0 ? ($ingr - $cst) / $delivered : 0;
                                    $pct = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;

                                    $pItems[] = [
                                        'id' => (string) $pid,
                                        'name' => (string) $pName,
                                        'image' => (is_string($pImg) && !empty($pImg)) ? 'https://images.weserv.nl/?url=' . urlencode($pImg) . '&w=100&h=100&fit=cover&output=webp' : null,
                                        'warehouse' => (string) $pWarehouse,
                                        'total' => $total,
                                        'delivered' => $delivered,
                                        'ingresos' => $ingr,
                                        'margen' => $margen,
                                        'pct' => $pct
                                    ];
                                }
                                $jsonItems = json_encode($pItems, JSON_UNESCAPED_UNICODE);
                                ?>
                                <div class="card track-card border-0 shadow-sm mb-5" id="section-productos" x-data="{ 
                        items: <?= htmlspecialchars($jsonItems ?: '[]', ENT_QUOTES, 'UTF-8') ?>,
                        sKey: 'total',
                        sDir: 'desc',
                        get sorted() {
                            return [...this.items].sort((a, b) => {
                                let va = a[this.sKey], vb = b[this.sKey];
                                if (va < vb) return this.sDir === 'asc' ? -1 : 1;
                                if (va > vb) return this.sDir === 'asc' ? 1 : -1;
                                return 0;
                            });
                        },
                        sortBy(k) {
                            if (this.sKey === k) this.sDir = this.sDir === 'asc' ? 'desc' : 'asc';
                            else { this.sKey = k; this.sDir = 'desc'; }
                        },
                        fmt(v) { return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v); }
                     }">

                                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                        <h5 class="card-title fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-2"></i>Nodos de
                                            Producto</h5>
                                        <p class="text-muted small mb-0 mt-1 uppercase fw-bold" style="letter-spacing: 1px;">Rendimiento
                                            de Inventario</p>
                                    </div>
                                    <div class="card-body p-0 mt-3">
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0 table-borderless track-table"
                                                style="--bs-table-bg: transparent; --bs-table-color: var(--track-table-color); --bs-table-hover-bg: var(--track-hover-bg);">
                                                <thead style="border-bottom: 1px solid var(--track-border);">
                                                    <tr>
                                                        <th @click="sortBy('name')" class="px-4 cursor-pointer text-uppercase fw-bold"
                                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                            Producto <i class="bi"
                                                                :class="sKey==='name'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i>
                                                        </th>
                                                        <th @click="sortBy('total')"
                                                            class="text-center cursor-pointer text-uppercase fw-bold"
                                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                            Total Órdenes <i class="bi"
                                                                :class="sKey==='total'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i>
                                                        </th>
                                                        <th @click="sortBy('pct')"
                                                            class="text-center cursor-pointer text-uppercase fw-bold"
                                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                            Éxito (%) <i class="bi"
                                                                :class="sKey==='pct'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i>
                                                        </th>
                                                        <th @click="sortBy('ingresos')"
                                                            class="text-end cursor-pointer text-uppercase fw-bold"
                                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                            Ingresos Brutos <i class="bi"
                                                                :class="sKey==='ingresos'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i>
                                                        </th>
                                                        <th @click="sortBy('margen')"
                                                            class="text-end px-4 cursor-pointer text-uppercase fw-bold"
                                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--track-table-head);">
                                                            Margen / Unidad <i class="bi"
                                                                :class="sKey==='margen'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="p in sorted" :key="p.id">
                                                        <tr :class="p.pct < 40 ? 'semaphore-danger' : (p.pct > 70 ? 'semaphore-success' : 'semaphore-warning')"
                                                            @mouseover="$el.style.background='var(--track-hover-bg)'"
                                                            @mouseleave="$el.style.background='transparent'">
                                                            <td class="px-4 py-3">
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <template x-if="p.image">
                                                                        <img :src="p.image" class="rounded border shadow-sm"
                                                                            style="width: 45px; height: 45px; object-fit: cover;">
                                                                    </template>
                                                                    <template x-if="!p.image">
                                                                        <div class="rounded d-flex align-items-center justify-content-center"
                                                                            style="width: 45px; height: 45px; background: rgba(255,255,255,0.05); border: 1px solid var(--track-border);">
                                                                            <i class="bi bi-box fs-4"
                                                                                style="color: var(--track-table-head);"></i></div>
                                                                    </template>
                                                                    <div>
                                                                        <div class="fw-bold lh-sm" style="color: var(--track-text);"
                                                                            x-text="p.name"></div>
                                                                        <div class="text-muted" style="font-size: 0.7rem;"
                                                                            x-text="p.warehouse"></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center fw-medium" x-text="p.total"></td>
                                                            <td class="text-center">
                                                                <div class="d-flex flex-column align-items-center gap-1">
                                                                    <span class="fw-bold"
                                                                        :class="p.pct > 70 ? 'text-success' : (p.pct < 40 ? 'text-danger' : 'text-warning')"
                                                                        x-text="p.pct + '%'"></span>
                                                                    <div class="progress w-75" style="height: 4px;">
                                                                        <div class="progress-bar"
                                                                            :class="p.pct > 70 ? 'bg-success' : (p.pct < 40 ? 'bg-danger' : 'bg-warning')"
                                                                            :style="'width: ' + p.pct + '%'"></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-end fw-bold" style="color: var(--track-text);"
                                                                x-text="fmt(p.ingresos)"></td>
                                                            <td class="text-end px-4 fw-bold"
                                                                :class="p.margen > 0 ? 'text-success' : 'text-danger'"
                                                                x-text="fmt(p.margen)"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($activeTab === 'finanzas'): ?>
                            <!-- Panel de Resumen Financiero Consolidado (Global) -->
                            <?php if (isset($globalFinancials) && $globalFinancials !== null): ?>
                                <div class="row g-4 mb-5" id="section-finanzas">
                                    <div class="col-12">
                                        <div class="card track-card track-card--emphasis border-0 overflow-hidden"
                                            style="background: var(--track-surface);">
                                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                                <h5 class="card-title fw-bold mb-0 text-primary"><i
                                                        class="bi bi-graph-up-arrow me-2"></i>Frecuencia Financiera Global</h5>
                                                <p class="text-muted small mb-0 mt-1 uppercase fw-bold" style="letter-spacing: 1px;">
                                                    Balance de Red Consolidado</p>
                                            </div>
                                            <div class="card-body p-4">
                                                <!-- Fila Principal (Volumen y Profit) -->
                                                <div class="row g-3 mb-4">
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-3 rounded-3 shadow-sm border-start border-4 border-success"
                                                            style="background: var(--track-surface-high);">
                                                            <div class="text-muted small fw-bold text-uppercase mb-1">Ingresos de Red
                                                            </div>
                                                            <div class="h4 mb-0 fw-bold" style="color: var(--track-text);">
                                                                <?= $fmt($globalFinancials['ingresos_brutos']) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-3 rounded-3 shadow-sm border-start border-4 border-primary h-100"
                                                            style="background: var(--track-surface-high);">
                                                            <div class="text-muted small fw-bold text-uppercase mb-1">Inyección Pauta
                                                            </div>
                                                            <div class="h4 mb-0 fw-bold" style="color: var(--track-text);">
                                                                <?= $fmt($globalFinancials['pauta']) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-3 rounded-3 shadow-sm border-start border-4 border-danger h-100"
                                                            style="background: var(--track-surface-high);">
                                                            <div class="text-muted small fw-bold text-uppercase mb-1">Fuga por
                                                                Devolución</div>
                                                            <div class="h4 mb-0 fw-bold" style="color: var(--track-text);">
                                                                <?= $fmt($globalFinancials['costos_devolucion']) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-3 rounded-3 shadow-sm border-start border-4 border-primary h-100 <?= $profitClass($globalFinancials['profit']) ?>"
                                                            style="box-shadow: 0 0 20px rgba(0,255,255,0.1);">
                                                            <div class="small fw-bold text-uppercase mb-1 opacity-75">Profit Real Neto
                                                            </div>
                                                            <div class="h3 mb-0 fw-bold" style="color: inherit !important;">
                                                                <?= $fmt($globalFinancials['profit']) ?></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Fila Métricas de Eficiencia -->
                                                <div class="row g-3">
                                                    <div class="col-6 col-lg-4">
                                                        <div
                                                            class="bg-primary bg-opacity-10 p-3 rounded-3 border border-primary border-opacity-20 h-100">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <div class="text-primary small fw-bold text-uppercase"
                                                                        style="font-size: 0.7rem; letter-spacing:1px;">Multiplicador
                                                                        ROAS</div>
                                                                    <div class="h4 mb-0 fw-bold" style="color: var(--track-text);">
                                                                        <?= $globalFinancials['roas'] ?>x</div>
                                                                </div>
                                                                <div class="h2 mb-0 text-primary opacity-25"><i
                                                                        class="bi bi-pie-chart"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-lg-4">
                                                        <div
                                                            class="bg-secondary bg-opacity-10 p-3 rounded-3 border border-secondary border-opacity-20 h-100">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <div class="text-secondary small fw-bold text-uppercase"
                                                                        style="font-size: 0.7rem; letter-spacing:1px;">CPA Promedio
                                                                    </div>
                                                                    <div class="h4 mb-0 fw-bold" style="color: var(--track-text);">
                                                                        <?= $fmt($globalFinancials['cpa']) ?></div>
                                                                </div>
                                                                <div class="h2 mb-0 text-secondary opacity-25"><i
                                                                        class="bi bi-person-check"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-lg-4">
                                                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 border border-primary border-opacity-20 h-100"
                                                            style="background: linear-gradient(to right, rgba(var(--track-primary-rgb, 0,255,255), 0.1), transparent);">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <div class="text-primary small fw-bold text-uppercase"
                                                                        style="font-size: 0.7rem; letter-spacing:1px;">Margen Neto
                                                                        Unitario</div>
                                                                    <div class="h4 mb-0 fw-bold" style="color: var(--track-text);">
                                                                        <?= $fmt($globalFinancials['margen_unidad']) ?></div>
                                                                </div>
                                                                <div class="h2 mb-0 text-primary opacity-25"><i class="bi bi-gem"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <h5 class="fw-bold mb-3 px-1" id="section-monthly-details"><i
                                    class="bi bi-cash-stack me-2 text-success"></i>Detalle Financiero por Mes</h5>

                            <div class="accordion" id="statsAccordion">
                                <?php foreach (($months ?? []) as $idx => $m): ?>
                                    <?php
                                    $show = $idx === 0 ? 'show' : '';
                                    $collapsed = $idx === 0 ? '' : 'collapsed';
                                    ?>
                                    <div class="accordion-item mb-4 border-0 rounded-4 shadow-lg overflow-hidden"
                                        style="background: var(--track-surface); backdrop-filter: var(--track-blur); border: 1px solid var(--track-border) !important;">
                                        <h2 class="accordion-header" id="heading-<?= $m['mes'] ?>">
                                            <button class="accordion-button fw-bold fs-5 <?= $collapsed ?>" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapse-<?= $m['mes'] ?>"
                                                aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>"
                                                aria-controls="collapse-<?= $m['mes'] ?>"
                                                style="background: transparent; color: var(--track-primary);">
                                                <i class="bi bi-calendar-check me-2"></i> Registro Temporal:
                                                <?= htmlspecialchars($m['mes'], ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?= $m['mes'] ?>" class="accordion-collapse collapse <?= $show ?>"
                                            aria-labelledby="heading-<?= $m['mes'] ?>">
                                            <div class="accordion-body p-4">
                                                <!-- Tarjetas KPIs Principales -->
                                                <div class="row g-3 mb-4">
                                                    <div class="col-sm-6 col-lg-3">
                                                        <div class="card border-0 shadow-sm <?= $profitClass($m['profit']) ?>">
                                                            <div class="card-body">
                                                                <div class="small text-uppercase fw-semibold mb-1 opacity-75">Profit
                                                                    Neto</div>
                                                                <h4 class="mb-0 fw-bold" style="color: inherit !important;">
                                                                    <?= $fmt($m['profit']) ?></h4>
                                                                <div class="small mt-2 opacity-75"><i
                                                                        class="bi bi-check-circle-fill me-1"></i>
                                                                    <?= $m['entregadas'] ?> unidades legalizadas</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 col-lg-3">
                                                        <div class="card border-0 shadow-sm"
                                                            style="background: var(--track-surface-high); border: 1px solid var(--track-border) !important;">
                                                            <div class="card-body">
                                                                <div class="small text-uppercase fw-bold mb-2 opacity-75"
                                                                    style="letter-spacing:1px; font-size:0.65rem; color: var(--track-text);">
                                                                    Eficiencia Publicitaria</div>
                                                                <div class="d-flex align-items-baseline gap-1">
                                                                    <h4 class="mb-0 fw-bold" style="color: var(--track-info);">
                                                                        <?= $m['roas'] ?><span
                                                                            style="font-size: 0.9rem; opacity: 0.8;">x</span></h4>
                                                                    <span class="small"
                                                                        style="color: var(--track-muted); margin-left: 2px;">ROAS</span>
                                                                </div>
                                                                <div class="small mt-2" style="color: var(--track-muted);">CPA: <strong
                                                                        style="color: var(--track-text);"><?= $fmt($m['cpa']) ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 col-lg-3">
                                                        <div class="card border-0 shadow-sm"
                                                            style="background: rgba(var(--track-info-rgb, 0, 210, 255), 0.05); border: 1px solid rgba(var(--track-info-rgb, 0, 210, 255), 0.2) !important;">
                                                            <div class="card-body">
                                                                <div class="small text-uppercase fw-semibold mb-1"
                                                                    style="color: var(--track-info) !important;">Margen por Unidad</div>
                                                                <h4 class="mb-0" style="color: var(--track-text);">
                                                                    <?= $fmt($m['margen_unidad']) ?></h4>
                                                                <div class="small text-muted mt-2">Ganancia libre por cada entrega</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 col-lg-3">
                                                        <div class="card border-0 shadow-sm"
                                                            style="background: rgba(var(--track-warning-rgb, 255, 184, 0), 0.05); border: 1px solid rgba(var(--track-warning-rgb, 255, 184, 0), 0.2) !important;">
                                                            <div class="card-body">
                                                                <div class="small text-uppercase fw-semibold mb-1"
                                                                    style="color: var(--track-warning);">Logística</div>
                                                                <h4 class="mb-0" style="color: var(--track-text);">
                                                                    <?= $m['efectividad_pct'] ?>%</h4>
                                                                <div class="small text-muted mt-2"><i
                                                                        class="bi bi-arrow-return-left me-1"></i>
                                                                    <?= $m['devolucion_pct'] ?>% devoluciones</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row g-4">
                                                    <!-- Desglose de Operación -->
                                                    <div class="col-lg-6">
                                                        <div class="card border-0 shadow-sm h-100"
                                                            style="background: var(--track-card-alt); border: 1px solid var(--track-border) !important;">
                                                            <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                                                                <h6 class="mb-0 fw-bold" style="color: var(--track-text);"><i
                                                                        class="bi bi-truck me-2"
                                                                        style="color: var(--track-primary);"></i>Métricas de Despachos
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <ul class="list-group list-group-flush bg-transparent">
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent border-bottom border-secondary border-opacity-25"
                                                                        style="color: var(--track-text);">
                                                                        <span class="text-secondary">Total Despachado</span>
                                                                        <span
                                                                            class="badge bg-secondary rounded-pill"><?= $m['despachadas'] ?></span>
                                                                    </li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent border-bottom border-secondary border-opacity-25"
                                                                        style="color: var(--track-text);">
                                                                        <span class="text-muted">Entregados / Legalizados</span>
                                                                        <span
                                                                            class="badge bg-success rounded-pill"><?= $m['entregadas'] ?></span>
                                                                    </li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent border-bottom border-secondary border-opacity-25"
                                                                        style="color: var(--track-text);">
                                                                        <span class="text-muted">Devueltos</span>
                                                                        <span class="badge rounded-pill"
                                                                            style="color: white; background: var(--track-danger);"><?= $m['devueltas'] ?></span>
                                                                    </li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0 pb-0 bg-transparent"
                                                                        style="color: var(--track-text);">
                                                                        <span class="text-muted">En ruta / Tránsito</span>
                                                                        <span class="badge rounded-pill text-dark"
                                                                            style="background: var(--track-primary);"><?= $m['en_proceso'] ?></span>
                                                                    </li>
                                                                </ul>
                                                                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                                                                    <div class="d-flex justify-content-between mb-1">
                                                                        <span class="small fw-semibold text-secondary">Efectividad de
                                                                            Entrega</span>
                                                                        <span
                                                                            class="small fw-bold text-success"><?= $m['efectividad_pct'] ?>%</span>
                                                                    </div>
                                                                    <div class="progress" style="height: 10px;">
                                                                        <div class="progress-bar bg-success" role="progressbar"
                                                                            style="width: <?= $m['efectividad_pct'] ?>%"
                                                                            aria-valuenow="<?= $m['efectividad_pct'] ?>"
                                                                            aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Finanzas y Pauta -->
                                                    <div class="col-lg-6">
                                                        <div class="card border-0 shadow-sm h-100"
                                                            style="background: var(--track-card-alt); border: 1px solid var(--track-border) !important;">
                                                            <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                                                                <h6 class="mb-0 fw-bold" style="color: var(--track-text);"><i
                                                                        class="bi bi-wallet2 me-2"
                                                                        style="color: var(--track-primary);"></i>Desglose Financiero
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-sm table-borderless mb-3"
                                                                    style="--bs-table-bg: transparent; --bs-table-color: var(--track-table-color); color: var(--track-text);">
                                                                    <tbody style="border-color: var(--track-border);">
                                                                        <tr>
                                                                            <td style="color: var(--track-table-head);">+ Ventas Brutas
                                                                            </td>
                                                                            <td class="text-end fw-medium">
                                                                                <?= $fmt($m['ingresos_brutos']) ?></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="color: var(--track-table-head);">- Costo de
                                                                                Productos</td>
                                                                            <td class="text-end fw-medium"
                                                                                style="color: var(--track-danger);">
                                                                                <?= $fmt($m['costos_producto']) ?></td>
                                                                        </tr>
                                                                        <tr class="border-bottom"
                                                                            style="border-color: var(--track-border) !important;">
                                                                            <td class="pb-2" style="color: var(--track-table-head);">-
                                                                                Costo Envíos Exitosos</td>
                                                                            <td class="text-end fw-medium pb-2"
                                                                                style="color: var(--track-danger);">
                                                                                <?= $fmt($m['costos_envio_exito']) ?></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="pt-2 fw-bold"
                                                                                style="color: var(--track-primary);">= Utilidad Bruta
                                                                            </td>
                                                                            <td class="pt-2 text-end fw-bold"
                                                                                style="color: var(--track-primary);">
                                                                                <?= $fmt($m['utilidad_bruta']) ?></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="color: var(--track-table-head);">- Costo
                                                                                Devoluciones</td>
                                                                            <td class="text-end fw-medium"
                                                                                style="color: var(--track-danger);">
                                                                                <?= $fmt($m['costos_devolucion']) ?></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>

                                                                <form action="/estadisticas/pauta" method="POST"
                                                                    class="mt-4 p-3 rounded"
                                                                    style="background: var(--track-hover-bg); border: 1px solid var(--track-border);">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="mes"
                                                                        value="<?= htmlspecialchars($m['mes'], ENT_QUOTES, 'UTF-8') ?>">

                                                                    <label for="pauta_<?= $m['mes'] ?>"
                                                                        class="form-label small fw-bold mb-2"
                                                                        style="color: var(--track-text);">Gasto en Pauta
                                                                        (Publicidad)</label>
                                                                    <div class="input-group input-group-sm mb-2"
                                                                        style="border: 1px solid rgba(var(--track-primary-rgb, 0,255,255), 0.3); border-radius: 6px; overflow: hidden;">
                                                                        <span class="input-group-text border-0"
                                                                            style="background: rgba(var(--track-primary-rgb, 0,255,255), 0.1); color: var(--track-primary);">$</span>
                                                                        <input type="number" step="0.01" min="0" name="amount"
                                                                            id="pauta_<?= $m['mes'] ?>" class="form-control border-0"
                                                                            style="background: rgba(0,0,0,0.1); color: var(--track-text);"
                                                                            value="<?= htmlspecialchars((string) $m['pauta'], ENT_QUOTES, 'UTF-8') ?>"
                                                                            placeholder="Ej: 500000">
                                                                        <button class="btn fw-bold" type="submit"
                                                                            style="background: rgba(var(--track-primary-rgb, 0,255,255), 0.15); color: var(--track-primary); border-left: 1px solid rgba(var(--track-primary-rgb, 0,255,255), 0.3);">Guardar
                                                                            Pauta</button>
                                                                    </div>
                                                                    <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;"><i
                                                                            class="bi bi-info-circle me-1"></i> El valor de la pauta se
                                                                        resta de tu utilidad para calcular el Profit Neto mostrado
                                                                        arriba.</p>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?> <!-- Closes detailedStats (263) -->
                <?php endif; ?> <!-- Closes else (225) -->
                <!-- Spacer to prevent footer overlap -->
                <div class="py-5 my-5 d-none d-lg-block"></div>
                <div class="py-3 d-lg-none"></div>

            </div> <!-- Close Column lg-9 -->
        </div> <!-- Close Row -->

        <!-- Mobile Navigation (Offcanvas) -->
        <div class="offcanvas offcanvas-start track-surface" tabindex="-1" id="statsMobileNav"
            aria-labelledby="statsMobileNavLabel"
            style="background: var(--track-surface); backdrop-filter: var(--track-blur); border-right: 1px solid var(--track-border);">
            <div class="offcanvas-header border-bottom border-secondary border-opacity-10">
                <h5 class="offcanvas-title fw-bold text-primary" id="statsMobileNavLabel"><i
                        class="bi bi-bar-chart-fill me-2"></i>Navegación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="nav flex-column nav-pills-stats-vertical">
                    <div class="nav-stats-category">Resumen</div>
                    <a class="nav-link" href="/estadisticas?tab=consolidado"
                        :class="{ 'active': activeTab === 'consolidado' }">
                        <i class="bi bi-globe2"></i> Consolidado Global
                    </a>

                    <div class="nav-stats-category">Operación</div>
                    <a class="nav-link" href="/estadisticas?tab=logistica"
                        :class="{ 'active': activeTab === 'logistica' }">
                        <i class="bi bi-truck"></i> Logística y Courier
                    </a>
                    <a class="nav-link" href="/estadisticas?tab=geografia"
                        :class="{ 'active': activeTab === 'geografia' }">
                        <i class="bi bi-geo-fill"></i> Análisis Geográfico
                    </a>
                    <a class="nav-link" href="/estadisticas?tab=productos"
                        :class="{ 'active': activeTab === 'productos' }">
                        <i class="bi bi-box-seam"></i> Rendimiento SKUs
                    </a>

                    <div class="nav-stats-category">Económico</div>
                    <a class="nav-link" href="/estadisticas?tab=finanzas"
                        :class="{ 'active': activeTab === 'finanzas' }">
                        <i class="bi bi-cash-stack"></i> Finanzas y Pauta
                    </a>
                </div>
            </div>
        </div>
    </div> <!-- End of Alpine x-data context -->
</div> <!-- Container -->