<?php
$procedureLabel = $procedureTypes[$record['procedure_type']] ?? ucfirst((string) $record['procedure_type']);
$createdAt = $record['created_at'] ? date('M d, Y H:i', strtotime((string) $record['created_at'])) : 'N/A';
$updatedAt = $record['updated_at'] ? date('M d, Y H:i', strtotime((string) $record['updated_at'])) : 'N/A';
?>
<section class="page-title">
    <div class="page-title-meta">
        <h1><?= htmlspecialchars($procedureLabel, ENT_QUOTES, 'UTF-8') ?> Record</h1>
        <div class="breadcrumb">Home &gt; Medical &gt; Record #<?= (int) $record['id'] ?></div>
        <p class="text-muted">Review or update the procedure linked to <?= htmlspecialchars(($animal['name'] ?: 'Unnamed Animal') . ' (' . $animal['animal_id'] . ')', ENT_QUOTES, 'UTF-8') ?>.</p>
    </div>
    <div class="cluster">
        <?php if (($can ?? static fn (): bool => false)('animals.read')): ?>
            <a class="btn-secondary" href="/animals/<?= (int) $animal['id'] ?>">Open Animal</a>
        <?php endif; ?>
        <a class="btn-secondary" href="/medical">Back to List</a>
    </div>
</section>

<section class="medical-form-shell" id="medical-form-page">
    <article class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3>Record Summary</h3>
                <p class="text-muted">Medical record metadata and linked animal information.</p>
            </div>
            <span class="medical-procedure-pill"><?= htmlspecialchars($procedureLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <dl class="medical-summary-grid">
            <div><dt>Record ID</dt><dd class="mono">#<?= (int) $record['id'] ?></dd></div>
            <div><dt>Animal</dt><dd><?= htmlspecialchars(($animal['animal_id'] ?: 'No ID') . ' - ' . ($animal['name'] ?: 'Unnamed Animal'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Record Date</dt><dd><?= htmlspecialchars(date('M d, Y H:i', strtotime((string) $record['record_date'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Veterinarian</dt><dd><?= htmlspecialchars((string) ($record['veterinarian_name'] ?: 'Unknown'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Created</dt><dd><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Updated</dt><dd><?= htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>

        <?php if (!empty($record['general_notes'])): ?>
            <div class="medical-detail-block">
                <span class="field-label">General Notes</span>
                <p><?= nl2br(htmlspecialchars((string) $record['general_notes'], ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
        <?php endif; ?>
    </article>

    <article class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3>Edit Record</h3>
                <p class="text-muted">Procedure type is fixed after creation. Update the common and procedure-specific details here.</p>
            </div>
            <span class="medical-procedure-pill" id="medical-active-type-label"><?= htmlspecialchars($procedureLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <form class="stack" id="medical-record-form" data-mode="update" data-record-id="<?= (int) $record['id'] ?>" data-lock-type="1">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="animal_id" value="<?= (int) $animal['id'] ?>">
            <input type="hidden" name="procedure_type" value="<?= htmlspecialchars((string) $record['procedure_type'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="medical-type-grid" role="tablist" aria-label="Procedure type">
                <?php foreach ($procedureTypes as $key => $label): ?>
                    <button class="medical-type-card<?= $key === $record['procedure_type'] ? ' is-active' : '' ?>" id="medical-tab-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" role="tab" aria-selected="<?= $key === $record['procedure_type'] ? 'true' : 'false' ?>" aria-controls="medical-panel-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" type="button" data-procedure-type="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $key === $record['procedure_type'] ? '' : 'disabled' ?>>
                        <strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="text-muted"><?= $key === $record['procedure_type'] ? 'Active procedure type' : 'Locked' ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="medical-form-grid">
                <label class="field">
                    <span class="field-label">Record Date</span>
                    <input class="input" type="datetime-local" name="record_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime((string) $record['record_date'])), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label class="field">
                    <span class="field-label">Veterinarian / Practitioner</span>
                    <select class="select" name="veterinarian_id" required>
                        <option value="">Select a practitioner</option>
                        <?php foreach ($practitioners as $practitioner): ?>
                            <option value="<?= (int) $practitioner['id'] ?>" <?= (int) $record['veterinarian_id'] === (int) $practitioner['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(trim(($practitioner['first_name'] ?? '') . ' ' . ($practitioner['last_name'] ?? '')) ?: $practitioner['email'], ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars((string) $practitioner['role_display_name'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field medical-form-span-2">
                    <span class="field-label">General Notes</span>
                    <textarea class="textarea" name="general_notes" rows="3"><?= htmlspecialchars((string) ($record['general_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
            </div>

            <div class="stack" id="medical-dynamic-form-panels">
                <?php require __DIR__ . '/partials/form-vaccination.php'; ?>
                <?php require __DIR__ . '/partials/form-surgery.php'; ?>
                <?php require __DIR__ . '/partials/form-examination.php'; ?>
                <?php require __DIR__ . '/partials/form-treatment.php'; ?>
                <?php require __DIR__ . '/partials/form-deworming.php'; ?>
                <?php require __DIR__ . '/partials/form-euthanasia.php'; ?>
            </div>

            <?php require __DIR__ . '/partials/form-vital-signs.php'; ?>
            <?php require __DIR__ . '/partials/form-prescriptions.php'; ?>
            <?php require __DIR__ . '/partials/form-lab-results.php'; ?>

            <div class="cluster" style="justify-content: space-between;">
                <button class="btn-secondary" type="button" id="medical-delete-button">Delete Record</button>
                <button class="btn-primary" type="submit" id="medical-submit-button">Save Changes</button>
            </div>
        </form>
    </article>
</section>

<script id="medical-page-data" type="application/json"><?= json_encode([
    'mode' => 'update',
    'animal' => $animal,
    'record' => $record,
    'procedureTypes' => $procedureTypes,
    'formConfigs' => $formConfigs,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
