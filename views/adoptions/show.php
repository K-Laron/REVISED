<?php
$fullAddress = trim(implode(', ', array_filter([
    $application['address_line1'] ?? '',
    $application['address_line2'] ?? '',
    $application['city'] ?? '',
    $application['province'] ?? '',
    $application['zip_code'] ?? '',
])));

$currentStatus = (string) ($application['status'] ?? '');
$availableStatuses = array_values($application['available_statuses'] ?? []);
$statusMoveOptions = array_values(array_filter(
    $availableStatuses,
    static fn (string $status): bool => $status !== 'rejected'
));
$canReject = in_array('rejected', $availableStatuses, true);
$canManageInterview = in_array($currentStatus, ['pending_review', 'interview_scheduled'], true);
$canRegisterSeminar = in_array($currentStatus, ['interview_completed', 'seminar_scheduled'], true);
$canUpdateAttendance = in_array($currentStatus, ['seminar_scheduled', 'seminar_completed', 'pending_payment'], true)
    && $application['seminars'] !== [];
$canComplete = in_array($currentStatus, ['seminar_completed', 'pending_payment'], true)
    && $application['completion'] === null;
?>
<section class="page-title" id="adoption-show-page" data-application-id="<?= (int) $application['id'] ?>">
    <div class="page-title-meta">
        <h1><?= htmlspecialchars($application['application_number'], ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="breadcrumb">Home &gt; Adoptions &gt; <?= htmlspecialchars($application['application_number'], ENT_QUOTES, 'UTF-8') ?></div>
        <p class="text-muted">Manage interviews, seminar attendance, billing readiness, and the final adoption release from one place.</p>
    </div>
    <div class="cluster">
        <span class="adoption-status-badge adoption-status-<?= htmlspecialchars((string) $application['status'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($statusLabels[$application['status']] ?? $application['status'], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <?php if (($application['completion']['certificate_path'] ?? null) !== null): ?>
            <a class="btn-secondary" href="/api/adoptions/<?= (int) $application['id'] ?>/certificate">Download Certificate</a>
        <?php endif; ?>
    </div>
</section>

<section class="adoption-detail-grid">
    <article class="card stack">
        <h3>Application Summary</h3>
        <dl class="adoption-summary-grid">
            <div>
                <dt>Adopter</dt>
                <dd><?= htmlspecialchars($application['adopter_name'], ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd><?= htmlspecialchars($application['adopter_email'], ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd><?= htmlspecialchars((string) ($application['adopter_phone'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Address</dt>
                <dd><?= htmlspecialchars($fullAddress !== '' ? $fullAddress : 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Animal</dt>
                <dd>
                    <?php if (($application['animal_id'] ?? null) !== null): ?>
                        <?php if (($can ?? static fn (): bool => false)('animals.read')): ?>
                            <a href="/animals/<?= (int) $application['animal_id'] ?>"><?= htmlspecialchars(($application['animal_name'] ?? 'Unnamed Animal') . ' (' . ($application['animal_code'] ?? 'N/A') . ')', ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars(($application['animal_name'] ?? 'Unnamed Animal') . ' (' . ($application['animal_code'] ?? 'N/A') . ')', ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    <?php else: ?>
                        No animal assigned
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Preferred Match</dt>
                <dd><?= htmlspecialchars(trim(implode(' / ', array_filter([$application['preferred_species'] ?? '', $application['preferred_breed'] ?? '', $application['preferred_size'] ?? '']))) ?: 'Flexible', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Housing</dt>
                <dd><?= htmlspecialchars(trim(implode(' / ', array_filter([$application['housing_type'] ?? '', $application['housing_ownership'] ?? '']))) ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Household</dt>
                <dd><?= (int) ($application['num_adults'] ?? 0) ?> adults, <?= (int) ($application['num_children'] ?? 0) ?> children</dd>
            </div>
        </dl>

        <div class="adoption-text-block">
            <strong>Experience</strong>
            <p><?= nl2br(htmlspecialchars((string) ($application['previous_pet_experience'] ?? 'No experience details provided.'), ENT_QUOTES, 'UTF-8')) ?></p>
        </div>
        <div class="adoption-text-block">
            <strong>Existing Pets</strong>
            <p><?= nl2br(htmlspecialchars((string) ($application['existing_pets_description'] ?? 'No existing pets recorded.'), ENT_QUOTES, 'UTF-8')) ?></p>
        </div>
    </article>

    <article class="card stack">
        <h3>Progress Controls</h3>
        <?php if ($statusMoveOptions !== []): ?>
            <form class="adoption-form-grid" id="adoption-status-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label class="field">
                    <span class="field-label">Move To</span>
                    <select class="select" name="status">
                        <?php foreach ($statusMoveOptions as $availableStatus): ?>
                            <option value="<?= htmlspecialchars($availableStatus, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$availableStatus] ?? $availableStatus, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn-secondary" type="submit">Update Status</button>
            </form>
        <?php else: ?>
            <div class="adoption-empty-state">No direct status changes are available for this application at its current stage.</div>
        <?php endif; ?>

        <?php if ($canReject): ?>
            <form class="adoption-form-grid" id="adoption-reject-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label class="field adoption-form-span-2">
                    <span class="field-label">Reject Application</span>
                    <textarea class="textarea" name="rejection_reason" rows="3" placeholder="State the reason for rejection"></textarea>
                </label>
                <button class="btn-danger" type="submit">Reject</button>
            </form>
        <?php elseif ($currentStatus === 'completed'): ?>
            <div class="adoption-helper-note">Completed applications can no longer be rejected.</div>
        <?php elseif (in_array($currentStatus, ['rejected', 'withdrawn'], true)): ?>
            <div class="adoption-helper-note">This application is already closed and can no longer be rejected again.</div>
        <?php endif; ?>

        <div class="adoption-mini-grid">
            <div class="adoption-mini-card">
                <span class="field-label">Billing State</span>
                <strong><?= htmlspecialchars(strtoupper($application['billing_summary']['payment_state']), ENT_QUOTES, 'UTF-8') ?></strong>
                <small class="text-muted">Invoices: <?= (int) $application['billing_summary']['invoice_count'] ?> | Balance: PHP <?= number_format((float) $application['billing_summary']['balance_due'], 2) ?></small>
            </div>
            <div class="adoption-mini-card">
                <span class="field-label">Days In Stage</span>
                <strong><?= (int) $application['days_in_stage'] ?></strong>
                <small class="text-muted">Based on last application update</small>
            </div>
        </div>

        <?php if ($application['invoices'] !== []): ?>
            <div class="adoption-related-list">
                <?php foreach ($application['invoices'] as $invoice): ?>
                    <?php if (($can ?? static fn (): bool => false)('billing.read')): ?>
                        <a class="adoption-related-item" href="/billing/invoices/<?= (int) $invoice['id'] ?>">
                            <strong><?= htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>PHP <?= number_format((float) $invoice['total_amount'], 2) ?> | <?= htmlspecialchars($invoice['payment_status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php else: ?>
                        <article class="adoption-related-item">
                            <strong><?= htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>PHP <?= number_format((float) $invoice['total_amount'], 2) ?> | <?= htmlspecialchars($invoice['payment_status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="adoption-empty-state">No linked invoices yet. Create one from Billing if this adoption requires payment collection.</div>
        <?php endif; ?>
    </article>
</section>

<section class="adoption-detail-grid">
    <article class="card stack">
        <h3>Interview Workflow</h3>
        <?php if ($canManageInterview): ?>
            <form class="adoption-form-grid" id="adoption-interview-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="interview_id" value="">
                <label class="field">
                    <span class="field-label">Existing Interview</span>
                    <select class="select" name="selected_interview">
                        <option value="">Create new interview</option>
                        <?php foreach ($application['interviews'] as $interview): ?>
                            <option value="<?= (int) $interview['id'] ?>"><?= htmlspecialchars(date('M d, Y h:i A', strtotime((string) $interview['scheduled_date'])) . ' - ' . $interview['status'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label field-label-required">Scheduled Date</span>
                    <input class="input" type="datetime-local" name="scheduled_date" required>
                </label>
                <label class="field">
                    <span class="field-label">Interview Type</span>
                    <select class="select" name="interview_type">
                        <option value="in_person">In Person</option>
                        <option value="video_call">Video Call</option>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">Status</span>
                    <select class="select" name="status">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </label>
                <label class="field adoption-form-span-2">
                    <span class="field-label">Location / Video Link</span>
                    <div class="adoption-inline-grid">
                        <input class="input" type="text" name="location" placeholder="Office, shelter, or barangay hall">
                        <input class="input" type="url" name="video_call_link" placeholder="https://meet.example.com/...">
                    </div>
                </label>
                <label class="field">
                    <span class="field-label">Conducted By</span>
                    <select class="select" name="conducted_by">
                        <option value="">Assign later</option>
                        <?php foreach ($staff as $member): ?>
                            <option value="<?= (int) $member['id'] ?>"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">Recommendation</span>
                    <select class="select" name="overall_recommendation">
                        <option value="">Pending</option>
                        <option value="Approve">Approve</option>
                        <option value="Conditional">Conditional</option>
                        <option value="Reject">Reject</option>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">Pet Care Score</span>
                    <input class="input" type="number" name="pet_care_knowledge_score" min="1" max="10">
                </label>
                <label class="field adoption-form-span-2">
                    <span class="field-label">Home Assessment Notes</span>
                    <textarea class="textarea" name="home_assessment_notes" rows="3"></textarea>
                </label>
                <label class="field adoption-form-span-2">
                    <span class="field-label">Interviewer Notes</span>
                    <textarea class="textarea" name="interviewer_notes" rows="3"></textarea>
                </label>
                <button class="btn-primary" type="submit" id="adoption-interview-submit">Save Interview</button>
            </form>
        <?php else: ?>
            <div class="adoption-empty-state">Interview actions are only available while the application is pending review or already in the interview stage.</div>
        <?php endif; ?>

        <div class="adoption-related-list" id="adoption-interview-list">
            <?php foreach ($application['interviews'] as $interview): ?>
                <article class="adoption-related-item">
                    <strong><?= htmlspecialchars(date('M d, Y h:i A', strtotime((string) $interview['scheduled_date'])), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($interview['interview_type'] . ' | ' . $interview['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </article>
            <?php endforeach; ?>
            <?php if ($application['interviews'] === []): ?>
                <div class="adoption-empty-state">No interviews scheduled yet.</div>
            <?php endif; ?>
        </div>
    </article>

    <article class="card stack">
        <h3>Seminar Workflow</h3>
        <?php require __DIR__ . '/seminars.php'; ?>
    </article>
</section>

<section class="card stack">
    <h3>Completion</h3>
    <?php if ($canComplete): ?>
        <form class="adoption-form-grid" id="adoption-completion-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label class="field">
                <span class="field-label field-label-required">Completion Date</span>
                <input class="input" type="datetime-local" name="completion_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
            <label class="field adoption-check">
                <input type="checkbox" name="payment_confirmed" value="1">
                <span>Payment confirmed</span>
            </label>
            <label class="field adoption-check">
                <input type="checkbox" name="contract_signed" value="1">
                <span>Contract signed</span>
            </label>
            <label class="field adoption-check">
                <input type="checkbox" name="medical_records_provided" value="1">
                <span>Medical records provided</span>
            </label>
            <label class="field adoption-check">
                <input type="checkbox" name="spay_neuter_agreement" value="1">
                <span>Spay / neuter agreement signed</span>
            </label>
            <label class="field adoption-form-span-2">
                <span class="field-label">Completion Notes</span>
                <textarea class="textarea" name="notes" rows="3"></textarea>
            </label>
            <button class="btn-primary" type="submit">Complete Adoption</button>
        </form>
    <?php elseif ($application['completion'] === null && ($application['animal_id'] ?? null) === null): ?>
        <div class="adoption-empty-state">Assign an animal to this application before the adoption can be completed.</div>
    <?php elseif ($application['completion'] === null): ?>
        <div class="adoption-empty-state">Completion becomes available only after seminar completion or payment clearance.</div>
    <?php endif; ?>
    <?php if ($application['completion'] !== null): ?>
        <div class="adoption-success-card">
            <strong>Completed on <?= htmlspecialchars(date('M d, Y h:i A', strtotime((string) $application['completion']['completion_date'])), ENT_QUOTES, 'UTF-8') ?></strong>
            <span>Certificate generated and animal status should now be marked as adopted.</span>
        </div>
    <?php endif; ?>
</section>

<script id="adoption-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'application' => $application,
    'seminars' => $seminars,
    'statusLabels' => $statusLabels,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
