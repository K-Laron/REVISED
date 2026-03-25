<?php
$applicationCount = count($myApplications);
$activeCount = count(array_filter($myApplications, static fn (array $application): bool => !in_array((string) ($application['status'] ?? ''), ['rejected', 'withdrawn', 'completed'], true)));
$preferredAnimalLabel = 'No specific animal selected';
foreach ($availableAnimals as $animal) {
    if ((int) $preferredAnimalId === (int) $animal['id']) {
        $preferredAnimalLabel = (string) (($animal['animal_id'] ?? '') . ' - ' . ($animal['name'] ?: 'Unnamed'));
        break;
    }
}
?>
<section class="portal-page-shell">
    <section class="page-title">
        <div class="page-title-meta">
            <span class="portal-landing-eyebrow">Adopter dashboard</span>
            <h1>My Adoption Application</h1>
            <div class="breadcrumb">Adopt &gt; Apply</div>
            <p class="text-muted">Signed in as <?= htmlspecialchars((string) ($currentUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>. Submit a new application or review the current status of earlier submissions.</p>
        </div>
        <div class="cluster portal-account-actions">
            <button class="btn-secondary" type="button" data-portal-logout data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">Logout</button>
        </div>
    </section>

    <section class="portal-application-stats">
        <article class="portal-application-stat">
            <strong><?= $applicationCount ?></strong>
            <span>Total applications on this account</span>
        </article>
        <article class="portal-application-stat">
            <strong><?= $activeCount ?></strong>
            <span>Applications still under review or in progress</span>
        </article>
        <article class="portal-application-stat">
            <strong><?= htmlspecialchars($preferredAnimalLabel, ENT_QUOTES, 'UTF-8') ?></strong>
            <span>Current preselected animal from the portal flow</span>
        </article>
    </section>

    <section class="portal-auth-grid">
        <div class="card stack portal-auth-panel">
            <div class="portal-section-header">
                <div>
                    <span class="portal-landing-eyebrow">Application form</span>
                    <h2>Submit a new application</h2>
                    <p class="text-muted">Provide household, preference, and verification details so shelter staff can review the fit.</p>
                </div>
            </div>
            <form class="stack portal-form" id="portal-apply-form" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-grid portal-form-grid portal-form-grid-two">
                    <label class="field portal-form-grid-full">
                        <span class="field-label">Selected animal</span>
                        <select class="select" name="animal_id">
                            <option value="">No specific animal yet</option>
                            <?php foreach ($availableAnimals as $animal): ?>
                                <option value="<?= (int) $animal['id'] ?>" <?= (int) $preferredAnimalId === (int) $animal['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) (($animal['animal_id'] ?? '') . ' - ' . ($animal['name'] ?: 'Unnamed') . ' (' . ($animal['species'] ?? '') . ')'), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label">Preferred species</span>
                        <select class="select" name="preferred_species">
                            <option value="">Any</option>
                            <option>Dog</option>
                            <option>Cat</option>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label">Preferred breed</span>
                        <input class="input" type="text" name="preferred_breed">
                    </label>
                    <label class="field">
                        <span class="field-label">Preferred size</span>
                        <select class="select" name="preferred_size">
                            <option value="">Any</option>
                            <?php foreach (['Small', 'Medium', 'Large', 'Extra Large'] as $option): ?>
                                <option><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label">Preferred gender</span>
                        <select class="select" name="preferred_gender">
                            <option value="">Any</option>
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label">Preferred min age (years)</span>
                        <input class="input" type="number" name="preferred_age_min" min="0" max="30" inputmode="numeric">
                    </label>
                    <label class="field">
                        <span class="field-label">Preferred max age (years)</span>
                        <input class="input" type="number" name="preferred_age_max" min="0" max="30" inputmode="numeric">
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Housing type</span>
                        <select class="select" name="housing_type" required>
                            <?php foreach (['House', 'Apartment', 'Condo'] as $option): ?>
                                <option><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Housing ownership</span>
                        <select class="select" name="housing_ownership" required>
                            <?php foreach (['Owned', 'Rented'] as $option): ?>
                                <option><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Has yard</span>
                        <select class="select" name="has_yard" required>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label">Yard size</span>
                        <input class="input" type="text" name="yard_size">
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Adults in household</span>
                        <input class="input" type="number" name="num_adults" min="1" max="20" inputmode="numeric" value="1" required>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Children in household</span>
                        <input class="input" type="number" name="num_children" min="0" max="20" inputmode="numeric" value="0" required>
                    </label>
                    <label class="field portal-form-grid-full">
                        <span class="field-label">Children ages</span>
                        <input class="input" type="text" name="children_ages" placeholder="Optional">
                    </label>
                    <label class="field portal-form-grid-full">
                        <span class="field-label">Existing pets</span>
                        <textarea class="textarea" name="existing_pets_description" rows="3"></textarea>
                    </label>
                    <label class="field portal-form-grid-full">
                        <span class="field-label">Previous pet experience</span>
                        <textarea class="textarea" name="previous_pet_experience" rows="3"></textarea>
                    </label>
                    <label class="field">
                        <span class="field-label">Vet reference name</span>
                        <input class="input" type="text" name="vet_reference_name" autocomplete="name">
                    </label>
                    <label class="field">
                        <span class="field-label">Vet clinic</span>
                        <input class="input" type="text" name="vet_reference_clinic">
                    </label>
                    <label class="field portal-form-grid-full">
                        <span class="field-label">Vet contact</span>
                        <input class="input" type="tel" name="vet_reference_contact" inputmode="tel" placeholder="09xxxxxxxxx">
                    </label>
                    <label class="field portal-form-grid-full">
                        <span class="field-label field-label-required">Valid ID</span>
                        <input class="input" type="file" name="valid_id_path" accept=".jpg,.jpeg,.png,.pdf" required>
                    </label>
                </div>
                <div class="stack portal-checkbox-group">
                    <label class="portal-checkbox">
                        <input type="checkbox" name="agrees_to_policies" value="1" required>
                        <span class="field-label-required">I agree to the shelter adoption policies.</span>
                    </label>
                    <label class="portal-checkbox">
                        <input type="checkbox" name="agrees_to_home_visit" value="1" required>
                        <span class="field-label-required">I agree to a possible home visit and screening interview.</span>
                    </label>
                    <label class="portal-checkbox">
                        <input type="checkbox" name="agrees_to_return_policy" value="1" required>
                        <span class="field-label-required">I understand the shelter return policy if the adoption fails.</span>
                    </label>
                </div>
                <div id="portal-apply-errors" class="badge badge-danger" hidden></div>
                <div class="cluster portal-form-actions" style="justify-content: flex-end;">
                    <button class="btn-primary" type="submit">Submit application</button>
                </div>
            </form>
        </div>

        <div class="stack">
            <section class="card stack portal-auth-panel">
                <div class="portal-section-header">
                    <div>
                        <span class="portal-landing-eyebrow">Application history</span>
                        <h2>My applications</h2>
                        <p class="text-muted">Status updates appear here as staff review your submission.</p>
                    </div>
                </div>
                <div id="portal-my-applications-list" class="stack portal-history-list">
                    <?php foreach ($myApplications as $application): ?>
                        <article class="portal-status-card">
                            <div class="cluster portal-status-card-head" style="justify-content: space-between;">
                                <strong><?= htmlspecialchars((string) $application['application_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="badge badge-info"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $application['status'])), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted">
                                <?= htmlspecialchars((string) (($application['animal_name'] ?? 'Preference-based application') . (($application['animal_code'] ?? null) ? ' • ' . $application['animal_code'] : '')), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="portal-card-meta">Created <?= htmlspecialchars((string) $application['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if (($application['rejection_reason'] ?? null) !== null): ?>
                                <p class="text-muted">Reason: <?= htmlspecialchars((string) $application['rejection_reason'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($myApplications === []): ?>
                        <article class="portal-empty-card card">
                            <span class="portal-landing-eyebrow">No submissions yet</span>
                            <h3>No applications submitted yet.</h3>
                            <p class="text-muted">Complete the form on this page whenever you’re ready to start the shelter review process.</p>
                        </article>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card stack portal-auth-panel">
                <span class="portal-landing-eyebrow">Before you submit</span>
                <h2>What the shelter checks next</h2>
                <ul class="portal-note-list">
                    <li>Only animals currently marked available can be chosen in the portal.</li>
                    <li>Staff review applications before scheduling interviews.</li>
                    <li>Seminar confirmation and final certificate steps happen later in the pipeline.</li>
                </ul>
            </section>
        </div>
    </section>
</section>
