<!DOCTYPE html>
<html lang="en" data-ui-theme="civic-ledger">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $appSettings = $GLOBALS['app']['settings'] ?? []; ?>
    <meta name="application-name" content="Catarman Animal Shelter">
    <meta name="apple-mobile-web-app-title" content="Catarman Animal Shelter">
    <meta name="theme-color" content="#f8fafc">
    <meta name="msapplication-TileColor" content="#f8fafc">
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
            const themeColor = theme === 'dark' ? '#020617' : '#f8fafc';
            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', themeColor);
            document.querySelector('meta[name="msapplication-TileColor"]')?.setAttribute('content', themeColor);
        })();
    </script>
    <style>
        html {
            min-height: 100%;
            background-color: #f8fafc;
            color-scheme: light;
        }

        html[data-theme="dark"] {
            background-color: #020617;
            color-scheme: dark;
        }

        body {
            min-height: 100%;
            margin: 0;
            background-color: #f8fafc;
            background-image: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            color: #0f172a;
        }

        html[data-theme="dark"] body {
            background-color: #020617;
            background-image: linear-gradient(180deg, #020617 0%, #0f172a 100%);
            color: rgba(248, 250, 252, 0.96);
        }

        .page-transition-shield {
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
            opacity: 1;
            background-color: #f8fafc;
            transition: opacity 140ms ease;
        }

        html[data-theme="dark"] .page-transition-shield {
            background-color: #020617;
        }

        html[data-page-ready="true"] .page-transition-shield {
            opacity: 0;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Lexend:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/reset.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/toast.css">
    <link rel="stylesheet" href="/assets/css/background-canvas.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <?php foreach (($extraCss ?? []) as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="/assets/css/dark-mode-overrides.css">
</head>
<body>
    <!-- Animated background canvases -->
    <div class="background-canvas-container" aria-hidden="true">
        <canvas id="bg-canvas-dark"></canvas>
        <canvas id="bg-canvas-light"></canvas>
    </div>
    <div class="page-transition-shield" aria-hidden="true"></div>
    <?php
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/adopt', PHP_URL_PATH) ?: '/adopt';
        $isCurrentPublicPath = static function (string $path) use ($requestPath): bool {
            if ($path === '/adopt') {
                return $requestPath === '/adopt';
            }

            return str_starts_with($requestPath, $path);
        };
    ?>
    <a class="skip-link" href="#public-main">Skip to main content</a>
    <div class="public-shell">
        <header class="public-topbar">
            <div class="sidebar-brand public-brand">
                <div class="sidebar-logo">CA</div>
                <div class="public-brand-copy">
                    <strong><?= htmlspecialchars((string) ($appSettings['app_name'] ?? ($GLOBALS['app']['name'] ?? 'Catarman Animal Shelter')), ENT_QUOTES, 'UTF-8') ?></strong>
                    <div class="text-muted">Public portal</div>
                    <span class="public-brand-tag">Adoption and application access</span>
                </div>
            </div>
            <button
                class="icon-button public-menu-toggle"
                type="button"
                aria-label="Open public navigation"
                aria-expanded="false"
                aria-controls="public-nav-panel"
                data-public-nav-toggle
            >
                <svg class="icon-glyph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="4" y1="7" x2="20" y2="7"></line>
                    <line x1="4" y1="12" x2="20" y2="12"></line>
                    <line x1="4" y1="17" x2="20" y2="17"></line>
                </svg>
            </button>
            <div class="cluster public-topbar-actions public-nav-panel" id="public-nav-panel">
                <div class="public-nav-panel-header">
                    <div class="public-nav-panel-copy">
                        <strong>Portal Menu</strong>
                        <span class="text-muted">Browse animals, apply, or sign in.</span>
                    </div>
                    <button
                        class="icon-button public-nav-close"
                        type="button"
                        aria-label="Close public navigation"
                        data-public-nav-close
                    >
                        <svg class="icon-glyph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <nav class="cluster public-nav" aria-label="Public portal navigation">
                    <a class="public-nav-link<?= $isCurrentPublicPath('/adopt') ? ' is-active' : '' ?>" href="/adopt" <?= $isCurrentPublicPath('/adopt') ? 'aria-current="page"' : '' ?>>Home</a>
                    <a class="public-nav-link<?= $isCurrentPublicPath('/adopt/animals') ? ' is-active' : '' ?>" href="/adopt/animals" <?= $isCurrentPublicPath('/adopt/animals') ? 'aria-current="page"' : '' ?>>Animals</a>
                    <?php $systemLandingHref = \App\Support\LandingPage::forUser(($currentUser ?? null)); ?>
                    <?php if ((($currentUser ?? null)['role_name'] ?? null) === 'adopter'): ?>
                        <a class="public-nav-link public-nav-link-accent<?= $isCurrentPublicPath('/adopt/apply') ? ' is-active' : '' ?>" href="/adopt/apply" <?= $isCurrentPublicPath('/adopt/apply') ? 'aria-current="page"' : '' ?>>My Adoption</a>
                    <?php elseif (($currentUser ?? null) !== null && (($can ?? static fn (): bool => false)(null, ['super_admin', 'shelter_head']))): ?>
                        <a class="public-nav-link public-nav-link-accent" href="/dashboard">Dashboard</a>
                    <?php elseif (($currentUser ?? null) !== null): ?>
                        <a class="public-nav-link public-nav-link-accent" href="<?= htmlspecialchars($systemLandingHref, ENT_QUOTES, 'UTF-8') ?>">Open System</a>
                    <?php else: ?>
                        <a class="public-nav-link public-nav-link-accent<?= $requestPath === '/login' ? ' is-active' : '' ?>" href="/login" <?= $requestPath === '/login' ? 'aria-current="page"' : '' ?>>Sign In</a>
                    <?php endif; ?>
                </nav>
                <button id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode" title="Toggle theme">
                    <svg class="theme-icon theme-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg class="theme-icon theme-icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
            </div>
        </header>
        <div class="public-nav-backdrop" data-public-nav-backdrop></div>
        <main class="public-main" id="public-main">
            <?= $content ?>
        </main>
        <footer class="footer">
            <span>&copy; 2026 <?= htmlspecialchars((string) ($appSettings['organization_name'] ?? 'Catarman Dog Pound'), ENT_QUOTES, 'UTF-8') ?></span>
            <span>v1.0.0</span>
        </footer>
    </div>
    <script src="/assets/js/theme.js" data-core-asset="js"></script>
    <script src="/assets/js/toast.js" data-core-asset="js"></script>
    <script src="/assets/js/core/app-runtime.js" data-core-asset="js"></script>
    <script src="/assets/js/core/app-shell.js" data-core-asset="js"></script>
    <script src="/assets/js/core/app-navigation.js" data-core-asset="js"></script>
    <script src="/assets/js/app.js" data-core-asset="js"></script>
    <script src="/assets/js/background-canvas.js" data-core-asset="js"></script>
    <?php foreach (($extraJs ?? []) as $script): ?>
        <script src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>" data-page-asset="js"></script>
    <?php endforeach; ?>
</body>
</html>
