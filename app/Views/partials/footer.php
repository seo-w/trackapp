<?php

declare(strict_types=1);

/** @var array<string, mixed> $app */

$baseName = htmlspecialchars((string) ($app['name'] ?? 'TrackApp'), ENT_QUOTES, 'UTF-8');
?>
<footer class="track-footer py-4 mt-auto" aria-label="Pie de página">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
        <div>
            <strong><?= $baseName ?></strong> —
            Consultas Merkaweb por estado, resultados normalizados y registro en historial. PHP puro: sin Node ni frameworks de aplicación.
        </div>
        <div class="text-md-end small text-secondary">
            Las claves y tokens no se exponen en la interfaz; solo datos cifrados o variables de entorno en el servidor.
        </div>
    </div>
</footer>
