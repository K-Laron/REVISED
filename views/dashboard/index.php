<?php
    $dashboardQuickActions = [
        ['label' => 'New Intake', 'href' => '/animals/create', 'class' => 'btn-primary', 'permission' => 'animals.create'],
        ['label' => 'View Animals', 'href' => '/animals', 'class' => 'btn-secondary', 'permission' => 'animals.read'],
        ['label' => 'View Kennels', 'href' => '/kennels', 'class' => 'btn-secondary', 'permission' => 'kennels.read'],
        ['label' => 'Generate Report', 'href' => '/reports', 'class' => 'btn-secondary', 'permission' => 'reports.read'],
    ];
    $visibleQuickActions = array_values(array_filter($dashboardQuickActions, static fn (array $action): bool => ($can ?? static fn (): bool => false)($action['permission'])));
?>

<section class="dashboard-briefing" data-dashboard>
    <article class="card dashboard-briefing-hero">
        <div class="dashboard-briefing-copy">
            <span class="badge badge-info">Live Operations</span>
            <h1>Dashboard</h1>
            <p class="text-muted">Review live shelter intake, occupancy, adoption movement, and medical volume from one command surface.</p>
            <div class="breadcrumb">Home &gt; Dashboard</div>
        </div>
        <div class="dashboard-briefing-side">
            <div class="dashboard-briefing-meta">
                <span class="field-label">Current operator</span>
                <strong><?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="mono"><?= htmlspecialchars($user['role_display_name'] ?? $user['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <button class="btn-secondary dashboard-logout-button" id="logout" type="button">Logout</button>
        </div>
    </article>
</section>

<section class="dashboard-kpi-grid" id="stats-grid" aria-live="polite"></section>

<section class="dashboard-command-grid">
    <article class="card dashboard-chart-panel dashboard-chart-panel-featured dashboard-intake-panel">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Twelve-month intake</span>
                <h3>Intake Trend</h3>
            </div>
            <p class="text-muted">Review the latest monthly intake posture before kennel pressure tightens.</p>
        </div>
        <div class="dashboard-intake-stage" data-intake-summary-shell>
            <div class="dashboard-intake-metrics" id="intake-summary-metrics" aria-live="polite">
                <article class="dashboard-intake-metric dashboard-intake-metric-primary">
                    <span class="field-label">Latest intake</span>
                    <strong class="mono">--</strong>
                    <span class="text-muted">Waiting for intake data</span>
                </article>
                <article class="dashboard-intake-metric">
                    <span class="field-label">Vs previous month</span>
                    <strong>--</strong>
                    <span class="text-muted">Delta will appear here</span>
                </article>
                <article class="dashboard-intake-metric">
                    <span class="field-label">12-month peak</span>
                    <strong>--</strong>
                    <span class="text-muted">Peak month will appear here</span>
                </article>
            </div>
            <p class="dashboard-intake-insight text-muted" id="intake-summary-insight" aria-live="polite">
                Intake summary will appear once the chart data loads.
            </p>
            <canvas id="intake-chart"></canvas>
        </div>
    </article>

    <aside class="card dashboard-action-deck">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Workflow shortcuts</span>
                <h3>Quick Actions</h3>
            </div>
            <p class="text-muted">Jump directly into the workflows staff use most often.</p>
        </div>

        <?php if ($visibleQuickActions !== []): ?>
            <div class="quick-actions">
                <?php foreach ($visibleQuickActions as $action): ?>
                    <button class="<?= htmlspecialchars($action['class'], ENT_QUOTES, 'UTF-8') ?>" type="button" data-quick-link="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-empty-state">
                <strong>No quick actions are available.</strong>
                <p class="text-muted">Your current access level does not expose command shortcuts yet.</p>
            </div>
        <?php endif; ?>

        <div class="dashboard-action-notes">
            <article class="dashboard-note-card">
                <span class="field-label">Shift posture</span>
                <strong>Stay ahead of kennel pressure and intake backlogs.</strong>
            </article>
            <article class="dashboard-note-card">
                <span class="field-label">Best practice</span>
                <strong>Review adoption movement and medical volume before daily intake starts.</strong>
            </article>
        </div>
    </aside>
</section>

<section class="dashboard-command-grid">
    <article class="card dashboard-action-queue">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Operator focus</span>
                <h3>Action Queue</h3>
            </div>
            <p class="text-muted">Prioritized follow-up work assembled from billing, inventory, adoption, and medical signals.</p>
        </div>

        <?php if (($actionQueue ?? []) !== []): ?>
            <div class="dashboard-action-queue-list">
                <?php foreach (($actionQueue ?? []) as $item): ?>
                    <?php
                        $queueModule = (string) ($item['module'] ?? 'Operations');
                        $queueUrgency = (string) ($item['urgency'] ?? 'Low');
                    ?>
                    <a class="dashboard-queue-item" href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="dashboard-queue-item-head">
                            <span class="field-label"><?= htmlspecialchars($queueModule, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge <?= $queueUrgency === 'High' ? 'badge-danger' : ($queueUrgency === 'Medium' ? 'badge-warning' : 'badge-info') ?>">
                                <?= htmlspecialchars($queueUrgency, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <div class="dashboard-queue-item-body">
                            <div>
                                <strong><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <p class="text-muted"><?= htmlspecialchars((string) $item['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="dashboard-queue-count mono"><?= htmlspecialchars((string) $item['count'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-empty-state">
                <strong>No immediate action items are active.</strong>
                <p class="text-muted">The current signals are clear across the modules you can access.</p>
            </div>
        <?php endif; ?>
    </article>

    <article class="card dashboard-chart-panel">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Capacity snapshot</span>
                <h3>Kennel Occupancy</h3>
            </div>
            <p class="text-muted">Available, occupied, maintenance, and quarantine capacity in one glance.</p>
        </div>
        <div class="dashboard-occupancy-module" data-occupancy-shell>
            <div class="dashboard-occupancy-stage">
                <canvas id="occupancy-chart"></canvas>
            </div>
            <div class="dashboard-occupancy-summary" id="occupancy-summary" data-occupancy-summary aria-live="polite"></div>
            <div class="dashboard-occupancy-breakdown" id="occupancy-breakdown" aria-live="polite"></div>
        </div>
    </article>
</section>

<section class="dashboard-command-grid dashboard-command-grid-supporting">
    <article class="card dashboard-chart-panel">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Adoption throughput</span>
                <h3>Adoption Pipeline</h3>
            </div>
            <p class="text-muted">Applications created by month to show conversion momentum.</p>
        </div>
        <canvas id="adoption-chart"></canvas>
    </article>

    <article class="card dashboard-chart-panel">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Care operations</span>
                <h3>Medical Procedures</h3>
            </div>
            <p class="text-muted">Procedure volume by type for the current reporting period.</p>
        </div>
        <canvas id="medical-chart"></canvas>
    </article>
</section>

<section class="dashboard-command-grid">
    <article class="card dashboard-activity-feed">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Audit trail</span>
                <h3>Recent Activity</h3>
            </div>
            <p class="text-muted">Latest cross-module records captured by the shelter ledger.</p>
        </div>
        <div class="dashboard-activity-module" data-activity-shell>
            <div class="activity-list" id="activity-list" aria-live="polite"></div>
            <div class="dashboard-activity-digest" id="activity-digest" data-activity-digest aria-live="polite"></div>
        </div>
    </article>
</section>

<script>
    document.getElementById('logout').addEventListener('click', async function () {
        const response = await fetch('/api/auth/logout', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>'
            },
            body: JSON.stringify({ _token: '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>' })
        });
        const result = await response.json();
        if (response.ok) {
            window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
        }
    });
</script>
