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
    html { scroll-behavior: smooth; scroll-padding-top: 100px; }
    .sticky-nav-stats {
        position: sticky;
        top: 72px; 
        z-index: 1020;
        background: var(--track-surface);
        backdrop-filter: var(--track-blur);
        -webkit-backdrop-filter: var(--track-blur);
        border-bottom: 1px solid var(--track-border);
        margin-bottom: var(--track-spacer-4);
        padding: var(--track-spacer-2) 0;
    }
    .stats-grid {
        display: grid;
        gap: var(--track-spacer-4);
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: var(--track-spacer-3);
        }
    }
</style>

<div x-data="{ 
    activeSection: 'section-logistics-global',
    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.activeSection = entry.target.id;
                }
            });
        }, { threshold: 0.2, rootMargin: '-10% 0px -70% 0px' });

        document.querySelectorAll('div[id^=\'section-\']').forEach(section => {
            observer.observe(section);
        });
    }
}">
    <nav class="sticky-nav-stats mb-5 pb-3">
        <div class="container overflow-auto px-1 no-scrollbar">
            <div class="nav nav-pills nav-pills-stats flex-nowrap gap-1">
                <a class="nav-link" href="#section-logistics-global" :class="{ 'active': activeSection === 'section-logistics-global' }"><i class="bi bi-globe2 me-1"></i> Global</a>
                <a class="nav-link" href="#section-monthly-logistics" :class="{ 'active': activeSection === 'section-monthly-logistics' }"><i class="bi bi-calendar3 me-1"></i> Mensual Logística</a>
                <a class="nav-link" href="#section-success-cities" :class="{ 'active': activeSection === 'section-success-cities' }"><i class="bi bi-star-fill me-1"></i> Éxito Ciudades</a>
                <a class="nav-link" href="#section-return-cities" :class="{ 'active': activeSection === 'section-return-cities' }"><i class="bi bi-geo-fill me-1"></i> Devoluciones</a>
                <a class="nav-link" href="#section-products" :class="{ 'active': activeSection === 'section-products' }"><i class="bi bi-box-seam me-1"></i> Productos</a>
                <a class="nav-link" href="#section-financial-summary" :class="{ 'active': activeSection === 'section-financial-summary' }"><i class="bi bi-graph-up-arrow me-1"></i> Finanzas Global</a>
                <a class="nav-link" href="#section-monthly-details" :class="{ 'active': activeSection === 'section-monthly-details' }"><i class="bi bi-cash-stack me-1"></i> Detalle Mensual</a>
            </div>
        </div>
    </nav>


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

    <?php if (!isset($months) || $months === []): ?>
        <div class="alert alert-light border text-center p-5 text-secondary">
            <i class="bi bi-bar-chart fs-1 d-block mb-3"></i>
            <p class="mb-0">No hay información suficiente para generar estadísticas. <br>Asegúrate de tener órdenes creadas y el API de Merkaweb configurado correctamente.</p>
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
                    case 2: return 'color: #00f0ff !important; text-shadow: 0 0 10px rgba(0,255,255,0.3);';
                    case 3: return 'color: #20c997 !important; text-shadow: 0 0 10px rgba(32,201,151,0.3);';
                    case 4: return 'color: #ffb800 !important; text-shadow: 0 0 10px rgba(255,184,0,0.3);';
                    case 5: return 'color: #00d2ff !important; text-shadow: 0 0 10px rgba(0,210,255,0.3);';
                    default: return $default;
                }
            }
        }

        // Formatear pesos
        $fmt = function($val) {
            return '$' . number_format((float) $val, 0, ',', '.');
        };

        // Clase dinámica para el profit
        $profitClass = function($val, $bgOnly = false) {
            $val = (float) $val;
            if ($val > 0) return $bgOnly ? 'bg-success' : 'bg-success text-white';
            if ($val < 0) return $bgOnly ? 'bg-danger' : 'bg-danger text-white';
            return $bgOnly ? 'bg-warning' : 'bg-warning text-dark';
        };
        ?>

    <?php if (isset($detailedStats) && $detailedStats !== null): ?>
            <!-- Vista Global -->
            <div class="row g-4 mb-5" id="section-logistics-global">
                <div class="col-12">
                    <div class="card track-card track-card--emphasis border-0">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="track-icon-circle" style="width:2rem; height:2rem; font-size:1rem;"><i class="bi bi-cpu"></i></span>
                                <h5 class="card-title fw-bold mb-0 text-primary uppercase" style="letter-spacing:1px;">Logística Centralizada</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="row g-3">
                                <?php foreach ($labels as $code => $label): ?>
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 rounded-3 h-100" style="background: rgba(45, 53, 70, 0.4); border: 1px solid rgba(132, 148, 149, 0.15);">
                                            <div class="text-white mb-2 fw-bold text-uppercase opacity-75" style="font-size: 0.65rem; letter-spacing: 1px;"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="h2 mb-0 fw-bold" style="<?= stats_status_color((int)$code, 'color: #dbe2f8;') ?>">
                                                <?= (int)($detailedStats['global'][$code] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4 pt-3 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                                <span class="text-muted small text-uppercase fw-bold">Nivel de Operación Consolidado:</span>
                                <span class="h3 mb-0 fw-bold text-white"><?= (int)$detailedStats['grandTotal'] ?> <small class="text-muted fs-6">unidades</small></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen Logístico Mensual -->
            <div class="card track-card border-0 mb-5 overflow-hidden" id="section-monthly-logistics">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="card-title fw-bold mb-0 text-primary"><i class="bi bi-calendar3 me-2"></i>Telemetría Mensual</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-borderless" style="--bs-table-bg: transparent; --bs-table-color: #dbe2f8; --bs-table-hover-bg: rgba(0, 240, 255, 0.05);">
                            <thead style="border-bottom: 1px solid rgba(132, 148, 149, 0.2);">
                                <tr>
                                    <th class="px-4 py-3 text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Mes / Periodo</th>
                                    <?php foreach ($labels as $label): ?>
                                        <th class="text-center py-3 text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                                    <?php endforeach; ?>
                                    <th class="text-center px-4 py-3 text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Totales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailedStats['months'] as $m): ?>
                                    <tr>
                                        <td class="px-4 py-3 fw-bold text-white"><?= htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php foreach ($labels as $code => $label): ?>
                                            <td class="text-center py-3">
                                                <?php $count = (int)($m['stats'][$code] ?? 0); ?>
                                                <span class="<?= $count > 0 ? 'fw-bold' : '' ?>" style="<?= $count > 0 ? stats_status_color((int)$code, 'color: #dbe2f8;') : 'color: rgba(132, 148, 149, 0.4);' ?>">
                                                    <?= $count ?>
                                                </span>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="text-center px-4 py-3 fw-bold text-primary" style="background: rgba(0,255,255,0.05);"><?= (int)$m['total'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ciudades con Mayor Éxito de Entrega -->
            <?php if (!empty($detailedStats['successByCity'])): ?>
                <div class="card track-card border-0 shadow-sm mb-4" id="section-success-cities">

                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                        <h5 class="card-title fw-bold mb-0 text-white"><i class="bi bi-star-fill me-2" style="color: #00f0ff;"></i>Ciudades con Mayor Éxito de Entrega</h5>
                        <p class="small mb-0 mt-1" style="color: #b9cacb;">Ciudades con más de 3 pedidos, ordenadas por tasa de entrega efectiva.</p>
                    </div>
                        <div class="stats-grid" id="section-success-cities">
                            <?php 
                            $topSuccess = array_slice($detailedStats['successByCity'], 0, 12, true);
                            foreach ($topSuccess as $city => $data): 
                            ?>
                                <div class="card track-card p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="fw-bold text-white text-truncate" style="max-width: 70%;"><?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-20"><?= $data['pct'] ?>%</span>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px; background: rgba(255,255,255,0.05);">
                                        <div class="progress-bar bg-success shadow-glow" role="progressbar" style="width: <?= $data['pct'] ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                                        <span>Entregados: <strong class="text-white"><?= $data['delivered'] ?></strong></span>
                                        <span>Total: <strong class="text-white"><?= $data['total'] ?></strong></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                </div>
            <?php endif; ?>

            <!-- Ciudades con más Devoluciones -->
            <?php if (!empty($detailedStats['returnsByCity'])): ?>
                <div class="card track-card border-0 shadow-sm mb-5" id="section-return-cities">

                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                        <h5 class="card-title fw-bold mb-0 text-white"><i class="bi bi-geo-fill me-2" style="color: #ff24e4;"></i>Ciudades con más Devoluciones</h5>
                        <p class="small mb-0 mt-1" style="color: #b9cacb;">Top de lugares donde se presentan la mayor cantidad de retornos.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                            <?php 
                            $topCities = array_slice($detailedStats['returnsByCity'], 0, 6, true);
                            foreach ($topCities as $city => $count): 
                            ?>
                                <div class="col">
                                    <div class="d-flex justify-content-between align-items-center p-3 rounded h-100" style="background: rgba(45, 53, 70, 0.4); border: 1px solid rgba(132, 148, 149, 0.2);">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(255, 36, 228, 0.1);">
                                                <i class="bi bi-pin-map-fill small" style="color: #ff24e4;" aria-hidden="true"></i>
                                            </div>
                                            <span class="fw-semibold text-white text-truncate" style="max-width: 150px;"><?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(255, 36, 228, 0.2); color: #fface8; border: 1px solid #ff24e4;"><?= $count ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rendimiento por Producto -->
            <?php if (isset($detailedStats) && $detailedStats !== null && !empty($detailedStats['productStats'])): ?>
                <?php
                $pItems = [];
                foreach (($detailedStats['productStats'] ?? []) as $pid => $pStat) {
                    $pData = $products[(string)$pid] ?? null;
                    $rawName = (string)($pData['name'] ?? '');
                    // Safe string check for "Producto ID"
                    $isPlaceholder = (substr($rawName, 0, 11) === 'Producto ID');
                    
                    $pName = ($pData && !empty($rawName) && !$isPlaceholder) ? $rawName : ($pStat['fallback_name'] ?? 'S/N');
                    $pImg = (string)($pData['image_url'] ?? '');
                    $pWarehouse = (string)($pData['warehouse'] ?? 'Info. del pedido');
                    $delivered = (int)($pStat['delivered'] ?? 0);
                    $total = (int)($pStat['total'] ?? 0);
                    $ingr = (float)($pStat['ingresos'] ?? 0);
                    $cst = (float)($pStat['costos'] ?? 0);
                    $margen = $delivered > 0 ? ($ingr - $cst) / $delivered : 0;
                    $pct = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;

                    $pItems[] = [
                        'id' => (string)$pid,
                        'name' => (string)$pName,
                        'image' => (is_string($pImg) && !empty($pImg)) ? 'https://images.weserv.nl/?url=' . urlencode($pImg) . '&w=100&h=100&fit=cover&output=webp' : null,
                        'warehouse' => (string)$pWarehouse,
                        'total' => $total,
                        'delivered' => $delivered,
                        'ingresos' => $ingr,
                        'margen' => $margen,
                        'pct' => $pct
                    ];
                }
                $jsonItems = json_encode($pItems, JSON_UNESCAPED_UNICODE);
                ?>
                <div class="card track-card border-0 shadow-sm mb-5" id="section-products" 
                     x-data="{ 
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
                        <h5 class="card-title fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-2"></i>Nodos de Producto</h5>
                        <p class="text-muted small mb-0 mt-1 uppercase fw-bold" style="letter-spacing: 1px;">Rendimiento de Inventario</p>
                    </div>
                    <div class="card-body p-0 mt-3">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 table-borderless track-table" style="--bs-table-bg: transparent; --bs-table-color: #dbe2f8; --bs-table-hover-bg: rgba(0, 240, 255, 0.05);">
                                <thead style="border-bottom: 1px solid rgba(132, 148, 149, 0.2);">
                                    <tr>
                                        <th @click="sortBy('name')" class="px-4 cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Producto <i class="bi" :class="sKey==='name'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i></th>
                                        <th @click="sortBy('total')" class="text-center cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Total Órdenes <i class="bi" :class="sKey==='total'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i></th>
                                        <th @click="sortBy('pct')" class="text-center cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Éxito (%) <i class="bi" :class="sKey==='pct'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i></th>
                                        <th @click="sortBy('ingresos')" class="text-end cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Ingresos Brutos <i class="bi" :class="sKey==='ingresos'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i></th>
                                        <th @click="sortBy('margen')" class="text-end px-4 cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Margen / Unidad <i class="bi" :class="sKey==='margen'?(sDir==='asc'?'bi-sort-up':'bi-sort-down'):'bi-arrow-down-up opacity-25'"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in sorted" :key="p.id">
                                        <tr :class="p.pct < 40 ? 'semaphore-danger' : (p.pct > 70 ? 'semaphore-success' : 'semaphore-warning')" @mouseover="$el.style.background='rgba(0, 240, 255, 0.05)'" @mouseleave="$el.style.background='transparent'">
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <template x-if="p.image">
                                                        <img :src="p.image" class="rounded border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                                                    </template>
                                                    <template x-if="!p.image">
                                                        <div class="rounded d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(255,255,255,0.05); border: 1px solid rgba(132, 148, 149, 0.2);"><i class="bi bi-box fs-4" style="color: #b9cacb;"></i></div>
                                                    </template>
                                                    <div>
                                                        <div class="fw-bold text-white lh-sm" x-text="p.name"></div>
                                                        <div class="text-muted" style="font-size: 0.7rem;" x-text="p.warehouse"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center fw-medium" x-text="p.total"></td>
                                            <td class="text-center">
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    <span class="fw-bold" :class="p.pct > 70 ? 'text-success' : (p.pct < 40 ? 'text-danger' : 'text-warning')" x-text="p.pct + '%'"></span>
                                                    <div class="progress w-75" style="height: 4px;">
                                                        <div class="progress-bar" :class="p.pct > 70 ? 'bg-success' : (p.pct < 40 ? 'bg-danger' : 'bg-warning')" :style="'width: ' + p.pct + '%'"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold text-white" x-text="fmt(p.ingresos)"></td>
                                            <td class="text-end px-4 fw-bold" :class="p.margen > 0 ? 'text-success' : 'text-danger'" x-text="fmt(p.margen)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; // closes 283 ?>
        <?php endif; ?>
        <!-- Panel de Resumen Financiero Consolidado (Global) -->
        <?php if (isset($globalFinancials) && $globalFinancials !== null): ?>
            <div class="row g-4 mb-5" id="section-financial-summary">
                <div class="col-12">
                    <div class="card track-card track-card--emphasis border-0 overflow-hidden" style="background: linear-gradient(135deg, rgba(21, 26, 35, 0.9) 0%, rgba(10, 14, 22, 0.7) 100%);">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0 text-primary"><i class="bi bi-graph-up-arrow me-2"></i>Frecuencia Financiera Global</h5>
                            <p class="text-muted small mb-0 mt-1 uppercase fw-bold" style="letter-spacing: 1px;">Balance de Red Consolidado</p>
                        </div>
                        <div class="card-body p-4">
                            <!-- Fila Principal (Volumen y Profit) -->
                            <div class="row g-3 mb-4">
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 shadow-sm border-start border-4 border-success h-100" style="background: rgba(255,255,255,0.03);">
                                        <div class="text-muted small fw-bold text-uppercase mb-1">Ingresos de Red</div>
                                        <div class="h4 mb-0 fw-bold text-white"><?= $fmt($globalFinancials['ingresos_brutos']) ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 shadow-sm border-start border-4 border-primary h-100" style="background: rgba(255,255,255,0.03);">
                                        <div class="text-muted small fw-bold text-uppercase mb-1">Inyección Pauta</div>
                                        <div class="h4 mb-0 fw-bold text-white"><?= $fmt($globalFinancials['pauta']) ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 shadow-sm border-start border-4 border-danger h-100" style="background: rgba(255,255,255,0.03);">
                                        <div class="text-muted small fw-bold text-uppercase mb-1">Fuga por Devolución</div>
                                        <div class="h4 mb-0 fw-bold text-white"><?= $fmt($globalFinancials['costos_devolucion']) ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 shadow-sm border-start border-4 border-primary h-100 <?= $profitClass($globalFinancials['profit']) ?>" style="box-shadow: 0 0 20px rgba(0,255,255,0.1);">
                                        <div class="small fw-bold text-uppercase mb-1 opacity-75">Profit Real Neto</div>
                                        <div class="h3 mb-0 fw-bold"><?= $fmt($globalFinancials['profit']) ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Fila Métricas de Eficiencia -->
                            <div class="row g-3">
                                <div class="col-6 col-lg-4">
                                    <div class="bg-primary bg-opacity-5 p-3 rounded-3 border border-primary border-opacity-20 h-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-primary small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing:1px;">Multiplicador ROAS</div>
                                                <div class="h4 mb-0 fw-bold text-white"><?= $globalFinancials['roas'] ?>x</div>
                                            </div>
                                            <div class="h2 mb-0 text-primary opacity-25"><i class="bi bi-pie-chart"></i></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-4">
                                    <div class="bg-secondary bg-opacity-10 p-3 rounded-3 border border-secondary border-opacity-20 h-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-secondary small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing:1px;">CPA Promedio</div>
                                                <div class="h4 mb-0 fw-bold text-white"><?= $fmt($globalFinancials['cpa']) ?></div>
                                            </div>
                                            <div class="h2 mb-0 text-secondary opacity-25"><i class="bi bi-person-check"></i></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-3 border border-primary border-opacity-20 h-100" style="background: linear-gradient(to right, rgba(0,255,255,0.1), transparent);">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-primary small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing:1px;">Margen Neto Unitario</div>
                                                <div class="h4 mb-0 fw-bold text-white"><?= $fmt($globalFinancials['margen_unidad']) ?></div>
                                            </div>
                                            <div class="h2 mb-0 text-primary opacity-25"><i class="bi bi-gem"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <h5 class="fw-bold mb-3 px-1" id="section-monthly-details"><i class="bi bi-cash-stack me-2 text-success"></i>Detalle Financiero por Mes</h5>



        <div class="accordion" id="statsAccordion">
            <?php foreach ($months as $idx => $m): ?>
                <?php 
                    $show = $idx === 0 ? 'show' : ''; 
                    $collapsed = $idx === 0 ? '' : 'collapsed'; 
                ?>
                <div class="accordion-item mb-4 border-0 rounded-4 shadow-lg overflow-hidden" style="background: rgba(21, 26, 35, 0.4); backdrop-filter: blur(10px); border: 1px solid var(--track-border) !important;">
                    <h2 class="accordion-header" id="heading-<?= $m['mes'] ?>">
                        <button class="accordion-button fw-bold fs-5 <?= $collapsed ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $m['mes'] ?>" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>" aria-controls="collapse-<?= $m['mes'] ?>" style="background: transparent; color: var(--track-primary);">
                            <i class="bi bi-calendar-check me-2"></i> Registro Temporal: <?= htmlspecialchars($m['mes'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </h2>
                    <div id="collapse-<?= $m['mes'] ?>" class="accordion-collapse collapse <?= $show ?>" aria-labelledby="heading-<?= $m['mes'] ?>">
                        <div class="accordion-body p-4">
                                          <!-- Tarjetas KPIs Principales -->
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm <?= $profitClass($m['profit']) ?>">
                                        <div class="card-body">
                                            <div class="small text-uppercase fw-semibold mb-1 opacity-75">Profit Neto</div>
                                            <h4 class="mb-0 fw-bold"><?= $fmt($m['profit']) ?></h4>
                                            <div class="small mt-2 opacity-75"><i class="bi bi-check-circle-fill me-1"></i> <?= $m['entregadas'] ?> unidades legalizadas</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(132, 148, 149, 0.15) !important;">
                                        <div class="card-body">
                                            <div class="small text-uppercase fw-bold mb-2 text-white opacity-75" style="letter-spacing:1px; font-size:0.65rem;">Eficiencia Publicitaria</div>
                                            <div class="d-flex align-items-baseline gap-1">
                                                <h4 class="mb-0 fw-bold" style="color: #00f0ff;"><?= $m['roas'] ?><span style="font-size: 0.9rem; opacity: 0.8;">x</span></h4>
                                                <span class="small" style="color: #b9cacb; margin-left: 2px;">ROAS</span>
                                            </div>
                                            <div class="small mt-2" style="color: #b9cacb;">CPA: <strong class="text-white"><?= $fmt($m['cpa']) ?></strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm" style="background: rgba(0, 240, 255, 0.05); border: 1px solid rgba(0, 240, 255, 0.2) !important;">
                                        <div class="card-body">
                                            <div class="text-secondary small text-uppercase fw-semibold mb-1" style="color: #00f0ff !important;">Margen por Unidad</div>
                                            <h4 class="mb-0 text-white"><?= $fmt($m['margen_unidad']) ?></h4>
                                            <div class="small text-muted mt-2">Ganancia libre por cada entrega</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm" style="background: rgba(255, 193, 7, 0.05); border: 1px solid rgba(255, 193, 7, 0.2) !important;">
                                        <div class="card-body">
                                            <div class="small text-uppercase fw-semibold mb-1 text-warning">Logística</div>
                                            <h4 class="mb-0 text-white"><?= $m['efectividad_pct'] ?>%</h4>
                                            <div class="small text-muted mt-2"><i class="bi bi-arrow-return-left me-1"></i> <?= $m['devolucion_pct'] ?>% devoluciones</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <!-- Desglose de Operación -->
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm h-100" style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(132, 148, 149, 0.15) !important;">
                                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                                            <h6 class="mb-0 fw-bold text-white"><i class="bi bi-truck me-2" style="color: #00f0ff;"></i>Métricas de Despachos</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush bg-transparent">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent text-white border-bottom border-secondary border-opacity-25">
                                                    <span class="text-secondary">Total Despachado</span>
                                                    <span class="badge bg-secondary rounded-pill"><?= $m['despachadas'] ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent text-white border-bottom border-secondary border-opacity-25">
                                                    <span class="text-muted">Entregados / Legalizados</span>
                                                    <span class="badge bg-success rounded-pill"><?= $m['entregadas'] ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent text-white border-bottom border-secondary border-opacity-25">
                                                    <span class="text-muted">Devueltos</span>
                                                    <span class="badge rounded-pill text-white" style="background: #ff24e4;"><?= $m['devueltas'] ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0 pb-0 bg-transparent text-white">
                                                    <span class="text-muted">En ruta / Tránsito</span>
                                                    <span class="badge rounded-pill text-dark" style="background: #00f0ff;"><?= $m['en_proceso'] ?></span>
                                                </li>
                                            </ul>
                                            <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="small fw-semibold text-secondary">Efectividad de Entrega</span>
                                                    <span class="small fw-bold text-success"><?= $m['efectividad_pct'] ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $m['efectividad_pct'] ?>%" aria-valuenow="<?= $m['efectividad_pct'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Finanzas y Pauta -->
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm h-100" style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(132, 148, 149, 0.15) !important;">
                                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                                            <h6 class="mb-0 fw-bold text-white"><i class="bi bi-wallet2 me-2" style="color: #00f0ff;"></i>Desglose Financiero</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless mb-3 text-white" style="--bs-table-bg: transparent; --bs-table-color: #dbe2f8;">
                                                <tbody style="border-color: rgba(132, 148, 149, 0.15);">
                                                    <tr>
                                                        <td style="color: #b9cacb;">+ Ventas Brutas</td>
                                                        <td class="text-end fw-medium"><?= $fmt($m['ingresos_brutos']) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="color: #b9cacb;">- Costo de Productos</td>
                                                        <td class="text-end fw-medium" style="color: #ff24e4;"><?= $fmt($m['costos_producto']) ?></td>
                                                    </tr>
                                                    <tr class="border-bottom" style="border-color: rgba(132, 148, 149, 0.2) !important;">
                                                        <td class="pb-2" style="color: #b9cacb;">- Costo Envíos Exitosos</td>
                                                        <td class="text-end fw-medium pb-2" style="color: #ff24e4;"><?= $fmt($m['costos_envio_exito']) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="pt-2 fw-bold" style="color: #00f0ff;">= Utilidad Bruta</td>
                                                        <td class="pt-2 text-end fw-bold" style="color: #00f0ff;"><?= $fmt($m['utilidad_bruta']) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="color: #b9cacb;">- Costo Devoluciones</td>
                                                        <td class="text-end fw-medium" style="color: #ff24e4;"><?= $fmt($m['costos_devolucion']) ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <form action="/estadisticas/pauta" method="POST" class="mt-4 p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(132, 148, 149, 0.15);">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="mes" value="<?= htmlspecialchars($m['mes'], ENT_QUOTES, 'UTF-8') ?>">
                                                
                                                <label for="pauta_<?= $m['mes'] ?>" class="form-label small fw-bold mb-2" style="color: #dbe2f8;">Gasto en Pauta (Publicidad)</label>
                                                <div class="input-group input-group-sm mb-2" style="border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 6px; overflow: hidden;">
                                                    <span class="input-group-text border-0" style="background: rgba(0, 240, 255, 0.1); color: #00f0ff;">$</span>
                                                    <input type="number" step="0.01" min="0" name="amount" id="pauta_<?= $m['mes'] ?>" class="form-control border-0 text-white" style="background: rgba(0,0,0,0.2);" value="<?= htmlspecialchars((string) $m['pauta'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 500000">
                                                    <button class="btn fw-bold" type="submit" style="background: rgba(0, 240, 255, 0.15); color: #00f0ff; border-left: 1px solid rgba(0, 240, 255, 0.3);">Guardar Pauta</button>
                                                </div>
                                                <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1"></i> El valor de la pauta se resta de tu utilidad para calcular el Profit Neto mostrado arriba.</p>
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
</div> <!-- End of Alpine x-data context -->
</div>
