<?php

declare(strict_types=1);

/** @var string $heading */
/** @var string $subtitle */
/** @var list<int> $selectedStates */
/** @var string|null $fechaDesde */
/** @var string|null $fechaHasta */
/** @var string|null $filterSummary */
/** @var list<array<string, mixed>> $orders */
/** @var list<string> $apiWarnings */
/** @var bool $allApisOk */
/** @var string|null $queryError */
/** @var string|null $formError */
/** @var array|null $detailedStats */
/** @var bool $showResults */

$h = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
$sub = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');

function estado_checked(array $selected, int $e): string
{
    return in_array($e, $selected, true) ? ' checked' : '';
}

$labels = [
    2 => 'Despachado',
    3 => 'Entregado',
    4 => 'Devuelto',
    5 => 'Legalizado',
];

function consulta_search_blob(array $order): string
{
    $parts = [
        (string) ($order['orderId'] ?? ''),
        (string) ($order['customerName'] ?? ''),
        (string) ($order['customerPhone'] ?? ''),
        (string) ($order['carrierName'] ?? ''),
        (string) ($order['guideNumber'] ?? ''),
    ];
    $s = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)));

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($s, 'UTF-8');
    }

    return strtolower($s);
}

function consulta_badge_class(int $code): string
{
    switch ($code) {
        case 2: return 'text-bg-secondary';
        case 3: return 'text-bg-success';
        case 4: return 'text-bg-warning text-dark';
        case 5: return 'text-bg-info text-dark';
        default: return 'text-bg-light text-dark border';
    }
}

function consulta_status_color(int $code, string $default = 'text-dark'): string
{
    switch ($code) {
        case 3: return 'text-success';
        case 4: return 'text-warning-emphasis';
        case 5: return 'text-info-emphasis';
        default: return $default;
    }
}

$noQueryErr = $queryError === null || $queryError === '';
$noFormErr = $formError === null || $formError === '';

?>
<style>
    .track-filter-bar {
        background: rgba(19, 28, 43, 0.7);
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        border: 1px solid rgba(132, 148, 149, 0.15);
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }
    .track-input-group {
        position: relative;
        display: flex;
        align-items: center;
        background: rgba(34, 42, 58, 0.5); /* surface_container_high */
        border: none;
        border-bottom: 2px solid rgba(59, 73, 75, 0.8); /* outline_variant */
        border-radius: 8px 8px 0 0;
        padding: 0.8rem 1rem;
        transition: border-color 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
    }
    .track-input-group:focus-within {
        border-bottom-color: #00f0ff;
        background: rgba(0, 240, 255, 0.05);
        box-shadow: 0 10px 15px -5px rgba(0, 240, 255, 0.1);
    }
    .track-input-group .form-control {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        height: auto !important;
        color: #dbe2f8 !important;
        padding-left: 0.5rem !important;
    }
    .track-label-mini {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #dbe2f8; /* on_surface */
        letter-spacing: 0.1em;
        margin-bottom: 0.8rem;
        display: block;
        opacity: 0.7;
    }
    /* Estados como Chips Futuristas */
    .track-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.8rem;
        border-radius: 9999px;
        background: rgba(45, 53, 70, 0.4);
        border: 1px solid rgba(132, 148, 149, 0.2);
        color: #b9cacb;
        cursor: pointer;
        font-weight: 700;
        font-size: 1rem; /* 16px */
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }
    
    .track-filter-active-panel {
        background: rgba(0, 240, 255, 0.03);
        border: 1px solid rgba(0, 240, 255, 0.2);
        color: #00f0ff;
        border-radius: 12px;
        padding: 1.2rem;
    }
    .track-status-pill:hover { 
        border-color: rgba(0, 240, 255, 0.4);
        color: #dbe2f8;
        background: rgba(0, 240, 255, 0.08);
    }

    .track-status-pill.active {
        color: #001f24 !important;
        font-weight: 800 !important;
        box-shadow: 0 0 15px rgba(0, 240, 255, 0.2);
    }
    .track-status-pill.active i {
        color: #001f24 !important;
    }
    .track-status-pill.active[data-status="4"], .track-status-pill.active[data-status="4"] i { color: #3b0000 !important; }
    .track-status-pill.active[data-status="5"], .track-status-pill.active[data-status="5"] i { color: #3a0033 !important; }

    .track-status-pill.active[data-status="2"] { background: linear-gradient(135deg, #00dbe9 0%, #00f0ff 100%); border-color: transparent; }
    .track-status-pill.active[data-status="3"] { background: linear-gradient(135deg, #00f0ff 0%, #00dbe9 100%); border-color: transparent; }
    .track-status-pill.active[data-status="4"] { background: linear-gradient(135deg, #ffb4ab 0%, #ff897d 100%); border-color: transparent; }
    .track-status-pill.active[data-status="5"] { background: linear-gradient(135deg, #fface8 0%, #ff24e4 100%); border-color: transparent; }

    .btn-search-main {
        background: rgba(0, 240, 255, 0.08) !important;
        color: #00f0ff !important;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 0.6rem 1.2rem;
        border-radius: 0.35rem;
        border: 1px solid rgba(0, 240, 255, 0.3) !important;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .btn-search-main:hover {
        background: rgba(0, 240, 255, 0.15) !important;
        border-color: #00f0ff !important;
        box-shadow: 0 0 15px rgba(0, 240, 255, 0.2);
    }
</style>

<div class="container track-page-header pt-5 mt-4 mb-4">
    <div class="d-flex align-items-center gap-4">
        <div class="track-icon-circle shadow-lg rounded-circle" style="width: 64px; height: 64px; background: rgba(0, 240, 255, 0.1); border: 1px solid rgba(0, 240, 255, 0.2);">
            <i class="bi bi-radar fs-3 text-primary"></i>
        </div>
        <div>
            <span class="track-pill mb-2 d-inline-block px-3 py-1 fw-bold" style="background: rgba(0, 240, 255, 0.15); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 9999px; color: #00f0ff;"><i class="bi bi-terminal"></i> Scanner Activo</span>
            <h1 class="track-page-title h1 mb-2 fw-bold text-uppercase" style="letter-spacing: 0.03em; font-family: 'Space Grotesk', sans-serif;">Rastreador de Nodos</h1>
            <p class="track-page-lead mb-0" style="color: #b9cacb; max-width: 800px;"><?= $sub ?></p>
        </div>
    </div>
</div>

<div class="container pb-5 mt-2">
    <?php if (is_string($formError) && $formError !== ''): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
            <span class="fw-medium"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if (is_string($queryError) && $queryError !== ''): ?>
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
            <i class="bi bi-x-octagon-fill fs-5 text-danger"></i>
            <span class="fw-medium"><?= htmlspecialchars($queryError, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <div class="track-filter-bar shadow-sm mb-5"
         x-data="{ 
            selected: <?= json_encode($selectedStates) ?>,
            isSelected(st) { return this.selected.includes(st); },
            toggle(st) {
                st = parseInt(st);
                if (this.isSelected(st)) {
                    this.selected = this.selected.filter(x => x !== st);
                } else {
                    this.selected.push(st);
                }
            }
         }">
        <form method="post" action="/consultas" class="row align-items-end g-3">
            <?= csrf_field() ?>
            
            <!-- Rango de Fechas -->
            <div class="col-12 col-xl-5">
                <span class="track-label-mini">Periodo de tiempo</span>
                <div class="d-flex gap-3">
                    <div class="track-input-group flex-fill">
                        <i class="bi bi-calendar-event me-2" style="color: #849495;"></i>
                        <input type="date" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde ?? '', ENT_QUOTES, 'UTF-8') ?>" title="Desde" required>
                    </div>
                    <div class="track-input-group flex-fill">
                        <i class="bi bi-calendar-check me-2" style="color: #849495;"></i>
                        <input type="date" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta ?? '', ENT_QUOTES, 'UTF-8') ?>" title="Hasta" required>
                    </div>
                </div>
            </div>

            <!-- Chips de Estado -->
            <div class="col-12 col-xl-5">
                <span class="track-label-mini">Filtrar por estados</span>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                    $labels = [2 => 'Despachado', 3 => 'Entregado', 4 => 'Devuelto', 5 => 'Legalizado'];
                    $icons = [2 => 'bi-truck', 3 => 'bi-check-all', 4 => 'bi-x-circle', 5 => 'bi-shield-check'];
                    foreach ($labels as $code => $label): 
                    ?>
                        <label class="track-status-pill" :class="isSelected(<?= $code ?>) ? 'active' : ''" data-status="<?= $code ?>">
                            <input type="checkbox" name="estados[]" value="<?= $code ?>" class="d-none" @change="toggle(<?= $code ?>)" <?= estado_checked($selectedStates, $code) ?>>
                            <i class="bi <?= $icons[$code] ?>"></i>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Acciones -->
            <div class="col-12 col-xl-2">
                <div class="d-flex gap-3 justify-content-xl-end align-items-center h-100 pb-1">
                    <a href="/consultas" class="text-decoration-none small fw-bold" style="color: #849495; letter-spacing: 0.05em; text-transform: uppercase;">Limpiar</a>
                    <button type="submit" class="btn btn-search-main w-100 w-xl-auto">
                        <i class="bi bi-search me-2"></i> Consultar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if ($showResults && $filterSummary !== null && $filterSummary !== ''): ?>
        <div class="track-filter-active-panel mb-4">
            <div class="small mb-1 text-uppercase fw-bold opacity-75" style="letter-spacing:1px; font-size:0.65rem;">Filtros de Nodo Activos</div>
            <p class="mb-0 fw-bold text-white"><?= htmlspecialchars($filterSummary, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <?php if ($showResults && $apiWarnings !== []): ?>
        <div class="alert alert-warning d-flex align-items-start gap-2 track-settings-alert" role="status">
            <i class="bi bi-cloud-slash mt-1" aria-hidden="true"></i>
            <div>
                <strong>Atención:</strong> alguna petición al API devolvió error. Se muestran los resultados parciales disponibles.
                <ul class="mb-0 mt-2 small">
                    <?php foreach ($apiWarnings as $w): ?>
                        <li><?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($showResults && $noQueryErr && $orders === [] && $noFormErr): ?>
        <div class="alert alert-light border track-settings-alert" role="status">
            <i class="bi bi-inbox me-2 text-secondary" aria-hidden="true"></i>
            No se encontraron órdenes en la respuesta del API para los estados consultados.
        </div>
    <?php endif; ?>

    <?php if ($showResults && !empty($orders)): ?>
        <?php
        $enriched = [];
        foreach ($orders as $order) {
            $pid = (string) ($order['productId'] ?? '');
            $pData = $products[$pid] ?? null;
            $pName = ($pData && !empty($pData['name']) && !str_starts_with($pData['name'], 'Producto ID')) ? $pData['name'] : ($order['orderProductName'] ?? '');
            $pImg = ($pData && !empty($pData['image_url'])) ? $pData['image_url'] : ($order['orderProductImage'] ?? null);
            $pWarehouse = $pData ? $pData['warehouse'] : 'Info. del pedido';
            
            $enriched[] = [
                'id' => (string)($order['orderId'] ?? ''),
                'name' => (string)$pName,
                'image' => $pImg ? 'https://images.weserv.nl/?url=' . urlencode($pImg) . '&w=100&h=100&fit=cover&output=webp' : null,
                'warehouse' => (string)$pWarehouse,
                'customer' => (string)($order['customerName'] ?? ''),
                'phone' => (string)($order['customerPhone'] ?? ''),
                'carrier' => (string)($order['carrierName'] ?? ''),
                'guide' => (string)($order['guideNumber'] ?? ''),
                'city' => (string)($order['cityName'] ?? ''),
                'dept' => (string)($order['departmentName'] ?? ''),
                'status' => (string)($order['statusLabel'] ?? ''),
                'statusCode' => (int)($order['statusCode'] ?? 0),
                'date' => (string)($order['eventDate'] ?? ''),
                'time' => (string)($order['eventTime'] ?? ''),
                'motive' => (string)($order['returnReason'] ?? ''),
                'tracking' => (string)($order['trackingUrl'] ?? ''),
                'search' => strtolower(consulta_search_blob($order) . ' ' . ($order['cityName'] ?? '') . ' ' . $pName),
            ];
        }
        ?>

        <div x-data="{ 
            all: <?= htmlspecialchars(json_encode($enriched), ENT_QUOTES, 'UTF-8') ?>,
            q: '',
            page: 1,
            pageSize: 15,
            sortKey: 'date',
            sortDir: 'desc',
            get filtered() {
                if (!this.q.trim()) return this.all;
                const query = this.q.toLowerCase();
                return this.all.filter(o => o.search.includes(query));
            },
            get sorted() {
                return [...this.filtered].sort((a, b) => {
                    let va = a[this.sortKey] || '';
                    let vb = b[this.sortKey] || '';
                    if (!isNaN(va) && !isNaN(vb)) { va = Number(va); vb = Number(vb); }
                    if (va < vb) return this.sortDir === 'asc' ? -1 : 1;
                    if (va > vb) return this.sortDir === 'asc' ? 1 : -1;
                    return 0;
                });
            },
            get paginated() {
                const start = (this.page - 1) * this.pageSize;
                return this.sorted.slice(start, start + this.pageSize);
            },
            get totalPages() { return Math.ceil(this.filtered.length / this.pageSize); },
            sortBy(key) {
                if (this.sortKey === key) this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                else { this.sortKey = key; this.sortDir = 'desc'; }
                this.page = 1;
            },
            getBadgeClass(sc) {
                const map = { 2: 'text-bg-info text-dark', 3: 'text-bg-success', 4: 'text-bg-danger', 5: 'text-bg-secondary' };
                return map[sc] || 'bg-light text-secondary';
            }
        }">
            <!-- Filtro de Búsqueda -->
            <div class="card track-card mb-4 border-0" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                <div class="card-body py-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                        <div class="flex-grow-1">
                            <label for="consultaBuscar" class="track-label-mini">Búsqueda en resultados</label>
                            <div class="track-input-group">
                                <i class="bi bi-search me-2" style="color: #00f0ff;"></i>
                                <input type="search" class="form-control border-0 bg-transparent text-white p-0" id="consultaBuscar" x-model="q" @input="page = 1" placeholder="Filtrar por Nombre, Ciudad, ID..." autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                             <span class="text-white small fw-bold" style="letter-spacing: 0.1em;">FILAS POR PÁGINA:</span>
                             <select class="form-select form-select-sm w-auto bg-dark border-secondary text-white" x-model="pageSize" @change="page = 1" style="border-radius: 8px;">
                                  <option value="15">15</option>
                                  <option value="30">30</option>
                                  <option value="50">50</option>
                                  <option value="100">100</option>
                             </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vista Desktop -->
            <div class="d-none d-lg-block mb-4">
                <div class="card border-0 overflow-hidden" style="background: rgba(19, 28, 43, 0.7); backdrop-filter: blur(30px); border-radius: 16px; border: 1px solid rgba(132, 148, 149, 0.15); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
                    <div class="table-responsive p-2">
                        <table class="table align-middle mb-0 table-borderless" style="--bs-table-bg: transparent; --bs-table-color: #dbe2f8; --bs-table-hover-bg: rgba(0, 240, 255, 0.05);">
                            <thead style="border-bottom: 1px solid rgba(132, 148, 149, 0.2);">
                                <tr>
                                    <th @click="sortBy('id')" class="cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em; padding-left: 1.5rem;">Pedido <i class="bi" :class="sortKey === 'id' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Producto</th>
                                    <th @click="sortBy('customer')" class="cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Cliente <i class="bi" :class="sortKey === 'customer' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Guía y Transp.</th>
                                    <th @click="sortBy('city')" class="cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Destino <i class="bi" :class="sortKey === 'city' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th @click="sortBy('statusCode')" class="cursor-pointer text-center text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Estado <i class="bi" :class="sortKey === 'statusCode' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th @click="sortBy('date')" class="cursor-pointer text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Actualizado <i class="bi" :class="sortKey === 'date' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th class="text-end px-4 text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="o in paginated" :key="o.id">
                                    <tr :class="o.statusCode === 4 ? 'semaphore-danger' : (o.statusCode === 3 || o.statusCode === 5 ? 'semaphore-success' : 'semaphore-info')" style="background: transparent !important; transition: background 0.3s ease;" @mouseover="$el.style.background='rgba(0, 240, 255, 0.05)'" @mouseleave="$el.style.background='transparent'">
                                        <td style="padding-left: 1.5rem;">
                                            <div class="fw-bold fs-6" style="color: #00f0ff; text-shadow: 0 0 10px rgba(0, 240, 255, 0.3);" x-text="'#' + o.id"></div>
                                            <div class="small text-uppercase fw-bold mt-1" style="color: #849495; letter-spacing: 0.05em; font-size: 0.65rem;" x-text="o.carrier.substring(0,12)"></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <template x-if="o.image">
                                                    <img :src="o.image" class="rounded shadow-sm" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid rgba(132, 148, 149, 0.2);" loading="lazy">
                                                </template>
                                                <template x-if="!o.image">
                                                    <div class="rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border: 1px solid rgba(132, 148, 149, 0.2);"><i class="bi bi-box small" style="color: #b9cacb;"></i></div>
                                                </template>
                                                <div style="line-height: 1.3">
                                                    <div class="fw-bold text-truncate text-white" style="max-width: 180px;" x-text="o.name || 'Sin nombre'"></div>
                                                    <div style="font-size: 0.75rem; color: #849495;" x-text="o.warehouse"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-white" x-text="o.customer"></div>
                                            <div class="d-flex align-items-center gap-2 mt-1" style="font-size: 0.8rem; color: #849495;">
                                                <span x-text="o.phone"></span>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" @click="navigator.clipboard.writeText(o.phone).then(() => notify('Teléfono copiado', 'success'))"><i class="bi bi-clipboard" style="color: #00f0ff;"></i></button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-monospace d-flex align-items-center gap-2 text-white">
                                                <span class="text-truncate" style="max-width: 120px;" x-text="o.guide"></span>
                                                <button type="button" class="btn btn-sm p-0" title="Copiar Guía" @click="navigator.clipboard.writeText(o.guide).then(() => notify('Guía copiada', 'success'))"><i class="bi bi-clipboard" style="color: #00f0ff;"></i></button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-white lh-1 mb-1" x-text="o.city"></div>
                                            <div class="small" style="color: #849495;" x-text="o.dept"></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill px-3 py-2 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.7rem;" :class="getBadgeClass(o.statusCode)" x-text="o.status"></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-white lh-1 mb-1" x-text="o.date"></div>
                                            <div class="small" style="color: #849495;" x-text="o.time"></div>
                                        </td>
                                        <td class="text-end px-4">
                                            <template x-if="o.tracking">
                                                <a :href="o.tracking" target="_blank" class="btn btn-sm rounded-pill px-4 fw-bold" style="background: rgba(0, 240, 255, 0.1); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.3);">Rastrear</a>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Vista Móvil -->
            <div class="d-lg-none">
                <template x-for="o in paginated" :key="o.id">
                    <div class="card border-0 mb-3 overflow-hidden" style="background: rgba(19, 28, 43, 0.7); backdrop-filter: blur(20px); border-radius: 12px; border: 1px solid rgba(132, 148, 149, 0.15); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <template x-if="o.image">
                                        <img :src="o.image" class="rounded shadow-sm" style="width: 48px; height: 48px; object-fit: cover; border: 1px solid rgba(132, 148, 149, 0.2);">
                                    </template>
                                    <div>
                                        <div class="small fw-bold text-uppercase" style="color: #00f0ff; font-size: 0.65rem;">Pedido #<span x-text="o.id"></span></div>
                                        <div class="fw-bold text-white lh-sm mt-1" x-text="o.name"></div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill px-2 py-1" :class="getBadgeClass(o.statusCode)" x-text="o.status"></span>
                            </div>
                            <div class="row g-3 small mb-3">
                                <div class="col-6">
                                    <label class="d-block mb-1 text-uppercase fw-bold" style="color: #849495; font-size: 0.65rem;">Cliente</label>
                                    <div class="fw-medium text-white" x-text="o.customer"></div>
                                </div>
                                <div class="col-6">
                                    <label class="d-block mb-1 text-uppercase fw-bold" style="color: #849495; font-size: 0.65rem;">Destino</label>
                                    <div class="fw-medium text-white" x-text="o.city"></div>
                                </div>
                                <div class="col-6">
                                    <label class="d-block mb-1 text-uppercase fw-bold" style="color: #849495; font-size: 0.65rem;">Guía</label>
                                    <div class="font-monospace text-white" x-text="o.guide"></div>
                                </div>
                                <div class="col-6">
                                    <label class="d-block mb-1 text-uppercase fw-bold" style="color: #849495; font-size: 0.65rem;">Actualización</label>
                                    <div class="text-white" x-text="o.date"></div>
                                </div>
                            </div>
                            <div class="pt-3 d-flex justify-content-between gap-2" style="border-top: 1px solid rgba(132, 148, 149, 0.15);">
                                <button class="btn btn-sm w-100 fw-bold" style="background: rgba(255,255,255,0.05); color: #dbe2f8; border: 1px solid rgba(132,148,149,0.2); border-radius: 8px;" @click="navigator.clipboard.writeText(o.guide).then(() => notify('Guía copiada', 'success'))"><i class="bi bi-clipboard me-2"></i>Copiar Guía</button>
                                <template x-if="o.tracking">
                                    <a :href="o.tracking" target="_blank" class="btn btn-sm w-100 fw-bold" style="background: rgba(0, 240, 255, 0.1); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 8px;">Rastrear</a>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Controles de Paginación -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 mb-5" x-show="filtered.length > 0">
                <div class="text-secondary small">
                    Mostrando <span class="fw-bold text-dark" x-text="((page-1)*pageSize)+1"></span> a <span class="fw-bold text-dark" x-text="Math.min(page*pageSize, filtered.length)"></span> de <span class="fw-bold" x-text="filtered.length"></span> resultados.
                </div>
                <nav aria-label="Paginación">
                    <ul class="pagination mb-0 track-pagination">
                        <li class="page-item" :class="page === 1 ? 'disabled' : ''">
                            <button class="page-link" @click="page = Math.max(1, page - 1)" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
                        </li>
                        <template x-for="p in totalPages">
                             <template x-if="p === 1 || p === totalPages || (p >= page - 2 && p <= page + 2)">
                                <li class="page-item" :class="p === page ? 'active' : ''">
                                    <button class="page-link" @click="page = p" x-text="p"></button>
                                </li>
                             </template>
                        </template>
                        <li class="page-item" :class="page === totalPages ? 'disabled' : ''">
                            <button class="page-link" @click="page = Math.min(totalPages, page + 1)" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Estado Vacío -->
            <div x-show="filtered.length === 0" class="text-center py-5">
                <i class="bi bi-search fs-1 text-secondary opacity-25 d-block mb-3"></i>
                <h3 class="h5 text-secondary">No hay coincidencias para "<span x-text="q"></span>"</h3>
                <p class="text-muted small">Prueba con otros términos o limpia el filtro.</p>
                <button class="btn btn-sm btn-outline-primary rounded-pill mt-2" @click="q = ''">Limpiar búsqueda</button>
            </div>
        </div>
    <?php endif; ?>
</div>
