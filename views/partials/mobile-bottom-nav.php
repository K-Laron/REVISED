<?php
/**
 * Mobile Bottom Navigation Partial
 * Visible only on mobile viewports (< 768px)
 */
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/adopt', PHP_URL_PATH) ?: '/adopt';
$isCurrent = static function (string $path) use ($requestPath): bool {
    if ($path === '/adopt') {
        return $requestPath === '/adopt';
    }
    return str_starts_with($requestPath, $path);
};

$navItems = [
    [
        'label' => 'Home',
        'href' => '/adopt',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
        'active' => $isCurrent('/adopt') && $requestPath !== '/adopt/animals' && $requestPath !== '/adopt/apply'
    ],
    [
        'label' => 'Animals',
        'href' => '/adopt/animals',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12c0-1.7 1.3-3 3-3 1 0 1.9.5 2.5 1.2.6-.7 1.5-1.2 2.5-1.2 1.7 0 3 1.3 3 3 0 3.5-5.5 6.5-5.5 6.5S5 15.5 5 12Z"></path><circle cx="7.5" cy="7.5" r="1.25"></circle><circle cx="12" cy="5.5" r="1.25"></circle><circle cx="16.5" cy="7.5" r="1.25"></circle></svg>',
        'active' => $isCurrent('/adopt/animals')
    ],
    [
        'label' => 'My Apps',
        'href' => '/adopt/apply',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20s-6-3.6-8.5-7.2C1.4 9.8 3 6 6.5 6c2 0 3.2 1 3.9 2.1C11.3 7 12.5 6 14.5 6 18 6 19.6 9.8 20.5 12.8 18 16.4 12 20 12 20Z"></path></svg>',
        'active' => $isCurrent('/adopt/apply')
    ]
];
?>

<nav class="mobile-bottom-nav" aria-label="Mobile Bottom Navigation">
    <div class="mobile-bottom-nav-container">
        <?php foreach ($navItems as $item): ?>
            <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" 
               class="mobile-bottom-nav-link<?= $item['active'] ? ' is-active' : '' ?>"
               <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                <span class="mobile-bottom-nav-icon"><?= $item['icon'] ?></span>
                <span class="mobile-bottom-nav-label"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
        
        <button type="button" 
                class="mobile-bottom-nav-link" 
                data-public-nav-toggle 
                aria-label="Open portal menu"
                aria-expanded="false">
            <span class="mobile-bottom-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="4" y1="7" x2="20" y2="7"></line>
                    <line x1="4" y1="12" x2="20" y2="12"></line>
                    <line x1="4" y1="17" x2="20" y2="17"></line>
                </svg>
            </span>
            <span class="mobile-bottom-nav-label">Menu</span>
        </button>
    </div>
</nav>
