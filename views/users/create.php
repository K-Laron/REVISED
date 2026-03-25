<section class="page-title" id="user-create-page">
    <div class="page-title-meta">
        <h1>Create User</h1>
        <div class="breadcrumb">Home &gt; Users &gt; Create</div>
        <p class="text-muted">Provision a new shelter account with a temporary password and role assignment.</p>
    </div>
</section>

<section class="card stack">
    <form class="user-form-grid" id="user-create-form">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label class="field"><span class="field-label field-label-required">Role</span><select class="select" name="role_id" required><?php foreach ($roles as $role): ?><option value="<?= htmlspecialchars((string) $role['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $role['display_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
        <label class="field"><span class="field-label">Username</span><input class="input" type="text" value="Generated automatically from role after save" readonly></label>
        <label class="field"><span class="field-label field-label-required">Email</span><input class="input" type="email" name="email" required></label>
        <label class="field"><span class="field-label field-label-required">First Name</span><input class="input" type="text" name="first_name" required></label>
        <label class="field"><span class="field-label field-label-required">Last Name</span><input class="input" type="text" name="last_name" required></label>
        <label class="field"><span class="field-label">Middle Name</span><input class="input" type="text" name="middle_name"></label>
        <label class="field"><span class="field-label">Phone</span><input class="input" type="text" name="phone"></label>
        <label class="field"><span class="field-label">City</span><input class="input" type="text" name="city"></label>
        <label class="field"><span class="field-label">Province</span><input class="input" type="text" name="province"></label>
        <label class="field"><span class="field-label">ZIP Code</span><input class="input" type="text" name="zip_code"></label>
        <label class="field"><span class="field-label">Address Line 1</span><input class="input" type="text" name="address_line1"></label>
        <label class="field"><span class="field-label">Address Line 2</span><input class="input" type="text" name="address_line2"></label>
        <label class="field"><span class="field-label field-label-required">Temporary Password</span><div class="password-input-shell" data-password-field data-password-visible="false"><input class="input" type="password" name="password" value="ChangeMe@2026" required data-password-input><button class="password-visibility-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><svg class="password-visibility-icon password-visibility-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="password-visibility-icon password-visibility-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.58 10.58A2 2 0 0 0 12 16c.47 0 .92-.16 1.28-.42"></path><path d="M9.88 5.09A9.77 9.77 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.17 3.19"></path><path d="M6.61 6.61A21.13 21.13 0 0 0 1 12s4 7 11 7a10.76 10.76 0 0 0 5.39-1.39"></path></svg></button></div></label>
        <label class="field"><span class="field-label field-label-required">Confirm Password</span><div class="password-input-shell" data-password-field data-password-visible="false"><input class="input" type="password" name="password_confirmation" value="ChangeMe@2026" required data-password-input><button class="password-visibility-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><svg class="password-visibility-icon password-visibility-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="password-visibility-icon password-visibility-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.58 10.58A2 2 0 0 0 12 16c.47 0 .92-.16 1.28-.42"></path><path d="M9.88 5.09A9.77 9.77 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.17 3.19"></path><path d="M6.61 6.61A21.13 21.13 0 0 0 1 12s4 7 11 7a10.76 10.76 0 0 0 5.39-1.39"></path></svg></button></div></label>
        <label class="field"><span class="field-label">Active</span><select class="select" name="is_active"><option value="1">Yes</option><option value="0">No</option></select></label>
        <label class="field"><span class="field-label">Email Verified</span><select class="select" name="email_verified"><option value="0">No</option><option value="1">Yes</option></select></label>
        <label class="field"><span class="field-label">Force Password Change</span><select class="select" name="force_password_change"><option value="1">Yes</option><option value="0">No</option></select></label>
        <div class="cluster user-form-actions">
            <button class="btn-primary" type="submit">Create User</button>
            <a class="btn-secondary" href="/users">Cancel</a>
        </div>
    </form>
</section>

<script id="user-create-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
