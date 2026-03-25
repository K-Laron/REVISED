<?php
$photo = $animal['photos'][0]['file_path'] ?? null;
$displayName = (string) ($animal['name'] ?: $animal['animal_id']);
$isAdopter = (($currentUser['role_name'] ?? null) === 'adopter');
$ageParts = [];
if ((int) ($animal['age_years'] ?? 0) > 0) {
    $ageParts[] = (int) $animal['age_years'] . ' yr';
}
if ((int) ($animal['age_months'] ?? 0) > 0) {
    $ageParts[] = (int) $animal['age_months'] . ' mo';
}
$profileMeta = trim(((string) $animal['gender']) . (($animal['size'] ?? '') !== '' ? ' • ' . (string) $animal['size'] : '') . ($ageParts !== [] ? ' • ' . implode(' ', $ageParts) : ''));
?>
<section class="portal-page-shell">
    <section class="portal-page-hero">
        <div class="portal-page-copy">
            <div class="cluster">
                <span class="badge badge-success"><?= htmlspecialchars((string) $animal['species'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="badge badge-info mono"><?= htmlspecialchars((string) $animal['animal_id'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <span class="portal-landing-eyebrow">Animal profile</span>
            <h1><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted"><?= htmlspecialchars((string) ($animal['breed_name'] ?? 'Mixed Breed'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="cluster portal-page-actions">
                <?php if ($isAdopter): ?>
                    <a class="btn-primary" href="/adopt/apply?animal_id=<?= (int) $animal['id'] ?>">Apply for this animal</a>
                <?php else: ?>
                    <a class="btn-primary" href="/adopt/register">Create adopter account</a>
                    <a class="btn-secondary" href="/login">Sign in instead</a>
                <?php endif; ?>
            </div>
        </div>
        <aside class="portal-page-aside card">
            <span class="portal-landing-eyebrow">What to review</span>
            <div class="portal-process-list">
                <div class="portal-process-step">
                    <strong>Profile fit</strong>
                    <p class="text-muted">Check size, age, and known behavior against your home and routine.</p>
                </div>
                <div class="portal-process-step">
                    <strong>Application readiness</strong>
                    <p class="text-muted">The form asks for household details and a valid ID before staff review.</p>
                </div>
                <div class="portal-process-step">
                    <strong>Staff follow-up</strong>
                    <p class="text-muted">Qualified applications move to interview and seminar scheduling after review.</p>
                </div>
            </div>
        </aside>
    </section>

    <section class="portal-profile-grid">
        <article class="portal-detail-photo card">
            <?php if ($photo !== null): ?>
                <img src="/<?= htmlspecialchars((string) $photo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
                <div class="portal-photo-fallback portal-photo-fallback-lg"><?= htmlspecialchars(substr($displayName, 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </article>

        <div class="stack">
            <article class="portal-detail-summary card">
                <span class="portal-landing-eyebrow">Summary</span>
                <p class="text-muted"><?= htmlspecialchars($profileMeta !== '' ? $profileMeta : 'Shelter profile available for review.', ENT_QUOTES, 'UTF-8') ?></p>
            </article>

            <div class="portal-detail-grid">
                <article class="portal-profile-card card">
                    <span class="portal-landing-eyebrow">Temperament</span>
                    <strong>Behavior and disposition</strong>
                    <p class="text-muted"><?= htmlspecialchars((string) ($animal['temperament'] ?? 'Not yet documented'), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
                <article class="portal-profile-card card">
                    <span class="portal-landing-eyebrow">Condition</span>
                    <strong>Intake notes</strong>
                    <p class="text-muted"><?= htmlspecialchars((string) ($animal['condition_at_intake'] ?? 'No condition recorded'), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
                <article class="portal-profile-card card">
                    <span class="portal-landing-eyebrow">Distinguishing features</span>
                    <strong>Markers and appearance</strong>
                    <p class="text-muted"><?= htmlspecialchars((string) ($animal['distinguishing_features'] ?? $animal['color_markings'] ?? 'No additional notes available'), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
                <article class="portal-profile-card card">
                    <span class="portal-landing-eyebrow">Next step</span>
                    <strong>Move into the adopter flow</strong>
                    <p class="text-muted">If this profile fits your household, continue into registration or application submission.</p>
                </article>
            </div>

            <article class="portal-cta-card card">
                <div class="portal-cta-card-copy">
                    <span class="portal-landing-eyebrow">Ready to proceed</span>
                    <h2>Keep this animal in mind while you prepare your application.</h2>
                </div>
                <div class="cluster portal-page-actions">
                    <?php if ($isAdopter): ?>
                        <a class="btn-primary" href="/adopt/apply?animal_id=<?= (int) $animal['id'] ?>">Apply now</a>
                    <?php else: ?>
                        <a class="btn-primary" href="/adopt/register">Create adopter account</a>
                    <?php endif; ?>
                    <a class="btn-secondary" href="/adopt/animals">Back to all animals</a>
                </div>
            </article>
        </div>
    </section>
</section>
