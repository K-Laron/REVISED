<?php
$euthanasia = ($record['procedure_type'] ?? '') === 'euthanasia' ? ($record['details'] ?? []) : [];
?>
<section class="medical-dynamic-panel<?= ($record['procedure_type'] ?? '') === 'euthanasia' ? ' is-active' : '' ?>" id="medical-panel-euthanasia" role="tabpanel" aria-labelledby="medical-tab-euthanasia" data-medical-form-type="euthanasia" hidden>
    <div class="medical-form-grid">
        <label class="field">
            <span class="field-label">Reason Category</span>
            <select class="select" name="reason_category">
                <option value="">Select category</option>
                <?php foreach (['Medical', 'Behavioral', 'Legal/Court Order', 'Population Management'] as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($euthanasia['reason_category'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Authorized By</span>
            <select class="select" name="authorized_by">
                <option value="">Select approving user</option>
                <?php foreach ($practitioners as $practitioner): ?>
                    <option value="<?= (int) $practitioner['id'] ?>" <?= (int) ($euthanasia['authorized_by'] ?? 0) === (int) $practitioner['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(trim(($practitioner['first_name'] ?? '') . ' ' . ($practitioner['last_name'] ?? '')) ?: $practitioner['email'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field medical-form-span-2">
            <span class="field-label">Reason Details</span>
            <textarea class="textarea" name="reason_details" rows="3"><?= htmlspecialchars((string) ($euthanasia['reason_details'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
        <label class="field">
            <span class="field-label">Method</span>
            <input class="input" type="text" name="method" value="<?= htmlspecialchars((string) ($euthanasia['method'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Drug Used</span>
            <input class="input" type="text" name="drug_used" value="<?= htmlspecialchars((string) ($euthanasia['drug_used'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Drug Dosage</span>
            <input class="input" type="text" name="drug_dosage" value="<?= htmlspecialchars((string) ($euthanasia['drug_dosage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Time of Death</span>
            <input class="input" type="datetime-local" name="time_of_death" value="<?= htmlspecialchars(isset($euthanasia['time_of_death']) ? date('Y-m-d\TH:i', strtotime((string) $euthanasia['time_of_death'])) : '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="field">
            <span class="field-label">Disposal Method</span>
            <select class="select" name="disposal_method">
                <option value="">Select method</option>
                <?php foreach (['Cremation', 'Burial'] as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($euthanasia['disposal_method'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
</section>
