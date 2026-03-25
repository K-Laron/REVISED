document.addEventListener('DOMContentLoaded', () => {
  bindMedicalIndex();
  bindMedicalForm();
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

    const response = await fetch('/api/medical?' + params.toString(), {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
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
    const response = await fetch(endpoint, {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();
    if (!response.ok) return;

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
    const method = currentMode === 'create' ? 'POST' : 'PUT';

    const response = await fetch(endpoint, {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': form.elements._token.value
      },
      body: new URLSearchParams(new FormData(form)).toString()
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Medical save failed', extractError(result));
      return;
    }

    window.toast?.success('Medical record saved', result.message);
    window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
  });

  deleteButton?.addEventListener('click', async () => {
    const response = await fetch(`/api/medical/${recordId}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': form.elements._token.value
      }
    });
    const result = await response.json();

    if (!response.ok) {
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

function titleCase(value) {
  return String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatDate(value) {
  if (!value) return 'No due date';
  return new Date(value).toLocaleDateString();
}

function formatDateTime(value) {
  if (!value) return 'N/A';
  return new Date(value.replace(' ', 'T')).toLocaleString();
}

function extractError(result) {
  if (result?.error?.details && typeof result.error.details === 'object') {
    const firstKey = Object.keys(result.error.details)[0];
    const detail = result.error.details[firstKey];
    if (Array.isArray(detail) && detail[0]) {
      return detail[0];
    }
  }

  return result?.error?.message || 'Unexpected server response.';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
