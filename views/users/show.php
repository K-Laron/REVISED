<section class="page-title" id="user-show-page">
    <div class="page-title-meta">
        <h1><?= htmlspecialchars(trim(($userRecord['first_name'] ?? '') . ' ' . ($userRecord['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="breadcrumb">Home &gt; Users &gt; <?= htmlspecialchars((string) $userRecord['email'], ENT_QUOTES, 'UTF-8') ?></div>
        <p class="text-muted">Review account details, role assignment, and active sessions.</p>
    </div>
</section>

<section class="user-detail-grid">
    <article class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3>Account Profile</h3>
                <p class="text-muted">Core account details and role assignment.</p>
            </div>
            <span class="badge <?= (int) $userRecord['is_deleted'] === 1 ? 'badge-danger' : ((int) $userRecord['is_active'] === 1 ? 'badge-success' : 'badge-warning') ?>">
                <?= (int) $userRecord['is_deleted'] === 1 ? 'Deleted' : ((int) $userRecord['is_active'] === 1 ? 'Active' : 'Inactive') ?>
            </span>
        </div>
        <form class="user-form-grid" id="user-update-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label class="field"><span class="field-label">Role</span><select class="select" name="role_id"><?php foreach ($roles as $role): ?><option value="<?= htmlspecialchars((string) $role['id'], ENT_QUOTES, 'UTF-8') ?>" <?= (int) $role['id'] === (int) $userRecord['role_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $role['display_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
            <label class="field"><span class="field-label">Username</span><input class="input" type="text" value="<?= htmlspecialchars((string) ($userRecord['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly></label>
            <label class="field"><span class="field-label">Email</span><input class="input" type="email" name="email" value="<?= htmlspecialchars((string) $userRecord['email'], ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">First Name</span><input class="input" type="text" name="first_name" value="<?= htmlspecialchars((string) $userRecord['first_name'], ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Last Name</span><input class="input" type="text" name="last_name" value="<?= htmlspecialchars((string) $userRecord['last_name'], ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Middle Name</span><input class="input" type="text" name="middle_name" value="<?= htmlspecialchars((string) ($userRecord['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Phone</span><input class="input" type="text" name="phone" value="<?= htmlspecialchars((string) ($userRecord['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">City</span><input class="input" type="text" name="city" value="<?= htmlspecialchars((string) ($userRecord['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Province</span><input class="input" type="text" name="province" value="<?= htmlspecialchars((string) ($userRecord['province'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">ZIP Code</span><input class="input" type="text" name="zip_code" value="<?= htmlspecialchars((string) ($userRecord['zip_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Address Line 1</span><input class="input" type="text" name="address_line1" value="<?= htmlspecialchars((string) ($userRecord['address_line1'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Address Line 2</span><input class="input" type="text" name="address_line2" value="<?= htmlspecialchars((string) ($userRecord['address_line2'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span class="field-label">Active</span><select class="select" name="is_active"><option value="1" <?= (int) $userRecord['is_active'] === 1 ? 'selected' : '' ?>>Yes</option><option value="0" <?= (int) $userRecord['is_active'] === 0 ? 'selected' : '' ?>>No</option></select></label>
            <label class="field"><span class="field-label">Email Verified</span><select class="select" name="email_verified"><option value="1" <?= $userRecord['email_verified_at'] ? 'selected' : '' ?>>Yes</option><option value="0" <?= !$userRecord['email_verified_at'] ? 'selected' : '' ?>>No</option></select></label>
            <label class="field"><span class="field-label">Force Password Change</span><select class="select" name="force_password_change"><option value="1" <?= (int) $userRecord['force_password_change'] === 1 ? 'selected' : '' ?>>Yes</option><option value="0" <?= (int) $userRecord['force_password_change'] === 0 ? 'selected' : '' ?>>No</option></select></label>
            <div class="cluster user-form-actions">
                <button class="btn-primary" type="submit">Save Changes</button>
                <?php if ((int) $userRecord['is_deleted'] === 1): ?>
                    <button class="btn-secondary" type="button" id="user-restore-button">Restore User</button>
                <?php else: ?>
                    <button class="btn-danger" type="button" id="user-delete-button">Delete User</button>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="card stack">
        <div>
            <h3>Reset Password</h3>
            <p class="text-muted">Issue a new temporary password and force the user to change it on next sign in.</p>
        </div>
        <form class="stack" id="user-password-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label class="field"><span class="field-label">Temporary Password</span><div class="password-input-shell" data-password-field data-password-visible="false"><input class="input" type="password" name="password" value="ChangeMe@2026" data-password-input><button class="password-visibility-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><svg class="password-visibility-icon password-visibility-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="password-visibility-icon password-visibility-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.58 10.58A2 2 0 0 0 12 16c.47 0 .92-.16 1.28-.42"></path><path d="M9.88 5.09A9.77 9.77 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.17 3.19"></path><path d="M6.61 6.61A21.13 21.13 0 0 0 1 12s4 7 11 7a10.76 10.76 0 0 0 5.39-1.39"></path></svg></button></div></label>
            <label class="field"><span class="field-label">Confirm Password</span><div class="password-input-shell" data-password-field data-password-visible="false"><input class="input" type="password" name="password_confirmation" value="ChangeMe@2026" data-password-input><button class="password-visibility-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><svg class="password-visibility-icon password-visibility-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="password-visibility-icon password-visibility-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.58 10.58A2 2 0 0 0 12 16c.47 0 .92-.16 1.28-.42"></path><path d="M9.88 5.09A9.77 9.77 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.17 3.19"></path><path d="M6.61 6.61A21.13 21.13 0 0 0 1 12s4 7 11 7a10.76 10.76 0 0 0 5.39-1.39"></path></svg></button></div></label>
            <button class="btn-secondary" type="submit">Reset Password</button>
        </form>
    </article>
</section>

<section class="card stack">
    <div>
        <h3>Active Sessions</h3>
        <p class="text-muted">Terminate stale sessions after a role change, lost device, or security event.</p>
    </div>
    <div class="users-table-wrap">
        <table class="users-table">
            <thead><tr><th>IP Address</th><th>User Agent</th><th>Last Activity</th><th>Expires</th><th>Actions</th></tr></thead>
            <tbody id="user-sessions-body">
                <?php foreach (($userRecord['sessions'] ?? []) as $session): ?>
                    <tr data-session-id="<?= htmlspecialchars((string) $session['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <td><?= htmlspecialchars((string) $session['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $session['user_agent'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $session['last_activity_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $session['expires_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><button class="btn-secondary" type="button" data-kill-session>Terminate</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script id="user-show-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'userId' => (int) $userRecord['id'],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
