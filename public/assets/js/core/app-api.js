(function () {
  if (window.CatarmanApi && window.CatarmanDom) {
    return;
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }

  function extractError(payload, fallback = 'Request failed.') {
    if (payload?.error?.details && typeof payload.error.details === 'object') {
      const firstKey = Object.keys(payload.error.details)[0];
      if (firstKey && Array.isArray(payload.error.details[firstKey])) {
        return payload.error.details[firstKey][0];
      }
    }

    return payload?.error?.message || fallback;
  }

  async function request(url, options = {}) {
    const headers = {
      Accept: options.accept || 'application/json',
      ...(options.headers || {})
    };

    if (options.csrfToken) {
      headers['X-CSRF-TOKEN'] = options.csrfToken;
    }

    const response = await fetch(url, {
      method: options.method || 'GET',
      headers,
      body: options.body
    });

    let data = {};
    try {
      data = await response.json();
    } catch {
      data = {};
    }

    return {
      ok: response.ok,
      status: response.status,
      data,
      response
    };
  }

  async function parseResponse(response) {
    let result;
    try {
      result = await response.json();
    } catch {
      if (!response.ok) {
        throw { message: 'The server returned an unexpected response.', errors: {} };
      }

      return { success: true, data: {}, meta: {}, message: '' };
    }

    if (!response.ok) {
      throw {
        message: extractError(result, 'Request failed.'),
        errors: result?.error?.details || {},
        payload: result
      };
    }

    return result;
  }

  window.CatarmanApi = {
    extractError,
    parseResponse,
    request
  };

  window.CatarmanDom = {
    escapeHtml
  };
})();
