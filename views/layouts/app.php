<!DOCTYPE html>
<html lang="en" data-ui-theme="civic-ledger">
<head>
    <?php $layoutCsrfToken = $csrfToken ?? \App\Middleware\CsrfMiddleware::token(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($layoutCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="application-name" content="Catarman Animal Shelter">
    <meta name="apple-mobile-web-app-title" content="Catarman Animal Shelter">
    <meta name="theme-color" content="#F5F1E8">
    <meta name="msapplication-TileColor" content="#F5F1E8">
    <title><?= htmlspecialchars($title ?? ($GLOBALS['app']['name'] ?? 'Catarman Animal Shelter'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <script>
        (function () {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = saved || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            const themeColor = theme === 'dark' ? '#101A2C' : '#F5F1E8';
            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', themeColor);
            document.querySelector('meta[name="msapplication-TileColor"]')?.setAttribute('content', themeColor);
        })();
    </script>
    <style>
        :root {
            --color-bg-primary: #F5F1E8;
            --color-bg-secondary: #EBE4D6;
            --color-text-primary: #14233B;
        }

        [data-theme="dark"] {
            --color-bg-primary: #101A2C;
            --color-bg-secondary: #16233B;
            --color-text-primary: rgba(247, 242, 233, 0.96);
        }

        html {
            min-height: 100%;
            background-color: var(--color-bg-primary);
            color-scheme: light;
        }

        html[data-theme="dark"] {
            background-color: var(--color-bg-primary);
            color-scheme: dark;
        }

        body {
            min-height: 100%;
            margin: 0;
            background-color: var(--color-bg-primary);
            background-image: linear-gradient(180deg, var(--color-bg-primary) 0%, var(--color-bg-secondary) 100%);
            color: var(--color-text-primary);
        }

        html[data-theme="dark"] body {
            background-color: var(--color-bg-primary);
            background-image: linear-gradient(180deg, var(--color-bg-primary) 0%, var(--color-bg-secondary) 100%);
            color: var(--color-text-primary);
        }

        .page-transition-shield {
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
            opacity: 1;
            background-color: var(--color-bg-primary);
            transition: opacity 140ms ease;
        }

        html[data-theme="dark"] .page-transition-shield {
            background-color: var(--color-bg-primary);
        }

        html[data-page-ready="true"] .page-transition-shield {
            opacity: 0;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Lexend:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/variables.css" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/reset.css" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/base.css" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/components.css" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/toast.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/layout.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/responsive.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/qr-modal.css?v=<?= time() ?>" data-core-asset="css">
    <?php foreach (($extraCss ?? []) as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8') ?>?v=<?= time() ?>" data-page-asset="css">
    <?php endforeach; ?>
    <link rel="stylesheet" href="/assets/css/loading-skeletons.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/empty-states.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/progress.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/tooltips.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/dark-mode-overrides.css?v=<?= time() ?>" data-core-asset="css">
    <link rel="stylesheet" href="/assets/css/background-canvas.css?v=<?= time() ?>" data-core-asset="css">
</head>
<?php
    $renderedContent = \App\Support\Breadcrumbs::enhanceAuthenticatedContent($content, $_SERVER['REQUEST_URI'] ?? '/dashboard');
    $layoutPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $layoutModule = explode('/', trim($layoutPath, '/'))[0] ?? '';
    $allowedModules = ['animals', 'medical', 'kennels', 'adoptions', 'billing', 'inventory', 'reports', 'users', 'settings', 'dashboard'];
    $dataModule = in_array($layoutModule, $allowedModules, true) ? $layoutModule : '';
?>
<body<?= $dataModule !== '' ? ' data-module="' . $dataModule . '"' : '' ?>>
    <!-- Animated background canvases -->
    <div class="background-canvas-container" aria-hidden="true">
        <canvas id="bg-canvas-dark"></canvas>
        <canvas id="bg-canvas-light"></canvas>
    </div>
    <div class="page-transition-shield" aria-hidden="true"></div>
    <div class="app-shell" data-page-shell="app">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <div class="mobile-sidebar-backdrop"></div>
        <div class="app-main">
            <?php require __DIR__ . '/../partials/header.php'; ?>
            <main class="content-area">
                <?= $renderedContent ?>
            </main>
            <?php require __DIR__ . '/../partials/footer.php'; ?>
            <?php require __DIR__ . '/../partials/qr-modal.php'; ?>
        </div>
    </div>
    <script src="/assets/js/theme.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/toast.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/core/app-api.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/core/app-formatters.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/core/app-runtime.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/core/app-shell.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/core/app-breadcrumbs.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/core/app-navigation.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/app.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/notifications.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/qr-modal.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/tooltips.js?v=<?= time() ?>" data-core-asset="js"></script>
    <script src="/assets/js/background-canvas.js?v=<?= time() ?>" data-core-asset="js"></script>
    <?php foreach (($extraJs ?? []) as $script): ?>
        <script src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>?v=<?= time() ?>" data-page-asset="js"></script>
    <?php endforeach; ?>
</body>
</html>
