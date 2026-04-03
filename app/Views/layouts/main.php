<?php

declare(strict_types=1);

/** @var string $content */
/** @var string $title */
/** @var array<string, mixed> $app */

$docTitle = htmlspecialchars($title . ' — ' . ($app['name'] ?? 'TrackApp'), ENT_QUOTES, 'UTF-8');
$charset = htmlspecialchars((string) ($app['charset'] ?? 'UTF-8'), ENT_QUOTES, 'UTF-8');
$lang = htmlspecialchars((string) ($app['locale'] ?? 'es'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="<?= $charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema inteligente de seguimiento logístico y estadísticas para dropshipping. Optimiza tus entregas y controla tus devoluciones en tiempo real.">
    <title><?= $docTitle ?></title>
    <script>
        (function() {
            const theme = localStorage.getItem('track_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <!-- Preconnect & DNS-Prefetch (CWV Optimization) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://images.weserv.nl" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://images.weserv.nl">

    <!-- Premium Typography: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <!-- Frameworks & UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/app.css">

    <!-- Alpine.js (Lite Reactivity) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <a class="visually-hidden-focusable position-absolute top-0 start-0 p-3 m-2 bg-body shadow-sm rounded text-decoration-none z-3" href="#main-content">Saltar al contenido principal</a>

    <?php require BASE_PATH . '/app/Views/partials/nav.php'; ?>

    <main id="main-content" tabindex="-1">
        <?= $content ?>
    </main>

    <?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
    <?php require BASE_PATH . '/app/Views/partials/toasts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('themeToggle');
        const icon = document.getElementById('themeIcon');
        const html = document.documentElement;
        
        function updateIcon(theme) {
            if (!icon) return;
            icon.className = theme === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
        }

        updateIcon(html.getAttribute('data-theme'));

        if (toggle) {
            toggle.addEventListener('click', () => {
                const current = html.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-theme', next);
                localStorage.setItem('track_theme', next);
                updateIcon(next);
            });
        }
    });
    </script>

</body>
</html>
