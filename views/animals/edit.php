<?php
$mode = 'edit';
$renderPhotoCard = static function (array $photo, int $index, int $total): void {
    $photoId = (int) ($photo['id'] ?? 0);
    $isPrimary = (int) ($photo['is_primary'] ?? 0) === 1;
    $filePath = (string) ($photo['file_path'] ?? '');
    ?>
    <article class="animal-photo-card" data-animal-photo-item data-photo-id="<?= $photoId ?>" data-file-path="<?= htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8') ?>" data-is-primary="<?= $isPrimary ? '1' : '0' ?>" draggable="true">
        <img src="/<?= htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8') ?>" alt="Animal thumbnail">
        <div class="animal-photo-card-meta">
            <span class="animal-photo-badge<?= $isPrimary ? ' is-primary' : '' ?>"><?= $isPrimary ? 'Primary' : 'Gallery' ?></span>
            <span class="animal-photo-drag-handle">Drag to reorder</span>
        </div>
        <div class="animal-photo-card-actions">
            <button class="animal-photo-action" type="button" data-animal-photo-action="make-primary" data-photo-id="<?= $photoId ?>" <?= $isPrimary ? 'disabled' : '' ?>>Primary</button>
            <button class="animal-photo-action" type="button" data-animal-photo-action="move-left" data-photo-id="<?= $photoId ?>" <?= $index === 0 ? 'disabled' : '' ?>>Left</button>
            <button class="animal-photo-action" type="button" data-animal-photo-action="move-right" data-photo-id="<?= $photoId ?>" <?= $index === ($total - 1) ? 'disabled' : '' ?>>Right</button>
            <button class="animal-photo-action is-danger" type="button" data-animal-photo-action="delete" data-photo-id="<?= $photoId ?>">Delete</button>
        </div>
    </article>
    <?php
};
?>
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

<?php $photos = $animal['photos'] ?? []; ?>
<section class="card stack" data-animal-photo-collection data-animal-id="<?= (int) $animal['id'] ?>" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <div>
        <h3>Photo Upload</h3>
        <p class="text-muted">Upload more photos without leaving the edit workflow. Drag photos to reorder them. JPG, PNG, or WebP, maximum 5MB each.</p>
    </div>

    <div class="stack animal-photo-library">
        <strong data-animal-photo-heading <?= empty($photos) ? 'hidden' : '' ?>>Current photos</strong>
        <div class="animal-thumb-grid" data-animal-photo-grid <?= empty($photos) ? 'hidden' : '' ?>>
            <?php foreach ($photos as $index => $photo): ?>
                <?php $renderPhotoCard($photo, $index, count($photos)); ?>
            <?php endforeach; ?>
        </div>
        <div class="animal-photo-empty" data-animal-photo-empty <?= !empty($photos) ? 'hidden' : '' ?>>No photos uploaded yet.</div>
    </div>

    <form class="stack animal-photo-upload-form" data-animal-id="<?= (int) $animal['id'] ?>">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label class="animal-dropzone">
            <input type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp" data-photo-upload-input>
            <span>Drag and drop photos here or click to browse.</span>
        </label>
        <div class="photo-preview-grid" data-photo-upload-preview></div>
        <div class="cluster" style="justify-content: flex-end;">
            <button class="btn-secondary" type="submit">Upload more photos</button>
        </div>
    </form>
</section>
