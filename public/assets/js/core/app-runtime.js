(function () {
  if (window.CatarmanRuntime) {
    return;
  }

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

  function hardNavigate(url, options) {
    const navigateOptions = options || {};
    if (!url) {
      return;
    }

    if (navigateOptions.replace) {
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

  window.CatarmanRuntime = {
    state,
    cleanupPageBindings,
    setPageReady,
    normalizeUrl,
    isHtmlNavigationUrl,
    getAppShell,
    canSoftNavigate,
    hardNavigate,
    showToastError,
    updateDocumentMetadata,
    syncPageStyles,
    loadPageScripts,
    runInlineShellScripts,
    runCapturedPageReadyListeners,
    queueLinkPrefetch
  };
})();
