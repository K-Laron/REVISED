<?php
$treatment = ($record['procedure_type'] ?? '') === 'treatment' ? ($record['details'] ?? []) : [];
?>
<section class="medical-dynamic-panel<?= ($record['procedure_type'] ?? '') === 'treatment' ? ' is-active' : '' ?>" id="medical-panel-treatment" role="tabpanel" aria-labelledby="medical-tab-treatment" data-medical-form-type="treatment" hidden>
    <div class="medical-form-grid">
        <label class="field">
            <span class="field-label">Diagnosis</span>
            <input class="input" type="text" name="diagnosis" value="<?= htmlspecialchars((string) ($treatment['diagnosis'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Medication Name</span>
            <input class="input" type="text" name="medication_name" value="<?= htmlspecialchars((string) ($treatment['medication_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Dosage</span>
            <input class="input" type="text" name="dosage" value="<?= htmlspecialchars((string) ($treatment['dosage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Route</span>
            <select class="select" name="route">
                <option value="">Select route</option>
                <?php foreach (['Oral', 'Injection', 'Topical', 'IV'] as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($treatment['route'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Frequency</span>
            <input class="input" type="text" name="frequency" value="<?= htmlspecialchars((string) ($treatment['frequency'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Twice daily">
        </label>
        <label class="field">
            <span class="field-label">Duration (days)</span>
            <input class="input" type="number" min="1" max="365" name="duration_days" value="<?= htmlspecialchars((string) ($treatment['duration_days'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Start Date</span>
            <input class="input" type="date" name="start_date" value="<?= htmlspecialchars((string) ($treatment['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">End Date</span>
            <input class="input" type="date" name="end_date" value="<?= htmlspecialchars((string) ($treatment['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Quantity Dispensed</span>
            <input class="input" type="number" min="1" max="1000" name="quantity_dispensed" value="<?= htmlspecialchars((string) ($treatment['quantity_dispensed'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Inventory Link</span>
            <select class="select" name="inventory_item_id">
                <option value="">No linked inventory deduction</option>
                <?php foreach ($inventoryItems as $item): ?>
                    <option value="<?= (int) $item['id'] ?>" <?= (int) ($treatment['inventory_item_id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($item['sku'] . ' - ' . $item['name'] . ' (' . $item['quantity_on_hand'] . ' ' . $item['unit_of_measure'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field medical-form-span-2">
            <span class="field-label">Special Instructions</span>
            <textarea class="textarea" name="special_instructions" rows="3"><?= htmlspecialchars((string) ($treatment['special_instructions'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </div>
</section>
