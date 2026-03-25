(function () {
  const state = {
    navigationToken: 0,
    navigating: false,
    pendingPageReadyListeners: new Set(),
    pageEventBindings: [],
    capturePageBindings: false,
    prefetchedUrls: new Set()
  };

  const nativeAddEventListener = EventTarget.prototype.addEventListener;
  const nativeRemoveEventListener = EventTarget.prototype.removeEventListener;
  const idle = window.requestIdleCallback || function (callback) {
    return window.setTimeout(callback, 180);
  };

  function isFunction(value) {
    return typeof value === 'function';
  }

  function getCurrentScriptScope() {
    const script = document.currentScript;
    if (!script) {
      return '';
    }

    if (script.dataset.pageAsset === 'js' || script.dataset.inlinePageScript === 'true') {
      return 'page';
    }

    if (script.dataset.coreAsset === 'js') {
      return 'core';
    }

    return '';
  }

  EventTarget.prototype.addEventListener = function (type, listener, options) {
    const scriptScope = getCurrentScriptScope();
    const shouldCapturePageBinding = state.capturePageBindings || scriptScope === 'page';

    if (this === document && type === 'DOMContentLoaded' && isFunction(listener) && scriptScope === 'page') {
      state.pendingPageReadyListeners.add(listener);
    }

    if (shouldCapturePageBinding && isFunction(listener)) {
      state.pageEventBindings.push({
        target: this,
        type,
        listener,
        options
      });
    }

    return nativeAddEventListener.call(this, type, listener, options);
  };

  function cleanupPageBindings() {
    state.pageEventBindings.forEach((binding) => {
      try {
        nativeRemoveEventListener.call(binding.target, binding.type, binding.listener, binding.options);
      } catch (error) {
        console.error(error);
      }
    });
    state.pageEventBindings = [];
  }

  function setPageReady(value) {
    document.documentElement.setAttribute('data-page-ready', value ? 'true' : 'false');
  }

  function isModifiedClick(event) {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
  }

  function normalizeUrl(value) {
    try {
      return new URL(value, window.location.href);
    } catch (error) {
      return null;
    }
  }

  function isHtmlNavigationUrl(url) {
    if (!url || url.origin !== window.location.origin) {
      return false;
    }

    if (!/^https?:$/.test(url.protocol)) {
      return false;
    }

    if (url.pathname.startsWith('/api/')) {
      return false;
    }

    if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) {
      return false;
    }

    return true;
  }

  function getAppShell() {
    return document.querySelector('[data-page-shell="app"]');
  }

  function canSoftNavigate(url) {
    return Boolean(getAppShell()) && isHtmlNavigationUrl(url);
  }

  function hardNavigate(url, options = {}) {
    if (!url) {
      return;
    }

    if (options.replace) {
      window.location.replace(url.href || String(url));
      return;
    }

    window.location.href = url.href || String(url);
  }

  function showToastError(title, message) {
    if (window.toast?.error) {
      window.toast.error(title, message);
    }
  }

  function updateDocumentMetadata(nextDocument) {
    const nextTitle = nextDocument.querySelector('title');
    if (nextTitle) {
      document.title = nextTitle.textContent || document.title;
    }

    const currentCsrf = document.querySelector('meta[name="csrf-token"]');
    const nextCsrf = nextDocument.querySelector('meta[name="csrf-token"]');
    if (currentCsrf && nextCsrf) {
      currentCsrf.setAttribute('content', nextCsrf.getAttribute('content') || '');
    }
  }

  function syncPageStyles(nextDocument) {
    document.querySelectorAll('link[data-page-asset="css"]').forEach((node) => node.remove());

    const marker = document.querySelector('link[href="/assets/css/dark-mode-overrides.css"][data-core-asset="css"]')
      || document.head.querySelector('script[data-core-asset="js"]');

    nextDocument.querySelectorAll('link[data-page-asset="css"]').forEach((stylesheet) => {
      const clone = stylesheet.cloneNode(true);
      if (marker?.parentNode) {
        marker.parentNode.insertBefore(clone, marker);
        return;
      }

      document.head.appendChild(clone);
    });
  }

  function removeCurrentPageScripts() {
    document.querySelectorAll('script[data-page-asset="js"]').forEach((node) => node.remove());
  }

  function loadExternalScript(template) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      Array.from(template.attributes).forEach((attribute) => {
        script.setAttribute(attribute.name, attribute.value);
      });
      script.addEventListener('load', resolve, { once: true });
      script.addEventListener('error', reject, { once: true });
      document.body.appendChild(script);
    });
  }

  function runInlineShellScripts(shell) {
    shell.querySelectorAll('script:not([src])').forEach((scriptNode) => {
      const type = (scriptNode.getAttribute('type') || '').trim().toLowerCase();
      if (type === 'application/json' || type === 'importmap') {
        return;
      }

      const replacement = document.createElement('script');
      Array.from(scriptNode.attributes).forEach((attribute) => {
        replacement.setAttribute(attribute.name, attribute.value);
      });
      replacement.dataset.inlinePageScript = 'true';
      replacement.textContent = scriptNode.textContent || '';
      scriptNode.replaceWith(replacement);
    });
  }

  async function loadPageScripts(nextDocument) {
    removeCurrentPageScripts();
    state.pendingPageReadyListeners = new Set();

    for (const script of nextDocument.querySelectorAll('script[data-page-asset="js"][src]')) {
      await loadExternalScript(script);
    }
  }

  function runCapturedPageReadyListeners() {
    if (state.pendingPageReadyListeners.size === 0) {
      return;
    }

    const listeners = Array.from(state.pendingPageReadyListeners);
    state.pendingPageReadyListeners.clear();
    const event = new Event('DOMContentLoaded');

    listeners.forEach((listener) => {
      try {
        state.capturePageBindings = true;
        listener.call(document, event);
      } catch (error) {
        console.error(error);
      } finally {
        state.capturePageBindings = false;
      }
    });
  }

  function syncSharedShellFeatures() {
    bindSidebarToggle();
    bindPublicNavToggle();
    bindGlobalSearchShortcut();
    bindPasswordToggles();
    window.CatarmanTheme?.init?.();
    window.CatarmanNotifications?.init?.();
    scheduleInitialPrefetch();
  }

  async function swapAppShell(nextDocument) {
    const currentShell = getAppShell();
    const nextShell = nextDocument.querySelector('[data-page-shell="app"]');

    if (!currentShell || !nextShell) {
      throw new Error('App shell not available for soft navigation.');
    }

    const replacement = nextShell.cloneNode(true);

    if (typeof document.startViewTransition === 'function') {
      const transition = document.startViewTransition(() => {
        currentShell.replaceWith(replacement);
      });
      await transition.finished;
      return replacement;
    }

    currentShell.replaceWith(replacement);
    return replacement;
  }

  async function fetchDocument(url) {
    const response = await fetch(url.href, {
      headers: {
        Accept: 'text/html,application/xhtml+xml'
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error('Navigation request failed.');
    }

    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('text/html')) {
      return { response, document: null };
    }

    const html = await response.text();
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    return { response, document: parsed };
  }

  async function performSoftNavigation(targetUrl, options = {}) {
    const url = normalizeUrl(targetUrl);
    if (!canSoftNavigate(url)) {
      hardNavigate(url, { replace: options.history === 'replace' });
      return false;
    }

    if (state.navigating) {
      return false;
    }

    state.navigating = true;
    const navigationToken = ++state.navigationToken;

    try {
      const { response, document: nextDocument } = await fetchDocument(url);
      if (!nextDocument) {
        hardNavigate(normalizeUrl(response.url) || url, { replace: options.history === 'replace' });
        return false;
      }

      const nextShell = nextDocument.querySelector('[data-page-shell="app"]');
      if (!nextShell) {
        hardNavigate(normalizeUrl(response.url) || url, { replace: options.history === 'replace' });
        return false;
      }

      cleanupPageBindings();
      updateDocumentMetadata(nextDocument);
      syncPageStyles(nextDocument);
      const replacementShell = await swapAppShell(nextDocument);
      await loadPageScripts(nextDocument);
      runInlineShellScripts(replacementShell);
      runCapturedPageReadyListeners();
      syncSharedShellFeatures();

      if (navigationToken !== state.navigationToken) {
        return false;
      }

      const finalUrl = normalizeUrl(response.url) || url;
      if (options.history === 'replace') {
        window.history.replaceState({ url: finalUrl.href }, '', finalUrl.href);
      } else if (options.history === 'push') {
        window.history.pushState({ url: finalUrl.href }, '', finalUrl.href);
      }

      window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
      window.dispatchEvent(new CustomEvent('app:navigated', {
        detail: {
          url: finalUrl.href,
          soft: true
        }
      }));

      return true;
    } catch (error) {
      console.error(error);
      showToastError('Navigation failed', 'Unable to load the next page cleanly. Falling back to a full reload.');
      hardNavigate(url, { replace: options.history === 'replace' });
      return false;
    } finally {
      state.navigating = false;
      setPageReady(true);
    }
  }

  function navigate(targetUrl, options = {}) {
    const url = normalizeUrl(targetUrl);
    if (!url) {
      return Promise.resolve(false);
    }

    if (!canSoftNavigate(url) || options.hard === true) {
      hardNavigate(url, { replace: options.replace === true });
      return Promise.resolve(false);
    }

    return performSoftNavigation(url, {
      history: options.replace ? 'replace' : 'push'
    });
  }

  function reload(options = {}) {
    if (options.hard === true || !getAppShell()) {
      window.location.reload();
      return Promise.resolve(false);
    }

    return performSoftNavigation(window.location.href, { history: 'replace' });
  }

  function bindSidebarToggle() {
    const shell = document.querySelector('.app-shell');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('.mobile-sidebar-backdrop');

    if (!shell || !toggle || shell.dataset.sidebarBound === 'true') {
      return;
    }

    shell.dataset.sidebarBound = 'true';

    const openSidebar = () => shell.classList.add('sidebar-open');
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

  function handleLinkClick(event) {
    if (event.defaultPrevented || isModifiedClick(event)) {
      return;
    }

    const anchor = event.target.closest('a[href]');
    if (!anchor) {
      return;
    }

    if (anchor.hasAttribute('download') || (anchor.getAttribute('target') || '').toLowerCase() === '_blank') {
      return;
    }

    const url = normalizeUrl(anchor.href);
    if (!canSoftNavigate(url)) {
      return;
    }

    event.preventDefault();
    void performSoftNavigation(url, { history: 'push' });
  }

  function handlePopState() {
    if (!getAppShell()) {
      return;
    }

    void performSoftNavigation(window.location.href, { history: 'replace' });
  }

  function addPrefetchLink(url) {
    if (!isHtmlNavigationUrl(url) || state.prefetchedUrls.has(url.href)) {
      return;
    }

    state.prefetchedUrls.add(url.href);
    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.as = 'document';
    link.href = url.href;
    document.head.appendChild(link);
  }

  function queueLinkPrefetch(anchor) {
    const url = normalizeUrl(anchor?.href);
    if (!isHtmlNavigationUrl(url)) {
      return;
    }

    idle(() => addPrefetchLink(url));
  }

  function scheduleInitialPrefetch() {
    document.querySelectorAll('.sidebar a[href], .topbar a[href], a[href]').forEach((anchor) => {
      queueLinkPrefetch(anchor);
    });
  }

  function bindPrefetchHints() {
    if (document.body.dataset.prefetchHintsBound === 'true') {
      return;
    }

    document.body.dataset.prefetchHintsBound = 'true';
    document.addEventListener('mouseover', (event) => {
      const anchor = event.target.closest('a[href]');
      if (anchor) {
        queueLinkPrefetch(anchor);
      }
    });
    document.addEventListener('focusin', (event) => {
      const anchor = event.target.closest('a[href]');
      if (anchor) {
        queueLinkPrefetch(anchor);
      }
    });
  }

  window.CatarmanApp = {
    navigate,
    reload,
    hardNavigate
  };

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

  function initialize() {
    syncSharedShellFeatures();
    bindGlobalEscapeHandler();
    bindPrefetchHints();
    setPageReady(true);
  }

  document.addEventListener('click', handleLinkClick);
  window.addEventListener('popstate', handlePopState);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  } else {
    initialize();
  }
})();
