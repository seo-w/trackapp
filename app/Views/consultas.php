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
        background: #fff;
        border: 1px solid var(--track-border);
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .track-input-group {
        position: relative;
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.6rem;
        padding: 0.5rem 0.75rem;
        transition: all 0.2s;
    }
    .track-input-group:focus-within {
        border-color: var(--track-accent);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }
    .track-label-mini {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 0.4rem;
        display: block;
    }
    /* Estados como Chips/Tags */
    .track-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #64748b;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
        user-select: none;
    }
    .track-status-pill:hover { background: #e2e8f0; }

    /* Estados Activos con alto contraste */
    .track-status-pill.active[data-status="2"] { background: #64748b; color: #fff; border-color: #64748b; }
    .track-status-pill.active[data-status="3"] { background: #10b981; color: #fff; border-color: #10b981; }
    .track-status-pill.active[data-status="4"] { background: #ef4444; color: #fff; border-color: #ef4444; }
    .track-status-pill.active[data-status="5"] { background: #3b82f6; color: #fff; border-color: #3b82f6; }

    .btn-search-main {
        background: #0d6efd !important;
        color: #fff !important;
        font-weight: 700;
        padding: 0.75rem 2rem;
        border-radius: 0.6rem;
        border: none;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
    }
    .btn-search-main:hover {
        background: #004dc2 !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(13, 110, 253, 0.35);
    }
</style>

<div class="container track-page-header">
    <div class="d-flex align-items-center gap-3">
        <div class="track-icon-circle shadow-sm">
            <i class="bi bi-funnel"></i>
        </div>
        <div>
            <h1 class="track-page-title h3 mb-0">Panel de Consultas</h1>
            <p class="track-page-lead small mb-0"><?= $sub ?></p>
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
        <form method="post" action="/consultas" class="row align-items-end g-4">
            <?= csrf_field() ?>
            
            <!-- Rango de Fechas -->
            <div class="col-12 col-xl-4">
                <span class="track-label-mini">Periodo de tiempo</span>
                <div class="d-flex gap-2">
                    <div class="track-input-group flex-fill">
                        <i class="bi bi-calendar-event me-2 text-secondary"></i>
                        <input type="date" class="form-control border-0 bg-transparent p-0 small" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde ?? '', ENT_QUOTES, 'UTF-8') ?>" title="Desde">
                    </div>
                    <div class="track-input-group flex-fill">
                        <i class="bi bi-calendar-check me-2 text-secondary"></i>
                        <input type="date" class="form-control border-0 bg-transparent p-0 small" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta ?? '', ENT_QUOTES, 'UTF-8') ?>" title="Hasta">
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
            <div class="col-12 col-xl-3">
                <div class="d-flex gap-2 justify-content-xl-end">
                    <a href="/consultas" class="btn btn-link text-secondary text-decoration-none small fw-bold mt-2">Limpiar</a>
                    <button type="submit" class="btn btn-search-main w-100 w-xl-auto">
                        <i class="bi bi-search me-1"></i> Consultar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if ($showResults && $filterSummary !== null && $filterSummary !== ''): ?>
        <div class="card track-card border-0 bg-body-secondary mb-4">
            <div class="card-body py-3 px-4">
                <div class="small mb-1 text-secondary text-uppercase fw-semibold consulta-kicker">Filtros activos</div>
                <p class="mb-0 fw-medium"><?= htmlspecialchars($filterSummary, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
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
            <div class="card track-card mb-4 shadow-sm border-0">
                <div class="card-body py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="flex-grow-1">
                            <label for="consultaBuscar" class="form-label small text-secondary mb-1 fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Filtro Rápido</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-search"></i></span>
                                <input type="search" class="form-control border-start-0" id="consultaBuscar" x-model="q" @input="page = 1" placeholder="Ej: Pedido #, Cliente, Transportadora o Ciudad..." autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                             <span class="text-secondary small">Mostrar:</span>
                             <select class="form-select form-select-sm w-auto" x-model="pageSize" @change="page = 1">
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
                <div class="card track-card border-0 shadow-sm overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-premium table-striped-soft" style="font-size: 0.85rem;">
                            <thead>
                                <tr>
                                    <th @click="sortBy('id')" class="cursor-pointer" style="cursor:pointer">Pedido <i class="bi" :class="sortKey === 'id' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th>Producto</th>
                                    <th @click="sortBy('customer')" class="cursor-pointer" style="cursor:pointer">Cliente <i class="bi" :class="sortKey === 'customer' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th>Guía</th>
                                    <th @click="sortBy('city')" class="cursor-pointer" style="cursor:pointer">Ubicación <i class="bi" :class="sortKey === 'city' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th @click="sortBy('statusCode')" class="cursor-pointer text-center" style="cursor:pointer">Estado <i class="bi" :class="sortKey === 'statusCode' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th @click="sortBy('date')" class="cursor-pointer" style="cursor:pointer">Fecha <i class="bi" :class="sortKey === 'date' ? (sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25'"></i></th>
                                    <th class="text-end px-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="o in paginated" :key="o.id">
                                    <tr :class="o.statusCode === 4 ? 'semaphore-danger' : (o.statusCode === 3 || o.statusCode === 5 ? 'semaphore-success' : 'semaphore-info')">
                                        <td>
                                            <div class="fw-bold text-dark" x-text="'#' + o.id"></div>
                                            <div class="small text-secondary text-uppercase fw-bold" style="font-size: 0.65rem;" x-text="o.carrier.substring(0,8)"></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <template x-if="o.image">
                                                    <img :src="o.image" class="rounded border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;" loading="lazy">
                                                </template>
                                                <template x-if="!o.image">
                                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center text-secondary" style="width: 36px; height: 36px;"><i class="bi bi-box small"></i></div>
                                                </template>
                                                <div style="line-height: 1.2">
                                                    <div class="fw-bold text-truncate" style="max-width: 150px;" x-text="o.name || 'Sin nombre'"></div>
                                                    <div class="text-secondary" style="font-size: 0.7rem;" x-text="o.warehouse"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark" x-text="o.customer"></div>
                                            <div class="d-flex align-items-center gap-1 small text-secondary">
                                                <span x-text="o.phone"></span>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" @click="navigator.clipboard.writeText(o.phone).then(() => notify('Teléfono copiado', 'success'))"><i class="bi bi-clipboard small"></i></button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-monospace small d-flex align-items-center gap-1">
                                                <span class="text-truncate" style="max-width: 100px;" x-text="o.guide"></span>
                                                <button type="button" class="btn btn-sm p-0 text-secondary" @click="navigator.clipboard.writeText(o.guide).then(() => notify('Guía copiada', 'success'))"><i class="bi bi-clipboard small"></i></button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark lh-1 mb-1" x-text="o.city"></div>
                                            <div class="text-secondary small" x-text="o.dept"></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill px-3 py-2 fw-semibold" :class="getBadgeClass(o.statusCode)" x-text="o.status"></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark lh-1 mb-1" x-text="o.date"></div>
                                            <div class="text-secondary small" x-text="o.time"></div>
                                        </td>
                                        <td class="text-end px-4">
                                            <template x-if="o.tracking">
                                                <a :href="o.tracking" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">Tracking</a>
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
                    <div class="card track-card border-0 shadow-sm mb-3 overflow-hidden" :class="o.statusCode === 4 ? 'semaphore-danger' : (o.statusCode === 3 || o.statusCode === 5 ? 'semaphore-success' : 'semaphore-info')">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <template x-if="o.image">
                                        <img :src="o.image" class="rounded border shadow-sm" style="width: 42px; height: 42px; object-fit: cover;">
                                    </template>
                                    <div>
                                        <div class="small text-secondary text-uppercase fw-bold" style="font-size: 0.6rem;">Pedido #<span x-text="o.id"></span></div>
                                        <div class="fw-bold text-dark" x-text="o.name"></div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill px-2" :class="getBadgeClass(o.statusCode)" x-text="o.status"></span>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <label class="text-secondary d-block mb-0">Cliente</label>
                                    <div class="fw-medium text-dark" x-text="o.customer"></div>
                                </div>
                                <div class="col-6">
                                    <label class="text-secondary d-block mb-0">Ciudad</label>
                                    <div class="fw-medium text-dark" x-text="o.city"></div>
                                </div>
                                <div class="col-6">
                                    <label class="text-secondary d-block mb-0">Guía</label>
                                    <div class="font-monospace text-dark" x-text="o.guide"></div>
                                </div>
                                <div class="col-6">
                                    <label class="text-secondary d-block mb-0">Fecha</label>
                                    <div class="text-dark" x-text="o.date"></div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-top d-flex justify-content-between">
                                <button class="btn btn-sm btn-light border rounded-pill px-3" @click="navigator.clipboard.writeText(o.guide).then(() => notify('Guía copiada', 'success'))"><i class="bi bi-clipboard me-1"></i>Copiar Guía</button>
                                <template x-if="o.tracking">
                                    <a :href="o.tracking" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3">Rastrear</a>
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
