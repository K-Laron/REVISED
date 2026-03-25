<section class="stack portal-auth-shell">
    <form class="card stack portal-auth-card" id="forgot-form">
        <div class="stack portal-auth-header">
            <span class="badge badge-warning">Recovery</span>
            <h1>Forgot password</h1>
            <p class="text-muted">Enter your account email. If it exists, the reset flow will be prepared.</p>
        </div>
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label class="field">
            <span class="field-label field-label-required">Email</span>
            <input class="input" type="email" name="email" required>
        </label>
        <button class="btn-primary" type="submit">Send Reset Link</button>
        <a class="text-muted" href="/login">Back to login</a>
        <div class="text-muted" id="message"></div>
    </form>
</section>
<script>
    document.getElementById('forgot-form').addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const payload = Object.fromEntries(form.entries());
        const response = await fetch('/api/auth/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token
            },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        document.getElementById('message').textContent = response.ok ? result.message : (result.error?.message ?? 'Request failed.');
        if (response.ok) {
            window.toast?.info('Reset requested', result.message);
        } else {
            window.toast?.error('Request failed', result.error?.message ?? 'Request failed.');
        }
    });
</script>
