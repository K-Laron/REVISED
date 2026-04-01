<section class="page-title settings-ops-hero" id="settings-page">
    <div class="page-title-meta">
        <span class="badge badge-info">Runtime Operations</span>
        <h1>Settings</h1>
        <div class="breadcrumb">Home &gt; Settings</div>
        <p class="text-muted">Monitor runtime health, backup safety, maintenance posture, and deployment readiness from one operational console.</p>
    </div>
    <div class="settings-ops-summary">
        <article class="settings-ops-summary-card">
            <span class="field-label">Access posture</span>
            <strong><?= ($canManageSystem ?? false) ? 'Editable' : 'Read Only' ?></strong>
        </article>
        <article class="settings-ops-summary-card">
            <span class="field-label">Settings store</span>
            <strong class="mono"><?= htmlspecialchars((string) ($settingsMeta['settings_storage_driver'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        </article>
        <article class="settings-ops-summary-card">
            <span class="field-label">Environment</span>
            <strong class="mono"><?= htmlspecialchars((string) ($settingsMeta['app_env'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        </article>
    </div>
</section>

<section class="settings-grid settings-zone-grid">
    <article class="card stack">
        <div class="settings-zone-header">
            <h3>System Health</h3>
            <p class="text-muted">Live application status based on the current runtime and database connection.</p>
        </div>
        <div class="settings-health-grid" id="settings-health-grid">
            <div class="notification-empty">Loading system health.</div>
        </div>
        <div class="settings-inline-note">
            <strong>Maintenance mode:</strong>
            <span id="settings-maintenance-status">Checking status…</span>
        </div>
    </article>

    <article class="card stack settings-profile-console">
        <div class="settings-zone-header">
            <h3>Application Profile</h3>
            <p class="text-muted">Current runtime metadata and session policy from the active environment.</p>
        </div>
        <dl class="settings-profile-list">
            <div><dt>Application</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Organization</dt><dd><?= htmlspecialchars((string) ($settingsMeta['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Settings Store</dt><dd><?= htmlspecialchars((string) ($settingsMeta['settings_storage_driver'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Environment</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_env'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Application URL</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Timezone</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_timezone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Session Lifetime</dt><dd><?= htmlspecialchars((string) ($settingsMeta['session_lifetime'] ?? 0), ENT_QUOTES, 'UTF-8') ?> minutes</dd></div>
            <div><dt>Trusted Proxies</dt><dd><?= htmlspecialchars((string) (($settingsMeta['trusted_proxies'] ?? '') !== '' ? $settingsMeta['trusted_proxies'] : 'Not configured'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Current Role</dt><dd><?= htmlspecialchars((string) ($currentUser['role_display_name'] ?? $currentUser['role_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
    </article>
</section>

<section class="settings-grid settings-grid-secondary settings-zone-grid">
    <article class="card stack">
        <div class="cluster settings-zone-header" style="justify-content: space-between;">
            <div>
                <h3>System Configuration</h3>
                <p class="text-muted">Persistent deployment settings stored in MySQL, with legacy file fallback only when the table is unavailable.</p>
            </div>
            <span class="badge <?= ($canManageSystem ?? false) ? 'badge-success' : 'badge-warning' ?>">
                <?= ($canManageSystem ?? false) ? 'Editable' : 'Read Only' ?>
            </span>
        </div>
        <form class="stack" id="settings-config-form">
            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Application Name</span>
                    <input class="input" type="text" name="app_name" maxlength="150" value="<?= htmlspecialchars((string) ($settingsMeta['app_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>>
                </label>
                <label class="field">
                    <span class="field-label">Organization Name</span>
                    <input class="input" type="text" name="organization_name" maxlength="150" value="<?= htmlspecialchars((string) ($settingsMeta['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>>
                </label>
                <label class="field">
                    <span class="field-label">Contact Email</span>
                    <input class="input" type="email" name="contact_email" maxlength="255" value="<?= htmlspecialchars((string) ($settingsMeta['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>>
                </label>
                <label class="field">
                    <span class="field-label">Contact Phone</span>
                    <input class="input" type="text" name="contact_phone" maxlength="30" value="<?= htmlspecialchars((string) ($settingsMeta['contact_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>>
                </label>
                <label class="field settings-span-2">
                    <span class="field-label">Office Address</span>
                    <textarea class="textarea" name="office_address" rows="3" maxlength="500" <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>><?= htmlspecialchars((string) ($settingsMeta['office_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <label class="field">
                    <span class="field-label">Mail Delivery Mode</span>
                    <select class="input" name="mail_delivery_mode" <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>>
                        <option value="log_only" <?= (($settingsMeta['mail_delivery_mode'] ?? 'log_only') === 'log_only') ? 'selected' : '' ?>>Log Only</option>
                        <option value="smtp" <?= (($settingsMeta['mail_delivery_mode'] ?? '') === 'smtp') ? 'selected' : '' ?>>SMTP</option>
                        <option value="disabled" <?= (($settingsMeta['mail_delivery_mode'] ?? '') === 'disabled') ? 'selected' : '' ?>>Disabled</option>
                    </select>
                </label>
                <label class="field settings-span-2 settings-toggle-row">
                    <span class="field-label">Public Adoption Portal</span>
                    <label class="switch">
                        <input type="checkbox" name="public_portal_enabled" value="1" <?= !empty($settingsMeta['public_portal_enabled']) ? 'checked' : '' ?> <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>>
                        <span>Allow public animal browsing, registration, and applications</span>
                    </label>
                </label>
            </div>
            <?php if (($canManageSystem ?? false) === true): ?>
                <div class="cluster">
                    <button class="btn-primary" type="submit">Save Configuration</button>
                </div>
            <?php endif; ?>
        </form>
    </article>

    <article class="card stack settings-backup-ledger">
        <div class="cluster settings-zone-header" style="justify-content: space-between;">
            <div>
                <h3>Database Backups</h3>
                <p class="text-muted">Create fresh compressed SQL backups and review recent backup history.</p>
            </div>
            <?php if (($canManageSystem ?? false) === true): ?>
                <div class="cluster">
                    <button class="btn-secondary" type="button" id="settings-backup-schema">Create Schema Backup</button>
                    <button class="btn-primary" type="button" id="settings-backup-full">Create Full Backup</button>
                </div>
            <?php else: ?>
                <span class="badge badge-warning">Read Only</span>
            <?php endif; ?>
        </div>
        <div class="settings-inline-note">
            <strong>Restore policy:</strong>
            <span>Restore is available here for `super_admin` users and requires a typed confirmation before execution.</span>
        </div>
        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Started</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Size</th>
                        <th>Checksum</th>
                        <th>Created By</th>
                        <?php if (($canManageSystem ?? false) === true): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="settings-backups-body">
                    <tr><td colspan="<?= ($canManageSystem ?? false) === true ? '7' : '6' ?>"><div class="notification-empty">Loading backup history.</div></td></tr>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="settings-grid settings-grid-secondary settings-zone-grid">
    <article class="card stack">
        <div class="cluster settings-zone-header" style="justify-content: space-between;">
            <div>
                <h3>Maintenance Mode</h3>
                <p class="text-muted">Toggle system maintenance without manually creating or removing flag files.</p>
            </div>
            <span class="badge" id="settings-maintenance-badge">Checking</span>
        </div>
        <form class="stack" id="settings-maintenance-form">
            <label class="field settings-toggle-row">
                <span class="field-label">Maintenance Enabled</span>
                <label class="switch">
                    <input type="checkbox" name="enabled" value="1" <?= !empty($settingsMeta['maintenance_mode_enabled']) ? 'checked' : '' ?> <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>>
                    <span>Block normal traffic and return the maintenance page</span>
                </label>
            </label>
            <label class="field">
                <span class="field-label">Maintenance Message</span>
                <textarea class="textarea" name="message" rows="3" maxlength="500" <?= ($canManageSystem ?? false) ? '' : 'disabled' ?>><?= htmlspecialchars((string) ($settingsMeta['maintenance_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <?php if (($canManageSystem ?? false) === true): ?>
                <div class="cluster">
                    <button class="btn-secondary" type="submit">Apply Maintenance Settings</button>
                </div>
            <?php endif; ?>
        </form>
    </article>

    <article class="card stack settings-readiness-board">
        <div class="settings-zone-header">
            <h3>Deployment Readiness</h3>
            <p class="text-muted">Production-prep checks for environment, secrets, mail, and writable storage.</p>
        </div>
        <div class="settings-readiness-summary" id="settings-readiness-summary">
            <div class="notification-empty">Loading readiness checks.</div>
        </div>
        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="settings-readiness-body">
                    <tr><td colspan="3"><div class="notification-empty">Loading readiness checks.</div></td></tr>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="settings-grid settings-grid-secondary settings-zone-grid settings-zone-grid-single">
    <article class="card stack">
        <div>
            <h3>Operations Notes</h3>
            <p class="text-muted">Safe defaults and reminders for running this instance.</p>
        </div>
        <ul class="settings-notes">
            <li>Sessions are issued with `HttpOnly` cookies and `SameSite=Strict`.</li>
            <li>Animal uploads are validated and stored with randomized filenames.</li>
            <li>System settings now persist through MySQL and still fall back to `storage/config/system_settings.json` if the settings table is unavailable.</li>
            <li>Maintenance mode persists through the system-settings store and still respects a legacy `storage/maintenance.flag` if one exists.</li>
            <li>Only `super_admin` users can trigger backups or access the audit-trail API.</li>
            <li>API and browser authentication use the same server-side session store and active-session validation.</li>
            <li>Before release, replace localhost `APP_URL`, set `TRUSTED_PROXIES` for any reverse proxy, and rotate the seeded admin password.</li>
            <li>Mail in `log_only` or `disabled` mode is acceptable for local use, but production password recovery requires SMTP or an intentionally documented alternative process.</li>
        </ul>
    </article>
</section>

<script id="settings-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'canManageSystem' => $canManageSystem ?? false,
    'settings' => $settingsMeta,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
