<?php
$quantityOnHand = (int) ($item['quantity_on_hand'] ?? 0);
$reorderLevel = (int) ($item['reorder_level'] ?? 0);
$isLowStock = !empty($item['is_low_stock']);
$isExpiring = !empty($item['is_expiring']);
$transactions = is_array($item['transactions'] ?? null) ? $item['transactions'] : [];
$expiryDate = ($item['expiry_date'] ?? '') !== '' ? date('M d, Y', strtotime((string) $item['expiry_date'])) : 'No expiry';
$estimatedValue = $quantityOnHand * (float) ($item['cost_per_unit'] ?? 0);
?>
<section class="page-title">
    <div class="page-title-meta">
        <h1><?= htmlspecialchars((string) ($item['name'] ?? 'Inventory Item'), ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="breadcrumb">Home &gt; Inventory &gt; <?= htmlspecialchars((string) ($item['sku'] ?? 'Item'), ENT_QUOTES, 'UTF-8') ?></div>
        <p class="text-muted">Detailed inventory profile with current stock posture and recent movement history.</p>
    </div>
    <div class="cluster">
        <a class="btn-secondary" href="/inventory">Back to Inventory</a>
    </div>
</section>

<?php if ($isLowStock || $isExpiring): ?>
    <section class="card inventory-alert-bar">
        <?php if ($isLowStock): ?>
            <div class="inventory-alert-pill">
                <strong>Low Stock</strong>
                <span>Quantity is at or below the reorder threshold.</span>
                <a class="btn-secondary" href="/inventory">Open Restocking Queue</a>
            </div>
        <?php endif; ?>
        <?php if ($isExpiring): ?>
            <div class="inventory-alert-pill">
                <strong>Expiring Soon</strong>
                <span>Review this batch before <?= htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8') ?>.</span>
                <a class="btn-secondary" href="/inventory">Review Inventory Alerts</a>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="inventory-stats-grid">
    <article class="card inventory-stat-card">
        <span class="field-label">Quantity On Hand</span>
        <strong><?= number_format($quantityOnHand) ?> <?= htmlspecialchars((string) ($item['unit_of_measure'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        <small class="text-muted">Live quantity tracked for this item</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Reorder Level</span>
        <strong><?= number_format($reorderLevel) ?></strong>
        <small class="text-muted">Minimum target before replenishment</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Expiry Date</span>
        <strong><?= htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8') ?></strong>
        <small class="text-muted">Tracked expiry window for this stock record</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Unit Cost</span>
        <strong>PHP <?= number_format((float) ($item['cost_per_unit'] ?? 0), 2) ?></strong>
        <small class="text-muted">Current configured unit valuation</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Estimated Stock Value</span>
        <strong>PHP <?= number_format($estimatedValue, 2) ?></strong>
        <small class="text-muted">Quantity on hand multiplied by unit cost</small>
    </article>
</section>

<section class="card stack">
    <div class="cluster" style="justify-content: space-between; align-items: start;">
        <div>
            <h3>Item Snapshot</h3>
            <p class="text-muted">SKU, source, storage, and current alert flags.</p>
        </div>
        <div class="inventory-status-badges">
            <span class="inventory-badge" data-tone="neutral"><?= !empty($item['is_active']) ? 'Active' : 'Inactive' ?></span>
            <?php if ($isLowStock): ?>
                <span class="inventory-badge" data-tone="warning">Low Stock</span>
            <?php endif; ?>
            <?php if ($isExpiring): ?>
                <span class="inventory-badge" data-tone="<?= ($item['expiry_date'] ?? '') !== '' && strtotime((string) $item['expiry_date']) <= strtotime('+7 days') ? 'danger' : 'warning' ?>">Expiring</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="inventory-detail-summary">
        <div class="inventory-detail-stat">
            <span class="field-label">SKU</span>
            <strong><?= htmlspecialchars((string) ($item['sku'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="inventory-detail-stat">
            <span class="field-label">Category</span>
            <strong><?= htmlspecialchars((string) ($item['category_name'] ?? 'Uncategorized'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="inventory-detail-stat">
            <span class="field-label">Storage Location</span>
            <strong><?= htmlspecialchars((string) ($item['storage_location'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="inventory-detail-stat">
            <span class="field-label">Supplier</span>
            <strong><?= htmlspecialchars((string) ($item['supplier_name'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="inventory-detail-stat">
            <span class="field-label">Supplier Contact</span>
            <strong><?= htmlspecialchars((string) ($item['supplier_contact'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="inventory-detail-stat">
            <span class="field-label">Last Updated</span>
            <strong><?= htmlspecialchars(date('M d, Y H:i', strtotime((string) ($item['updated_at'] ?? $item['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    </div>
</section>

<section class="card stack">
    <div class="cluster" style="justify-content: space-between;">
        <div>
            <h3>Recent Transactions</h3>
            <p class="text-muted"><?= count($transactions) ?> recorded movement<?= count($transactions) === 1 ? '' : 's' ?> tied to this inventory record.</p>
        </div>
        <a class="btn-secondary" href="/inventory">Open Inventory Module</a>
    </div>
    <div class="inventory-history-list">
        <?php if ($transactions === []): ?>
            <div class="inventory-empty-state">No stock transactions recorded yet.</div>
        <?php else: ?>
            <?php foreach ($transactions as $transaction): ?>
                <?php
                    $quantity = (int) ($transaction['quantity'] ?? 0);
                    $prefix = $quantity > 0 ? '+' : '';
                ?>
                <div class="inventory-history-entry">
                    <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($transaction['transaction_type'] ?? 'transaction'))), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($prefix . number_format($quantity), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="text-muted"><?= htmlspecialchars((string) ($transaction['transacted_at'] ?? $transaction['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= htmlspecialchars((string) ($transaction['reason'] ?? 'No reason provided'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-muted">Before <?= number_format((int) ($transaction['quantity_before'] ?? 0)) ?> / After <?= number_format((int) ($transaction['quantity_after'] ?? 0)) ?></span>
                    <?php if (($transaction['notes'] ?? '') !== ''): ?>
                        <span><?= htmlspecialchars((string) $transaction['notes'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
