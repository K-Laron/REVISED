document.addEventListener('DOMContentLoaded', () => {
  bindSearchPage();
});

const presetStorageKey = 'catarman:search-presets';

function bindSearchPage() {
  const page = document.getElementById('search-page');
  if (!page) return;

  const data = JSON.parse(document.getElementById('search-page-data')?.textContent || '{}');
  const form = document.getElementById('search-form');
  const results = document.getElementById('search-results');
  const emptyState = document.getElementById('search-empty-state');
  const loadingState = document.getElementById('search-loading-state');
  const noResults = document.getElementById('search-no-results');
  const totalBadge = document.getElementById('search-total-badge');
  const input = form?.elements?.q;
  const initialQuery = String(data.initialQuery || '').trim();
  const initialFilters = data.initialFilters || {};
  const defaultPresets = Array.isArray(data.defaultPresets) ? data.defaultPresets : [];
  const moduleCheckboxes = Array.from(form?.querySelectorAll('input[name="modules[]"]') || []);
  const secondaryFields = Array.from(form?.querySelectorAll('[data-module-filter]') || []);
  const presetList = document.querySelector('[data-search-preset-list]');
  const savePresetButton = document.querySelector('[data-search-save-preset]');

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    await runSearch();
  });

  form?.addEventListener('reset', () => {
    window.setTimeout(() => {
      syncSecondaryFilters();
      window.history.replaceState({}, '', '/search');
      results.innerHTML = '';
      emptyState.hidden = false;
      loadingState.hidden = true;
      noResults.hidden = true;
      totalBadge.textContent = 'Ready';
    }, 0);
  });

  moduleCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', syncSecondaryFilters);
  });

  savePresetButton?.addEventListener('click', () => {
    const current = serializeCurrentPreset();
    const suggestedName = current.query ? `Search: ${current.query}` : 'Saved search';
    const name = window.prompt('Preset name', suggestedName);
    if (!name) {
      return;
    }

    const presets = readSavedPresets();
    presets.unshift({
      id: 'saved-' + Date.now(),
      label: name.trim(),
      query: current.query,
      filters: current.filters,
    });
    localStorage.setItem(presetStorageKey, JSON.stringify(presets.slice(0, 8)));
    renderPresets();
    totalBadge.textContent = 'Saved';
  });

  applyInitialFilters(initialFilters);
  syncSecondaryFilters();
  renderPresets();

  if (initialQuery.length >= 2) {
    runSearch(true);
  }

  async function runSearch(isInitialLoad = false) {
    const params = buildParams();
    const query = String(params.get('q') || '').trim();

    if (query.length < 2) {
      results.innerHTML = '';
      emptyState.hidden = false;
      loadingState.hidden = true;
      noResults.hidden = true;
      totalBadge.textContent = 'Ready';
      return;
    }

    if (!isInitialLoad) {
      window.history.replaceState({}, '', `/search?${params.toString()}`);
    }

    emptyState.hidden = true;
    noResults.hidden = true;
    loadingState.hidden = false;
    results.innerHTML = '';
    totalBadge.textContent = 'Searching';

    const response = await fetch(`/api/search/global?${params.toString()}`, {
      headers: { Accept: 'application/json' }
    });

    let payload = {};
    try {
      payload = await response.json();
    } catch (error) {
      payload = {};
    }

    loadingState.hidden = true;

    if (!response.ok || payload.success !== true) {
      totalBadge.textContent = 'Error';
      noResults.hidden = false;
      return;
    }

    const responseData = payload.data || {};
    const sections = Array.isArray(responseData.sections) ? responseData.sections : [];
    const total = Number(responseData.total_results || 0);
    totalBadge.textContent = `${total} result${total === 1 ? '' : 's'}`;

    if (sections.length === 0) {
      noResults.hidden = false;
      return;
    }

    results.innerHTML = sections.map((section) => `
      <article class="card stack search-section search-section-ledger">
        <div class="search-section-head">
          <div>
            <span class="field-label">${escapeHtml(section.key || '')}</span>
            <h3>${escapeHtml(section.label || '')}</h3>
            <p class="text-muted">${escapeHtml(String(section.count || 0))} matching record${Number(section.count || 0) === 1 ? '' : 's'}</p>
          </div>
          <a class="btn-secondary" href="${escapeHtml(section.href || '#')}">Open Module</a>
        </div>
        <div class="search-result-list">
          ${(section.items || []).map((item) => `
            <a class="search-result-item" href="${escapeHtml(item.href || '#')}">
              <div class="search-result-copy">
                <strong>${escapeHtml(item.title || '')}</strong>
                <div class="text-muted">${escapeHtml(item.subtitle || '')}</div>
                <div class="search-result-meta">${escapeHtml(item.meta || '')}</div>
              </div>
              <span class="badge badge-info">${escapeHtml(item.badge || '')}</span>
            </a>
          `).join('')}
        </div>
      </article>
    `).join('');
  }

  function buildParams() {
    const params = new URLSearchParams();
    params.set('q', String(input?.value || '').trim());
    params.set('per_section', String(form?.elements?.per_section?.value || '5'));
    const dateFrom = String(form?.elements?.date_from?.value || '').trim();
    const dateTo = String(form?.elements?.date_to?.value || '').trim();
    const selectedModules = selectedModuleKeys();

    if (dateFrom) {
      params.set('date_from', dateFrom);
    }

    if (dateTo) {
      params.set('date_to', dateTo);
    }

    form?.querySelectorAll('input[name="modules[]"]:checked').forEach((checkbox) => {
      params.append('modules[]', checkbox.value);
    });

    selectedModules.forEach((moduleKey) => {
      form?.querySelectorAll(`[data-module-filter="${moduleKey}"] select`).forEach((select) => {
        const value = String(select.value || '').trim();
        if (value) {
          params.set(select.name, value);
        }
      });
    });

    return params;
  }

  function applyInitialFilters(filters) {
    if (form?.elements?.q) {
      form.elements.q.value = String(data.initialQuery || '');
    }

    if (form?.elements?.per_section && filters.per_section) {
      form.elements.per_section.value = String(filters.per_section);
    }

    if (form?.elements?.date_from && filters.date_from) {
      form.elements.date_from.value = String(filters.date_from);
    }

    if (form?.elements?.date_to && filters.date_to) {
      form.elements.date_to.value = String(filters.date_to);
    }

    const selectedModules = Array.isArray(filters.modules) ? filters.modules : [];
    if (selectedModules.length > 0) {
      form?.querySelectorAll('input[name="modules[]"]').forEach((checkbox) => {
        checkbox.checked = selectedModules.includes(checkbox.value);
      });
    }

    secondaryFields.forEach((field) => {
      const select = field.querySelector('select');
      if (!select) return;
      if (Object.prototype.hasOwnProperty.call(filters, select.name)) {
        select.value = String(filters[select.name] || '');
      }
    });
  }

  function selectedModuleKeys() {
    return moduleCheckboxes
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => checkbox.value);
  }

  function syncSecondaryFilters() {
    const selected = new Set(selectedModuleKeys());

    secondaryFields.forEach((field) => {
      const moduleKey = field.getAttribute('data-module-filter') || '';
      const enabled = selected.has(moduleKey);
      const select = field.querySelector('select');
      field.hidden = !enabled;

      if (select) {
        select.disabled = !enabled;
        if (!enabled) {
          select.value = '';
        }
      }
    });
  }

  function serializeCurrentPreset() {
    return {
      query: String(input?.value || '').trim(),
      filters: {
        per_section: String(form?.elements?.per_section?.value || '5'),
        date_from: String(form?.elements?.date_from?.value || '').trim(),
        date_to: String(form?.elements?.date_to?.value || '').trim(),
        modules: selectedModuleKeys(),
        ...selectedSecondaryFilterValues()
      }
    };
  }

  function selectedSecondaryFilterValues() {
    const filters = {};

    selectedModuleKeys().forEach((moduleKey) => {
      form?.querySelectorAll(`[data-module-filter="${moduleKey}"] select`).forEach((select) => {
        const value = String(select.value || '').trim();
        if (value) {
          filters[select.name] = value;
        }
      });
    });

    return filters;
  }

  function applyPreset(preset) {
    if (!form) {
      return;
    }

    form.elements.q.value = String(preset.query || '');
    form.elements.per_section.value = String(preset.filters?.per_section || '5');
    form.elements.date_from.value = String(preset.filters?.date_from || '');
    form.elements.date_to.value = String(preset.filters?.date_to || '');

    const selected = new Set(Array.isArray(preset.filters?.modules) ? preset.filters.modules : []);
    moduleCheckboxes.forEach((checkbox) => {
      checkbox.checked = selected.size === 0 ? checkbox.checked : selected.has(checkbox.value);
    });

    secondaryFields.forEach((field) => {
      const select = field.querySelector('select');
      if (!select) {
        return;
      }

      select.value = String(preset.filters?.[select.name] || '');
    });

    syncSecondaryFilters();
    runSearch().catch((error) => {
      console.error(error);
    });
  }

  function readSavedPresets() {
    try {
      const parsed = JSON.parse(localStorage.getItem(presetStorageKey) || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function renderPresets() {
    if (!presetList) {
      return;
    }

    const presets = [...defaultPresets, ...readSavedPresets()];
    if (presets.length === 0) {
      presetList.innerHTML = '<p class="text-muted">No presets saved yet.</p>';
      return;
    }

    presetList.innerHTML = presets.map((preset, index) => `
      <button class="search-preset-chip" type="button" data-search-preset-index="${index}">
        <span>${escapeHtml(preset.label || 'Preset')}</span>
      </button>
    `).join('');

    presetList.querySelectorAll('[data-search-preset-index]').forEach((button) => {
      button.addEventListener('click', () => {
        applyPreset(presets[Number(button.dataset.searchPresetIndex || 0)] || {});
      });
    });
  }
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
