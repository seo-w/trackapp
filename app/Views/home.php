<?php

declare(strict_types=1);

/** @var string $heading */

$h = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
?>
<div class="container track-page-header">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="track-pill mb-3"><i class="bi bi-info-circle" aria-hidden="true"></i> Versión de producción</span>
            <h1 class="track-page-title display-6"><?= $h ?></h1>
            <p class="track-page-lead lead">
                Centraliza la consulta del estado de pedidos y gestiona el rendimiento financiero de tu tienda. 
                Sincronización en tiempo real con Merkaweb para un seguimiento preciso de cada despacho.
            </p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a class="btn btn-primary btn-lg px-4" href="/consultas">Consultar Órdenes</a>
                <a class="btn btn-outline-secondary btn-lg px-4" href="/estadisticas">Panel Financiero</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card track-card track-card--emphasis p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="track-icon-circle"><i class="bi bi-diagram-3" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="h5 mb-1">Capacidades de TrackApp</h2>
                        <p class="text-secondary small mb-0">
                            Consulta estados logísticos (2, 3, 4, 5), gestiona el historial de búsquedas y analiza el ROI de tus campañas de Dropshipping.
                        </p>
                    </div>
                </div>
                <ul class="list-unstyled small text-secondary mb-0 ps-1">
                    <li class="mb-2"><i class="bi bi-check2-circle text-primary me-2" aria-hidden="true"></i>Cálculo automático de utilidades y pauta.</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-primary me-2" aria-hidden="true"></i>Soporte completo para SQLite y MySQL.</li>
                    <li><i class="bi bi-check2-circle text-primary me-2" aria-hidden="true"></i>Integración con 6+ transportadoras.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="row g-4 mt-5">
        <div class="col-md-4">
            <div class="card track-card h-100 border-0 shadow-sm">
                <div class="card-body p-4 d-flex flex-column">
                    <i class="bi bi-search fs-1 text-primary mb-3"></i>
                    <h3 class="h5 fw-bold">Rastreador</h3>
                    <p class="text-secondary small flex-grow-1">Busca pedidos individualmente o por estado para dar soporte rápido a tus clientes.</p>
                    <a href="/consultas" class="btn btn-link p-0 text-decoration-none fw-semibold stretched-link">Ir a consultas <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card track-card h-100 border-0 shadow-sm">
                <div class="card-body p-4 d-flex flex-column">
                    <i class="bi bi-bar-chart-line fs-1 text-primary mb-3"></i>
                    <h3 class="h5 fw-bold">Estadísticas</h3>
                    <p class="text-secondary small flex-grow-1">Mira tu utilidad real descontando costos de producto, envíos, devoluciones y pauta.</p>
                    <a href="/estadisticas" class="btn btn-link p-0 text-decoration-none fw-semibold stretched-link">Abrir finanzas <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card track-card h-100 border-0 shadow-sm">
                <div class="card-body p-4 d-flex flex-column">
                    <i class="bi bi-gear fs-1 text-primary mb-3"></i>
                    <h3 class="h5 fw-bold">Ajustes</h3>
                    <p class="text-secondary small flex-grow-1">Configura tu Token de Merkaweb, el ID de tienda y las llaves de encriptación.</p>
                    <a href="/configuracion" class="btn btn-link p-0 text-decoration-none fw-semibold stretched-link">Ir a configuración <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
