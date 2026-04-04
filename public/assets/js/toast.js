(function () {
  const icons = {
    success: '✓',
    error: '✕',
    warning: '!',
    info: 'i',
    loading: '⟳'
  };

  const defaults = {
    success: { duration: 4000, position: 'bottom-right' },
    info: { duration: 4000, position: 'bottom-right' },
    warning: { duration: 6000, position: 'bottom-right' },
    error: { duration: 0, position: 'bottom-right' },
    loading: { duration: 0, position: 'bottom-right' }
  };

  function getContainer(position) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    container.className = 'toast-container toast-position-' + position;
    return container;
  }

  function dismiss(toast) {
    const exitDuration = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220;
    toast.classList.add('toast-exit');
    window.setTimeout(() => toast.remove(), exitDuration);
  }

  function show(type, title, description = '', options = {}) {
    const settings = { ...defaults[type], ...options };
    const container = getContainer(settings.position);

    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

    const actions = (settings.actions || []).map((action) => {
      const button = document.createElement('button');
      button.className = 'toast-action-btn';
      button.type = 'button';
      button.textContent = action.label;
      button.addEventListener('click', (event) => {
        event.stopPropagation();
        action.onClick?.();
        dismiss(toast);
      });
      return button;
    });

    const actionsHtml = actions.length ? '<div class="toast-actions"></div>' : '';

    toast.innerHTML = `
      <div class="toast-pill">
        <span class="toast-icon">${icons[type] || ''}</span>
        <span class="toast-title"></span>
      </div>
      <div class="toast-content">
        <p class="toast-description"></p>
        ${actionsHtml}
      </div>
    `;

    toast.querySelector('.toast-title').textContent = title;
    toast.querySelector('.toast-description').textContent = description;

    if (actions.length) {
      const actionsNode = toast.querySelector('.toast-actions');
      actions.forEach((button) => actionsNode.appendChild(button));
    }

    toast.addEventListener('click', () => dismiss(toast));
    container.appendChild(toast);

    if (settings.duration > 0) {
      window.setTimeout(() => dismiss(toast), settings.duration);
    }

    return toast;
  }

  window.toast = {
    success(title, description = '', options = {}) {
      return show('success', title, description, options);
    },
    error(title, description = '', options = {}) {
      return show('error', title, description, options);
    },
    warning(title, description = '', options = {}) {
      return show('warning', title, description, options);
    },
    info(title, description = '', options = {}) {
      return show('info', title, description, options);
    },
    async promise(asyncFn, messages) {
      const loadingToast = show('loading', messages.loading?.title || 'Working', messages.loading?.description || '', messages.loading || {});
      try {
        const result = await asyncFn();
        dismiss(loadingToast);
        show('success', messages.success?.title || 'Done', messages.success?.description || '', messages.success || {});
        return result;
      } catch (error) {
        dismiss(loadingToast);
        show('error', messages.error?.title || 'Failed', messages.error?.description || '', messages.error || {});
        throw error;
      }
    }
  };
})();
