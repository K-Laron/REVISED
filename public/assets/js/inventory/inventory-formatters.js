(function () {
  if (window.CatarmanInventoryFormatters) {
    return;
  }

  const formatters = window.CatarmanFormatters;

  function addDays(date, days) {
    const next = new Date(date);
    next.setDate(next.getDate() + days);
    return next;
  }

  function currency(value) {
    return formatters.currency(value);
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('en-PH').format(Number(value || 0));
  }

  function formatDate(value, fallback) {
    return formatters.formatDate(value, fallback || '-', 'en-PH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }

  function formatTransactionType(value) {
    return String(value || '')
      .replaceAll('_', ' ')
      .replace(/\b\w/g, (char) => char.toUpperCase());
  }

  function signedQuantity(value) {
    const number = Number(value || 0);
    const prefix = number > 0 ? '+' : '';
    return prefix + formatNumber(number);
  }

  function extractError(result) {
    return window.CatarmanApi.extractError(result);
  }

  function escapeHtml(value) {
    return window.CatarmanDom.escapeHtml(value);
  }

  function itemState(item) {
    if (item.is_low_stock && item.is_expiring) return 'critical';
    if (item.is_low_stock || item.is_expiring) return 'low';
    return 'normal';
  }

  window.CatarmanInventoryFormatters = {
    addDays,
    currency,
    escapeHtml,
    extractError,
    formatDate,
    formatNumber,
    formatTransactionType,
    itemState,
    signedQuantity
  };
})();
