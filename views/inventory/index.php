<section class="page-title" id="inventory-page">
    <div class="page-title-meta">
        <h1>Inventory Management</h1>
        <div class="breadcrumb">Home &gt; Inventory</div>
        <p class="text-muted">Track consumables, medicines, and shelter supplies with quick stock movement tools and alerting.</p>
    </div>
    <div class="cluster">
        <button class="btn-secondary" type="button" data-open-category-modal aria-label="Add inventory category" aria-haspopup="dialog" aria-controls="inventory-category-modal">Add Category</button>
        <button class="btn-primary" type="button" data-open-item-modal aria-label="Add inventory item" aria-haspopup="dialog" aria-controls="inventory-item-modal">Add Item</button>
    </div>
</section>

<section class="inventory-stats-grid">
    <article class="card inventory-stat-card">
        <span class="field-label">Total Items</span>
        <strong id="inventory-stat-total-items">0</strong>
        <small class="text-muted">Distinct tracked inventory records</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Units On Hand</span>
        <strong id="inventory-stat-total-units">0</strong>
        <small class="text-muted">Combined quantity across active items</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Low Stock</span>
        <strong id="inventory-stat-low-stock">0</strong>
        <small class="text-muted">At or below reorder level</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Expiring Soon</span>
        <strong id="inventory-stat-expiring">0</strong>
        <small class="text-muted">Within the next 30 days</small>
    </article>
    <article class="card inventory-stat-card">
        <span class="field-label">Estimated Value</span>
        <strong id="inventory-stat-value">PHP 0.00</strong>
        <small class="text-muted">Quantity on hand multiplied by unit cost</small>
    </article>
</section>

<section class="card inventory-alert-bar" id="inventory-alert-bar" hidden>
    <div class="inventory-alert-pill" data-alert-type="low_stock">
        <strong id="inventory-alert-low-stock-count">0</strong>
        <span>items below reorder level</span>
        <button class="btn-secondary" type="button" data-alert-filter="low_stock">View Low Stock</button>
    </div>
    <div class="inventory-alert-pill" data-alert-type="expiring">
        <strong id="inventory-alert-expiring-count">0</strong>
        <span>items expiring within 30 days</span>
        <button class="btn-secondary" type="button" data-alert-filter="expiring">View Expiring</button>
    </div>
</section>

<section class="card stack">
    <div class="inventory-toolbar">
        <div class="inventory-tabs" id="inventory-category-tabs" role="tablist" aria-label="Inventory category tabs">
            <button class="tab-button is-active" id="inventory-category-all" role="tab" aria-selected="true" aria-controls="inventory-items-panel" type="button" data-category-id="">All Items</button>
            <?php foreach ($categories as $category): ?>
                <button class="tab-button" id="inventory-category-<?= htmlspecialchars((string) $category['id'], ENT_QUOTES, 'UTF-8') ?>" role="tab" aria-selected="false" aria-controls="inventory-items-panel" type="button" data-category-id="<?= htmlspecialchars((string) $category['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form class="inventory-filter-grid" id="inventory-filter-form">
            <label class="field inventory-filter-span-2">
                <span class="field-label">Search</span>
                <input class="input" type="search" name="search" placeholder="SKU or item name">
            </label>
            <label class="field">
                <span class="field-label">Status</span>
                <select class="select" name="status">
                    <option value="">All</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="expiring">Expiring Soon</option>
                </select>
            </label>
            <div class="cluster inventory-filter-actions">
                <button class="btn-secondary" type="reset">Reset</button>
            </div>
        </form>
    </div>

    <div class="inventory-table-wrap" id="inventory-items-panel" role="tabpanel" aria-labelledby="inventory-category-all">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Item Name</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="inventory-table-body"></tbody>
        </table>
    </div>
</section>

<aside class="inventory-drawer" id="inventory-detail-drawer" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="inventory-detail-title" aria-describedby="inventory-detail-subtitle">
    <div class="inventory-drawer-card card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3 id="inventory-detail-title">Inventory Item</h3>
                <p class="text-muted" id="inventory-detail-subtitle">Loading item information.</p>
            </div>
            <button class="btn-secondary" type="button" data-close-inventory-drawer aria-label="Back to inventory list">Back</button>
        </div>
        <div id="inventory-detail-body">
            <div class="inventory-empty-state">Select an item to inspect stock movement and details.</div>
        </div>
    </div>
</aside>

<div class="inventory-modal" id="inventory-item-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="inventory-item-modal-title" aria-describedby="inventory-item-modal-description">
    <div class="inventory-modal-card card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3 id="inventory-item-modal-title">Add Inventory Item</h3>
                <p class="text-muted" id="inventory-item-modal-description">Create or update an inventory record.</p>
            </div>
            <button class="btn-secondary" type="button" data-close-item-modal aria-label="Close inventory item form">Close</button>
        </div>

        <form class="inventory-form-grid" id="inventory-item-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="">

            <label class="field">
                <span class="field-label field-label-required">SKU</span>
                <input class="input" type="text" name="sku" required>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Item Name</span>
                <input class="input" type="text" name="name" required>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Category</span>
                <select class="select" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars((string) $category['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Unit of Measure</span>
                <select class="select" name="unit_of_measure" required>
                    <option value="pcs">pcs</option>
                    <option value="ml">ml</option>
                    <option value="mg">mg</option>
                    <option value="kg">kg</option>
                    <option value="box">box</option>
                    <option value="pack">pack</option>
                    <option value="bottle">bottle</option>
                    <option value="vial">vial</option>
                    <option value="tube">tube</option>
                    <option value="roll">roll</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Cost per Unit</span>
                <input class="input" type="number" min="0" step="0.01" name="cost_per_unit">
            </label>
            <label class="field">
                <span class="field-label field-label-required">Reorder Level</span>
                <input class="input" type="number" min="0" max="10000" name="reorder_level" value="0" required>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Starting Quantity</span>
                <input class="input" type="number" min="0" max="99999" name="quantity_on_hand" value="0" required>
                <small class="text-muted" id="inventory-quantity-help">Used only when creating a new item.</small>
            </label>
            <label class="field">
                <span class="field-label">Storage Location</span>
                <input class="input" type="text" name="storage_location">
            </label>
            <label class="field">
                <span class="field-label">Supplier Name</span>
                <input class="input" type="text" name="supplier_name">
            </label>
            <label class="field">
                <span class="field-label">Supplier Contact</span>
                <input class="input" type="text" name="supplier_contact">
            </label>
            <label class="field">
                <span class="field-label">Expiry Date</span>
                <input class="input" type="date" name="expiry_date">
            </label>
            <label class="field">
                <span class="field-label">Active</span>
                <select class="select" name="is_active">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </label>

            <div class="cluster inventory-form-actions inventory-form-span-2">
                <button class="btn-primary" type="submit">Save Item</button>
                <button class="btn-secondary" type="button" id="inventory-delete-button" hidden>Delete Item</button>
            </div>
        </form>
    </div>
</div>

<div class="inventory-modal" id="inventory-stock-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="inventory-stock-modal-title" aria-describedby="inventory-stock-modal-subtitle">
    <div class="inventory-modal-card card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3 id="inventory-stock-modal-title">Stock Action</h3>
                <p class="text-muted" id="inventory-stock-modal-subtitle">Record a stock movement.</p>
            </div>
            <button class="btn-secondary" type="button" data-close-stock-modal aria-label="Close stock movement form">Close</button>
        </div>

        <form class="inventory-form-grid" id="inventory-stock-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="item_id" value="">
            <input type="hidden" name="action" value="stock-in">

            <label class="field">
                <span class="field-label field-label-required">Quantity</span>
                <input class="input" type="number" min="1" max="10000" name="quantity" required>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Reason</span>
                <select class="select" name="reason" required>
                    <option value="purchase">purchase</option>
                    <option value="donation">donation</option>
                    <option value="return">return</option>
                    <option value="usage">usage</option>
                    <option value="dispensed">dispensed</option>
                    <option value="wastage">wastage</option>
                    <option value="transfer">transfer</option>
                    <option value="count_correction">count_correction</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Batch / Lot Number</span>
                <input class="input" type="text" name="batch_lot_number">
            </label>
            <label class="field">
                <span class="field-label">Expiry Date</span>
                <input class="input" type="date" name="expiry_date">
            </label>
            <label class="field inventory-form-span-2">
                <span class="field-label">Source Supplier</span>
                <input class="input" type="text" name="source_supplier">
            </label>
            <label class="field inventory-form-span-2">
                <span class="field-label">Notes</span>
                <textarea class="textarea" name="notes" rows="3"></textarea>
            </label>
            <button class="btn-primary inventory-form-span-2" type="submit">Save Stock Movement</button>
        </form>
    </div>
</div>

<div class="inventory-modal" id="inventory-category-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="inventory-category-modal-title" aria-describedby="inventory-category-modal-description">
    <div class="inventory-modal-card card stack inventory-modal-card-compact">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3 id="inventory-category-modal-title">Add Category</h3>
                <p class="text-muted" id="inventory-category-modal-description">Create a new inventory grouping.</p>
            </div>
            <button class="btn-secondary" type="button" data-close-category-modal aria-label="Close category form">Close</button>
        </div>

        <form class="stack" id="inventory-category-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label class="field">
                <span class="field-label field-label-required">Category Name</span>
                <input class="input" type="text" name="name" required>
            </label>
            <label class="field">
                <span class="field-label">Description</span>
                <textarea class="textarea" name="description" rows="3"></textarea>
            </label>
            <button class="btn-primary" type="submit">Save Category</button>
        </form>
    </div>
</div>

<script id="inventory-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'categories' => $categories,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
