(function () {
  const sharedApi = window.CatarmanApi;
  const sharedDom = window.CatarmanDom;

  if (!sharedApi || !sharedDom) {
    return;
  }

  const manifest = [
    '/assets/js/portal/shared.js',
    '/assets/js/portal/register-form.js',
    '/assets/js/portal/apply-form.js',
    '/assets/js/portal/logout.js',
    '/assets/js/portal/featured-carousel.js',
    '/assets/js/portal/boot.js',
  ];
  const ns = window.CatarmanPortal = window.CatarmanPortal || {};

  function runBoot() {
    ns.boot?.(document);
  }

  function attachBoot() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', runBoot, { once: true });
      return;
    }

    runBoot();
  }

  function loadScript(index) {
    if (index >= manifest.length) {
      attachBoot();
      return;
    }

    const source = manifest[index];
    if (document.querySelector(`script[data-portal-module="${source}"]`)) {
      loadScript(index + 1);
      return;
    }

    const script = document.createElement('script');
    script.src = source;
    script.async = false;
    script.dataset.portalModule = source;
    script.onload = () => loadScript(index + 1);
    document.head.appendChild(script);
  }

  if (typeof ns.boot === 'function') {
    attachBoot();
    return;
  }

  loadScript(0);
})();
