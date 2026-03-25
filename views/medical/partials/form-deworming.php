<?php
$deworming = ($record['procedure_type'] ?? '') === 'deworming' ? ($record['details'] ?? []) : [];
?>
<section class="medical-dynamic-panel<?= ($record['procedure_type'] ?? '') === 'deworming' ? ' is-active' : '' ?>" id="medical-panel-deworming" role="tabpanel" aria-labelledby="medical-tab-deworming" data-medical-form-type="deworming" hidden>
    <div class="medical-form-grid">
        <label class="field">
            <span class="field-label">Dewormer Name</span>
            <input class="input" type="text" name="dewormer_name" value="<?= htmlspecialchars((string) ($deworming['dewormer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Brand</span>
            <input class="input" type="text" name="brand" value="<?= htmlspecialchars((string) ($deworming['brand'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Dosage</span>
            <input class="input" type="text" name="dosage" value="<?= htmlspecialchars((string) ($deworming['dosage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Weight at Treatment (kg)</span>
            <input class="input" type="number" step="0.01" min="0.1" name="weight_at_treatment_kg" value="<?= htmlspecialchars((string) ($deworming['weight_at_treatment_kg'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Next Due Date</span>
            <input class="input" type="date" name="next_due_date" data-auto-due="deworming" value="<?= htmlspecialchars((string) ($deworming['next_due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
    </div>
</section>
