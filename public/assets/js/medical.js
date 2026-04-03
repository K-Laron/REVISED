function apiRequest(url, options = {}) {
  return window.CatarmanApi.request(url, options);
}

function extractError(payload) {
  return window.CatarmanApi.extractError(payload, 'Unexpected server response.');
}

function escapeHtml(value) {
  return window.CatarmanDom.escapeHtml(value);
}

function titleCase(value) {
  return window.CatarmanFormatters.titleCase(value);
}

function formatDate(value, fallback = 'No due date') {
  return window.CatarmanFormatters.formatDate(value, fallback);
}

function formatDateTime(value, fallback = 'N/A') {
  return window.CatarmanFormatters.formatDateTime(value, fallback);
}

document.addEventListener('DOMContentLoaded', () => {
  bindMedicalIndex();
  bindMedicalForm();
  bindSharedSections();
});

function bindMedicalIndex() {
  const page = document.getElementById('medical-list-page');
  if (!page) return;

  const form = document.getElementById('medical-filter-form');
  const tableBody = document.getElementById('medical-table-body');
  const summary = document.getElementById('medical-pagination-summary');
  const controls = document.getElementById('medical-pagination-controls');
  const animalSelector = document.getElementById('medical-animal-selector');
  const createLink = document.getElementById('medical-create-link');
  let currentPage = 1;

  animalSelector?.addEventListener('change', () => {
    const animalId = animalSelector.value;
    createLink.href = animalId ? `/medical/create/${animalId}` : '/medical';
    createLink.setAttribute('aria-disabled', animalId ? 'false' : 'true');
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadRecords(1);
  });

  form.addEventListener('change', () => loadRecords(1));
  form.addEventListener('reset', () => setTimeout(() => loadRecords(1), 0));

  loadDue('/api/medical/due-vaccinations', 'medical-due-vaccinations', 'medical-due-vaccination-count', 'vaccine_name', 'next_due_date');
  loadDue('/api/medical/due-dewormings', 'medical-due-dewormings', 'medical-due-deworming-count', 'dewormer_name', 'next_due_date');
  loadRecords();

  async function loadRecords(pageNumber = 1) {
    currentPage = pageNumber;
    const params = new URLSearchParams(new FormData(form));
    params.set('page', String(pageNumber));
    params.set('per_page', '20');

    const { ok, data: result } = await apiRequest('/api/medical?' + params.toString());
    if (!ok) {
      window.toast?.error('Medical records load failed', extractError(result));
      return;
    }

    const items = Array.isArray(result.data) ? result.data : [];
    const meta = result.meta || {};
    tableBody.innerHTML = '';

    if (items.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="6">
            <div class="medical-empty-state">No medical records matched the current filters.</div>
          </td>
        </tr>
      `;
    } else {
      items.forEach((item) => {
        const row = document.createElement('tr');
        const notes = item.general_notes ? escapeHtml(item.general_notes).slice(0, 110) : 'No notes recorded.';
        row.innerHTML = `
          <td>${formatDateTime(item.record_date)}</td>
          <td>
            <strong>${escapeHtml(item.animal_name || 'Unnamed Animal')}</strong><br>
            <span class="text-muted mono">${escapeHtml(item.animal_code || '')}</span>
          </td>
          <td><span class="medical-procedure-pill">${escapeHtml(titleCase(item.procedure_type || ''))}</span></td>
          <td>${escapeHtml(item.veterinarian_name || 'Unknown')}</td>
          <td>${notes}</td>
          <td><a class="btn-secondary" href="/medical/${item.id}">View</a></td>
        `;
        tableBody.appendChild(row);
      });
    }

    const total = Number(meta.total || 0);
    const perPage = Number(meta.per_page || 20);
    const totalPages = Number(meta.total_pages || 1);
    const start = total === 0 ? 0 : ((Number(meta.page || 1) - 1) * perPage) + 1;
    const end = Math.min(total, Number(meta.page || 1) * perPage);
    summary.textContent = `Showing ${start}-${end} of ${total}`;

    controls.innerHTML = '';

    const previous = document.createElement('button');
    previous.className = 'btn-secondary';
    previous.type = 'button';
    previous.textContent = 'Previous';
    previous.disabled = currentPage <= 1;
    previous.addEventListener('click', () => loadRecords(currentPage - 1));
    controls.appendChild(previous);

    const next = document.createElement('button');
    next.className = 'btn-secondary';
    next.type = 'button';
    next.textContent = 'Next';
    next.disabled = currentPage >= totalPages;
    next.addEventListener('click', () => loadRecords(currentPage + 1));
    controls.appendChild(next);
  }

  async function loadDue(endpoint, listId, countId, labelKey, dueKey) {
    const { ok, data: result } = await apiRequest(endpoint);
    if (!ok) return;

    const items = Array.isArray(result.data) ? result.data : [];
    const container = document.getElementById(listId);
    const counter = document.getElementById(countId);
    counter.textContent = String(items.length);
    container.innerHTML = '';

    if (items.length === 0) {
      container.innerHTML = '<div class="medical-empty-state">Nothing due in the next 30 days.</div>';
      return;
    }

    items.slice(0, 5).forEach((item) => {
      const node = document.createElement('article');
      node.className = 'medical-due-item';
      node.innerHTML = `
        <strong>${escapeHtml(item.animal_name || 'Unnamed Animal')}</strong>
        <span class="text-muted mono">${escapeHtml(item.animal_code || '')}</span>
        <span>${escapeHtml(item[labelKey] || '')}</span>
        <span class="text-muted">Due ${formatDate(item[dueKey])}</span>
        <a class="btn-secondary" href="/medical/${item.id}">Open</a>
      `;
      container.appendChild(node);
    });
  }
}

function bindMedicalForm() {
  const form = document.getElementById('medical-record-form');
  if (!form) return;

  const raw = document.getElementById('medical-page-data')?.textContent || '{}';
  const pageData = JSON.parse(raw);
  const configs = pageData.formConfigs || {};
  const typeInput = form.elements.procedure_type;
  const submitButton = document.getElementById('medical-submit-button');
  const deleteButton = document.getElementById('medical-delete-button');
  const typeLabel = document.getElementById('medical-active-type-label');
  const locked = form.dataset.lockType === '1';
  const currentMode = form.dataset.mode || 'create';
  const recordId = form.dataset.recordId;

  document.querySelectorAll('[data-procedure-type]').forEach((button) => {
    button.addEventListener('click', () => {
      if (locked) return;
      setProcedureType(button.dataset.procedureType || 'vaccination');
    });
  });

  form.querySelectorAll('[data-auto-due]').forEach((field) => {
    field.dataset.auto = field.value ? '0' : '1';
    field.addEventListener('input', () => {
      field.dataset.auto = field.value ? '0' : '1';
    });
  });

  form.elements.record_date?.addEventListener('input', refreshAutoDates);
  setProcedureType(typeInput.value || 'vaccination');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const endpoint = currentMode === 'create'
      ? `/api/medical/${typeInput.value}`
      : `/api/medical/${recordId}`;
    const method = 'POST';

    const formData = new FormData(form);
    if (currentMode === 'update') {
      formData.set('_method', 'PUT');
    }

    const labRows = collectLabResultRows(formData);
    const invalidLabRow = labRows.find((row) => String(row.test_name || '').trim() === '');
    if (invalidLabRow) {
      window.toast?.error('Medical save failed', 'Each lab or imaging entry requires a test name.');
      return;
    }

    formData.set('prescriptions', JSON.stringify(collectPrescriptionRows()));
    formData.set('lab_results', JSON.stringify(labRows));

    const { data: result } = await apiRequest(endpoint, {
      method,
      csrfToken: form.elements._token.value,
      body: formData
    });

    if (result.error) {
      window.toast?.error('Medical save failed', extractError(result));
      return;
    }

    window.toast?.success('Medical record saved', result.message);
    window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
  });

  deleteButton?.addEventListener('click', async () => {
    const { data: result } = await apiRequest(`/api/medical/${recordId}`, {
      method: 'DELETE',
      csrfToken: form.elements._token.value
    });

    if (result.error) {
      window.toast?.error('Delete failed', extractError(result));
      return;
    }

    window.toast?.success('Medical record deleted', result.message);
    window.CatarmanApp?.navigate?.('/medical') || (window.location.href = '/medical');
  });

  function setProcedureType(type) {
    typeInput.value = type;
    document.querySelectorAll('[data-procedure-type]').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.procedureType === type);
      button.setAttribute('aria-selected', button.dataset.procedureType === type ? 'true' : 'false');
    });

    document.querySelectorAll('[data-medical-form-type]').forEach((panel) => {
      const active = panel.dataset.medicalFormType === type;
      panel.hidden = !active;
      panel.classList.toggle('is-active', active);
      panel.querySelectorAll('input, select, textarea').forEach((field) => {
        field.disabled = !active;
      });
    });

    const label = configs[type]?.label || titleCase(type);
    typeLabel.textContent = label;
    submitButton.textContent = currentMode === 'create' ? 'Save Record' : 'Save Changes';
    refreshAutoDates();
  }

  function refreshAutoDates() {
    const recordDate = form.elements.record_date?.value;
    if (!recordDate) return;

    form.querySelectorAll('[data-auto-due]').forEach((field) => {
      const activePanel = field.closest('[data-medical-form-type]');
      if (!activePanel || activePanel.hidden || field.dataset.auto === '0') return;

      const type = field.dataset.autoDue;
      const days = Number(configs[type]?.default_due_days || 0);
      if (days <= 0) return;
      field.value = addDays(recordDate, days);
    });
  }
}

function addDays(value, days) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  date.setDate(date.getDate() + days);
  return date.toISOString().slice(0, 10);
}

function collectPrescriptionRows() {
  return Array.from(document.querySelectorAll('[data-prescription-row]')).reduce((rows, row) => {
    const item = {};
    row.querySelectorAll('[data-rx-field]').forEach((field) => {
      item[field.dataset.rxField] = field.value;
    });

    if (Object.values(item).some((value) => String(value || '').trim() !== '')) {
      rows.push(item);
    }

    return rows;
  }, []);
}

function collectLabResultRows(formData) {
  return Array.from(document.querySelectorAll('[data-lab-result-row]')).reduce((rows, row, rowIndex) => {
    const item = {};
    row.querySelectorAll('[data-lab-field]').forEach((field) => {
      item[field.dataset.labField] = field.value;
    });

    const fileInput = row.querySelector('[data-lab-file]');
    const file = fileInput?.files?.[0];
    if (file) {
      formData.append(`lab_attachments[${rowIndex}]`, file);
      item.attachment_index = rowIndex;
    }

    const hasContent = Object.entries(item).some(([key, value]) => key === 'attachment_index'
      ? true
      : String(value || '').trim() !== '');

    if (hasContent) {
      rows.push(item);
    }

    return rows;
  }, []);
}

function bindSharedSections() {
  // Collapsible sections
  document.querySelectorAll('[data-collapsible-toggle]').forEach((header) => {
    header.style.cursor = 'pointer';
    header.addEventListener('click', () => {
      const section = header.closest('[data-collapsible]');
      if (!section) return;
      section.classList.toggle('is-collapsed');
      const body = section.querySelector('.collapsible-body');
      if (body) body.hidden = section.classList.contains('is-collapsed');
    });
  });

  // Prescription rows
  document.querySelector('[data-add-prescription]')?.addEventListener('click', () => {
    const container = document.querySelector('[data-prescriptions-container]');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'repeatable-row';
    row.setAttribute('data-prescription-row', '');
    row.innerHTML = `
      <div class="animal-form-grid">
        <label class="field"><span class="field-label">Medicine Name</span>
          <input class="input" type="text" data-rx-field="medicine_name"></label>
        <label class="field"><span class="field-label">Dosage</span>
          <input class="input" type="text" data-rx-field="dosage"></label>
        <label class="field"><span class="field-label">Frequency</span>
          <input class="input" type="text" data-rx-field="frequency" placeholder="e.g., Every 8 hours"></label>
        <label class="field"><span class="field-label">Duration</span>
          <input class="input" type="text" data-rx-field="duration" placeholder="e.g., 7 days"></label>
        <label class="field"><span class="field-label">Qty</span>
          <input class="input" type="number" min="0" data-rx-field="quantity"></label>
        <label class="field"><span class="field-label">Instructions</span>
          <input class="input" type="text" data-rx-field="instructions"></label>
      </div>
      <button type="button" class="btn-danger-sm" data-remove-row title="Remove">✕</button>
    `;
    container.appendChild(row);
  });

  // Lab result rows
  document.querySelector('[data-add-lab-result]')?.addEventListener('click', () => {
    const container = document.querySelector('[data-lab-results-container]');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'repeatable-row';
    row.setAttribute('data-lab-result-row', '');
    row.innerHTML = `
      <div class="animal-form-grid">
        <label class="field"><span class="field-label">Test Name</span>
          <input class="input" type="text" data-lab-field="test_name"></label>
        <label class="field"><span class="field-label">Result</span>
          <input class="input" type="text" data-lab-field="result_value"></label>
        <label class="field"><span class="field-label">Normal Range</span>
          <input class="input" type="text" data-lab-field="normal_range" placeholder="e.g., 5.5 – 8.5"></label>
        <label class="field"><span class="field-label">Status</span>
          <select class="select" data-lab-field="status">
            <option value="Pending">Pending</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select></label>
        <label class="field"><span class="field-label">Date</span>
          <input class="input" type="date" data-lab-field="date_conducted"></label>
        <label class="field"><span class="field-label">Remarks</span>
          <input class="input" type="text" data-lab-field="remarks"></label>
        <label class="field medical-form-span-2"><span class="field-label">Attachment (X-ray / Image)</span>
          <input class="input" type="file" accept="image/*" data-lab-file>
          <input type="hidden" data-lab-field="attachment_path" value=""></label>
      </div>
      <button type="button" class="btn-danger-sm" data-remove-row title="Remove">✕</button>
    `;
    container.appendChild(row);
  });

  // Remove row delegation
  document.addEventListener('click', (event) => {
    const removeBtn = event.target.closest('[data-remove-row]');
    if (!removeBtn) return;
    removeBtn.closest('.repeatable-row')?.remove();
  });
}
