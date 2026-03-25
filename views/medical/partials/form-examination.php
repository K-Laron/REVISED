<?php
$examination = ($record['procedure_type'] ?? '') === 'examination' ? ($record['details'] ?? []) : [];
$statusOptions = ['Normal', 'Abnormal'];
?>
<section class="medical-dynamic-panel<?= ($record['procedure_type'] ?? '') === 'examination' ? ' is-active' : '' ?>" id="medical-panel-examination" role="tabpanel" aria-labelledby="medical-tab-examination" data-medical-form-type="examination" hidden>
    <div class="medical-form-grid">
        <label class="field">
            <span class="field-label">Weight (kg)</span>
            <input class="input" type="number" step="0.01" min="0.1" name="weight_kg" value="<?= htmlspecialchars((string) ($examination['weight_kg'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Temperature (C)</span>
            <input class="input" type="number" step="0.1" min="35" max="43" name="temperature_celsius" value="<?= htmlspecialchars((string) ($examination['temperature_celsius'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Heart Rate (bpm)</span>
            <input class="input" type="number" min="30" max="300" name="heart_rate_bpm" value="<?= htmlspecialchars((string) ($examination['heart_rate_bpm'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Respiratory Rate</span>
            <input class="input" type="number" min="5" max="100" name="respiratory_rate" value="<?= htmlspecialchars((string) ($examination['respiratory_rate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Body Condition Score</span>
            <input class="input" type="number" min="1" max="9" name="body_condition_score" value="<?= htmlspecialchars((string) ($examination['body_condition_score'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <div class="medical-mini-grid medical-form-span-2">
            <?php foreach (['eyes' => 'Eyes', 'ears' => 'Ears', 'teeth_gums' => 'Teeth & Gums', 'skin_coat' => 'Skin & Coat', 'musculoskeletal' => 'Musculoskeletal'] as $field => $label): ?>
                <div class="medical-mini-card">
                    <label class="field">
                        <span class="field-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> Status</span>
                        <select class="select" name="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>_status">
                            <option value="">Select status</option>
                            <?php foreach ($statusOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= (($examination[$field . '_status'] ?? '') === $option) ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> Notes</span>
                        <textarea class="textarea" name="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>_notes" rows="2"><?= htmlspecialchars((string) ($examination[$field . '_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <label class="field medical-form-span-2">
            <span class="field-label">Overall Assessment</span>
            <textarea class="textarea" name="overall_assessment" rows="3"><?= htmlspecialchars((string) ($examination['overall_assessment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
        <label class="field medical-form-span-2">
            <span class="field-label">Recommendations</span>
            <textarea class="textarea" name="recommendations" rows="3"><?= htmlspecialchars((string) ($examination['recommendations'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </div>
</section>
