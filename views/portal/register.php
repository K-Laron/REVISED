<?php
$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$hasFieldError = static function (string $field) use ($errors): bool {
    return isset($errors[$field]) && is_array($errors[$field]) && $errors[$field] !== [];
};

$compactRegisterError = static function (string $field, string $message): string {
    if ($message === '') {
        return '';
    }

    return match ($field) {
        'first_name', 'last_name' => str_contains($message, 'at least 2 characters')
            ? 'Use at least 2 characters.'
            : (str_contains(strtolower($message), 'required') ? '' : $message),
        'middle_name' => 'Use 100 characters or fewer.',
        'phone' => str_contains(strtolower($message), 'required') ? '' : 'Use a valid PH mobile number.',
        'email' => str_contains(strtolower($message), 'required') ? '' : 'Enter a valid email address.',
        'zip_code' => str_contains(strtolower($message), 'required') ? '' : 'Use letters, numbers, or dashes only.',
        'address_line1' => str_contains(strtolower($message), 'required') ? '' : $message,
        'address_line2' => 'Use 255 characters or fewer.',
        'city', 'province' => str_contains(strtolower($message), 'required') ? '' : $message,
        'password' => str_contains($message, 'at least 8 characters')
            ? 'Use at least 8 characters.'
            : (str_contains(strtolower($message), 'required') ? '' : 'Use uppercase, lowercase, number, and symbol.'),
        'password_confirmation' => str_contains(strtolower($message), 'required') ? '' : 'Passwords do not match.',
        default => $message,
    };
};

$globalErrorMessages = [];
foreach ($errors as $field => $messages) {
    if (!is_array($messages)) {
        continue;
    }

    if (!in_array($field, [
        'first_name',
        'last_name',
        'middle_name',
        'phone',
        'email',
        'zip_code',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'password',
        'password_confirmation',
    ], true)) {
        $globalErrorMessages = array_merge($globalErrorMessages, $messages);
    }
}
?>
<section class="portal-page-shell">
    <section class="portal-auth-shell portal-auth-shell-wide">
        <form class="card stack portal-auth-card portal-form portal-register-form" id="portal-register-form" method="POST" action="/adopt/register" novalidate>
            <div class="stack portal-auth-header">
                <span class="badge badge-info">New Adopter</span>
                <span class="portal-landing-eyebrow">Create an account</span>
                <h1>Set up your adopter access before you submit an application.</h1>
                <p class="text-muted">Use your real contact information so the shelter can reach you once your application is under review. A username is generated automatically, and you can sign in with either your email or that username.</p>
                <p class="text-muted portal-register-mobile-note">Create your account now, then browse animals and finish the adoption application when you are ready.</p>
            </div>
            <div class="portal-process-list portal-register-guidance">
                <div class="portal-process-step">
                    <strong>One account for the portal</strong>
                    <p class="text-muted">Use the same account to register, apply, and monitor your application status.</p>
                </div>
                <div class="portal-process-step">
                    <strong>Requirements are still checked later</strong>
                    <p class="text-muted">Creating an account does not complete the adoption process on its own.</p>
                </div>
                <div class="portal-process-step">
                    <strong>Return when ready</strong>
                    <p class="text-muted">You can browse animals first and finish the application after this step.</p>
                </div>
            </div>
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div id="portal-register-errors" class="badge badge-danger portal-register-summary" role="alert" aria-live="polite" <?= $globalErrorMessages === [] ? 'hidden' : '' ?>>
                <?= htmlspecialchars(implode(' ', $globalErrorMessages), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="portal-register-section">
                <div class="portal-register-section-header">
                    <h2 class="portal-register-section-title">Personal details</h2>
                    <p class="text-muted">Start with the contact information the shelter will use to reach you.</p>
                </div>
                <div class="form-grid portal-form-grid portal-form-grid-two">
                    <label class="field">
                        <span class="field-label field-label-required">First name</span>
                        <input class="input" type="text" name="first_name" autocomplete="given-name" value="<?= htmlspecialchars((string) ($old['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required aria-invalid="<?= $hasFieldError('first_name') ? 'true' : 'false' ?>" aria-describedby="portal-register-first_name-error">
                        <span class="portal-field-error" id="portal-register-first_name-error" data-field-error="first_name" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('first_name', (string) (($errors['first_name'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Last name</span>
                        <input class="input" type="text" name="last_name" autocomplete="family-name" value="<?= htmlspecialchars((string) ($old['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required aria-invalid="<?= $hasFieldError('last_name') ? 'true' : 'false' ?>" aria-describedby="portal-register-last_name-error">
                        <span class="portal-field-error" id="portal-register-last_name-error" data-field-error="last_name" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('last_name', (string) (($errors['last_name'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label">Middle name</span>
                        <input class="input" type="text" name="middle_name" autocomplete="additional-name" value="<?= htmlspecialchars((string) ($old['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" aria-invalid="<?= $hasFieldError('middle_name') ? 'true' : 'false' ?>" aria-describedby="portal-register-middle_name-error">
                        <span class="portal-field-error" id="portal-register-middle_name-error" data-field-error="middle_name" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('middle_name', (string) (($errors['middle_name'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Phone</span>
                        <input class="input" type="tel" name="phone" autocomplete="tel" inputmode="tel" value="<?= htmlspecialchars((string) ($old['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="09xxxxxxxxx" required aria-invalid="<?= $hasFieldError('phone') ? 'true' : 'false' ?>" aria-describedby="portal-register-phone-error">
                        <span class="portal-field-error" id="portal-register-phone-error" data-field-error="phone" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('phone', (string) (($errors['phone'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Email</span>
                        <input class="input" type="email" name="email" autocomplete="email" value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required aria-invalid="<?= $hasFieldError('email') ? 'true' : 'false' ?>" aria-describedby="portal-register-email-error">
                        <span class="portal-field-error" id="portal-register-email-error" data-field-error="email" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('email', (string) (($errors['email'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">ZIP code</span>
                        <input class="input" type="text" name="zip_code" inputmode="numeric" autocomplete="postal-code" value="<?= htmlspecialchars((string) ($old['zip_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required aria-invalid="<?= $hasFieldError('zip_code') ? 'true' : 'false' ?>" aria-describedby="portal-register-zip_code-error">
                        <span class="portal-field-error" id="portal-register-zip_code-error" data-field-error="zip_code" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('zip_code', (string) (($errors['zip_code'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                </div>
            </div>
            <div class="portal-register-section">
                <div class="portal-register-section-header">
                    <h2 class="portal-register-section-title">Address</h2>
                    <p class="text-muted">Enter the home location tied to your adopter profile.</p>
                </div>
                <div class="form-grid portal-form-grid portal-form-grid-two">
                    <label class="field portal-form-grid-full">
                        <span class="field-label field-label-required">Address line 1</span>
                        <input class="input" type="text" name="address_line1" autocomplete="address-line1" value="<?= htmlspecialchars((string) ($old['address_line1'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required aria-invalid="<?= $hasFieldError('address_line1') ? 'true' : 'false' ?>" aria-describedby="portal-register-address_line1-error">
                        <span class="portal-field-error" id="portal-register-address_line1-error" data-field-error="address_line1" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('address_line1', (string) (($errors['address_line1'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field portal-form-grid-full">
                        <span class="field-label">Address line 2</span>
                        <input class="input" type="text" name="address_line2" autocomplete="address-line2" value="<?= htmlspecialchars((string) ($old['address_line2'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" aria-invalid="<?= $hasFieldError('address_line2') ? 'true' : 'false' ?>" aria-describedby="portal-register-address_line2-error">
                        <span class="portal-field-error" id="portal-register-address_line2-error" data-field-error="address_line2" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('address_line2', (string) (($errors['address_line2'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">City / Municipality</span>
                        <input class="input" type="text" name="city" autocomplete="address-level2" value="<?= htmlspecialchars((string) ($old['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required aria-invalid="<?= $hasFieldError('city') ? 'true' : 'false' ?>" aria-describedby="portal-register-city-error">
                        <span class="portal-field-error" id="portal-register-city-error" data-field-error="city" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('city', (string) (($errors['city'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Province</span>
                        <input class="input" type="text" name="province" autocomplete="address-level1" value="<?= htmlspecialchars((string) ($old['province'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required aria-invalid="<?= $hasFieldError('province') ? 'true' : 'false' ?>" aria-describedby="portal-register-province-error">
                        <span class="portal-field-error" id="portal-register-province-error" data-field-error="province" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('province', (string) (($errors['province'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                </div>
            </div>
            <div class="portal-register-section">
                <div class="portal-register-section-header">
                    <h2 class="portal-register-section-title">Sign-in details</h2>
                    <p class="text-muted">Choose a strong password. Your username will be generated automatically after registration.</p>
                </div>
                <div class="form-grid portal-form-grid portal-form-grid-two">
                    <label class="field">
                        <span class="field-label field-label-required">Password</span>
                        <div class="password-input-shell" data-password-field data-password-visible="false">
                            <input class="input" type="password" name="password" autocomplete="new-password" minlength="8" required data-password-input aria-invalid="<?= $hasFieldError('password') ? 'true' : 'false' ?>" aria-describedby="portal-register-password-error portal-register-password-strength-text">
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
                        <span class="portal-field-error" id="portal-register-password-error" data-field-error="password" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('password', (string) (($errors['password'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label field-label-required">Confirm password</span>
                        <div class="password-input-shell" data-password-field data-password-visible="false">
                            <input class="input" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required data-password-input aria-invalid="<?= $hasFieldError('password_confirmation') ? 'true' : 'false' ?>" aria-describedby="portal-register-password_confirmation-error">
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
                        <span class="portal-field-error" id="portal-register-password_confirmation-error" data-field-error="password_confirmation" role="status" aria-live="polite"><?= htmlspecialchars($compactRegisterError('password_confirmation', (string) (($errors['password_confirmation'][0] ?? ''))), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <div class="portal-form-grid-full portal-password-strength" data-password-strength aria-live="polite">
                        <div class="portal-password-strength-bars" aria-hidden="true">
                            <span class="portal-password-strength-bar"></span>
                            <span class="portal-password-strength-bar"></span>
                            <span class="portal-password-strength-bar"></span>
                            <span class="portal-password-strength-bar"></span>
                        </div>
                        <span class="portal-password-strength-text" id="portal-register-password-strength-text" data-password-strength-text>Use uppercase, lowercase, number, and symbol.</span>
                    </div>
                </div>
            </div>
            <div class="cluster portal-auth-actions">
                <a class="text-muted" href="/login">Already have an account?</a>
                <button class="btn-primary" type="submit">Create account</button>
            </div>
        </form>
    </section>
</section>
