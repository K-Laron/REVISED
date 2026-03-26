<section class="page-title">
    <div class="page-title-meta">
        <h1>New Medical Record</h1>
        <div class="breadcrumb">Home &gt; Medical &gt; New Record</div>
        <p class="text-muted">Adding a record for <?= htmlspecialchars(($animal['name'] ?: 'Unnamed Animal') . ' (' . $animal['animal_id'] . ')', ENT_QUOTES, 'UTF-8') ?>.</p>
    </div>
    <div class="cluster">
        <?php if (($can ?? static fn (): bool => false)('animals.read')): ?>
            <a class="btn-secondary" href="/animals/<?= (int) $animal['id'] ?>">Back to Animal</a>
        <?php endif; ?>
        <a class="btn-secondary" href="/medical">All Records</a>
    </div>
</section>

<section class="medical-form-shell" id="medical-form-page">
    <article class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3>Animal Snapshot</h3>
                <p class="text-muted">The procedure will be linked to this animal dossier and timeline.</p>
            </div>
            <span class="badge badge-info"><?= htmlspecialchars((string) $animal['status'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <dl class="medical-summary-grid">
            <div><dt>Animal ID</dt><dd class="mono"><?= htmlspecialchars((string) $animal['animal_id'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Name</dt><dd><?= htmlspecialchars((string) ($animal['name'] ?: 'Unnamed Animal'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Species</dt><dd><?= htmlspecialchars((string) $animal['species'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Status</dt><dd><?= htmlspecialchars((string) $animal['status'], ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
    </article>

    <article class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3>Procedure Form</h3>
                <p class="text-muted">Common fields stay fixed while the procedure-specific section changes below.</p>
            </div>
            <span class="medical-procedure-pill" id="medical-active-type-label">Vaccination</span>
        </div>

        <?php if ($practitioners === []): ?>
            <div class="medical-warning-card">
                No active users are available for the veterinarian field. Add a user before creating medical records.
            </div>
        <?php endif; ?>

        <?php if ($inventoryItems === []): ?>
            <div class="medical-warning-card">
                No inventory items are available for treatment deductions yet. Treatment records can still be saved without stock-linked deductions.
            </div>
        <?php endif; ?>

        <form class="stack" id="medical-record-form" data-mode="create" data-lock-type="0">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="animal_id" value="<?= (int) $animal['id'] ?>">
            <input type="hidden" name="procedure_type" value="vaccination">

            <div class="medical-type-grid" role="tablist" aria-label="Procedure type">
                <?php foreach ($procedureTypes as $key => $label): ?>
                    <button class="medical-type-card<?= $key === 'vaccination' ? ' is-active' : '' ?>" id="medical-tab-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" role="tab" aria-selected="<?= $key === 'vaccination' ? 'true' : 'false' ?>" aria-controls="medical-panel-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" type="button" data-procedure-type="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                        <strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="text-muted">Switch procedure-specific fields</span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="medical-form-grid">
                <label class="field">
                    <span class="field-label field-label-required">Record Date</span>
                    <input class="input" type="datetime-local" name="record_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label class="field">
                    <span class="field-label field-label-required">Veterinarian / Practitioner</span>
                    <select class="select" name="veterinarian_id" required>
                        <option value="">Select a practitioner</option>
                        <?php foreach ($practitioners as $practitioner): ?>
                            <option value="<?= (int) $practitioner['id'] ?>">
                                <?= htmlspecialchars(trim(($practitioner['first_name'] ?? '') . ' ' . ($practitioner['last_name'] ?? '')) ?: $practitioner['email'], ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars((string) $practitioner['role_display_name'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field medical-form-span-2">
                    <span class="field-label">General Notes</span>
                    <textarea class="textarea" name="general_notes" rows="3" placeholder="Initial observations, follow-up context, or related notes."></textarea>
                </label>
            </div>

            <div class="stack" id="medical-dynamic-form-panels">
                <?php $record = null; require __DIR__ . '/partials/form-vaccination.php'; ?>
                <?php require __DIR__ . '/partials/form-surgery.php'; ?>
                <?php require __DIR__ . '/partials/form-examination.php'; ?>
                <?php require __DIR__ . '/partials/form-treatment.php'; ?>
                <?php require __DIR__ . '/partials/form-deworming.php'; ?>
                <?php require __DIR__ . '/partials/form-euthanasia.php'; ?>
            </div>

            <?php require __DIR__ . '/partials/form-vital-signs.php'; ?>
            <?php require __DIR__ . '/partials/form-prescriptions.php'; ?>
            <?php require __DIR__ . '/partials/form-lab-results.php'; ?>

            <div class="cluster" style="justify-content: flex-end;">
                <a class="btn-secondary" href="/medical">Cancel</a>
                <button class="btn-primary" type="submit" id="medical-submit-button">Save Record</button>
            </div>
        </form>
    </article>
</section>

<script id="medical-page-data" type="application/json"><?= json_encode([
    'mode' => 'create',
    'animal' => $animal,
    'record' => null,
    'procedureTypes' => $procedureTypes,
    'formConfigs' => $formConfigs,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
