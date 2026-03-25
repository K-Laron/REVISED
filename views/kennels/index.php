<section class="page-title" id="kennel-page">
    <div class="page-title-meta">
        <h1>Kennel Management</h1>
        <div class="breadcrumb">Home &gt; Kennels</div>
        <p class="text-muted">Monitor occupancy, assign animals, release occupants, and keep maintenance activity visible.</p>
    </div>
    <div class="cluster">
        <?php if (($can ?? static fn (): bool => false)('kennels.create')): ?>
            <button class="btn-primary" type="button" data-open-kennel-modal aria-label="Add kennel" aria-haspopup="dialog" aria-controls="kennel-modal">Add Kennel</button>
        <?php endif; ?>
    </div>
</section>

<section class="kennel-stats-grid" id="kennel-stats-grid">
    <article class="card kennel-stat-card">
        <span class="field-label">Total Kennels</span>
        <strong id="kennel-stat-total">0</strong>
        <small class="text-muted">All active kennel slots</small>
    </article>
    <article class="card kennel-stat-card">
        <span class="field-label">Available</span>
        <strong id="kennel-stat-available">0</strong>
        <small class="text-muted">Ready for intake or transfer</small>
    </article>
    <article class="card kennel-stat-card">
        <span class="field-label">Occupied</span>
        <strong id="kennel-stat-occupied">0</strong>
        <small class="text-muted">Currently housing animals</small>
    </article>
    <article class="card kennel-stat-card">
        <span class="field-label">Maintenance</span>
        <strong id="kennel-stat-maintenance">0</strong>
        <small class="text-muted">Cleaning or repair required</small>
    </article>
    <article class="card kennel-stat-card">
        <span class="field-label">Quarantine</span>
        <strong id="kennel-stat-quarantine">0</strong>
        <small class="text-muted">Restricted use</small>
    </article>
    <article class="card kennel-stat-card">
        <span class="field-label">Occupancy Rate</span>
        <strong id="kennel-stat-rate">0%</strong>
        <small class="text-muted">Occupied over active kennels</small>
    </article>
</section>

<section class="card stack">
    <div class="kennel-toolbar">
        <div class="kennel-view-toggle" role="tablist" aria-label="Kennel view toggle">
            <button class="btn-secondary is-active" id="kennel-view-grid-tab" role="tab" aria-selected="true" aria-controls="kennel-grid-view" type="button" data-kennel-view="grid">Grid View</button>
            <button class="btn-secondary" id="kennel-view-list-tab" role="tab" aria-selected="false" aria-controls="kennel-list-view" type="button" data-kennel-view="list">List View</button>
        </div>

        <form class="kennel-filter-grid" id="kennel-filter-form">
            <label class="field">
                <span class="field-label">Zone</span>
                <select class="select" name="zone">
                    <option value="">All</option>
                    <?php foreach (($zones ?? []) as $zone): ?>
                        <option value="<?= htmlspecialchars((string) $zone, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) $zone, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Status</span>
                <select class="select" name="status">
                    <option value="">All</option>
                    <option value="Available">Available</option>
                    <option value="Occupied">Occupied</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Quarantine">Quarantine</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Species</span>
                <select class="select" name="allowed_species">
                    <option value="">All</option>
                    <option value="Dog">Dog</option>
                    <option value="Cat">Cat</option>
                    <option value="Any">Any</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Size</span>
                <select class="select" name="size_category">
                    <option value="">All</option>
                    <option value="Small">Small</option>
                    <option value="Medium">Medium</option>
                    <option value="Large">Large</option>
                    <option value="Extra Large">Extra Large</option>
                </select>
            </label>
            <div class="cluster kennel-filter-actions">
                <button class="btn-secondary" type="reset">Reset</button>
            </div>
        </form>
    </div>

    <div class="kennel-grid-stack" id="kennel-grid-view" role="tabpanel" aria-labelledby="kennel-view-grid-tab"></div>

    <div class="kennel-list-wrap" id="kennel-list-view" role="tabpanel" aria-labelledby="kennel-view-list-tab" hidden>
        <table class="animal-table kennel-table">
            <thead>
                <tr>
                    <th>Kennel</th>
                    <th>Status</th>
                    <th>Occupant</th>
                    <th>Capacity</th>
                    <th>Zone</th>
                    <th>Allowed</th>
                </tr>
            </thead>
            <tbody id="kennel-table-body"></tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/detail-panel.php'; ?>

<div class="kennel-modal" id="kennel-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="kennel-modal-title" aria-describedby="kennel-modal-description">
    <div class="kennel-modal-card card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3 id="kennel-modal-title">Add Kennel</h3>
                <p class="text-muted" id="kennel-modal-description">Create or update kennel configuration.</p>
            </div>
            <button class="btn-secondary" type="button" data-close-kennel-modal aria-label="Close kennel form">Close</button>
        </div>

        <form class="kennel-form-grid" id="kennel-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="">

            <label class="field">
                <span class="field-label field-label-required">Kennel Code</span>
                <input class="input" type="text" name="kennel_code" required>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Zone</span>
                <input class="input" type="text" name="zone" required>
            </label>
            <label class="field">
                <span class="field-label">Row Number</span>
                <input class="input" type="text" name="row_number">
            </label>
            <label class="field">
                <span class="field-label field-label-required">Size Category</span>
                <select class="select" name="size_category" required>
                    <option value="Small">Small</option>
                    <option value="Medium">Medium</option>
                    <option value="Large">Large</option>
                    <option value="Extra Large">Extra Large</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Type</span>
                <select class="select" name="type" required>
                    <option value="Indoor">Indoor</option>
                    <option value="Outdoor">Outdoor</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Allowed Species</span>
                <select class="select" name="allowed_species" required>
                    <option value="Dog">Dog</option>
                    <option value="Cat">Cat</option>
                    <option value="Any">Any</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Max Occupants</span>
                <input class="input" type="number" min="1" max="20" name="max_occupants" value="1" required>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Status</span>
                <select class="select" name="status" required>
                    <option value="Available">Available</option>
                    <option value="Occupied">Occupied</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Quarantine">Quarantine</option>
                </select>
            </label>
            <label class="field kennel-form-span-2">
                <span class="field-label">Notes</span>
                <textarea class="textarea" name="notes" rows="3"></textarea>
            </label>

            <div class="cluster kennel-form-actions kennel-form-span-2">
                <button class="btn-primary" type="submit">Save Kennel</button>
                <button class="btn-secondary" type="button" id="kennel-delete-button" hidden>Delete Kennel</button>
            </div>
        </form>
    </div>
</div>

<script id="kennel-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'assignableAnimals' => $assignableAnimals,
    'existingKennelCodes' => $existingKennelCodes ?? [],
    'canReadAnimals' => ($can ?? static fn (): bool => false)('animals.read'),
    'canCreateKennels' => ($can ?? static fn (): bool => false)('kennels.create'),
    'canUpdateKennels' => ($can ?? static fn (): bool => false)('kennels.update'),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
