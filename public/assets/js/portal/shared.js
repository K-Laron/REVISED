(function (ns) {
  if (ns.initializers) {
    return;
  }

  ns.initializers = [];
  ns.escapeHtml = function escapeHtml(value) {
    return window.CatarmanDom.escapeHtml(value);
  };
  ns.parseResponse = function parseResponse(response) {
    return window.CatarmanApi.parseResponse(response);
  };
  ns.registerInitializer = function registerInitializer(initializer) {
    ns.initializers.push(initializer);
  };
  ns.boot = function boot(root = document) {
    ns.initializers.forEach((initializer) => initializer(root));
  };
})(window.CatarmanPortal = window.CatarmanPortal || {});
