<?php

declare(strict_types=1);

/** @var string $heading */
/** @var string $subtitle */
/** @var list<array<string, mixed>> $logs */
/** @var bool $dbUnavailable */

$h = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
$sub = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
?>
<div class="container track-page-header">
    <h1 class="track-page-title h2 mb-2"><?= $h ?></h1>
    <p class="track-page-lead mb-0"><?= $sub ?></p>
</div>

<div class="container pb-5">
    <?php if ($dbUnavailable): ?>
        <div class="alert alert-warning track-settings-alert" role="alert">
            <i class="bi bi-database-exclamation me-2" aria-hidden="true"></i>
            No se pudo leer el historial. Activa <code>DB_ENABLED</code>, configura las credenciales y ejecuta <code>php database/migrate.php</code>.
        </div>
    <?php elseif ($logs === []): ?>
        <div class="alert alert-light border track-settings-alert" role="status">
            <i class="bi bi-journal-text me-2 text-secondary" aria-hidden="true"></i>
            Aún no hay registros. Las consultas desde <a href="/consultas">Consultas</a> aparecerán aquí automáticamente.
        </div>
    <?php else: ?>
        <p class="text-secondary small mb-3" id="historial-desc">
            Mostrando las <?= count($logs) ?> entradas más recientes.
        </p>

        <div class="d-none d-lg-block">
            <div class="card track-card overflow-hidden">
                <div class="table-responsive historial-table-wrap">
                    <table class="table table-hover align-middle mb-0 small historial-table" aria-describedby="historial-desc">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Fecha y hora</th>
                                <th scope="col">Estados consultados</th>
                                <th scope="col" class="text-end">Resultados</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $row): ?>
                                <?php
                                $when = (string) ($row['created_at'] ?? '');
                                $states = (string) ($row['requested_states'] ?? '');
                                $count = (int) ($row['result_count'] ?? 0);
                                $ok = (int) ($row['success'] ?? 0) === 1;
                                $err = (string) ($row['error_message'] ?? '');
                                ?>
                                <tr>
                                    <td class="text-nowrap text-secondary"><?= $when !== '' ? htmlspecialchars($when, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                    <td><span class="font-monospace"><?= $states !== '' ? htmlspecialchars($states, ENT_QUOTES, 'UTF-8') : '—' ?></span></td>
                                    <td class="text-end fw-medium"><?= $count ?></td>
                                    <td>
                                        <?php if ($ok): ?>
                                            <span class="badge rounded-pill text-bg-success"><?= 'Éxito' ?></span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill text-bg-danger"><?= 'Error' ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="historial-msg-cell">
                                        <?php if ($err !== ''): ?>
                                            <span class="d-inline-block text-break small" title="<?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="text-secondary">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-lg-none historial-mobile" role="list">
            <?php foreach ($logs as $row): ?>
                <?php
                $when = (string) ($row['created_at'] ?? '');
                $states = (string) ($row['requested_states'] ?? '');
                $count = (int) ($row['result_count'] ?? 0);
                $ok = (int) ($row['success'] ?? 0) === 1;
                $err = (string) ($row['error_message'] ?? '');
                ?>
                <div class="card track-card mb-3" role="listitem">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="small text-secondary text-uppercase consulta-kicker">Fecha y hora</div>
                            <?php if ($ok): ?>
                                <span class="badge rounded-pill text-bg-success">Éxito</span>
                            <?php else: ?>
                                <span class="badge rounded-pill text-bg-danger">Error</span>
                            <?php endif; ?>
                        </div>
                        <p class="fw-medium mb-3"><?= $when !== '' ? htmlspecialchars($when, ENT_QUOTES, 'UTF-8') : '—' ?></p>
                        <dl class="row small mb-0 consulta-dl">
                            <dt class="col-5 text-secondary">Estados</dt>
                            <dd class="col-7 mb-2 font-monospace text-break"><?= $states !== '' ? htmlspecialchars($states, ENT_QUOTES, 'UTF-8') : '—' ?></dd>
                            <dt class="col-5 text-secondary">Resultados</dt>
                            <dd class="col-7 mb-2 fw-medium"><?= $count ?></dd>
                            <?php if ($err !== ''): ?>
                                <dt class="col-5 text-secondary">Mensaje</dt>
                                <dd class="col-7 mb-0 text-break small"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
