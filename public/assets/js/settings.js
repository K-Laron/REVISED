document.addEventListener('DOMContentLoaded', () => {
  bindSettingsPage();
});

function bindSettingsPage() {
  const page = document.getElementById('settings-page');
  if (!page) return;

  const data = JSON.parse(document.getElementById('settings-page-data')?.textContent || '{}');
  const csrfToken = data.csrfToken || '';
  const canManageSystem = Boolean(data.canManageSystem);
  const initialSettings = data.settings || {};
  const healthGrid = document.getElementById('settings-health-grid');
  const maintenanceStatus = document.getElementById('settings-maintenance-status');
  const maintenanceBadge = document.getElementById('settings-maintenance-badge');
  const backupsBody = document.getElementById('settings-backups-body');
  const readinessSummary = document.getElementById('settings-readiness-summary');
  const readinessBody = document.getElementById('settings-readiness-body');
  const configForm = document.getElementById('settings-config-form');
  const maintenanceForm = document.getElementById('settings-maintenance-form');
  const fullBackupButton = document.getElementById('settings-backup-full');
  const schemaBackupButton = document.getElementById('settings-backup-schema');

  if (canManageSystem) {
    fullBackupButton?.addEventListener('click', () => createBackup('full'));
    schemaBackupButton?.addEventListener('click', () => createBackup('schema_only'));
    configForm?.addEventListener('submit', saveSettings);
    maintenanceForm?.addEventListener('submit', saveMaintenance);
  }

  populateMaintenance(initialSettings);
  loadHealth();
  loadBackups();
  loadReadiness();

  async function loadHealth() {
    const result = await apiRequest('/api/system/health');
    if (!result.ok) {
      healthGrid.innerHTML = '<div class="notification-empty">Unable to load system health.</div>';
      if (maintenanceStatus) maintenanceStatus.textContent = 'Status unavailable.';
      if (maintenanceBadge) {
        maintenanceBadge.className = 'badge badge-danger';
        maintenanceBadge.textContent = 'Unavailable';
      }
      return;
    }

    const health = result.data.data || {};
    const database = health.database || {};
    const statusLabel = String(health.status || 'unknown');
    const dbStatus = String(database.status || 'unknown');

    healthGrid.innerHTML = [
      healthCard('Application Status', statusLabel),
      healthCard('Database', dbStatus),
      healthCard('Uptime', String(health.uptime_human || '0m')),
      healthCard('Checked At', String(health.checked_at || '')),
    ].join('');

    if (maintenanceStatus) {
      maintenanceStatus.textContent = health.maintenance_mode
        ? 'Enabled. Source: ' + escapeHtml(health.maintenance_source || 'settings') + '.'
        : 'Disabled.';
    }

    if (maintenanceBadge) {
      maintenanceBadge.className = 'badge ' + (health.maintenance_mode ? 'badge-warning' : 'badge-success');
      maintenanceBadge.textContent = health.maintenance_mode ? 'Maintenance On' : 'Maintenance Off';
    }
  }

  async function loadBackups() {
    if (!canManageSystem) {
      backupsBody.innerHTML = '<tr><td colspan="6"><div class="notification-empty">Backup history is visible only to super administrators.</div></td></tr>';
      return;
    }

    const result = await apiRequest('/api/system/backups?per_page=10');
    if (!result.ok) {
      backupsBody.innerHTML = '<tr><td colspan="7"><div class="notification-empty">Unable to load backup history.</div></td></tr>';
      return;
    }

    const items = Array.isArray(result.data.data) ? result.data.data : [];
    if (items.length === 0) {
      backupsBody.innerHTML = '<tr><td colspan="7"><div class="notification-empty">No backups created yet.</div></td></tr>';
      return;
    }

    backupsBody.innerHTML = items.map((item) => `
      <tr>
        <td>${escapeHtml(item.started_at || '')}</td>
        <td>${escapeHtml(item.backup_type || '')}</td>
        <td>${renderStatus(item.status || '')}</td>
        <td>${escapeHtml(formatBytes(item.file_size_bytes || 0))}</td>
        <td><code>${escapeHtml(truncate(item.checksum_sha256 || ''))}</code></td>
        <td>${escapeHtml(item.created_by_name || 'System')}</td>
        <td>${renderBackupActions(item)}</td>
      </tr>
    `).join('');

    backupsBody.querySelectorAll('[data-restore-backup-id]').forEach((button) => {
      button.addEventListener('click', () => restoreBackup(button.dataset.restoreBackupId));
    });
  }

  async function createBackup(type) {
    const result = await apiRequest('/api/system/backup', {
      method: 'POST',
      csrfToken,
      body: new URLSearchParams({ backup_type: type }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!result.ok) {
      window.toast?.error('Backup failed', extractError(result.data));
      return;
    }

    window.toast?.success('Backup created', result.data.message || 'Backup created successfully.');
    await loadBackups();
    await loadHealth();
    await loadReadiness();
  }

  async function restoreBackup(backupId) {
    const confirmation = window.prompt(`Type RESTORE ${backupId} to restore this backup. This replaces the current database state.`);
    if (confirmation !== `RESTORE ${backupId}`) {
      window.toast?.error('Restore cancelled', 'Typed confirmation did not match.');
      return;
    }

    const result = await apiRequest(`/api/system/backups/${backupId}/restore`, {
      method: 'POST',
      csrfToken,
      body: new URLSearchParams({ restore_confirmation: `RESTORE ${backupId}` }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!result.ok) {
      window.toast?.error('Restore failed', extractError(result.data));
      return;
    }

    window.toast?.success('Backup restored', result.data.message || 'Backup restored successfully.');
    await loadBackups();
    await loadHealth();
    await loadReadiness();
  }

  async function saveSettings(event) {
    event.preventDefault();
    const formData = new URLSearchParams();
    formData.set('app_name', configForm.elements.app_name.value);
    formData.set('organization_name', configForm.elements.organization_name.value);
    formData.set('contact_email', configForm.elements.contact_email.value);
    formData.set('contact_phone', configForm.elements.contact_phone.value);
    formData.set('office_address', configForm.elements.office_address.value);
    formData.set('mail_delivery_mode', configForm.elements.mail_delivery_mode.value);
    formData.set('public_portal_enabled', configForm.elements.public_portal_enabled.checked ? '1' : '0');
    formData.set('maintenance_message', maintenanceForm?.elements.message?.value || '');

    const result = await apiRequest('/api/system/settings', {
      method: 'PUT',
      csrfToken,
      body: formData.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!result.ok) {
      window.toast?.error('Settings save failed', extractError(result.data));
      return;
    }

    window.toast?.success('Configuration saved', result.data.message || 'System settings updated.');
  }

  async function saveMaintenance(event) {
    event.preventDefault();
    const formData = new URLSearchParams();
    formData.set('enabled', maintenanceForm.elements.enabled.checked ? '1' : '0');
    formData.set('message', maintenanceForm.elements.message.value);

    const result = await apiRequest('/api/system/maintenance', {
      method: 'PUT',
      csrfToken,
      body: formData.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    });

    if (!result.ok) {
      window.toast?.error('Maintenance update failed', extractError(result.data));
      return;
    }

    populateMaintenance(result.data.data || {});
    window.toast?.success('Maintenance updated', result.data.message || 'Maintenance mode updated.');
    await loadHealth();
  }

  async function loadReadiness() {
    const result = await apiRequest('/api/system/readiness');
    if (!result.ok) {
      readinessSummary.innerHTML = '<div class="notification-empty">Unable to load readiness checks.</div>';
      readinessBody.innerHTML = '<tr><td colspan="3"><div class="notification-empty">Readiness checks unavailable.</div></td></tr>';
      return;
    }

    const payload = result.data.data || {};
    const summary = payload.summary || {};
    readinessSummary.innerHTML = `
      <article class="settings-health-card"><span class="field-label">Overall</span><strong>${escapeHtml(payload.overall_status || 'unknown')}</strong></article>
      <article class="settings-health-card"><span class="field-label">Passed</span><strong>${escapeHtml(summary.pass || 0)}</strong></article>
      <article class="settings-health-card"><span class="field-label">Warnings</span><strong>${escapeHtml(summary.warn || 0)}</strong></article>
      <article class="settings-health-card"><span class="field-label">Failed</span><strong>${escapeHtml(summary.fail || 0)}</strong></article>
    `;

    const checks = Array.isArray(payload.checks) ? payload.checks : [];
    readinessBody.innerHTML = checks.map((check) => `
      <tr>
        <td>${escapeHtml(check.label || '')}</td>
        <td>${renderStatus(check.status || '')}</td>
        <td>${escapeHtml(check.message || '')}</td>
      </tr>
    `).join('');
  }

  function populateMaintenance(settings) {
    if (!maintenanceForm) return;

    if (maintenanceForm.elements.enabled) {
      maintenanceForm.elements.enabled.checked = Boolean(settings.maintenance_mode_enabled);
    }

    if (maintenanceForm.elements.message && settings.maintenance_message !== undefined) {
      maintenanceForm.elements.message.value = settings.maintenance_message;
    }
  }

  function healthCard(label, value) {
    return `
      <article class="settings-health-card">
        <span class="field-label">${escapeHtml(label)}</span>
        <strong>${escapeHtml(value)}</strong>
      </article>
    `;
  }

  function renderBackupActions(item) {
    if (String(item.status || '').toLowerCase() !== 'completed') {
      return '<span class="text-muted">Unavailable</span>';
    }

    return `<button class="btn-secondary" type="button" data-restore-backup-id="${escapeHtml(item.id || '')}">Restore</button>`;
  }
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

function renderStatus(status) {
  const normalized = String(status || '').toLowerCase();
  const variant = normalized === 'completed'
    ? 'success'
    : normalized === 'failed'
      ? 'danger'
      : normalized === 'pass'
        ? 'success'
        : normalized === 'warn'
          ? 'warning'
          : 'warning';

  return `<span class="badge badge-${variant}">${escapeHtml(status)}</span>`;
}

function formatBytes(value) {
  const bytes = Number(value || 0);
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function truncate(value) {
  const stringValue = String(value || '');
  if (stringValue.length <= 16) return stringValue;
  return stringValue.slice(0, 8) + '…' + stringValue.slice(-8);
}

function extractError(payload) {
  return payload?.error?.message || 'Request failed.';
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
