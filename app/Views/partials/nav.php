<?php

declare(strict_types=1);

/** @var string $currentPath */
/** @var array<string, mixed> $app */

$baseName = htmlspecialchars((string) ($app['name'] ?? 'TrackApp'), ENT_QUOTES, 'UTF-8');
$path = $currentPath;

function navActive(string $currentPath, string $route): string
{
    return trim($currentPath, '/') === trim($route, '/') ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg track-navbar py-2" aria-label="Principal">
    <div class="container">
        <a class="navbar-brand track-brand d-flex align-items-center gap-2" href="/">
            <i class="bi bi-truck-front-fill" aria-hidden="true"></i>
            <span><?= $baseName ?></span>
            <?php if (!empty($tienda_name)): ?>
                <span class="ms-2 opacity-75 px-3 py-1 rounded-pill" style="font-size: 0.7rem; background: rgba(var(--track-primary-rgb, 0,255,255), 0.1); border: 1px solid rgba(var(--track-primary-rgb, 0,255,255), 0.2); color: var(--track-primary); letter-spacing: 0.05em; font-weight: 700;">
                    <i class="bi bi-shop me-1"></i> <?= htmlspecialchars($tienda_name) ?>
                </span>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#trackMainNav" aria-controls="trackMainNav" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="trackMainNav">
            <ul class="navbar-nav ms-auto gap-lg-1 mt-3 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link track-nav-link <?= navActive($path, '/') ?>" href="/"<?= navActive($path, '/') === 'active' ? ' aria-current="page"' : '' ?>>Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link track-nav-link <?= navActive($path, '/consultas') ?>" href="/consultas"<?= navActive($path, '/consultas') === 'active' ? ' aria-current="page"' : '' ?>>Consultas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link track-nav-link <?= navActive($path, '/estadisticas') ?>" href="/estadisticas"<?= navActive($path, '/estadisticas') === 'active' ? ' aria-current="page"' : '' ?>>Estadísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link track-nav-link <?= navActive($path, '/configuracion') ?>" href="/configuracion"<?= navActive($path, '/configuracion') === 'active' ? ' aria-current="page"' : '' ?>>Configuración</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link track-nav-link <?= navActive($path, '/historial') ?>" href="/historial"<?= navActive($path, '/historial') === 'active' ? ' aria-current="page"' : '' ?>>Historial</a>
                </li>
                <?php if (session()->get('user_role') === 'admin' || session()->get('user_role') === 'superadmin'): ?>
                    <li class="nav-item">
                        <a class="nav-link track-nav-link <?= navActive($path, '/usuarios') ?>" href="/usuarios"<?= navActive($path, '/usuarios') === 'active' ? ' aria-current="page"' : '' ?>>Usuarios</a>
                    </li>
                <?php endif; ?>
                <?php if (session()->has('user_id')): ?>
                    <li class="nav-item ms-lg-2">
                        <form action="/logout" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-link nav-link track-nav-link text-danger-hover" title="Cerrar Sessión">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    </li>
                <?php endif; ?>
                <li class="nav-item ms-lg-1 d-flex align-items-center">
                    <button id="themeToggle" class="btn btn-link nav-item track-nav-link px-2" title="Cambiar luz del sistema">
                        <i class="bi bi-moon-stars" id="themeIcon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>
