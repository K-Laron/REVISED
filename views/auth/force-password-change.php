<section class="stack portal-auth-shell">
    <form class="card stack portal-auth-card" id="force-password-change-form">
        <div class="stack portal-auth-header">
            <span class="badge badge-warning">Password Update Required</span>
            <h1>Change your password</h1>
            <p class="text-muted">Your account is flagged to require a password update before you can continue.</p>
        </div>
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-grid">
            <label class="field">
                <span class="field-label field-label-required">Current Password</span>
                <div class="password-input-shell" data-password-field data-password-visible="false">
                    <input class="input" type="password" name="current_password" required minlength="8" autocomplete="current-password" data-password-input>
                    <button class="password-visibility-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <svg class="password-visibility-icon password-visibility-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="password-visibility-icon password-visibility-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m3 3 18 18"></path>
                            <path d="M10.58 10.58A2 2 0 0 0 12 16c.47 0 .92-.16 1.28-.42"></path>
                            <path d="M9.88 5.09A9.77 9.77 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.17 3.19"></path>
                            <path d="M6.61 6.61A21.13 21.13 0 0 0 1 12s4 7 11 7a10.76 10.76 0 0 0 5.39-1.39"></path>
                        </svg>
                    </button>
                </div>
            </label>
            <label class="field">
                <span class="field-label field-label-required">New Password</span>
                <div class="password-input-shell" data-password-field data-password-visible="false">
                    <input class="input" type="password" name="new_password" required minlength="8" autocomplete="new-password" data-password-input>
                    <button class="password-visibility-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <svg class="password-visibility-icon password-visibility-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="password-visibility-icon password-visibility-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m3 3 18 18"></path>
                            <path d="M10.58 10.58A2 2 0 0 0 12 16c.47 0 .92-.16 1.28-.42"></path>
                            <path d="M9.88 5.09A9.77 9.77 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.17 3.19"></path>
                            <path d="M6.61 6.61A21.13 21.13 0 0 0 1 12s4 7 11 7a10.76 10.76 0 0 0 5.39-1.39"></path>
                        </svg>
                    </button>
                </div>
                <small class="text-muted">Use at least 8 characters with uppercase, lowercase, number, and special character.</small>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Confirm New Password</span>
                <div class="password-input-shell" data-password-field data-password-visible="false">
                    <input class="input" type="password" name="new_password_confirmation" required minlength="8" autocomplete="new-password" data-password-input>
                    <button class="password-visibility-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <svg class="password-visibility-icon password-visibility-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="password-visibility-icon password-visibility-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m3 3 18 18"></path>
                            <path d="M10.58 10.58A2 2 0 0 0 12 16c.47 0 .92-.16 1.28-.42"></path>
                            <path d="M9.88 5.09A9.77 9.77 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.17 3.19"></path>
                            <path d="M6.61 6.61A21.13 21.13 0 0 0 1 12s4 7 11 7a10.76 10.76 0 0 0 5.39-1.39"></path>
                        </svg>
                    </button>
                </div>
            </label>
        </div>
        <div class="cluster portal-auth-actions">
            <a class="text-muted" href="/dashboard">Cancel</a>
            <button class="btn-primary" type="submit">Update Password</button>
        </div>
        <div id="force-password-change-message" class="text-muted"></div>
    </form>
</section>
<script>
    document.getElementById('force-password-change-form').addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const payload = Object.fromEntries(form.entries());
        const messageNode = document.getElementById('force-password-change-message');
        const response = await fetch('/api/auth/change-password', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token
            },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok) {
            const message = result.error?.message ?? 'Password update failed.';
            messageNode.textContent = message;
            window.toast?.error('Password update failed', message);
            return;
        }

        const message = result.message ?? 'Password updated successfully.';
        messageNode.textContent = message;
        window.toast?.success('Password updated', message);
        window.location.href = result.data.redirect || '/login';
    });
</script>
