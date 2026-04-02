<?php

declare(strict_types=1);

/** @var string $heading */
/** @var bool $dbUnavailable */
/** @var array<string, string> $errors */
/** @var string $api_base_url */
/** @var string $tienda_id */
/** @var bool $has_stored_token */
/** @var mixed $flashSuccess */
/** @var mixed $flashError */

$h = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
$apiVal = htmlspecialchars($api_base_url, ENT_QUOTES, 'UTF-8');
$tiendaVal = htmlspecialchars($tienda_id, ENT_QUOTES, 'UTF-8');
$errApi = $errors['api_base_url'] ?? null;
$errTienda = $errors['tienda_id'] ?? null;
?>
<div class="container track-page-header">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
        <div>
            <h1 class="track-page-title h2 mb-1"><?= $h ?></h1>
            <p class="track-page-lead mb-0">
                Define la URL base del API, el identificador de tienda y el token de acceso. El token se guarda cifrado y no se muestra en pantalla.
            </p>
        </div>
        <?php if ($has_stored_token && ! $dbUnavailable): ?>
            <span class="track-pill align-self-start"><i class="bi bi-shield-lock" aria-hidden="true"></i> Token almacenado de forma segura</span>
        <?php endif; ?>
    </div>
</div>

<div class="container pb-5">
    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show track-settings-alert" role="status">
            <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if (is_string($flashError) && $flashError !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show track-settings-alert" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if ($dbUnavailable): ?>
        <div class="card track-card p-4">
            <h2 class="h5">Base de datos no disponible</h2>
            <p class="text-secondary mb-0">
                Configura las variables <code>DB_*</code>, activa <code>DB_ENABLED=true</code> y ejecuta <code>php database/migrate.php</code> antes de usar esta pantalla.
            </p>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card track-card track-card--emphasis p-4 p-md-5">
                    <form method="post" action="/configuracion" class="track-settings-form" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="mb-4" x-data="{ apiUrl: '<?= addslashes($apiVal) ?>' }">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="api_base_url" class="form-label fw-medium mb-0">URL base del API</label>
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small text-secondary" @click="navigator.clipboard.writeText(apiUrl).then(() => notify('URL copiada', 'success'))" title="Copiar URL"><i class="bi bi-clipboard me-1"></i>Copiar</button>
                            </div>
                            <input
                                type="url"
                                name="api_base_url"
                                id="api_base_url"
                                class="form-control form-control-lg <?= $errApi ? 'is-invalid' : '' ?>"
                                x-model="apiUrl"
                                placeholder="https://api.ejemplo.com"
                                maxlength="512"
                                required
                            >
                            <?php if ($errApi): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errApi, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <div class="form-text">Incluye el esquema (<code>https://</code>). Se normaliza quitando la barra final.</div>
                        </div>

                        <div class="mb-4">
                            <label for="tienda_id" class="form-label fw-medium">Identificador de tienda</label>
                            <input
                                type="text"
                                name="tienda_id"
                                id="tienda_id"
                                class="form-control form-control-lg <?= $errTienda ? 'is-invalid' : '' ?>"
                                value="<?= $tiendaVal ?>"
                                placeholder="(tienda_id)"
                                maxlength="191"
                                required
                            >
                            <?php if ($errTienda): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errTienda, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4" x-data="{ show: false }">
                            <label for="access_token" class="form-label fw-medium">Token de acceso</label>
                            <div class="input-group input-group-lg">
                                <input
                                    :type="show ? 'text' : 'password'"
                                    name="access_token"
                                    id="access_token"
                                    class="form-control font-monospace"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="<?= $has_stored_token ? 'Dejar vacío para conservar el token actual' : 'Pega el token aquí' ?>"
                                    aria-describedby="access_token_help token_toggle_label"
                                >
                                <button class="btn btn-outline-secondary" type="button" @click="show = !show" :aria-pressed="show" aria-labelledby="token_toggle_label">
                                    <span id="token_toggle_label" class="visually-hidden">Mostrar u ocultar token</span>
                                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="access_token_help" class="form-text">
                                El valor no se rellena desde el servidor. <?= $has_stored_token ? 'Si está vacío, se mantiene el token ya guardado.' : 'Se cifrará antes de almacenarlo.' ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end pt-2 border-top mt-4 pt-4">
                            <a class="btn btn-outline-secondary" href="/">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1" aria-hidden="true"></i>Guardar configuración
                            </button>
                        </div>
                    </form>

                    <form method="post" action="/configuracion/probar-conexion" class="mt-4 pt-3 border-top">
                        <?= csrf_field() ?>
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 justify-content-between">
                            <div>
                                <h2 class="h6 mb-1">Prueba de conexión</h2>
                                <p class="text-secondary small mb-0">
                                    Envía una petición real <span class="text-nowrap">GET <code>/ordenes/find</code></span> con <code>estado=2</code> usando la configuración <strong>ya guardada</strong>. No muestra el token.
                                </p>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary flex-shrink-0">
                                <i class="bi bi-lightning-charge me-1" aria-hidden="true"></i>Probar conexión
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>
