<?php
$authUser = $user ?? ($_SESSION['auth.user'] ?? null);
$userInitials = $authUser
    ? strtoupper(substr((string) $authUser['first_name'], 0, 1) . substr((string) $authUser['last_name'], 0, 1))
    : 'GU';
$headerSearchValue = trim((string) ($_GET['q'] ?? ''));
$menuIcon = '<svg class="icon-glyph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h10"></path></svg>';
$searchIcon = '<svg class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>';
$bellIcon = '<svg class="icon-glyph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path><path d="M9.5 17a2.5 2.5 0 0 0 5 0"></path></svg>';
?>
<header class="topbar">
    <div class="topbar-context">
        <button class="icon-button mobile-menu-toggle" type="button" data-sidebar-toggle aria-label="Open navigation">
            <?= $menuIcon ?>
        </button>
        <div class="topbar-heading">
            <span class="topbar-eyebrow">Shelter Operations</span>
            <strong>Command surface</strong>
        </div>
    </div>
    <form class="topbar-search topbar-command-shell" action="/search" method="get">
            <label class="sr-only" for="global-search-input">Global search</label>
            <?= $searchIcon ?>
            <input id="global-search-input" type="search" name="q" value="<?= htmlspecialchars($headerSearchValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search an animal ID, adopter, invoice, or SKU" minlength="2" required data-global-search-input>
            <span class="topbar-status-pill">Ctrl /</span>
    </form>
    <div class="topbar-actions">
        <span class="topbar-status-pill"><?= htmlspecialchars($authUser['role_display_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8') ?></span>
        <div class="notification-shell">
            <button class="icon-button notification-trigger" type="button" aria-label="Notifications" aria-haspopup="dialog" aria-controls="notification-panel" data-notification-trigger aria-expanded="false">
                <?= $bellIcon ?>
                <span class="notification-badge" data-notification-badge hidden>0</span>
            </button>
            <div class="notification-panel card" id="notification-panel" role="dialog" aria-modal="false" aria-label="Notifications panel" aria-hidden="true" data-notification-panel hidden>
                <div class="notification-panel-header">
                    <div>
                        <strong>Notifications</strong>
                        <div class="text-muted">Recent system updates and workflow alerts.</div>
                    </div>
                    <button class="btn-secondary" type="button" data-notification-read-all>Mark all read</button>
                </div>
                <div class="notification-list" data-notification-list aria-live="polite">
                    <div class="notification-empty">Loading notifications.</div>
                </div>
            </div>
        </div>
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
        <div class="user-chip">
            <div class="user-avatar"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-chip-meta">
                <div class="user-chip-name"><?= htmlspecialchars(($authUser['first_name'] ?? 'Guest') . ' ' . ($authUser['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <small class="mono"><?= htmlspecialchars($authUser['role_name'] ?? 'guest', ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>
    </div>
</header>
