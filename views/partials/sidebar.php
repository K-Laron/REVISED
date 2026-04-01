<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$appSettings = $GLOBALS['app']['settings'] ?? [];
$appName = (string) ($appSettings['app_name'] ?? ($GLOBALS['app']['name'] ?? 'Catarman Animal Shelter'));
$nameParts = preg_split('/\s+/', trim($appName)) ?: [];
$initials = implode('', array_slice(array_map(static fn (string $part): string => strtoupper(substr($part, 0, 1)), $nameParts), 0, 2));
$initials = $initials !== '' ? $initials : 'CA';
$icon = static fn (string $path): string => sprintf(
    '<svg class="sidebar-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
    $path
);
$groups = [
    'Main' => [
        ['label' => 'Dashboard', 'href' => '/dashboard', 'roles' => ['super_admin', 'shelter_head'], 'icon' => $icon('<path d="M3 13h8V3H3v10Z"></path><path d="M13 21h8v-6h-8v6Z"></path><path d="M13 3v8h8V3h-8Z"></path><path d="M3 21h8v-4H3v4Z"></path>')],
    ],
    'Animal Management' => [
        ['label' => 'Animals', 'href' => '/animals', 'permission' => 'animals.read', 'icon' => $icon('<path d="M5 12c0-1.7 1.3-3 3-3 1 0 1.9.5 2.5 1.2.6-.7 1.5-1.2 2.5-1.2 1.7 0 3 1.3 3 3 0 3.5-5.5 6.5-5.5 6.5S5 15.5 5 12Z"></path><circle cx="7.5" cy="7.5" r="1.25"></circle><circle cx="12" cy="5.5" r="1.25"></circle><circle cx="16.5" cy="7.5" r="1.25"></circle>')],
        ['label' => 'Kennels', 'href' => '/kennels', 'permission' => 'kennels.read', 'icon' => $icon('<path d="M4 11 12 4l8 7"></path><path d="M6 10v9h12v-9"></path><path d="M10 19v-4h4v4"></path>')],
        ['label' => 'Medical', 'href' => '/medical', 'permission' => 'medical.read', 'icon' => $icon('<path d="M12 5v14"></path><path d="M5 12h14"></path><rect x="4" y="4" width="16" height="16" rx="4"></rect>')],
    ],
    'Services' => [
        ['label' => 'Adoptions', 'href' => '/adoptions', 'permission' => 'adoptions.read', 'icon' => $icon('<path d="M12 20s-6-3.6-8.5-7.2C1.4 9.8 3 6 6.5 6c2 0 3.2 1 3.9 2.1C11.3 7 12.5 6 14.5 6 18 6 19.6 9.8 20.5 12.8 18 16.4 12 20 12 20Z"></path>')],
        ['label' => 'Billing', 'href' => '/billing', 'permission' => 'billing.read', 'icon' => $icon('<path d="M12 3v18"></path><path d="M16.5 7.5c0-1.7-1.8-3-4.5-3S7.5 5.8 7.5 7.5 9.3 10.5 12 10.5s4.5 1.3 4.5 3-1.8 3-4.5 3-4.5-1.3-4.5-3"></path>')],
    ],
    'Operations' => [
        ['label' => 'Inventory', 'href' => '/inventory', 'permission' => 'inventory.read', 'icon' => $icon('<path d="M4 7.5 12 3l8 4.5-8 4.5L4 7.5Z"></path><path d="M4 7.5V16.5L12 21l8-4.5V7.5"></path><path d="M12 12v9"></path>')],
        ['label' => 'Reports', 'href' => '/reports', 'permission' => 'reports.read', 'icon' => $icon('<path d="M4 19h16"></path><path d="M7 15V9"></path><path d="M12 15V5"></path><path d="M17 15v-3"></path>')],
    ],
    'Administration' => [
        ['label' => 'Users', 'href' => '/users', 'permission' => 'users.read', 'icon' => $icon('<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="3"></circle><path d="M20 21v-2a4 4 0 0 0-3-3.87"></path><path d="M14 4.13a4 4 0 0 1 0 5.74"></path>')],
    ],
];
$settingsIcon = $icon('<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.03-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1.03H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.64 8.4a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.04a1.7 1.7 0 0 0 1.03-1.55V2.4a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.07 4a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.43 8.4c.08.35.43.6.79.6H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.03Z"></path>');
$logoutIcon = $icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>');
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sidebar-brand-copy">
            <span class="sidebar-brand-kicker">Operations Ledger</span>
            <strong><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></strong>
            <div class="text-muted"><?= htmlspecialchars((string) ($appSettings['organization_name'] ?? 'Animal Shelter'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="sidebar-rail-summary">
        <span class="sidebar-rail-label">Command rail</span>
        <strong>Navigate every shelter workflow from one place.</strong>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($groups as $groupLabel => $links): ?>
            <section class="sidebar-group sidebar-group-card">
                <span class="sidebar-group-label"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php foreach ($links as $link): ?>
                    <?php if (isset($link['roles']) && !($can ?? static fn (): bool => true)(null, $link['roles'])) continue; ?>
                    <?php if (isset($link['permission']) && !($can ?? static fn (): bool => true)($link['permission'])) continue; ?>
                    <?php $isActive = $currentPath === $link['href'] || str_starts_with($currentPath, $link['href'] . '/'); ?>
                    <a class="sidebar-link<?= $isActive ? ' is-active' : '' ?>" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= $link['icon'] ?>
                        <span class="sidebar-link-label"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </nav>
    <div>
        <div class="sidebar-divider"></div>
        <a class="sidebar-link" href="/settings"><?= $settingsIcon ?><span class="sidebar-link-label">Settings</span></a>
        <a class="sidebar-link" href="#" id="sidebar-logout"><?= $logoutIcon ?><span class="sidebar-link-label">Logout</span></a>
    </div>
</aside>
<script>
document.getElementById('sidebar-logout')?.addEventListener('click', async function (e) {
    e.preventDefault();
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        const res = await fetch('/api/auth/logout', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ _token: token })
        });
        const data = await res.json();
        if (res.ok) {
            window.CatarmanApp?.navigate?.(data.data.redirect) || (window.location.href = data.data.redirect);
        } else {
            window.location.href = '/login';
        }
    } catch {
        window.location.href = '/login';
    }
});
</script>
