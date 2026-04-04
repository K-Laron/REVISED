(function () {
  const sharedApi = window.CatarmanApi;
  const sharedDom = window.CatarmanDom;

  if (!sharedApi || !sharedDom) {
    return;
  }

  const manifest = [
    '/assets/js/animals/shared.js',
    '/assets/js/animals/list.js',
    '/assets/js/animals/form.js',
    '/assets/js/animals/tabs.js',
    '/assets/js/animals/status-form.js',
    '/assets/js/animals/photo-upload.js',
    '/assets/js/animals/scanner.js',
    '/assets/js/animals/timeline.js',
    '/assets/js/animals/boot.js',
  ];
  const ns = window.CatarmanAnimals = window.CatarmanAnimals || {};

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
    if (document.querySelector(`script[data-animals-module="${source}"]`)) {
      loadScript(index + 1);
      return;
    }

    const script = document.createElement('script');
    script.src = source;
    script.async = false;
    script.dataset.animalsModule = source;
    script.onload = () => loadScript(index + 1);
    document.head.appendChild(script);
  }

  if (typeof ns.boot === 'function') {
    attachBoot();
    return;
  }

  loadScript(0);
})();
