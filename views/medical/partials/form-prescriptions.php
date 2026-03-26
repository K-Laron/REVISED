<?php
$prescriptions = $record['prescriptions'] ?? [];
?>
<section class="card" data-prescriptions-section>
    <header class="cluster" style="justify-content: space-between; margin-bottom: var(--space-4)">
        <h4>
            <svg class="sidebar-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;vertical-align:-2px;margin-right:0.35rem"><path d="M10.5 1.5H8A6.5 6.5 0 0 0 8 14.5h8a6.5 6.5 0 0 0 0-13h-2.5"></path><line x1="12" y1="5.5" x2="12" y2="10.5"></line><line x1="9.5" y1="8" x2="14.5" y2="8"></line></svg>
            Medications / Prescriptions
        </h4>
        <button type="button" class="btn-secondary" data-add-prescription>+ Add Prescription</button>
    </header>
    <div data-prescriptions-container>
        <?php if (count($prescriptions) > 0): ?>
            <?php foreach ($prescriptions as $index => $rx): ?>
                <div class="repeatable-row" data-prescription-row>
                    <div class="animal-form-grid">
                        <label class="field"><span class="field-label">Medicine Name</span>
                            <input class="input" type="text" data-rx-field="medicine_name" value="<?= htmlspecialchars((string) ($rx['medicine_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field"><span class="field-label">Dosage</span>
                            <input class="input" type="text" data-rx-field="dosage" value="<?= htmlspecialchars((string) ($rx['dosage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field"><span class="field-label">Frequency</span>
                            <input class="input" type="text" data-rx-field="frequency" value="<?= htmlspecialchars((string) ($rx['frequency'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., Every 8 hours">
                        </label>
                        <label class="field"><span class="field-label">Duration</span>
                            <input class="input" type="text" data-rx-field="duration" value="<?= htmlspecialchars((string) ($rx['duration'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., 7 days">
                        </label>
                        <label class="field"><span class="field-label">Qty</span>
                            <input class="input" type="number" min="0" data-rx-field="quantity" value="<?= htmlspecialchars((string) ($rx['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field"><span class="field-label">Instructions</span>
                            <input class="input" type="text" data-rx-field="instructions" value="<?= htmlspecialchars((string) ($rx['instructions'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                    </div>
                    <button type="button" class="btn-danger-sm" data-remove-row title="Remove">✕</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
