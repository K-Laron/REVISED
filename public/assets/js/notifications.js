window.CatarmanNotifications = window.CatarmanNotifications || {};
window.CatarmanNotifications.controller = window.CatarmanNotifications.controller || null;

function bindNotifications() {
  const trigger = document.querySelector('[data-notification-trigger]');
  const panel = document.querySelector('[data-notification-panel]');
  const list = document.querySelector('[data-notification-list]');
  const badge = document.querySelector('[data-notification-badge]');
  const readAllButton = document.querySelector('[data-notification-read-all]');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  if (!trigger || !panel || !list || !badge) return;
  window.CatarmanNotifications.controller?.abort?.();
  const controller = new AbortController();
  const signal = controller.signal;
  window.CatarmanNotifications.controller = controller;
  panel.setAttribute('aria-hidden', panel.hidden ? 'true' : 'false');

  trigger.addEventListener('click', async () => {
    const isHidden = panel.hidden;
    panel.hidden = !isHidden;
    trigger.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    panel.setAttribute('aria-hidden', isHidden ? 'false' : 'true');

    if (isHidden) {
      await Promise.all([loadUnreadCount(), loadNotifications()]);
    }
  }, { signal });

  document.addEventListener('click', (event) => {
    if (panel.hidden) return;
    if (panel.contains(event.target) || trigger.contains(event.target)) return;
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    panel.setAttribute('aria-hidden', 'true');
  }, { signal });

  readAllButton?.addEventListener('click', async () => {
    const response = await fetch('/api/notifications/read-all', {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    });

    if (!response.ok) {
      window.toast?.error('Notification update failed', 'Unable to mark all notifications as read.');
      return;
    }

    await Promise.all([loadUnreadCount(), loadNotifications()]);
  }, { signal });

  loadUnreadCount();

  // Poll for unread count every 30 seconds
  const pollInterval = setInterval(loadUnreadCount, 30000);
  signal.addEventListener('abort', () => clearInterval(pollInterval));

  async function loadUnreadCount() {
    const response = await fetch('/api/notifications/unread-count', {
      headers: { Accept: 'application/json' }
    });
    if (!response.ok) return;

    const result = await response.json();
    const count = Number(result.data?.count || 0);
    badge.textContent = String(count);
    badge.hidden = count < 1;
    if (readAllButton) {
      readAllButton.disabled = count < 1;
    }
  }

  async function loadNotifications() {
    list.setAttribute('aria-busy', 'true');
    const response = await fetch('/api/notifications?per_page=8', {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      list.innerHTML = '<div class="notification-empty">Unable to load notifications.</div>';
      list.setAttribute('aria-busy', 'false');
      return;
    }

    const items = Array.isArray(result.data) ? result.data : [];
    const unreadItems = items.filter((item) => !item.is_read);
    if (unreadItems.length === 0) {
      list.innerHTML = '<div class="notification-empty">No unread notifications.</div>';
      list.setAttribute('aria-busy', 'false');
      return;
    }

    list.innerHTML = '';
    unreadItems.forEach((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'notification-item is-unread';
      button.innerHTML = `
        <div class="notification-item-meta">
          <strong>${escapeHtml(item.title || 'Notification')}</strong>
          <span>${formatNotificationDate(item.created_at)}</span>
        </div>
        <div>${escapeHtml(item.message || '')}</div>
        <div class="text-muted">${escapeHtml(item.type || '')}</div>
      `;
      button.addEventListener('click', async () => {
        if (!item.is_read) {
          await fetch('/api/notifications/' + item.id + '/read', {
            method: 'PUT',
            headers: {
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrfToken
            }
          });
        }

        if (item.link) {
          window.CatarmanApp?.navigate?.(item.link) || (window.location.href = item.link);
          return;
        }

        await Promise.all([loadUnreadCount(), loadNotifications()]);
      });
      list.appendChild(button);
    });
    list.setAttribute('aria-busy', 'false');
  }
}

function formatNotificationDate(value) {
  if (!value) return 'Now';

  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return String(value);

  return date.toLocaleString([], {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit'
  });
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

window.CatarmanNotifications.init = bindNotifications;

document.addEventListener('DOMContentLoaded', bindNotifications);
