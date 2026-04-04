(function (ns) {
  if (ns.initializers) {
    return;
  }

  ns.initializers = [];
  ns.apiRequest = function apiRequest(url, options = {}) {
    return window.CatarmanApi.request(url, options);
  };
  ns.extractError = function extractError(payload) {
    return window.CatarmanApi.extractError(payload);
  };
  ns.escapeHtml = function escapeHtml(value) {
    return window.CatarmanDom.escapeHtml(value);
  };
  ns.badgeForStatus = function badgeForStatus(status) {
    return ({
      'Available': 'badge-success',
      'Under Medical Care': 'badge-warning',
      'In Adoption Process': 'badge-info',
      'Adopted': 'badge-success',
      'Deceased': 'badge-danger',
      'Transferred': 'badge-info',
      'Quarantine': 'badge-warning'
    })[status] || 'badge-info';
  };
  ns.registerInitializer = function registerInitializer(initializer) {
    ns.initializers.push(initializer);
  };
  ns.boot = function boot(root = document) {
    ns.initializers.forEach((initializer) => initializer(root));
  };
})(window.CatarmanAnimals = window.CatarmanAnimals || {});
