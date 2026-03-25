<section class="page-title" data-dashboard>
    <div class="page-title-meta">
        <h1>Dashboard</h1>
        <p class="text-muted">Welcome back, <?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?>.</p>
        <div class="breadcrumb">Home &gt; Dashboard</div>
    </div>
    <div class="cluster">
        <span class="badge badge-info">Live Session</span>
        <button class="btn-secondary" id="logout">Logout</button>
    </div>
</section>

<section class="stats-grid" id="stats-grid"></section>

<section class="dashboard-grid">
    <article class="card chart-card">
        <h3>Intake Trend</h3>
        <p class="text-muted">Animal intake over the last 12 months.</p>
        <canvas id="intake-chart"></canvas>
    </article>
    <article class="card chart-card">
        <h3>Kennel Occupancy</h3>
        <p class="text-muted">Available versus occupied, maintenance, and quarantine.</p>
        <canvas id="occupancy-chart"></canvas>
    </article>
</section>

<section class="dashboard-grid">
    <article class="card chart-card">
        <h3>Adoption Pipeline</h3>
        <p class="text-muted">Applications created by month.</p>
        <canvas id="adoption-chart"></canvas>
    </article>
    <article class="card chart-card">
        <h3>Recent Activity</h3>
        <p class="text-muted">Latest audit log entries.</p>
        <div class="activity-list" id="activity-list"></div>
    </article>
</section>

<section class="card stack">
    <div>
        <h3>Quick Actions</h3>
        <p class="text-muted">Common shortcuts for staff workflows.</p>
    </div>
    <?php
        $dashboardQuickActions = [
            ['label' => 'New Intake', 'href' => '/animals/create', 'class' => 'btn-primary', 'permission' => 'animals.create'],
            ['label' => 'View Animals', 'href' => '/animals', 'class' => 'btn-secondary', 'permission' => 'animals.read'],
            ['label' => 'View Kennels', 'href' => '/kennels', 'class' => 'btn-secondary', 'permission' => 'kennels.read'],
            ['label' => 'Generate Report', 'href' => '/reports', 'class' => 'btn-secondary', 'permission' => 'reports.read'],
        ];
        $visibleQuickActions = array_values(array_filter($dashboardQuickActions, static fn (array $action): bool => ($can ?? static fn (): bool => false)($action['permission'])));
    ?>
    <?php if ($visibleQuickActions !== []): ?>
        <div class="quick-actions">
            <?php foreach ($visibleQuickActions as $action): ?>
                <button class="<?= htmlspecialchars($action['class'], ENT_QUOTES, 'UTF-8') ?>" type="button" data-quick-link="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-muted">No quick actions are available for your current access level.</div>
    <?php endif; ?>
</section>

<section class="card chart-card">
    <h3>Medical Procedures</h3>
    <p class="text-muted">Procedure volume by type.</p>
    <canvas id="medical-chart"></canvas>
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
