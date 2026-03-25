<section class="stack portal-auth-shell">
    <form class="card stack portal-auth-card" id="login-form">
        <div class="stack portal-auth-header">
            <span class="badge badge-info">Shelter Access</span>
            <h1>Sign in</h1>
            <p class="text-muted">Staff can access the management portal here, and adopters can sign in to track applications.</p>
        </div>
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-grid">
            <label class="field">
                <span class="field-label field-label-required">Email or Username</span>
                <input class="input" type="text" name="login" autocomplete="username" required>
            </label>
            <label class="field">
                <span class="field-label field-label-required">Password</span>
                <div class="password-input-shell" data-password-field data-password-visible="false">
                    <input class="input" type="password" name="password" required minlength="8" data-password-input>
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
        <div class="cluster portal-auth-links">
            <a class="text-muted" href="/forgot-password">Forgot password?</a>
            <a class="text-muted" href="/adopt/register">Create adopter account</a>
        </div>
        <button class="btn-primary" type="submit">Login</button>
        <div class="text-muted mono portal-auth-meta">Default admin: admin@catarmanshelter.gov.ph or super_admin-0001</div>
        <div id="error" class="badge badge-danger" style="display:none;"></div>
    </form>
</section>
<script>
    document.getElementById('login-form').addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const payload = Object.fromEntries(form.entries());
        const errorNode = document.getElementById('error');
        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token
            },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok) {
            errorNode.style.display = 'inline-flex';
            errorNode.textContent = result.error?.message ?? 'Login failed.';
            window.toast?.error('Login failed', errorNode.textContent);
            return;
        }
        window.toast?.success('Login successful', 'Redirecting to your next page.');
        window.location.href = result.data.redirect;
    });
</script>
