document.addEventListener('DOMContentLoaded', () => {
  bindUsersIndex();
  bindUserCreate();
  bindUserShow();
});

function bindUsersIndex() {
  const page = document.getElementById('users-page');
  if (!page) return;

  const data = JSON.parse(document.getElementById('users-page-data')?.textContent || '{}');
  const csrfToken = data.csrfToken || '';
  const filterForm = document.getElementById('users-filter-form');
  const tableBody = document.getElementById('users-table-body');
  const roleSelect = document.getElementById('role-access-select');
  const roleGroups = document.getElementById('role-access-groups');
  const savePermissionsButton = document.getElementById('role-access-save');
  let activeTab = 'active';

  document.querySelectorAll('[data-users-tab]').forEach((button) => {
    button.addEventListener('click', () => {
      activeTab = button.dataset.usersTab || 'active';
      document.querySelectorAll('[data-users-tab]').forEach((node) => node.classList.toggle('is-active', node === button));
      loadUsers();
    });
  });

  filterForm.addEventListener('input', loadUsers);
  filterForm.addEventListener('submit', (event) => {
    event.preventDefault();
    loadUsers();
  });

  roleSelect?.addEventListener('change', loadRolePermissions);
  savePermissionsButton?.addEventListener('click', saveRolePermissions);

  loadUsers();
  loadRolePermissions();

  async function loadUsers() {
    const params = new URLSearchParams(new FormData(filterForm));
    params.set('tab', activeTab);
    params.set('per_page', '50');

    const result = await apiRequest('/api/users?' + params.toString());
    if (!result.ok) {
      tableBody.innerHTML = '<tr><td colspan="5"><div class="notification-empty">Unable to load users.</div></td></tr>';
      return;
    }

    const items = Array.isArray(result.data.data) ? result.data.data : [];
    renderUserStats(items);

    if (items.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="5"><div class="notification-empty">No users matched the current filters.</div></td></tr>';
      return;
    }

    tableBody.innerHTML = '';
    items.forEach((item) => {
      const row = document.createElement('tr');
      const subtitleParts = [];
      if (item.username) {
        subtitleParts.push('@' + item.username);
      }
      if (item.email) {
        subtitleParts.push(item.email);
      }
      row.innerHTML = `
        <td>
          <div class="users-row-main">
            <strong>${escapeHtml(item.first_name + ' ' + item.last_name)}</strong>
            <span class="text-muted">${escapeHtml(subtitleParts.join(' • '))}</span>
          </div>
        </td>
        <td>${escapeHtml(item.role_display_name || item.role_name || '')}</td>
        <td>${renderUserStatus(item)}</td>
        <td>${escapeHtml(item.last_login_at || 'Never')}</td>
        <td>
          <div class="cluster">
            <a class="btn-secondary" href="/users/${item.id}">View</a>
            ${Number(item.is_deleted) === 1 ? `<button class="btn-secondary" type="button" data-restore-id="${item.id}">Restore</button>` : `<button class="btn-danger" type="button" data-delete-id="${item.id}">Delete</button>`}
          </div>
        </td>
      `;
      tableBody.appendChild(row);
    });

    tableBody.querySelectorAll('[data-delete-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        const response = await apiRequest('/api/users/' + button.dataset.deleteId, {
          method: 'DELETE',
          csrfToken
        });
        if (!response.ok) {
          window.toast?.error('Delete failed', extractError(response.data));
          return;
        }
        window.toast?.success('User deleted', response.data.message);
        loadUsers();
      });
    });

    tableBody.querySelectorAll('[data-restore-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        const response = await apiRequest('/api/users/' + button.dataset.restoreId + '/restore', {
          method: 'POST',
          csrfToken
        });
        if (!response.ok) {
          window.toast?.error('Restore failed', extractError(response.data));
          return;
        }
        window.toast?.success('User restored', response.data.message);
        loadUsers();
      });
    });
  }

  async function loadRolePermissions() {
    if (!roleSelect) return;
    const result = await apiRequest('/api/roles/' + roleSelect.value + '/permissions');
    if (!result.ok) {
      roleGroups.innerHTML = '<div class="notification-empty">Unable to load role permissions.</div>';
      return;
    }

    const names = new Set(result.data.data?.permissions || []);
    const catalog = result.data.data?.catalog || {};
    roleGroups.innerHTML = '';

    Object.entries(catalog).forEach(([module, permissions]) => {
      const article = document.createElement('article');
      article.className = 'role-access-group';
      article.innerHTML = `<strong>${escapeHtml(module)}</strong>`;

      permissions.forEach((permission) => {
        const label = document.createElement('label');
        label.className = 'role-access-option';
        label.innerHTML = `
          <input type="checkbox" value="${permission.id}" ${names.has(permission.name) ? 'checked' : ''}>
          <span>${escapeHtml(permission.display_name || permission.name)}</span>
        `;
        article.appendChild(label);
      });

      roleGroups.appendChild(article);
    });
  }

  async function saveRolePermissions() {
    const ids = Array.from(roleGroups.querySelectorAll('input[type="checkbox"]:checked')).map((input) => input.value);
    const formData = new URLSearchParams();
    ids.forEach((id) => formData.append('permission_ids[]', id));
    const response = await apiRequest('/api/roles/' + roleSelect.value + '/permissions', {
      method: 'PUT',
      csrfToken,
      body: formData.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!response.ok) {
      window.toast?.error('Permission update failed', extractError(response.data));
      return;
    }

    window.toast?.success('Role access updated', response.data.message);
  }

  function renderUserStats(items) {
    const active = items.filter((item) => Number(item.is_deleted) === 0 && Number(item.is_active) === 1).length;
    const inactive = items.filter((item) => Number(item.is_deleted) === 0 && Number(item.is_active) === 0).length;
    const deleted = items.filter((item) => Number(item.is_deleted) === 1).length;
    document.getElementById('users-stat-active').textContent = String(active);
    document.getElementById('users-stat-inactive').textContent = String(inactive);
    document.getElementById('users-stat-deleted').textContent = String(deleted);
  }
}

function bindUserCreate() {
  const page = document.getElementById('user-create-page');
  if (!page) return;

  const data = JSON.parse(document.getElementById('user-create-page-data')?.textContent || '{}');
  const form = document.getElementById('user-create-form');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await apiRequest('/api/users', {
      method: 'POST',
      csrfToken: data.csrfToken,
      body: new URLSearchParams(new FormData(form)).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!response.ok) {
      window.toast?.error('User creation failed', extractError(response.data));
      return;
    }

    const target = '/users/' + response.data.data.id;
    window.CatarmanApp?.navigate?.(target) || (window.location.href = target);
  });
}

function bindUserShow() {
  const page = document.getElementById('user-show-page');
  if (!page) return;

  const data = JSON.parse(document.getElementById('user-show-page-data')?.textContent || '{}');
  const updateForm = document.getElementById('user-update-form');
  const passwordForm = document.getElementById('user-password-form');
  const deleteButton = document.getElementById('user-delete-button');
  const restoreButton = document.getElementById('user-restore-button');

  updateForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await apiRequest('/api/users/' + data.userId, {
      method: 'PUT',
      csrfToken: data.csrfToken,
      body: new URLSearchParams(new FormData(updateForm)).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!response.ok) {
      window.toast?.error('User update failed', extractError(response.data));
      return;
    }

    window.toast?.success('User updated', response.data.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  });

  passwordForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await apiRequest('/api/users/' + data.userId + '/reset-password', {
      method: 'POST',
      csrfToken: data.csrfToken,
      body: new URLSearchParams(new FormData(passwordForm)).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!response.ok) {
      window.toast?.error('Password reset failed', extractError(response.data));
      return;
    }

    window.toast?.success('Password reset', response.data.message);
  });

  deleteButton?.addEventListener('click', async () => {
    const response = await apiRequest('/api/users/' + data.userId, {
      method: 'DELETE',
      csrfToken: data.csrfToken
    });

    if (!response.ok) {
      window.toast?.error('Delete failed', extractError(response.data));
      return;
    }

    window.CatarmanApp?.navigate?.('/users') || (window.location.href = '/users');
  });

  restoreButton?.addEventListener('click', async () => {
    const response = await apiRequest('/api/users/' + data.userId + '/restore', {
      method: 'POST',
      csrfToken: data.csrfToken
    });

    if (!response.ok) {
      window.toast?.error('Restore failed', extractError(response.data));
      return;
    }

    window.CatarmanApp?.reload?.() || window.location.reload();
  });

  document.querySelectorAll('[data-kill-session]').forEach((button) => {
    button.addEventListener('click', async () => {
      const row = button.closest('tr');
      const sessionId = row?.dataset.sessionId;
      if (!sessionId) return;

      const response = await apiRequest('/api/users/' + data.userId + '/sessions/' + sessionId, {
        method: 'DELETE',
        csrfToken: data.csrfToken
      });

      if (!response.ok) {
        window.toast?.error('Session termination failed', extractError(response.data));
        return;
      }

      row.remove();
      window.toast?.success('Session terminated', response.data.message);
    });
  });
}

async function apiRequest(url, options = {}) {
  const headers = {
    Accept: 'application/json',
    ...(options.headers || {})
  };

  if (options.csrfToken) {
    headers['X-CSRF-TOKEN'] = options.csrfToken;
  }

  const response = await fetch(url, {
    method: options.method || 'GET',
    headers,
    body: options.body
  });

  let data = {};
  try {
    data = await response.json();
  } catch (error) {
    data = {};
  }

  return { ok: response.ok, data };
}

function extractError(payload) {
  return payload?.error?.message || 'Request failed.';
}

function renderUserStatus(item) {
  if (Number(item.is_deleted) === 1) return '<span class="badge badge-danger">Deleted</span>';
  if (Number(item.is_active) === 1) return '<span class="badge badge-success">Active</span>';
  return '<span class="badge badge-warning">Inactive</span>';
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
