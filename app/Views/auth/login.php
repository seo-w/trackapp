<?php

declare(strict_types=1);

/** @var string $title */
/** @var string|null $flashError */
/** @var string|null $flashSuccess */

$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        :root {
            --track-login-bg: #030712;
            --track-login-accent: #06b6d4;
            --track-login-glow: rgba(6, 182, 212, 0.4);
        }

        body {
            background: var(--track-login-bg);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .login-orb {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--track-login-glow) 0%, rgba(6, 182, 212, 0) 70%);
            z-index: 0;
            filter: blur(80px);
            animation: orbPulse 15s infinite alternate ease-in-out;
        }

        .orb-1 { top: -200px; left: -200px; }
        .orb-2 { bottom: -200px; right: -200px; background: radial-gradient(circle, rgba(168, 85, 247, 0.3) 0%, rgba(168, 85, 247, 0) 70%); }

        @keyframes orbPulse {
            0% { transform: scale(1) translate(0, 0); opacity: 0.5; }
            100% { transform: scale(1.3) translate(50px, 50px); opacity: 0.8; }
        }

        .login-card {
            background: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .login-card:hover {
            transform: translateY(-5px);
            border-color: rgba(6, 182, 212, 0.3);
        }

        .login-logo {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--track-login-accent) 0%, #3b82f6 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            color: white;
            font-size: 2.25rem;
            box-shadow: 0 10px 20px -5px rgba(6, 182, 212, 0.5);
            animation: logoFloat 6s infinite ease-in-out;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-title {
            color: white;
            font-weight: 800;
            text-align: center;
            font-size: 1.85rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .login-subtitle {
            color: var(--track-text-muted);
            text-align: center;
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 0.8rem 1.2rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--track-login-accent);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
            color: white;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding-right: 0.5rem;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--track-login-accent) 0%, #3b82f6 100%);
            border: none;
            border-radius: 16px;
            padding: 1rem;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: scale(1.02);
            filter: brightness(1.1);
            box-shadow: 0 10px 25px -5px rgba(6, 182, 212, 0.5);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .alert-premium {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            color: #f87171;
            padding: 1rem;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success-premium {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            color: #34d399;
            padding: 1rem;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.75rem;
            text-align: center;
            margin-top: 2rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
        }

        .glow-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            display: inline-block;
            box-shadow: 0 0 10px #22c55e;
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="login-orb orb-1"></div>
    <div class="login-orb orb-2"></div>

    <div class="login-card shadow-2xl">
        <div class="login-logo">
            <i class="bi bi-truck-front-fill"></i>
        </div>
        <h1 class="login-title">TrackApp</h1>
        <p class="login-subtitle">Introduce tus credenciales de Merkaweb para continuar</p>

        <?php if ($flashError): ?>
            <div class="alert-premium">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <div><?= htmlspecialchars($flashError) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess): ?>
            <div class="alert-success-premium">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div><?= htmlspecialchars($flashSuccess) ?></div>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label for="email" class="form-label small text-uppercase fw-bold opacity-50 px-2 mb-2">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nombre@ejemplo.com" required autofocus>
            </div>
            
            <div class="mb-5">
                <div class="d-flex justify-content-between">
                    <label for="password" class="form-label small text-uppercase fw-bold opacity-50 px-2 mb-2">Contraseña</label>
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control border-end-0" style="border-radius: 16px 0 0 16px;" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword" style="border-radius: 0 16px 16px 0; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.5);">
                        <i class="bi bi-eye-fill" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login mb-4">
                <span>Acceder al Sistema</span>
                <i class="bi bi-arrow-right ms-2"></i>
            </button>

            <div class="text-center">
                <a href="/recovery" class="text-secondary text-decoration-none small opacity-75 hover-opacity-100">
                    <i class="bi bi-shield-lock me-1"></i> ¿Olvidaste tus datos? (Admin)
                </a>
            </div>
        </form>

        <div class="footer-text mt-5">
            <span class="glow-dot"></span> Acceso Seguro Protegido por Token Merkaweb
        </div>
    </div>

    <!-- Particles Background Logic (Optional visual enhancement) -->
    <script>
        // Toggle password visibility
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                eyeIcon.className = type === 'password' ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
            });
        }

        // Smooth transition effect
        document.querySelector('form').addEventListener('submit', function() {
            document.querySelector('.login-card').style.opacity = '0.5';
            document.querySelector('.login-card').style.transform = 'scale(0.98)';
        });
    </script>
</body>
</html>
