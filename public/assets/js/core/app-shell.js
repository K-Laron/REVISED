(function () {
  if (window.CatarmanShell) {
    return;
  }

  const SIDEBAR_SCROLL_KEY = 'catarman:sidebar-scroll-top';

  function getSidebarScrollRegion() {
    return document.querySelector('[data-sidebar-scroll-region]');
  }

  function persistSidebarScrollPosition() {
    const scrollRegion = getSidebarScrollRegion();
    if (!scrollRegion) {
      return;
    }

    try {
      sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(scrollRegion.scrollTop));
    } catch {
      // Ignore storage failures; losing scroll memory is preferable to breaking nav.
    }
  }

  function restoreSidebarScrollPosition() {
    const scrollRegion = getSidebarScrollRegion();
    if (!scrollRegion) {
      return;
    }

    let savedScrollTop = null;
    try {
      const rawValue = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
      savedScrollTop = rawValue === null ? null : Number.parseInt(rawValue, 10);
    } catch {
      savedScrollTop = null;
    }

    if (!Number.isFinite(savedScrollTop) || savedScrollTop === null || savedScrollTop <= 0) {
      return;
    }

    requestAnimationFrame(() => {
      const maxScrollTop = Math.max(0, scrollRegion.scrollHeight - scrollRegion.clientHeight);
      scrollRegion.scrollTop = Math.min(savedScrollTop, maxScrollTop);
    });
  }

  function bindSidebarScrollPersistence() {
    const shell = document.querySelector('.app-shell');
    const scrollRegion = getSidebarScrollRegion();

    if (!shell || !scrollRegion || shell.dataset.sidebarScrollBound === 'true') {
      restoreSidebarScrollPosition();
      return;
    }

    shell.dataset.sidebarScrollBound = 'true';

    scrollRegion.addEventListener('scroll', persistSidebarScrollPosition, { passive: true });

    shell.querySelectorAll('.sidebar a[href]').forEach((link) => {
      link.addEventListener('click', persistSidebarScrollPosition);
    });

    restoreSidebarScrollPosition();
  }

  function bindShellPersistenceGuards() {
    if (document.body.dataset.shellPersistenceBound === 'true') {
      return;
    }

    document.body.dataset.shellPersistenceBound = 'true';
    window.addEventListener('pagehide', persistSidebarScrollPosition);
  }

  function bindSidebarToggle() {
    const shell = document.querySelector('.app-shell');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('.mobile-sidebar-backdrop');

    if (!shell || !toggle || shell.dataset.sidebarBound === 'true') {
      return;
    }

    shell.dataset.sidebarBound = 'true';

    const closeSidebar = () => shell.classList.remove('sidebar-open');

    toggle.addEventListener('click', () => {
      shell.classList.toggle('sidebar-open');
    });
    backdrop?.addEventListener('click', closeSidebar);

    shell.querySelectorAll('.sidebar a').forEach((link) => {
      link.addEventListener('click', closeSidebar);
    });
  }

  function bindPublicNavToggle() {
    const shell = document.querySelector('.public-shell');
    const toggle = document.querySelector('[data-public-nav-toggle]');
    const closeButton = document.querySelector('[data-public-nav-close]');
    const backdrop = document.querySelector('[data-public-nav-backdrop]');

    if (!shell || !toggle || shell.dataset.publicNavBound === 'true') {
      return;
    }

    shell.dataset.publicNavBound = 'true';

    const setOpen = (open) => {
      shell.classList.toggle('public-nav-open', open);
      document.body.classList.toggle('is-public-nav-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => setOpen(!shell.classList.contains('public-nav-open')));
    closeButton?.addEventListener('click', () => setOpen(false));
    backdrop?.addEventListener('click', () => setOpen(false));
    shell.querySelectorAll('.public-nav a').forEach((link) => {
      link.addEventListener('click', () => setOpen(false));
    });
  }

  function bindGlobalSearchShortcut() {
    if (document.body.dataset.globalSearchShortcutBound === 'true') {
      return;
    }

    document.body.dataset.globalSearchShortcutBound = 'true';
    document.addEventListener('keydown', (event) => {
      if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
        return;
      }

      const active = document.activeElement;
      const isTyping = active && /^(input|textarea|select)$/i.test(active.tagName);
      if (isTyping) {
        return;
      }

      const input = document.querySelector('[data-global-search-input]');
      if (!input) {
        return;
      }

      event.preventDefault();
      input.focus();
      input.select?.();
    });
  }

  function bindPasswordToggles() {
    document.querySelectorAll('[data-password-field]').forEach((field) => {
      if (field.dataset.passwordBound === 'true') {
        return;
      }

      const input = field.querySelector('[data-password-input]');
      const toggle = field.querySelector('[data-password-toggle]');
      if (!input || !toggle) {
        return;
      }

      field.dataset.passwordBound = 'true';

      const setVisible = (visible) => {
        input.type = visible ? 'text' : 'password';
        field.dataset.passwordVisible = visible ? 'true' : 'false';
        toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
      };

      setVisible(field.dataset.passwordVisible === 'true');
      toggle.addEventListener('click', () => {
        setVisible(input.type === 'password');
      });
    });
  }

  function bindGlobalEscapeHandler() {
    if (document.body.dataset.globalEscapeBound === 'true') {
      return;
    }

    document.body.dataset.globalEscapeBound = 'true';
    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') {
        return;
      }

      document.querySelector('.app-shell')?.classList.remove('sidebar-open');

      const publicShell = document.querySelector('.public-shell');
      if (publicShell?.classList.contains('public-nav-open')) {
        publicShell.classList.remove('public-nav-open');
        document.body.classList.remove('is-public-nav-open');
        document.querySelector('[data-public-nav-toggle]')?.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function syncSharedShellFeatures() {
    bindShellPersistenceGuards();
    bindSidebarScrollPersistence();
    bindSidebarToggle();
    bindPublicNavToggle();
    bindGlobalSearchShortcut();
    bindPasswordToggles();
    window.CatarmanBreadcrumbs?.sync?.();
    window.CatarmanTheme?.init?.();
    window.CatarmanNotifications?.init?.();
    window.CatarmanNavigation?.scheduleInitialPrefetch?.();
  }

  window.CatarmanShell = {
    bindGlobalEscapeHandler,
    syncSharedShellFeatures
  };
})();
