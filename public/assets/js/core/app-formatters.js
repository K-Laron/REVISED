(function () {
  if (window.CatarmanFormatters) {
    return;
  }

  function normalizeDate(value, includeTime = false) {
    if (value === null || value === undefined || value === '') {
      return null;
    }

    const normalized = includeTime ? String(value).replace(' ', 'T') : String(value);
    const date = new Date(normalized);

    return Number.isNaN(date.getTime()) ? null : date;
  }

  function currency(value, locale = 'en-PH', currencyCode = 'PHP') {
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currencyCode
    }).format(Number(value || 0));
  }

  function titleCase(value) {
    return String(value || '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, (letter) => letter.toUpperCase());
  }

  function formatDate(value, fallback = '-', locale, options) {
    const date = normalizeDate(value);
    if (!date) {
      return fallback;
    }

    if (options) {
      return new Intl.DateTimeFormat(locale, options).format(date);
    }

    return date.toLocaleDateString(locale);
  }

  function formatDateTime(value, fallback = 'N/A', locale, options) {
    const date = normalizeDate(value, true);
    if (!date) {
      return fallback;
    }

    if (options) {
      return new Intl.DateTimeFormat(locale, options).format(date);
    }

    return date.toLocaleString(locale);
  }

  function toDateTimeLocal(value) {
    if (!value) {
      return '';
    }

    return String(value).replace(' ', 'T').slice(0, 16);
  }

  window.CatarmanFormatters = {
    currency,
    formatDate,
    formatDateTime,
    titleCase,
    toDateTimeLocal
  };
})();
