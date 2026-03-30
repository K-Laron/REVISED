(function () {
  function initialize() {
    window.CatarmanApp = {
      navigate: window.CatarmanNavigation?.navigate,
      reload: window.CatarmanNavigation?.reload,
      hardNavigate: window.CatarmanRuntime?.hardNavigate
    };

    window.CatarmanShell?.syncSharedShellFeatures?.();
    window.CatarmanShell?.bindGlobalEscapeHandler?.();
    window.CatarmanNavigation?.bindDocumentNavigation?.();
    window.CatarmanRuntime?.setPageReady?.(true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  } else {
    initialize();
  }
})();
