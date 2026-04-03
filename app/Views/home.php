<?php

declare(strict_types=1);

/** @var string $heading */

$h = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
?>
<div class="container py-5">
    <div class="row align-items-center g-5">
        <!-- Main Dashboard Column -->
        <div class="col-lg-7">
            <div class="mb-4">
                <span class="track-pill mb-3 d-inline-block"><i class="bi bi-cpu" aria-hidden="true"></i> Neural Network Active</span>
                <h1 class="track-page-title display-3 fw-bold text-uppercase" style="letter-spacing: 0.05em; font-family: 'Space Grotesk', sans-serif;"><?= $h ?></h1>
                <p class="track-page-lead fs-5 mt-4" style="line-height: 1.6;">
                    Tu centro de comando para logística inteligente. 
                    Control total sobre el flujo de datos con asimetría dinámica y diseño de vanguardia.
                </p>
            </div>

            <!-- Dashboard Preview -->
            <div class="mt-5 d-none d-md-block">
                <div class="row g-4 asymmetric-stack">
                    <div class="col-8">
                        <div class="card p-4" style="background: var(--track-surface); border: 1px solid var(--track-border); border-radius: 12px; box-shadow: 0 10px 30px var(--track-accent-glow);">
                            <div class="d-flex align-items-center gap-3">
                                <div class="track-icon-circle shadow-lg"><i class="bi bi-radar"></i></div>
                                <div>
                                    <h3 class="h5 mb-0" style="color: var(--track-text);">Scanner Principal</h3>
                                    <small class="text-muted">Sincronización Multicanal Activa</small>
                                </div>
                                <div class="ms-auto text-primary fw-bold">ONLINE</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 offset-5">
                        <div class="card p-4" style="margin-top: -1.5rem; margin-left: 2rem; backdrop-filter: var(--track-blur); background: var(--track-surface-high); border: 1px solid var(--track-border); box-shadow: 0 32px 64px rgba(0, 0, 0, 0.15); border-radius: 12px; position: relative; z-index: 2;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="track-icon-circle" style="color: var(--track-success); border-color: rgba(var(--track-success-rgb, 32, 201, 151), 0.2); box-shadow: 0 0 15px rgba(var(--track-success-rgb, 32, 201, 151), 0.15); background: rgba(var(--track-success-rgb, 32, 201, 151), 0.05); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="bi bi-shield-check"></i></div>
                                <div>
                                    <h3 class="h5 mb-0" style="color: var(--track-text);">Seguridad de Nodo</h3>
                                    <small class="text-muted">AES-256 Encryption Layer</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex flex-wrap gap-4 mt-5 pt-3">
                <a class="btn btn-lg px-5 py-3 track-btn-kinetic" href="/consultas" style="background: var(--track-primary); color: var(--track-bg); border-radius: 0.25rem; font-weight: 800; border: none; box-shadow: 0 0 20px var(--track-accent-glow);">
                    <i class="bi bi-search me-2"></i> Iniciar Consulta
                </a>
                <a class="btn btn-outline-secondary btn-lg px-5 py-3 track-btn-glass" href="/estadisticas" style="background: var(--track-surface-high); backdrop-filter: var(--track-blur); border: 1px solid var(--track-border); color: var(--track-text); border-radius: 0.25rem;">
                    <i class="bi bi-bar-chart-steps me-2"></i> Ver Analíticas
                </a>
            </div>
        </div>

        <!-- Sidebar / Visual column -->
        <div class="col-lg-5 ps-lg-5">
            <div class="card track-card track-card--emphasis p-5 position-relative overflow-hidden" style="background: var(--track-surface); border: 1px solid var(--track-border);">
                <!-- Ambient Glow inside card -->
                <div style="position:absolute; top:-15%; right:-15%; width:200px; height:200px; background:var(--track-primary); filter:blur(80px); opacity:0.1; z-index: 0;"></div>
                
                <div style="position: relative; z-index: 1;">
                    <h2 class="h5 mb-4 fw-bold text-uppercase" style="color: var(--track-text); letter-spacing: 0.1em; font-family: 'Space Grotesk', sans-serif;">Nodos de Control</h2>
                
                <div class="d-grid gap-4">
                    <a href="/consultas" class="text-decoration-none group-hover">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-2 transition-all group-hover" style="background: var(--track-surface-high); border: 1px solid var(--track-border);">
                            <i class="bi bi-box-seam fs-4" style="color: var(--track-primary); text-shadow: 0 0 10px var(--track-accent-glow);"></i>
                            <div>
                                <div class="fw-bold mb-1" style="color: var(--track-text); letter-spacing: 0.02em;">Órdenes</div>
                                <div class="small" style="color: var(--track-muted); opacity: 0.8;">Gestión de estados 2-5</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto" style="color: var(--track-muted);"></i>
                        </div>
                    </a>

                    <a href="/estadisticas" class="text-decoration-none group-hover">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-2 transition-all" style="background: var(--track-surface-high); border: 1px solid var(--track-border);">
                            <i class="bi bi-wallet2 fs-4" style="color: var(--track-info); text-shadow: 0 0 10px rgba(var(--track-info-rgb, 0,210,255), 0.3);"></i>
                            <div>
                                <div class="fw-bold mb-1" style="color: var(--track-text); letter-spacing: 0.02em;">Finanzas</div>
                                <div class="small" style="color: var(--track-muted); opacity: 0.8;">ROI y Utilidad Real</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto" style="color: var(--track-muted);"></i>
                        </div>
                    </a>

                    <a href="/configuracion" class="text-decoration-none group-hover">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-2 transition-all" style="background: var(--track-surface-high); border: 1px solid var(--track-border);">
                            <i class="bi bi-sliders2 fs-4" style="color: var(--track-warning); text-shadow: 0 0 10px rgba(var(--track-warning-rgb, 255,184,0), 0.3);"></i>
                            <div>
                                <div class="fw-bold mb-1" style="color: var(--track-text); letter-spacing: 0.02em;">Sistema</div>
                                <div class="small" style="color: var(--track-muted); opacity: 0.8;">API y Seguridad</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto" style="color: var(--track-muted);"></i>
                        </div>
                    </a>
                </div>

                <div class="mt-5 p-4 rounded-2 position-relative overflow-hidden" style="background: #060e1d; border: 1px solid rgba(132, 148, 149, 0.1);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="spinner-grow spinner-grow-sm text-primary" role="status"></div>
                        <span class="small fw-bold text-uppercase tracking-wider">Live System Feed</span>
                    </div>
                    <div class="font-monospace small text-muted" style="font-size: 0.75rem; line-height: 1.4;">
                        > Scanning Merkaweb API... OK<br>
                        > Encryption Layer: BASE64_CRYPT... OK<br>
                        > Database Drivers: SQLite... READY
                    </div>
                </div>
                    </div>
                </div>
        </div>
    </div>
</div>
