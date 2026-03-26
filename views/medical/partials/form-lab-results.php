<?php
$labResults = $record['lab_results'] ?? [];
?>
<section class="card" data-lab-results-section>
    <header class="cluster" style="justify-content: space-between; margin-bottom: var(--space-4)">
        <h4>
            <svg class="sidebar-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;vertical-align:-2px;margin-right:0.35rem"><path d="M9 3h6v11l-3 3-3-3V3Z"></path><path d="M9 14h6"></path><path d="M4 21h16"></path></svg>
            Lab Test Results
        </h4>
        <button type="button" class="btn-secondary" data-add-lab-result>+ Add Lab Result</button>
    </header>
    <div data-lab-results-container>
        <?php if (count($labResults) > 0): ?>
            <?php foreach ($labResults as $index => $lab): ?>
                <div class="repeatable-row" data-lab-result-row>
                    <div class="animal-form-grid">
                        <label class="field"><span class="field-label">Test Name</span>
                            <input class="input" type="text" data-lab-field="test_name" value="<?= htmlspecialchars((string) ($lab['test_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field"><span class="field-label">Result</span>
                            <input class="input" type="text" data-lab-field="result_value" value="<?= htmlspecialchars((string) ($lab['result_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field"><span class="field-label">Normal Range</span>
                            <input class="input" type="text" data-lab-field="normal_range" value="<?= htmlspecialchars((string) ($lab['normal_range'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., 5.5 – 8.5">
                        </label>
                        <label class="field"><span class="field-label">Status</span>
                            <select class="select" data-lab-field="status">
                                <?php foreach (['Pending', 'Normal', 'Abnormal'] as $option): ?>
                                    <option value="<?= $option ?>" <?= ($lab['status'] ?? 'Pending') === $option ? 'selected' : '' ?>><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field"><span class="field-label">Date</span>
                            <input class="input" type="date" data-lab-field="date_conducted" value="<?= htmlspecialchars((string) ($lab['date_conducted'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field"><span class="field-label">Remarks</span>
                            <input class="input" type="text" data-lab-field="remarks" value="<?= htmlspecialchars((string) ($lab['remarks'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                    </div>
                    <button type="button" class="btn-danger-sm" data-remove-row title="Remove">✕</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
