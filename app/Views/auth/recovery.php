<?php

declare(strict_types=1);

/** @var string $title */
/** @var string|null $flashError */
/** @var string|null $flashSuccess */
/** @var int $step */
/** @var string $email */

$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body { background: #030712; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Inter', sans-serif; }
        .recovery-card {
            background: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .step-icon {
            width: 60px; height: 60px;
            background: rgba(6, 182, 212, 0.1);
            color: #06b6d4;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin: 0 auto 1.5rem auto;
            border: 1px solid rgba(6, 182, 212, 0.2);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: white;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #06b6d4;
            color: white;
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1);
        }
        .btn-premium {
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            border: none; border-radius: 12px; padding: 0.8rem;
            font-weight: 700; width: 100%; color: white;
            transition: all 0.3s;
        }
        .btn-premium:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 10px 15px -3px rgba(6, 182, 212, 0.4); }
    </style>
</head>
<body>
    <div class="recovery-card">
        <div class="text-center mb-4">
            <div class="step-icon">
                <i class="bi <?= $step === 1 ? 'bi-shield-lock' : 'bi-key' ?>"></i>
            </div>
            <h1 class="h3 fw-bold text-white"><?= $step === 1 ? 'Recuperación Admin' : 'Nueva Contraseña' ?></h1>
            <p class="text-secondary small"><?= $step === 1 ? 'Validación de Token Físico' : 'Actualiza tus credenciales locales' ?></p>
        </div>

        <?php if ($flashError): ?>
            <div class="alert alert-danger border-0 rounded-4 p-3 small mb-4 bg-danger bg-opacity-10 text-danger d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form action="/recovery" method="POST" autocomplete="off">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small text-secondary fw-bold text-uppercase px-1">Email Registrado</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@sistema.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label small text-secondary fw-bold text-uppercase px-1">Token de Seguridad</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0" style="border-radius: 12px 0 0 12px; border-color: rgba(255,255,255,0.1);"><i class="bi bi-file-earmark-code text-secondary"></i></span>
                        <input type="password" name="token" class="form-control border-start-0" style="border-radius: 0 12px 12px 0;" placeholder="Contenido del archivo .token" required>
                    </div>
                    <div class="form-text opacity-50 small mt-2">Busca el archivo asignado a tu email en la carpeta de tokens del servidor.</div>
                </div>
                <button type="submit" class="btn btn-premium mb-3">Verificar Acceso</button>
                <div class="text-center">
                    <a href="/login" class="text-secondary text-decoration-none small"><i class="bi bi-arrow-left"></i> Volver al login</a>
                </div>
            </form>
        <?php else: ?>
            <form action="/reset-password" method="POST" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <div class="mb-3">
                    <label class="form-label small text-secondary fw-bold text-uppercase px-1">Nueva Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label small text-secondary fw-bold text-uppercase px-1">Confirmar Contraseña</label>
                    <input type="password" name="confirm" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-premium mb-3">Actualizar y Entrar</button>
                <div class="alert alert-info border-0 rounded-4 p-3 bg-info bg-opacity-10 text-info smaller mb-0">
                    <i class="bi bi-info-circle me-1"></i> Por seguridad, el token físico actual se invalidará y se generará uno nuevo en el servidor tras este cambio.
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
