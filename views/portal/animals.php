<?php
$hasFilters = array_filter($filters ?? [], static fn ($value) => (string) $value !== '') !== [];
?>
<section class="portal-page-shell">
    <section class="portal-page-hero">
        <div class="portal-page-copy">
            <span class="portal-landing-eyebrow">Available animals</span>
            <h1>Browse current shelter profiles before you apply.</h1>
            <p class="text-muted">Only animals marked available appear here. Use the filters to narrow by species, size, or gender, then open a full profile before starting your application.</p>
            <div class="cluster portal-page-actions">
                <a class="btn-primary" href="#portal-animal-results">View matches</a>
                <a class="btn-secondary" href="/adopt">Back to landing page</a>
            </div>
        </div>
        <aside class="portal-page-aside card">
            <span class="portal-landing-eyebrow">Quick guide</span>
            <div class="portal-process-list">
                <div class="portal-process-step">
                    <strong>Filter intentionally</strong>
                    <p class="text-muted">Start broad, then narrow down by size or gender once you know what fits your home.</p>
                </div>
                <div class="portal-process-step">
                    <strong>Open full profiles</strong>
                    <p class="text-muted">Review temperament, condition, and notes before preparing an application.</p>
                </div>
                <div class="portal-process-step">
                    <strong>Apply when ready</strong>
                    <p class="text-muted">The application asks for household details and a valid ID upload.</p>
                </div>
            </div>
        </aside>
    </section>

    <section class="portal-filter-card card stack">
        <div class="portal-section-header">
            <div>
                <span class="portal-landing-eyebrow">Search and filters</span>
                <h2>Refine the shortlist</h2>
            </div>
            <?php if ($hasFilters): ?>
                <a class="btn-secondary portal-filter-clear" href="/adopt/animals">Clear all filters</a>
            <?php endif; ?>
        </div>
        <form class="portal-filter-grid" method="GET" action="/adopt/animals">
            <label class="field portal-filter-search">
                <span class="field-label">Search</span>
                <input class="input" type="search" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Name, ID, or breed">
            </label>
            <label class="field">
                <span class="field-label">Species</span>
                <select class="select" name="species">
                    <option value="">All</option>
                    <?php foreach (['Dog', 'Cat'] as $option): ?>
                        <option value="<?= $option ?>" <?= ($filters['species'] ?? '') === $option ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Gender</span>
                <select class="select" name="gender">
                    <option value="">All</option>
                    <?php foreach (['Male', 'Female'] as $option): ?>
                        <option value="<?= $option ?>" <?= ($filters['gender'] ?? '') === $option ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Size</span>
                <select class="select" name="size">
                    <option value="">All</option>
                    <?php foreach (['Small', 'Medium', 'Large', 'Extra Large'] as $option): ?>
                        <option value="<?= $option ?>" <?= ($filters['size'] ?? '') === $option ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="cluster portal-filter-actions">
                <button class="btn-primary" type="submit">Apply filters</button>
                <a class="btn-secondary" href="/adopt/animals">Reset</a>
            </div>
        </form>
    </section>

    <section class="portal-section stack" id="portal-animal-results">
        <div class="portal-section-header">
            <div class="portal-results-meta">
                <span class="portal-landing-eyebrow">Results</span>
                <h2><?= (int) $total ?> animal<?= (int) $total === 1 ? '' : 's' ?> available</h2>
                <p class="text-muted">Page <?= (int) $page ?> of <?= (int) $totalPages ?>.</p>
            </div>
        </div>
        <div class="portal-card-grid">
            <?php foreach ($animals as $animal): ?>
                <article class="portal-animal-card card">
                    <div class="portal-animal-photo">
                        <?php if (($animal['primary_photo_path'] ?? null) !== null): ?>
                            <img src="/<?= htmlspecialchars((string) $animal['primary_photo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($animal['name'] ?: $animal['animal_id']), ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="portal-photo-fallback"><?= htmlspecialchars(substr((string) ($animal['name'] ?: $animal['animal_id']), 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="portal-animal-card-body">
                        <div class="cluster" style="justify-content: space-between;">
                            <strong><?= htmlspecialchars((string) ($animal['name'] ?: $animal['animal_id']), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="badge badge-success"><?= htmlspecialchars((string) $animal['species'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="text-muted"><?= htmlspecialchars((string) ($animal['breed_name'] ?? 'Mixed Breed'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="portal-card-meta"><?= htmlspecialchars(trim(((string) $animal['gender']) . ' • ' . ((string) $animal['size'])), ENT_QUOTES, 'UTF-8') ?></p>
                        <a class="btn-secondary" href="/adopt/animals/<?= (int) $animal['id'] ?>">View full profile</a>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($animals === []): ?>
                <article class="portal-empty-card card">
                    <span class="portal-landing-eyebrow">No matches</span>
                    <h3>No animals matched the current filters.</h3>
                    <p class="text-muted">Try removing one or more filters, or return later when new animals become available for adoption.</p>
                    <div class="cluster portal-empty-actions">
                        <a class="btn-secondary" href="/adopt/animals">Reset filters</a>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($totalPages > 1): ?>
        <section class="cluster portal-pagination">
            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <?php
                    $query = array_filter(array_merge($filters, ['page' => $pageNumber]), static fn ($value) => $value !== '');
                    $href = '/adopt/animals?' . http_build_query($query);
                ?>
                <a class="<?= $pageNumber === $page ? 'btn-primary' : 'btn-secondary' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
                    <?= $pageNumber ?>
                </a>
            <?php endfor; ?>
        </section>
    <?php endif; ?>
</section>
