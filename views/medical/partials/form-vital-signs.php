<?php
$vitalSigns = $record['vital_signs'] ?? [];
?>
<section class="card collapsible-section" data-collapsible>
    <header class="collapsible-header" data-collapsible-toggle>
        <h4>
            <svg class="sidebar-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;vertical-align:-2px;margin-right:0.35rem"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            Vital Signs
        </h4>
        <svg class="collapsible-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </header>
    <div class="collapsible-body">
        <div class="animal-form-grid">
            <label class="field">
                <span class="field-label">Weight (kg)</span>
                <input class="input" type="number" name="vs_weight_kg" min="0.1" max="150" step="0.1"
                       value="<?= htmlspecialchars((string) ($vitalSigns['weight_kg'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Temperature (°C)</span>
                <input class="input" type="number" name="vs_temperature_celsius" min="35" max="43" step="0.1"
                       value="<?= htmlspecialchars((string) ($vitalSigns['temperature_celsius'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Heart Rate (bpm)</span>
                <input class="input" type="number" name="vs_heart_rate_bpm" min="30" max="300"
                       value="<?= htmlspecialchars((string) ($vitalSigns['heart_rate_bpm'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Respiratory Rate</span>
                <input class="input" type="number" name="vs_respiratory_rate" min="5" max="100"
                       value="<?= htmlspecialchars((string) ($vitalSigns['respiratory_rate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Body Condition Score (1-9)</span>
                <input class="input" type="number" name="vs_body_condition_score" min="1" max="9"
                       value="<?= htmlspecialchars((string) ($vitalSigns['body_condition_score'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
        </div>
    </div>
</section>
