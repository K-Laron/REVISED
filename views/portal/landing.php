<?php
$isAdopter = (($currentUser['role_name'] ?? null) === 'adopter');
$featuredCount = count($featuredAnimals);
$heroPrimaryHref = $isAdopter ? '/adopt/apply' : '/adopt/animals';
$heroPrimaryLabel = $isAdopter ? 'Continue my adoption' : 'Browse available animals';
$heroSecondaryHref = $isAdopter ? '/adopt/animals' : '/adopt/register';
$heroSecondaryLabel = $isAdopter ? 'See all available animals' : 'Create adopter account';
?>

<section class="portal-landing-hero">
    <div class="portal-landing-intro">
        <span class="badge badge-info">Adopter Portal</span>
        <div class="portal-landing-kicker">Catarman Animal Shelter</div>
        <h1>Adoption starts with a calm, transparent first step.</h1>
        <p class="text-muted">Review currently available animals, learn the shelter process, and prepare your application before interviews and seminar scheduling begin.</p>
        <div class="cluster portal-landing-actions">
            <a class="btn-primary" href="<?= htmlspecialchars($heroPrimaryHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($heroPrimaryLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <a class="btn-secondary" href="<?= htmlspecialchars($heroSecondaryHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($heroSecondaryLabel, ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <div class="portal-landing-trust">
            <article class="portal-landing-trust-item">
                <strong><?= $featuredCount ?></strong>
                <span>Featured animals ready to review now</span>
            </article>
            <article class="portal-landing-trust-item">
                <strong>3 steps</strong>
                <span>From animal shortlist to interview and seminar</span>
            </article>
            <article class="portal-landing-trust-item">
                <strong>Online access</strong>
                <span>Submit and track your application anytime</span>
            </article>
        </div>
    </div>

    <div class="portal-landing-aside">
        <article class="portal-landing-highlight card">
            <div class="portal-landing-highlight-head">
                <span class="portal-landing-eyebrow">What to expect</span>
                <h2>Clear requirements before you apply</h2>
            </div>
            <div class="portal-landing-checklist">
                <div class="portal-landing-check">
                    <strong>1. Shortlist an animal</strong>
                    <p class="text-muted">Browse profiles first so the shelter can review the right fit for your home.</p>
                </div>
                <div class="portal-landing-check">
                    <strong>2. Prepare your details</strong>
                    <p class="text-muted">You will need household information, contact details, and a valid ID upload.</p>
                </div>
                <div class="portal-landing-check">
                    <strong>3. Wait for staff follow-up</strong>
                    <p class="text-muted">Qualified applications move to interview review and adoption seminar scheduling.</p>
                </div>
            </div>
        </article>
    </div>
</section>

<section class="portal-landing-band">
    <div class="portal-landing-band-copy">
        <span class="portal-landing-eyebrow">Why use the portal</span>
        <h2>Everything an adopter needs, before the first visit.</h2>
    </div>
    <div class="portal-landing-bento">
        <article class="portal-landing-bento-card">
            <strong>Only available animals</strong>
            <p class="text-muted">The public portal filters out animals that are not ready for adoption.</p>
        </article>
        <article class="portal-landing-bento-card">
            <strong>Application guidance</strong>
            <p class="text-muted">The process is explained upfront so you know what to prepare before submitting.</p>
        </article>
        <article class="portal-landing-bento-card">
            <strong>Track your status</strong>
            <p class="text-muted">Adopters can return to view application progress after sign-in.</p>
        </article>
    </div>
</section>

<section class="portal-section stack portal-landing-featured">
    <div class="portal-section-header portal-landing-featured-header">
        <div>
            <span class="portal-landing-eyebrow">Featured animals</span>
            <h2>Start with a profile worth a closer look.</h2>
            <p class="text-muted">Each featured profile gives you a quick read on species, size, and breed before you open the full adoption page.</p>
        </div>
        <div class="cluster portal-featured-actions">
            <div class="cluster portal-carousel-controls" data-carousel-controls<?= $featuredCount <= 1 ? ' hidden' : '' ?>>
                <button class="btn-secondary portal-carousel-button" type="button" data-carousel-prev aria-label="Show previous featured animal">Previous</button>
                <button class="btn-secondary portal-carousel-button" type="button" data-carousel-next aria-label="Show next featured animal">Next</button>
            </div>
            <a class="btn-secondary" href="/adopt/animals">View all animals</a>
        </div>
    </div>

    <div class="portal-featured-carousel portal-featured-carousel-enhanced" data-featured-carousel>
        <div class="portal-featured-stage" data-carousel-stage>
            <div class="portal-featured-track" data-carousel-track>
                <?php foreach ($featuredAnimals as $index => $animal): ?>
                    <?php
                    $displayName = (string) ($animal['name'] ?: $animal['animal_id']);
                    $breedLabel = (string) ($animal['breed_name'] ?? 'Mixed Breed');
                    $metaLabel = trim(((string) $animal['gender']) . ' • ' . ((string) $animal['size']));
                    $detailHref = '/adopt/animals/' . (int) $animal['id'];
                    ?>
                    <article
                        class="portal-animal-card card portal-featured-slide"
                        data-carousel-slide
                        data-slide-index="<?= $index ?>"
                        data-slide-href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>"
                        role="link"
                        tabindex="<?= $index === 0 ? '0' : '-1' ?>"
                        aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                    >
                        <div class="portal-animal-photo portal-featured-photo">
                            <?php if (($animal['primary_photo_path'] ?? null) !== null): ?>
                                <img src="/<?= htmlspecialchars((string) $animal['primary_photo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div class="portal-photo-fallback"><?= htmlspecialchars(substr($displayName, 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="portal-animal-card-body">
                            <div class="cluster" style="justify-content: space-between;">
                                <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="badge badge-success"><?= htmlspecialchars((string) $animal['species'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted"><?= htmlspecialchars($breedLabel, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="portal-card-meta"><?= htmlspecialchars($metaLabel !== '' ? $metaLabel : 'Shelter profile available', ENT_QUOTES, 'UTF-8') ?></p>
                            <a class="btn-secondary" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>">View profile</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="portal-carousel-indicators" data-carousel-indicators<?= $featuredCount <= 1 ? ' hidden' : '' ?>>
            <?php foreach ($featuredAnimals as $index => $animal): ?>
                <button
                    class="portal-carousel-indicator<?= $index === 0 ? ' is-active' : '' ?>"
                    type="button"
                    data-carousel-indicator
                    data-slide-to="<?= $index ?>"
                    aria-label="Show featured animal <?= $index + 1 ?>: <?= htmlspecialchars((string) ($animal['name'] ?: $animal['animal_id']), ENT_QUOTES, 'UTF-8') ?>"
                    aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                ></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="portal-landing-journey">
    <div class="portal-section-header">
        <div>
            <span class="portal-landing-eyebrow">How adoption works</span>
            <h2>A simple path, with shelter review built in.</h2>
        </div>
    </div>
    <div class="portal-landing-journey-grid">
        <article class="portal-landing-journey-card">
            <span class="portal-landing-step">01</span>
            <strong>Browse and compare</strong>
            <p class="text-muted">Start with the available-animals list and open full profiles for temperament, breed, and adoption fit.</p>
            <a class="btn-secondary" href="/adopt/animals">Browse profiles</a>
        </article>
        <article class="portal-landing-journey-card">
            <span class="portal-landing-step">02</span>
            <strong>Create your account</strong>
            <p class="text-muted">Register once so your contact details, address, and future adoption records stay in one place.</p>
            <a class="btn-secondary" href="/adopt/register">Create account</a>
        </article>
        <article class="portal-landing-journey-card">
            <span class="portal-landing-step">03</span>
            <strong>Submit for review</strong>
            <p class="text-muted">Staff review applications before moving qualified adopters to interview and seminar stages.</p>
            <?php if ($isAdopter): ?>
                <a class="btn-secondary" href="/adopt/apply">Open my application</a>
            <?php else: ?>
                <a class="btn-secondary" href="/login">Sign in later</a>
            <?php endif; ?>
        </article>
    </div>
</section>

<section class="portal-landing-prep">
    <article class="portal-landing-prep-card card">
        <div>
            <span class="portal-landing-eyebrow">Before you submit</span>
            <h2>Prepare the details the shelter will ask for.</h2>
        </div>
        <div class="portal-landing-prep-grid">
            <div class="portal-landing-prep-item">
                <strong>Household information</strong>
                <p class="text-muted">Living arrangement, housing type, and the people who will care for the animal.</p>
            </div>
            <div class="portal-landing-prep-item">
                <strong>Contact and address</strong>
                <p class="text-muted">A valid mobile number, email address, and current residence details.</p>
            </div>
            <div class="portal-landing-prep-item">
                <strong>ID and readiness</strong>
                <p class="text-muted">A valid identification document and agreement to the shelter’s adoption policies.</p>
            </div>
        </div>
    </article>
</section>

<section class="portal-landing-cta card">
    <div class="portal-landing-cta-copy">
        <span class="portal-landing-eyebrow">Ready when you are</span>
        <h2>Start with the animal list, then move into the application process.</h2>
        <p class="text-muted">The portal is designed to reduce guesswork. You can browse first, register when ready, and return later to track progress.</p>
    </div>
    <div class="cluster portal-landing-actions">
        <a class="btn-primary" href="/adopt/animals">See available animals</a>
        <?php if ($isAdopter): ?>
            <a class="btn-secondary" href="/adopt/apply">View my application</a>
        <?php else: ?>
            <a class="btn-secondary" href="/adopt/register">Create adopter account</a>
        <?php endif; ?>
    </div>
</section>
