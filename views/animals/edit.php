<?php $mode = 'edit'; ?>
<section class="page-title">
    <div class="page-title-meta">
        <h1>Edit Animal</h1>
        <div class="breadcrumb">Home &gt; Animals &gt; <?= htmlspecialchars($animal['animal_id'], ENT_QUOTES, 'UTF-8') ?> &gt; Edit</div>
        <p class="text-muted">Update the intake record and assignment details.</p>
    </div>
    <div class="cluster">
        <span class="badge badge-info"><?= htmlspecialchars($animal['animal_id'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
</section>

<?php require __DIR__ . '/form.php'; ?>
