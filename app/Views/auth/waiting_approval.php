<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $user_email */

$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body { 
            background: var(--track-bg); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            font-family: 'Inter', sans-serif; 
            transition: background 0.5s ease;
        }
        .approval-card {
            background: var(--track-surface);
            backdrop-filter: var(--track-blur);
            border: 1px solid var(--track-border);
            border-radius: 32px;
            padding: 4rem 3rem;
            width: 100%;
            max-width: 550px;
            text-align: center;
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.2);
        }
        .pulse-icon {
            font-size: 4rem;
            color: var(--track-warning);
            margin-bottom: 2rem;
            display: inline-block;
            animation: pulse 2s infinite ease-in-out;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
    <script>
        (function() {
            const theme = localStorage.getItem('track_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
    <div class="approval-card shadow-lg">
        <div class="pulse-icon">
            <i class="bi bi-person-badge-fill"></i>
        </div>
        <h1 class="h2 fw-bold mb-3" style="color: var(--track-text);">Acceso en Revisión</h1>
        <p class="mb-4" style="color: var(--track-muted);">
            Hola <span class="fw-bold" style="color: var(--track-primary);"><?= htmlspecialchars($user_email) ?></span>. Tu cuenta ha sido registrada correctamente, pero un administrador debe aprobar tu acceso antes de que puedas utilizar las herramientas de TrackApp.
        </p>
        
        <div class="alert alert-warning border-0 rounded-4 bg-warning bg-opacity-10 text-warning p-3 small mb-5">
            <i class="bi bi-clock-history me-2"></i> Por favor, contacta con el administrador del sistema para agilizar tu aprobación.
        </div>

        <div class="d-grid gap-3">
            <button onclick="window.location.reload();" class="btn btn-primary rounded-pill py-2 fw-bold">
                <i class="bi bi-arrow-clockwise me-2"></i> Ver si ya fui aprobado
            </button>
            
            <form action="/logout" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-link text-decoration-none opacity-50 hover-opacity-100" style="color: var(--track-text);">Cerrar Sesión</button>
            </form>
        </div>
    </div>
</body>
</html>
