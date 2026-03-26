<?php
$isEdit = ($mode ?? 'create') === 'edit';
$animal = $animal ?? null;
$selectedKennelId = $animal['current_kennel']['id'] ?? ($animal['kennel_id'] ?? null);
?>
<form class="card stack animal-form" id="animal-form" data-mode="<?= $isEdit ? 'edit' : 'create' ?>" <?= $isEdit ? 'data-animal-id="' . (int) $animal['id'] . '"' : '' ?>>
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <section class="stack">
        <div>
            <h3>Animal Information</h3>
            <p class="text-muted">Core identification and appearance details.</p>
        </div>
        <div class="animal-form-grid">
            <label class="field">
                <span class="field-label">Name</span>
                <input class="input" type="text" name="name" value="<?= htmlspecialchars((string) ($animal['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label field-label-required">Species</span>
                <select class="select" name="species" data-breed-species required>
                    <?php foreach (['Dog', 'Cat', 'Other'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['species'] ?? '') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Breed</span>
                <select class="select" name="breed_id" data-breed-select>
                    <option value="">Select breed</option>
                    <?php foreach ($breeds as $breed): ?>
                        <option value="<?= (int) $breed['id'] ?>" data-species="<?= htmlspecialchars($breed['species'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($animal['breed_id'] ?? '') === (string) $breed['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($breed['species'] . ' · ' . $breed['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Breed Other</span>
                <input class="input" type="text" name="breed_other" value="<?= htmlspecialchars((string) ($animal['breed_other'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label field-label-required">Gender</span>
                <select class="select" name="gender" required>
                    <?php foreach (['Male', 'Female'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['gender'] ?? '') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Age Years</span>
                <input class="input" type="number" min="0" max="30" name="age_years" value="<?= htmlspecialchars((string) ($animal['age_years'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Age Months</span>
                <input class="input" type="number" min="0" max="11" name="age_months" value="<?= htmlspecialchars((string) ($animal['age_months'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label field-label-required">Size</span>
                <select class="select" name="size" required>
                    <?php foreach (['Small', 'Medium', 'Large', 'Extra Large'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['size'] ?? '') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Weight (kg)</span>
                <input class="input" type="number" min="0.1" max="150" step="0.1" name="weight_kg" value="<?= htmlspecialchars((string) ($animal['weight_kg'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Color / Markings</span>
                <input class="input" type="text" name="color_markings" value="<?= htmlspecialchars((string) ($animal['color_markings'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label field-label-required">Temperament</span>
                <select class="select" name="temperament" required>
                    <?php foreach (['Friendly', 'Shy', 'Aggressive', 'Unknown'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['temperament'] ?? 'Unknown') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Microchip Number</span>
                <input class="input" type="text" name="microchip_number" value="<?= htmlspecialchars((string) ($animal['microchip_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., 900123456789012">
            </label>
            <label class="field">
                <span class="field-label">Spay / Neuter Status</span>
                <select class="select" name="spay_neuter_status">
                    <?php foreach (['Unknown', 'Yes', 'No'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['spay_neuter_status'] ?? 'Unknown') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field animal-form-span-2">
                <span class="field-label">Distinguishing Features</span>
                <textarea class="textarea" name="distinguishing_features" rows="3"><?= htmlspecialchars((string) ($animal['distinguishing_features'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <label class="field animal-form-span-2">
                <span class="field-label">Special Needs / Medical Notes</span>
                <textarea class="textarea" name="special_needs_notes" rows="3" placeholder="Any special needs, dietary requirements, or initial medical observations"><?= htmlspecialchars((string) ($animal['special_needs_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
        </div>
    </section>

    <section class="stack">
        <div>
            <h3>Intake Details</h3>
            <p class="text-muted">Capture the intake source, condition, and handoff information.</p>
        </div>
        <div class="animal-form-grid">
            <label class="field">
                <span class="field-label field-label-required">Intake Type</span>
                <select class="select" name="intake_type" data-intake-type required>
                    <?php foreach (['Stray', 'Owner Surrender', 'Confiscated', 'Transfer', 'Born in Shelter'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['intake_type'] ?? '') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Intake Date</span>
                <input class="input" type="datetime-local" name="intake_date" required value="<?= htmlspecialchars(isset($animal['intake_date']) ? date('Y-m-d\TH:i', strtotime((string) $animal['intake_date'])) : date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label field-label-required">Condition at Intake</span>
                <select class="select" name="condition_at_intake" required>
                    <?php foreach (['Healthy', 'Injured', 'Sick', 'Malnourished', 'Aggressive'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['condition_at_intake'] ?? '') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Vaccination Status at Intake</span>
                <select class="select" name="vaccination_status_at_intake">
                    <?php foreach (['Unknown', 'Up to date', 'Partial', 'None'] as $option): ?>
                        <option value="<?= $option ?>" <?= (($animal['vaccination_status_at_intake'] ?? 'Unknown') === $option) ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Kennel Assignment</span>
                <select class="select" name="kennel_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($kennels as $kennel): ?>
                        <option value="<?= (int) $kennel['id'] ?>" <?= ((string) $selectedKennelId === (string) $kennel['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kennel['kennel_code'] . ' · ' . $kennel['zone'] . ' · ' . $kennel['size_category'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Barangay of Origin</span>
                <input class="input" type="text" name="barangay_of_origin" value="<?= htmlspecialchars((string) ($animal['barangay_of_origin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., Brgy. Poblacion">
            </label>
            <label class="field">
                <span class="field-label">Impounding Officer</span>
                <input class="input" type="text" name="impounding_officer_name" value="<?= htmlspecialchars((string) ($animal['impounding_officer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Officer who brought the animal">
            </label>
            <label class="field">
                <span class="field-label">Impoundment Order No.</span>
                <input class="input" type="text" name="impoundment_order_number" value="<?= htmlspecialchars((string) ($animal['impoundment_order_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., IO-2026-0001">
            </label>
            <label class="field animal-form-span-2" data-location-found-field>
                <span class="field-label">Location Found</span>
                <input class="input" type="text" name="location_found" value="<?= htmlspecialchars((string) ($animal['location_found'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field animal-form-span-2" data-surrender-reason-field>
                <span class="field-label">Surrender Reason</span>
                <textarea class="textarea" name="surrender_reason" rows="3"><?= htmlspecialchars((string) ($animal['surrender_reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
        </div>
    </section>

    <section class="stack" data-authority-section>
        <div>
            <h3>Impounding Authority</h3>
            <p class="text-muted">Person or office that authorized the confiscation or impoundment.</p>
        </div>
        <div class="animal-form-grid">
            <label class="field">
                <span class="field-label">Authority Name</span>
                <input class="input" type="text" name="authority_name" value="<?= htmlspecialchars((string) ($animal['authority_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., Juan dela Cruz">
            </label>
            <label class="field">
                <span class="field-label">Position / Title</span>
                <input class="input" type="text" name="authority_position" value="<?= htmlspecialchars((string) ($animal['authority_position'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., Barangay Captain">
            </label>
            <label class="field">
                <span class="field-label">Contact Number</span>
                <input class="input" type="text" name="authority_contact" value="<?= htmlspecialchars((string) ($animal['authority_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="09XX XXX XXXX">
            </label>
        </div>
    </section>

    <section class="stack" data-brought-by-section>
        <div>
            <h3>Brought By</h3>
            <p class="text-muted">Optional handoff and contact information.</p>
        </div>
        <div class="animal-form-grid">
            <label class="field">
                <span class="field-label">Name</span>
                <input class="input" type="text" name="brought_by_name" value="<?= htmlspecialchars((string) ($animal['brought_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Contact</span>
                <input class="input" type="text" name="brought_by_contact" value="<?= htmlspecialchars((string) ($animal['brought_by_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field animal-form-span-2">
                <span class="field-label">Address</span>
                <textarea class="textarea" name="brought_by_address" rows="3"><?= htmlspecialchars((string) ($animal['brought_by_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
        </div>
    </section>

    <?php if (!$isEdit): ?>
        <section class="stack">
            <div>
                <h3>Photo Upload</h3>
                <p class="text-muted">Upload up to 5 photos. JPG, PNG, or WebP, maximum 5MB each.</p>
            </div>
            <label class="animal-dropzone">
                <input type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp" data-photo-input>
                <span>Drag and drop photos here or click to browse.</span>
            </label>
            <div class="photo-preview-grid" data-photo-preview></div>
        </section>
    <?php endif; ?>

    <div class="cluster" style="justify-content: space-between;">
        <a class="btn-secondary" href="<?= $isEdit ? '/animals/' . (int) $animal['id'] : '/animals' ?>">Cancel</a>
        <button class="btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Save & Generate QR' ?></button>
    </div>
</form>
