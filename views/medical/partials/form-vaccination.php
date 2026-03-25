<?php
$vaccination = ($record['procedure_type'] ?? '') === 'vaccination' ? ($record['details'] ?? []) : [];
?>
<section class="medical-dynamic-panel<?= ($record['procedure_type'] ?? 'vaccination') === 'vaccination' ? ' is-active' : '' ?>" id="medical-panel-vaccination" role="tabpanel" aria-labelledby="medical-tab-vaccination" data-medical-form-type="vaccination">
    <div class="medical-form-grid">
        <label class="field">
            <span class="field-label">Vaccine Name</span>
            <input class="input" type="text" name="vaccine_name" value="<?= htmlspecialchars((string) ($vaccination['vaccine_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Vaccine Brand</span>
            <input class="input" type="text" name="vaccine_brand" value="<?= htmlspecialchars((string) ($vaccination['vaccine_brand'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Batch / Lot Number</span>
            <input class="input" type="text" name="batch_lot_number" value="<?= htmlspecialchars((string) ($vaccination['batch_lot_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Dosage (ml)</span>
            <input class="input" type="number" step="0.01" min="0.01" name="dosage_ml" value="<?= htmlspecialchars((string) ($vaccination['dosage_ml'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Route</span>
            <select class="select" name="route">
                <option value="">Select route</option>
                <?php foreach (['Subcutaneous', 'Intramuscular', 'Oral'] as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($vaccination['route'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Injection Site</span>
            <input class="input" type="text" name="injection_site" value="<?= htmlspecialchars((string) ($vaccination['injection_site'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Dose Number</span>
            <input class="input" type="number" min="1" max="10" name="dose_number" value="<?= htmlspecialchars((string) ($vaccination['dose_number'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Next Due Date</span>
            <input class="input" type="date" name="next_due_date" data-auto-due="vaccination" value="<?= htmlspecialchars((string) ($vaccination['next_due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field medical-form-span-2">
            <span class="field-label">Adverse Reactions</span>
            <textarea class="textarea" name="adverse_reactions" rows="3"><?= htmlspecialchars((string) ($vaccination['adverse_reactions'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </div>
</section>
