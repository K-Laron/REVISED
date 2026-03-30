(function () {
  if (window.CatarmanNavigation) {
    return;
  }

  const runtime = window.CatarmanRuntime;

  function isModifiedClick(event) {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
  }

  async function swapAppShell(nextDocument) {
    const currentShell = runtime.getAppShell();
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

  async function performSoftNavigation(targetUrl, options) {
    const navigationOptions = options || {};
    const url = runtime.normalizeUrl(targetUrl);
    if (!runtime.canSoftNavigate(url)) {
      runtime.hardNavigate(url, { replace: navigationOptions.history === 'replace' });
      return false;
    }

    if (runtime.state.navigating) {
      return false;
    }

    runtime.state.navigating = true;
    const navigationToken = ++runtime.state.navigationToken;

    try {
      const { response, document: nextDocument } = await fetchDocument(url);
      if (!nextDocument) {
        runtime.hardNavigate(runtime.normalizeUrl(response.url) || url, { replace: navigationOptions.history === 'replace' });
        return false;
      }

      const nextShell = nextDocument.querySelector('[data-page-shell="app"]');
      if (!nextShell) {
        runtime.hardNavigate(runtime.normalizeUrl(response.url) || url, { replace: navigationOptions.history === 'replace' });
        return false;
      }

      runtime.cleanupPageBindings();
      runtime.updateDocumentMetadata(nextDocument);
      runtime.syncPageStyles(nextDocument);
      const replacementShell = await swapAppShell(nextDocument);
      await runtime.loadPageScripts(nextDocument);
      runtime.runInlineShellScripts(replacementShell);
      runtime.runCapturedPageReadyListeners();
      window.CatarmanShell?.syncSharedShellFeatures?.();

      if (navigationToken !== runtime.state.navigationToken) {
        return false;
      }

      const finalUrl = runtime.normalizeUrl(response.url) || url;
      if (navigationOptions.history === 'replace') {
        window.history.replaceState({ url: finalUrl.href }, '', finalUrl.href);
      } else if (navigationOptions.history === 'push') {
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
      runtime.showToastError('Navigation failed', 'Unable to load the next page cleanly. Falling back to a full reload.');
      runtime.hardNavigate(url, { replace: navigationOptions.history === 'replace' });
      return false;
    } finally {
      runtime.state.navigating = false;
      runtime.setPageReady(true);
    }
  }

  function navigate(targetUrl, options) {
    const navigateOptions = options || {};
    const url = runtime.normalizeUrl(targetUrl);
    if (!url) {
      return Promise.resolve(false);
    }

    if (!runtime.canSoftNavigate(url) || navigateOptions.hard === true) {
      runtime.hardNavigate(url, { replace: navigateOptions.replace === true });
      return Promise.resolve(false);
    }

    return performSoftNavigation(url, {
      history: navigateOptions.replace ? 'replace' : 'push'
    });
  }

  function reload(options) {
    const reloadOptions = options || {};
    if (reloadOptions.hard === true || !runtime.getAppShell()) {
      window.location.reload();
      return Promise.resolve(false);
    }

    return performSoftNavigation(window.location.href, { history: 'replace' });
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

    const url = runtime.normalizeUrl(anchor.href);
    if (!runtime.canSoftNavigate(url)) {
      return;
    }

    event.preventDefault();
    void performSoftNavigation(url, { history: 'push' });
  }

  function handlePopState() {
    if (!runtime.getAppShell()) {
      return;
    }

    void performSoftNavigation(window.location.href, { history: 'replace' });
  }

  function scheduleInitialPrefetch() {
    document.querySelectorAll('.sidebar a[href], .topbar a[href], a[href]').forEach((anchor) => {
      runtime.queueLinkPrefetch(anchor);
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
        runtime.queueLinkPrefetch(anchor);
      }
    });
    document.addEventListener('focusin', (event) => {
      const anchor = event.target.closest('a[href]');
      if (anchor) {
        runtime.queueLinkPrefetch(anchor);
      }
    });
  }

  function bindDocumentNavigation() {
    if (document.body.dataset.documentNavigationBound === 'true') {
      return;
    }

    document.body.dataset.documentNavigationBound = 'true';
    document.addEventListener('click', handleLinkClick);
    window.addEventListener('popstate', handlePopState);
    bindPrefetchHints();
  }

  window.CatarmanNavigation = {
    bindDocumentNavigation,
    navigate,
    reload,
    scheduleInitialPrefetch
  };
})();
