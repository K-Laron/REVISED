<?php $appSettings = $GLOBALS['app']['settings'] ?? []; ?>
<footer class="footer">
    <span>&copy; 2026 <?= htmlspecialchars((string) ($appSettings['organization_name'] ?? 'Catarman Dog Pound'), ENT_QUOTES, 'UTF-8') ?></span>
    <span>v1.0.0</span>
</footer>
