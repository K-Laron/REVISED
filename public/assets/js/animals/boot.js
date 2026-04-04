(function (ns) {
  if (ns.bootAttached === true) {
    return;
  }

  ns.bootAttached = true;

  function start() {
    ns.boot?.(document);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
    return;
  }

  start();
})(window.CatarmanAnimals = window.CatarmanAnimals || {});
