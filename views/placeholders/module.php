<section class="page-title">
    <div class="page-title-meta">
        <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="breadcrumb">Home &gt; <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></div>
        <p class="text-muted"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<section class="card stack">
    <span class="badge badge-warning">Planned Phase</span>
    <h3><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h3>
    <p class="text-muted">This route is reserved and reachable through the authenticated shell. Feature implementation will land in its assigned guide phase.</p>
    <div class="cluster">
        <a class="btn-secondary" href="/dashboard">Back to Dashboard</a>
    </div>
</section>
