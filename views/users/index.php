<section class="page-title" id="users-page">
    <div class="page-title-meta">
        <h1>User Management</h1>
        <div class="breadcrumb">Home &gt; Users</div>
        <p class="text-muted">Manage staff and adopter accounts, active sessions, and role access in one place.</p>
    </div>
    <div class="cluster">
        <?php if (($can ?? static fn (): bool => false)('users.create')): ?>
            <a class="btn-secondary" href="/users/create">Create User</a>
        <?php endif; ?>
    </div>
</section>

<section class="user-stats-grid">
    <article class="card user-stat-card"><span class="field-label">Active Users</span><strong id="users-stat-active">0</strong></article>
    <article class="card user-stat-card"><span class="field-label">Inactive Users</span><strong id="users-stat-inactive">0</strong></article>
    <article class="card user-stat-card"><span class="field-label">Deleted Users</span><strong id="users-stat-deleted">0</strong></article>
    <article class="card user-stat-card"><span class="field-label">Roles</span><strong id="users-stat-roles"><?= count($roles) ?></strong></article>
</section>

<section class="card stack">
    <div class="users-toolbar">
        <div class="inventory-tabs" id="users-tabs">
            <button class="tab-button is-active" type="button" data-users-tab="active">Active Users</button>
            <button class="tab-button" type="button" data-users-tab="deleted">Deleted Users</button>
        </div>
        <form class="users-filter-grid" id="users-filter-form">
            <label class="field users-filter-span-2">
                <span class="field-label">Search</span>
                <input class="input" type="search" name="search" placeholder="Email or full name">
            </label>
            <label class="field">
                <span class="field-label">Role</span>
                <select class="select" name="role_id">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars((string) $role['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $role['display_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Status</span>
                <select class="select" name="status">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
        </form>
    </div>
    <div class="users-table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                <tr><td colspan="5"><div class="notification-empty">Loading users.</div></td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php if (($canManageRolePermissions ?? false) === true): ?>
    <section class="card stack" id="role-access-card">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3>Role Access Matrix</h3>
                <p class="text-muted">Change permission bundles per role. Changes invalidate active sessions for affected users.</p>
            </div>
            <label class="field user-role-selector">
                <span class="field-label">Role</span>
                <select class="select" id="role-access-select">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars((string) $role['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $role['display_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div id="role-access-groups" class="role-access-groups">
            <div class="notification-empty">Loading permission matrix.</div>
        </div>
        <div class="cluster">
            <button class="btn-primary" type="button" id="role-access-save">Save Permissions</button>
        </div>
    </section>
<?php endif; ?>

<script id="users-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'roles' => $roles,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
