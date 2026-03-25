<section class="page-title" id="medical-list-page">
    <div class="page-title-meta">
        <h1>Medical Records</h1>
        <div class="breadcrumb">Home &gt; Medical</div>
        <p class="text-muted">Track all examinations, treatments, vaccinations, surgeries, deworming schedules, and euthanasia records in one place.</p>
    </div>
    <?php if (($can ?? static fn (): bool => false)('medical.create')): ?>
        <div class="cluster medical-quick-create">
            <select class="select" id="medical-animal-selector">
                <option value="">Select animal</option>
                <?php foreach ($animals as $animal): ?>
                    <option value="<?= (int) $animal['id'] ?>">
                        <?= htmlspecialchars(($animal['animal_id'] ?: 'No ID') . ' - ' . ($animal['name'] ?: 'Unnamed Animal'), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a class="btn-primary" href="/medical" id="medical-create-link" aria-disabled="true">New Record</a>
        </div>
    <?php endif; ?>
</section>

<section class="medical-due-grid">
    <article class="card medical-due-card">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <span class="field-label">Due Vaccinations</span>
                <strong id="medical-due-vaccination-count">0</strong>
            </div>
            <span class="badge badge-warning">30-day window</span>
        </div>
        <div class="medical-due-list" id="medical-due-vaccinations">
            <div class="medical-empty-state">No vaccination reminders yet.</div>
        </div>
    </article>

    <article class="card medical-due-card">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <span class="field-label">Due Dewormings</span>
                <strong id="medical-due-deworming-count">0</strong>
            </div>
            <span class="badge badge-info">30-day window</span>
        </div>
        <div class="medical-due-list" id="medical-due-dewormings">
            <div class="medical-empty-state">No deworming reminders yet.</div>
        </div>
    </article>
</section>

<section class="card stack">
    <form class="medical-filter-grid" id="medical-filter-form">
        <label class="field medical-filter-span-2">
            <span class="field-label">Search</span>
            <input class="input" type="search" name="search" placeholder="Animal code, name, or notes">
        </label>
        <label class="field">
            <span class="field-label">Procedure Type</span>
            <select class="select" name="procedure_type">
                <option value="">All types</option>
                <?php foreach ($procedureTypes as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Animal</span>
            <select class="select" name="animal_id">
                <option value="">All animals</option>
                <?php foreach ($animals as $animal): ?>
                    <option value="<?= (int) $animal['id'] ?>">
                        <?= htmlspecialchars(($animal['animal_id'] ?: 'No ID') . ' - ' . ($animal['name'] ?: 'Unnamed Animal'), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Veterinarian</span>
            <select class="select" name="veterinarian_id">
                <option value="">All practitioners</option>
                <?php foreach ($practitioners as $practitioner): ?>
                    <option value="<?= (int) $practitioner['id'] ?>">
                        <?= htmlspecialchars(trim(($practitioner['first_name'] ?? '') . ' ' . ($practitioner['last_name'] ?? '')) ?: $practitioner['email'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Date From</span>
            <input class="input" type="date" name="date_from">
        </label>
        <label class="field">
            <span class="field-label">Date To</span>
            <input class="input" type="date" name="date_to">
        </label>
        <div class="cluster medical-filter-actions">
            <button class="btn-secondary" type="reset">Reset</button>
        </div>
    </form>

    <div class="medical-table-wrap">
        <table class="medical-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Animal</th>
                    <th>Procedure</th>
                    <th>Veterinarian</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="medical-table-body"></tbody>
        </table>
    </div>
    <div class="cluster" style="justify-content: space-between;">
        <span class="text-muted" id="medical-pagination-summary">Showing 0-0 of 0</span>
        <div class="cluster" id="medical-pagination-controls"></div>
    </div>
</section>

<script id="medical-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'animals' => $animals,
    'practitioners' => $practitioners,
    'procedureTypes' => $procedureTypes,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
