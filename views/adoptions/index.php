<section class="page-title" id="adoption-index-page">
    <div class="page-title-meta">
        <h1>Adoption Pipeline</h1>
        <div class="breadcrumb">Home &gt; Adoptions</div>
        <p class="text-muted">Track applications through screening, seminars, billing clearance, and final adoption release.</p>
    </div>
    <div class="cluster">
        <span class="adoption-helper-note">Applications are expected to come from the adopter portal.</span>
    </div>
</section>

<section class="adoption-stat-grid" id="adoption-stat-grid"></section>

<section class="adoption-index-layout">
    <div class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <h3>Pipeline Board</h3>
            <span class="text-muted" id="adoption-pipeline-summary">Loading applications...</span>
        </div>

        <form class="adoption-filter-grid" id="adoption-filter-form">
            <label class="field adoption-filter-span-2">
                <span class="field-label">Search</span>
                <input class="input" type="search" name="search" placeholder="Application number, adopter, or animal">
            </label>
            <label class="field">
                <span class="field-label">Status</span>
                <select class="select" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                        <option value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="cluster adoption-filter-actions">
                <button class="btn-secondary" type="reset">Reset</button>
            </div>
        </form>

        <div class="adoption-board" id="adoption-board"></div>
    </div>

    <div class="stack">
        <section class="card stack">
            <div class="cluster" style="justify-content: space-between;">
                <h3>Seminars</h3>
                <span class="text-muted" id="adoption-seminar-count">0 listed</span>
            </div>

            <form class="adoption-form-grid" id="adoption-seminar-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label class="field adoption-form-span-2">
                    <span class="field-label field-label-required">Title</span>
                    <input class="input" type="text" name="title" required>
                </label>
                <label class="field">
                    <span class="field-label field-label-required">Start</span>
                    <input class="input" type="datetime-local" name="scheduled_date" required>
                </label>
                <label class="field">
                    <span class="field-label">End</span>
                    <input class="input" type="datetime-local" name="end_time">
                </label>
                <label class="field">
                    <span class="field-label field-label-required">Location</span>
                    <input class="input" type="text" name="location" required>
                </label>
                <label class="field">
                    <span class="field-label field-label-required">Capacity</span>
                    <input class="input" type="number" name="capacity" min="1" max="500" value="20" required>
                </label>
                <label class="field">
                    <span class="field-label">Facilitator</span>
                    <select class="select" name="facilitator_id">
                        <option value="">Assign later</option>
                        <?php foreach ($staff as $member): ?>
                            <option value="<?= (int) $member['id'] ?>"><?= htmlspecialchars($member['full_name'] . ' - ' . $member['role_display_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">Status</span>
                    <select class="select" name="status">
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </label>
                <label class="field adoption-form-span-2">
                    <span class="field-label">Description</span>
                    <textarea class="textarea" name="description" rows="3" placeholder="Orientation scope, reminders, and facilitator notes"></textarea>
                </label>
                <button class="btn-primary" type="submit">Create Seminar</button>
            </form>

            <div class="adoption-seminar-list" id="adoption-seminar-list"></div>
        </section>
    </div>
</section>

<script id="adoption-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'statusLabels' => $statusLabels,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
