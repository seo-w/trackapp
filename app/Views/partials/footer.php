<?php

declare(strict_types=1);

/** @var array<string, mixed> $app */

$baseName = htmlspecialchars((string) ($app['name'] ?? 'TrackApp'), ENT_QUOTES, 'UTF-8');
?>
<footer class="track-footer py-5 mt-auto" aria-label="Pie de página">
    <div class="container">
        <div class="row g-4 align-items-center">
            <!-- Columna 1: Identidad y Eslogan -->
            <div class="col-lg-7 text-center text-lg-start">
                <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-2 mb-3">
                    <span class="track-brand fs-4"><?= $baseName ?></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-2 py-1 rounded-pill" style="font-size: 0.65rem; letter-spacing: 1px;">v2.5</span>
                </div>
                <h5 class="fw-bold mb-2" style="color: var(--track-primary); letter-spacing: -0.02em;">Dominio total sobre tu logística.</h5>
                <p class="text-muted small mb-0" style="max-width: 500px; line-height: 1.6;">
                    Inteligencia analítica diseñada para optimizar resultados y normalizar el historial de tus pedidos Merkaweb en tiempo real.
                </p>
            </div>

            <!-- Columna 2: Navegación y Seguridad -->
            <div class="col-lg-5">
                <div class="d-flex flex-column align-items-center align-items-lg-end">
                    <nav class="d-flex gap-3 mb-4 flex-wrap justify-content-center justify-content-lg-end">
                        <a href="/consultas" class="footer-link">Consultas</a>
                        <a href="/estadisticas" class="footer-link">Estadísticas</a>
                        <a href="/configuracion" class="footer-link">Configuración</a>
                    </nav>
                    
                    <div class="d-flex align-items-center gap-2 py-2 px-3 rounded-pill bg-dark bg-opacity-10 border border-secondary border-opacity-10" style="background: rgba(var(--track-primary-rgb), 0.03);">
                        <i class="bi bi-shield-lock-fill text-info" style="font-size: 0.9rem;"></i>
                        <span class="text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">Protocolo de cifrado activo</span>
                    </div>
                    
                    <div class="mt-3 text-secondary small opacity-50">
                        &copy; <?= date('Y') ?> <?= $baseName ?>. Todos los derechos reservados.
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
