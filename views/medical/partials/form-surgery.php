<?php
$surgery = ($record['procedure_type'] ?? '') === 'surgery' ? ($record['details'] ?? []) : [];
?>
<section class="medical-dynamic-panel<?= ($record['procedure_type'] ?? '') === 'surgery' ? ' is-active' : '' ?>" id="medical-panel-surgery" role="tabpanel" aria-labelledby="medical-tab-surgery" data-medical-form-type="surgery" hidden>
    <div class="medical-form-grid">
        <label class="field">
            <span class="field-label">Surgery Type</span>
            <select class="select" name="surgery_type">
                <option value="">Select type</option>
                <?php foreach (['Spay', 'Neuter', 'Tumor Removal', 'Amputation', 'Wound Repair', 'Other'] as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($surgery['surgery_type'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Pre-op Weight (kg)</span>
            <input class="input" type="number" step="0.01" min="0.1" name="pre_op_weight_kg" value="<?= htmlspecialchars((string) ($surgery['pre_op_weight_kg'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Anesthesia Type</span>
            <select class="select" name="anesthesia_type">
                <option value="">Select type</option>
                <?php foreach (['General', 'Local', 'Sedation'] as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($surgery['anesthesia_type'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Anesthesia Drug</span>
            <input class="input" type="text" name="anesthesia_drug" value="<?= htmlspecialchars((string) ($surgery['anesthesia_drug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Anesthesia Dosage</span>
            <input class="input" type="text" name="anesthesia_dosage" value="<?= htmlspecialchars((string) ($surgery['anesthesia_dosage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Duration (minutes)</span>
            <input class="input" type="number" min="1" max="1440" name="duration_minutes" value="<?= htmlspecialchars((string) ($surgery['duration_minutes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field medical-form-span-2">
            <span class="field-label">Surgical Notes</span>
            <textarea class="textarea" name="surgical_notes" rows="3"><?= htmlspecialchars((string) ($surgery['surgical_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
        <label class="field">
            <span class="field-label">Complications</span>
            <textarea class="textarea" name="complications" rows="3"><?= htmlspecialchars((string) ($surgery['complications'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
        <label class="field">
            <span class="field-label">Follow-up Date</span>
            <input class="input" type="date" name="follow_up_date" value="<?= htmlspecialchars((string) ($surgery['follow_up_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field medical-form-span-2">
            <span class="field-label">Post-op Instructions</span>
            <textarea class="textarea" name="post_op_instructions" rows="3"><?= htmlspecialchars((string) ($surgery['post_op_instructions'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </div>
</section>
