<?php
$statusClassMap = [
    'Available' => 'badge-success',
    'Under Medical Care' => 'badge-warning',
    'In Adoption Process' => 'badge-info',
    'Adopted' => 'badge-success',
    'Deceased' => 'badge-danger',
    'Transferred' => 'badge-info',
    'Quarantine' => 'badge-warning',
];
$statusClass = $statusClassMap[$animal['status']] ?? 'badge-info';
?>
<section class="page-title">
    <div class="page-title-meta">
        <h1><?= htmlspecialchars($animal['name'] ?: 'Unnamed Animal', ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="breadcrumb">Home &gt; Animals &gt; <?= htmlspecialchars($animal['animal_id'], ENT_QUOTES, 'UTF-8') ?></div>
        <p class="text-muted">Complete animal profile, attachments, timeline, and status controls.</p>
    </div>
    <div class="cluster">
        <?php if (($can ?? static fn (): bool => false)('animals.update')): ?>
            <a class="btn-secondary" href="/animals/<?= (int) $animal['id'] ?>/edit">Edit</a>
        <?php endif; ?>
        <a class="btn-primary" href="/api/animals/<?= (int) $animal['id'] ?>/qr/download">Download QR</a>
    </div>
</section>

<section class="animal-detail-grid">
    <article class="card stack">
        <h3>Photo Gallery</h3>
        <div class="animal-photo-stage">
            <?php if (!empty($animal['photos'])): ?>
                <img src="/<?= htmlspecialchars($animal['photos'][0]['file_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Animal photo">
            <?php else: ?>
                <div class="animal-photo-empty">No photos uploaded</div>
            <?php endif; ?>
        </div>
        <div class="animal-thumb-grid">
            <?php foreach ($animal['photos'] as $photo): ?>
                <img src="/<?= htmlspecialchars($photo['file_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Animal thumbnail">
            <?php endforeach; ?>
        </div>
        <div class="qr-panel">
            <img src="/api/animals/<?= (int) $animal['id'] ?>/qr/download" alt="Animal QR code">
            <div>
                <div class="field-label">QR Linked ID</div>
                <div class="mono"><?= htmlspecialchars($animal['animal_id'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </article>

    <article class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <h3>Animal Details</h3>
            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($animal['status'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <dl class="detail-grid">
            <div><dt>Animal ID</dt><dd class="mono"><?= htmlspecialchars($animal['animal_id'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Species</dt><dd><?= htmlspecialchars($animal['species'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Breed</dt><dd><?= htmlspecialchars((string) ($animal['breed_name'] ?? $animal['breed_other'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Gender</dt><dd><?= htmlspecialchars($animal['gender'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Age</dt><dd><?= (int) ($animal['age_years'] ?? 0) ?> years <?= (int) ($animal['age_months'] ?? 0) ?> months</dd></div>
            <div><dt>Weight</dt><dd><?= htmlspecialchars((string) ($animal['weight_kg'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Color</dt><dd><?= htmlspecialchars((string) ($animal['color_markings'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Kennel</dt><dd><?= htmlspecialchars((string) ($animal['current_kennel']['kennel_code'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Intake</dt><dd><?= htmlspecialchars(date('M d, Y H:i', strtotime((string) $animal['intake_date'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Temperament</dt><dd><?= htmlspecialchars($animal['temperament'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="animal-detail-span-2"><dt>Features</dt><dd><?= htmlspecialchars((string) ($animal['distinguishing_features'] ?? 'None recorded'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
        <form class="cluster animal-status-form" data-animal-id="<?= (int) $animal['id'] ?>">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <select class="select" name="status">
                <?php foreach (['Available', 'Under Medical Care', 'In Adoption Process', 'Adopted', 'Deceased', 'Transferred', 'Quarantine'] as $option): ?>
                    <option value="<?= $option ?>" <?= $animal['status'] === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
            <input class="input" type="text" name="status_reason" placeholder="Reason for change">
            <button class="btn-primary" type="submit">Update Status</button>
        </form>
    </article>
</section>

<section class="card stack">
    <div class="cluster" role="tablist" aria-label="Animal detail sections">
        <button class="tab-button is-active" id="timeline-tab-button" role="tab" aria-selected="true" aria-controls="timeline-tab" type="button" data-tab-target="timeline-tab">Timeline</button>
        <button class="tab-button" id="medical-tab-button" role="tab" aria-selected="false" aria-controls="medical-tab" type="button" data-tab-target="medical-tab">Medical</button>
        <button class="tab-button" id="kennel-tab-button" role="tab" aria-selected="false" aria-controls="kennel-tab" type="button" data-tab-target="kennel-tab">Kennel History</button>
        <button class="tab-button" id="documents-tab-button" role="tab" aria-selected="false" aria-controls="documents-tab" type="button" data-tab-target="documents-tab">Documents</button>
    </div>

    <div class="tab-panel is-active" id="timeline-tab" role="tabpanel" aria-labelledby="timeline-tab-button">
        <div class="timeline-list" id="animal-timeline" data-animal-id="<?= (int) $animal['id'] ?>"></div>
    </div>

    <div class="tab-panel" id="medical-tab" role="tabpanel" aria-labelledby="medical-tab-button" hidden>
        <div class="timeline-list">
            <?php if ($animal['medical_records'] === []): ?>
                <div class="timeline-entry">
                    <strong>No medical records yet.</strong>
                    <span class="text-muted">Start the first procedure record for this animal.</span>
                    <?php if (($can ?? static fn (): bool => false)('medical.create')): ?>
                        <a class="btn-secondary" href="/medical/create/<?= (int) $animal['id'] ?>">Add Medical Record</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="cluster" style="margin-bottom: 0.75rem;">
                    <?php if (($can ?? static fn (): bool => false)('medical.create')): ?>
                        <a class="btn-secondary" href="/medical/create/<?= (int) $animal['id'] ?>">Add Medical Record</a>
                    <?php endif; ?>
                    <?php if (($can ?? static fn (): bool => false)('medical.read')): ?>
                        <a class="btn-secondary" href="/medical">Open Medical Module</a>
                    <?php endif; ?>
                </div>
                <?php foreach ($animal['medical_records'] as $record): ?>
                    <div class="timeline-entry">
                        <strong><?= htmlspecialchars(ucfirst((string) $record['procedure_type']), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="text-muted"><?= htmlspecialchars((string) $record['record_date'], ENT_QUOTES, 'UTF-8') ?></span>
                        <p><?= htmlspecialchars((string) ($record['general_notes'] ?: 'Medical entry recorded.'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (($can ?? static fn (): bool => false)('medical.read')): ?>
                            <a class="btn-secondary" href="/medical/<?= (int) $record['id'] ?>">Open Record</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-panel" id="kennel-tab" role="tabpanel" aria-labelledby="kennel-tab-button" hidden>
        <div class="timeline-list">
            <?php if ($animal['kennel_history'] === []): ?>
                <div class="timeline-entry"><strong>No kennel assignments yet.</strong></div>
            <?php else: ?>
                <?php foreach ($animal['kennel_history'] as $row): ?>
                    <div class="timeline-entry">
                        <strong><?= htmlspecialchars($row['kennel_code'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="text-muted"><?= htmlspecialchars((string) $row['assigned_at'], ENT_QUOTES, 'UTF-8') ?></span>
                        <p><?= htmlspecialchars($row['zone'] . ' · ' . $row['size_category'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-panel" id="documents-tab" role="tabpanel" aria-labelledby="documents-tab-button" hidden>
        <div class="stack">
            <form class="cluster animal-photo-upload-form" data-animal-id="<?= (int) $animal['id'] ?>">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input class="input" type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple>
                <button class="btn-secondary" type="submit">Upload Photos</button>
            </form>
            <div class="text-muted">Uploaded photos are stored under the animal record and shown in the gallery above.</div>
        </div>
    </div>
</section>
