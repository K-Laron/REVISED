(function () {
  if (window.CatarmanInventoryFormatters) {
    return;
  }

  function addDays(date, days) {
    const next = new Date(date);
    next.setDate(next.getDate() + days);
    return next;
  }

  function currency(value) {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(Number(value || 0));
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('en-PH').format(Number(value || 0));
  }

  function formatDate(value, fallback) {
    if (!value) return fallback || '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return String(value);
    }

    return new Intl.DateTimeFormat('en-PH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(date);
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
    if (result?.error?.details && typeof result.error.details === 'object') {
      const firstKey = Object.keys(result.error.details)[0];
      if (firstKey && Array.isArray(result.error.details[firstKey])) {
        return result.error.details[firstKey][0];
      }
    }

    return result?.error?.message || 'Request failed.';
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
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
