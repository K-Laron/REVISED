<?php
$reportTypeLabels = [
    'intake' => 'Animal Intake',
    'animal_intake' => 'Animal Intake',
    'medical' => 'Medical Activity',
    'adoptions' => 'Adoption Pipeline',
    'adoption' => 'Adoption Pipeline',
    'billing' => 'Billing Collections',
    'inventory' => 'Inventory Movement',
    'census' => 'Animal Census',
];
$cadenceLabels = [
    'day' => 'Day cadence',
    'week' => 'Week cadence',
    'month' => 'Month cadence',
    'quarter' => 'Quarter cadence',
    'year' => 'Year cadence',
];
?>

<section class="page-title" id="reports-page">
    <div class="page-title-meta">
        <h1>Reports &amp; Analytics</h1>
        <div class="breadcrumb">Home &gt; Reports</div>
        <p class="text-muted">Generate operational reports, export them as CSV or PDF, and pull animal dossiers on demand.</p>
    </div>
</section>

<section class="reports-grid reports-grid-primary">
    <article class="card stack reports-builder-card">
        <div>
            <h3>Report Builder</h3>
            <p class="text-muted">Choose a report type, date range, and grouping cadence.</p>
        </div>
        <form class="reports-filter-grid" id="reports-filter-form">
            <label class="field reports-span-2">
                <span class="field-label">Report Type</span>
                <div class="reports-type-grid" role="group" aria-label="Report type options">
                    <?php foreach ([
                        'intake' => 'Animal Intake',
                        'medical' => 'Medical Activity',
                        'adoptions' => 'Adoption Pipeline',
                        'billing' => 'Billing Collections',
                        'inventory' => 'Inventory Movement',
                        'census' => 'Animal Census',
                    ] as $value => $label): ?>
                        <button class="report-type-card<?= $value === 'intake' ? ' is-active' : '' ?>" type="button" data-report-type="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="<?= $value === 'intake' ? 'true' : 'false' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endforeach; ?>
                </div>
            </label>
            <input type="hidden" name="report_type" value="intake">
            <label class="field"><span class="field-label">Start Date</span><input class="input" type="date" name="start_date" value="<?= htmlspecialchars(date('Y-m-01'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">End Date</span><input class="input" type="date" name="end_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Group By</span><select class="select" name="group_by"><option value="day">Day</option><option value="week">Week</option><option value="month" selected>Month</option><option value="quarter">Quarter</option><option value="year">Year</option></select></label>
            <div class="cluster reports-actions">
                <button class="btn-primary" type="submit">Open PDF Preview</button>
                <button class="btn-secondary" type="button" id="report-save-template">Save Template</button>
            </div>
        </form>
        <div class="cluster reports-actions">
            <a class="btn-secondary" href="#" id="report-export-csv">Export CSV</a>
            <a class="btn-secondary" href="#" id="report-export-pdf">Export PDF</a>
        </div>
        <div class="cluster reports-selection-summary" id="report-selection-summary" aria-live="polite">
            <span class="reports-selection-chip mono" id="report-selection-type">Animal Intake</span>
            <span class="reports-selection-chip mono" id="report-selection-range">Range: <?= htmlspecialchars(date('Y-m-01'), ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="reports-selection-chip mono" id="report-selection-group">Group: month</span>
        </div>
    </article>

    <article class="card stack reports-sidebar-card">
        <div>
            <h3>Templates</h3>
            <p class="text-muted">Saved report presets available to your account.</p>
        </div>
        <div id="reports-template-list" class="reports-template-list">
            <?php if ($templates === []): ?>
                <div class="notification-empty">No templates saved yet.</div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <?php
                    $templateConfig = is_array($template['configuration'] ?? null) ? $template['configuration'] : [];
                    $templateReportType = (string) ($templateConfig['report_type'] ?? $template['report_type'] ?? 'intake');
                    $templateGroupBy = (string) ($templateConfig['group_by'] ?? 'month');
                    if (!isset($cadenceLabels[$templateGroupBy])) {
                        $templateGroupBy = 'month';
                    }
                    $templateReportLabel = $reportTypeLabels[$templateReportType] ?? ucwords(str_replace('_', ' ', $templateReportType));
                    ?>
                    <button class="report-template-item" type="button" data-template='<?= htmlspecialchars(json_encode($template, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'>
                        <strong><?= htmlspecialchars((string) $template['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="text-muted"><?= htmlspecialchars((string) $templateReportLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-muted report-template-applies">Applies: <?= htmlspecialchars((string) $templateReportLabel, ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($cadenceLabels[$templateGroupBy], ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="reports-support-grid reports-grid-secondary">
    <article class="card stack reports-support-card">
        <div>
            <h3>Animal Dossier</h3>
            <p class="text-muted">Export a consolidated PDF history for a specific animal record.</p>
        </div>
        <form class="stack" id="animal-dossier-form">
            <label class="field">
                <span class="field-label">Animal Numeric ID</span>
                <input class="input" type="number" min="1" name="animal_id" placeholder="Example: 3">
            </label>
            <button class="btn-secondary" type="submit">Download Dossier PDF</button>
        </form>
    </article>
</section>

<?php if (($canViewAuditTrail ?? false) === true): ?>
    <section class="card stack reports-audit-section">
        <div class="reports-audit-header">
            <h3>Audit Trail</h3>
            <p class="text-muted">Recent activity across modules for operational review.</p>
        </div>
        <form class="reports-filter-grid reports-audit-form" id="audit-filter-form">
            <label class="field"><span class="field-label">Module</span><select class="select" name="module"><option value="">All</option><option value="animals">Animals</option><option value="medical">Medical</option><option value="adoptions">Adoptions</option><option value="billing">Billing</option><option value="inventory">Inventory</option><option value="users">Users</option></select></label>
            <label class="field"><span class="field-label">Action</span><select class="select" name="action"><option value="">All</option><option value="create">Create</option><option value="update">Update</option><option value="delete">Delete</option><option value="restore">Restore</option><option value="login">Login</option><option value="logout">Logout</option></select></label>
            <div class="cluster reports-actions reports-audit-actions"><button class="btn-secondary" type="submit">Refresh Audit Trail</button></div>
        </form>
        <div class="users-table-wrap reports-audit-table-wrap">
            <table class="users-table reports-audit-table">
                <colgroup>
                    <col class="reports-audit-col-date">
                    <col class="reports-audit-col-module">
                    <col class="reports-audit-col-action">
                    <col class="reports-audit-col-user">
                    <col class="reports-audit-col-record">
                </colgroup>
                <thead><tr><th>Date</th><th>Module</th><th>Action</th><th>User</th><th>Record</th></tr></thead>
                <tbody id="audit-table-body" aria-live="polite"><tr><td colspan="5"><div class="notification-empty">Loading audit trail.</div></td></tr></tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<script id="reports-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'templates' => $templates,
    'canViewAuditTrail' => $canViewAuditTrail ?? false,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
