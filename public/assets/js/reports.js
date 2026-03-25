document.addEventListener('DOMContentLoaded', () => {
  bindReportsPage();
  bindReportViewer();
});

function bindReportsPage() {
  const page = document.getElementById('reports-page');
  if (!page) return;

  const data = JSON.parse(document.getElementById('reports-page-data')?.textContent || '{}');
  const csrfToken = data.csrfToken || '';
  const filterForm = document.getElementById('reports-filter-form');
  const csvLink = document.getElementById('report-export-csv');
  const pdfLink = document.getElementById('report-export-pdf');
  const selectionType = document.getElementById('report-selection-type');
  const selectionRange = document.getElementById('report-selection-range');
  const selectionGroup = document.getElementById('report-selection-group');
  const templateList = document.getElementById('reports-template-list');
  const auditForm = document.getElementById('audit-filter-form');
  const auditBody = document.getElementById('audit-table-body');
  const dossierForm = document.getElementById('animal-dossier-form');
  const canViewAuditTrail = Boolean(data.canViewAuditTrail);
  hydrateReportFilters(filterForm, new URLSearchParams(window.location.search));
  let currentQuery = new URLSearchParams(new FormData(filterForm));
  syncReportTargets();

  document.querySelectorAll('[data-report-type]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-report-type]').forEach((node) => {
        node.classList.toggle('is-active', node === button);
        node.setAttribute('aria-pressed', node === button ? 'true' : 'false');
      });
      filterForm.elements.report_type.value = button.dataset.reportType || 'intake';
      syncReportTargets();
    });
  });

  filterForm.addEventListener('submit', (event) => {
    event.preventDefault();
    currentQuery = new URLSearchParams(new FormData(filterForm));
    window.CatarmanApp?.navigate?.(buildViewerUrl(currentQuery)) || (window.location.href = buildViewerUrl(currentQuery));
  });

  ['start_date', 'end_date', 'group_by'].forEach((fieldName) => {
    filterForm.elements[fieldName]?.addEventListener('change', () => {
      syncReportTargets();
    });
  });

  document.getElementById('report-save-template')?.addEventListener('click', async () => {
    const name = window.prompt('Template name');
    if (!name) return;

    const configuration = Object.fromEntries(new FormData(filterForm).entries());
    const response = await fetch('/api/reports/templates', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        name,
        report_type: configuration.report_type,
        configuration
      })
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Template save failed', result?.error?.message || 'Request failed.');
      return;
    }

    window.toast?.success('Template saved', result.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  });

  templateList?.querySelectorAll('[data-template]').forEach((button) => {
    button.addEventListener('click', () => {
      const template = JSON.parse(button.dataset.template || '{}');
      const config = template.configuration || {};
      filterForm.elements.report_type.value = normalizeReportType(config.report_type || template.report_type || 'intake');
      document.querySelectorAll('[data-report-type]').forEach((node) => {
        const isActive = node.dataset.reportType === filterForm.elements.report_type.value;
        node.classList.toggle('is-active', isActive);
        node.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
      if (config.start_date) filterForm.elements.start_date.value = config.start_date;
      if (config.end_date) filterForm.elements.end_date.value = config.end_date;
      if (config.group_by) filterForm.elements.group_by.value = normalizeGroupBy(config.group_by);
      syncReportTargets();
    });
  });

  auditForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    await loadAudit();
  });

  dossierForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const animalId = dossierForm.elements.animal_id.value;
    if (!animalId) return;
    window.location.href = '/api/reports/animals/' + animalId + '/dossier';
  });

  if (canViewAuditTrail) {
    loadAudit();
  }

  async function loadAudit() {
    if (!auditForm || !auditBody) return;

    auditBody.setAttribute('aria-busy', 'true');
    const params = new URLSearchParams(new FormData(auditForm));
    const response = await fetch('/api/reports/audit-trail?' + params.toString(), {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      auditBody.innerHTML = '<tr><td colspan="5"><div class="notification-empty">Unable to load audit trail.</div></td></tr>';
      auditBody.setAttribute('aria-busy', 'false');
      return;
    }

    const items = Array.isArray(result.data) ? result.data : [];
    if (items.length === 0) {
      auditBody.innerHTML = '<tr><td colspan="5"><div class="notification-empty">No audit records matched the filters.</div></td></tr>';
      auditBody.setAttribute('aria-busy', 'false');
      return;
    }

    auditBody.innerHTML = '';
    items.forEach((item) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${escapeHtml(item.created_at || '')}</td>
        <td>${escapeHtml(item.module || '')}</td>
        <td>${escapeHtml(item.action || '')}</td>
        <td>${escapeHtml(item.user_name || 'System')}</td>
        <td>${escapeHtml(item.record_table || '')} #${escapeHtml(item.record_id || '')}</td>
      `;
      auditBody.appendChild(row);
    });
    auditBody.setAttribute('aria-busy', 'false');
  }

  function syncReportTargets() {
    currentQuery = new URLSearchParams(new FormData(filterForm));
    csvLink.href = '/api/reports/export/csv?' + currentQuery.toString();
    pdfLink.href = '/api/reports/export/pdf?' + currentQuery.toString();
    selectionType.textContent = formatReportType(filterForm.elements.report_type.value);
    selectionRange.textContent = 'Range: ' + (filterForm.elements.start_date.value || 'Not set') + ' to ' + (filterForm.elements.end_date.value || 'Not set');
    selectionGroup.textContent = 'Group: ' + (filterForm.elements.group_by.value || 'month');
  }
}

function bindReportViewer() {
  const page = document.getElementById('report-viewer-page');
  if (!page) return;

  const titleNode = document.getElementById('report-viewer-title');
  const descriptionNode = document.getElementById('report-viewer-description');
  const filtersNode = document.getElementById('report-viewer-filters');
  const emptyNode = document.getElementById('report-viewer-empty');
  const frame = document.getElementById('report-viewer-frame');
  const backLink = document.getElementById('report-viewer-back');
  const csvLink = document.getElementById('report-viewer-export-csv');
  const openPdfLink = document.getElementById('report-viewer-open-pdf');
  const downloadPdfLink = document.getElementById('report-viewer-download-pdf');
  const query = new URLSearchParams(window.location.search);
  const reportType = query.get('report_type');
  const reportsUrl = '/reports' + (query.toString() ? '?' + query.toString() : '');

  backLink.href = reportsUrl;
  if (!reportType) {
    csvLink.href = reportsUrl;
    openPdfLink.href = reportsUrl;
    downloadPdfLink.href = reportsUrl;
    return;
  }

  const csvUrl = '/api/reports/export/csv?' + query.toString();
  const pdfDownloadUrl = '/api/reports/export/pdf?' + query.toString();
  const pdfPreviewQuery = new URLSearchParams(query);
  pdfPreviewQuery.set('disposition', 'inline');
  const pdfPreviewUrl = '/api/reports/export/pdf?' + pdfPreviewQuery.toString();

  csvLink.href = csvUrl;
  openPdfLink.href = pdfPreviewUrl;
  downloadPdfLink.href = pdfDownloadUrl;
  filtersNode.innerHTML = [
    `Report: ${formatReportType(reportType)}`,
    `Start: ${query.get('start_date') || 'Not set'}`,
    `End: ${query.get('end_date') || 'Not set'}`,
    `Group: ${query.get('group_by') || 'month'}`
  ].map((item) => `<span class="text-muted">${escapeHtml(item)}</span>`).join('');
  titleNode.textContent = `${formatReportType(reportType)} PDF Preview`;
  descriptionNode.textContent = 'This viewer embeds the same PDF document that the export action downloads.';
  emptyNode.hidden = true;
  frame.hidden = false;
  frame.src = pdfPreviewUrl;
  frame.addEventListener('error', () => {
    emptyNode.hidden = false;
    emptyNode.textContent = 'Unable to load the PDF preview in this browser. Use Open PDF or Download PDF instead.';
    frame.hidden = true;
  }, { once: true });
}

function buildViewerUrl(query) {
  return '/reports/viewer?' + query.toString();
}

function normalizeReportType(value) {
  const normalized = String(value || '').trim().toLowerCase();

  return {
    animal_intake: 'intake',
    adoption: 'adoptions'
  }[normalized] || normalized || 'intake';
}

function normalizeGroupBy(value) {
  const normalized = String(value || '').trim().toLowerCase();

  return ['day', 'week', 'month', 'quarter', 'year'].includes(normalized)
    ? normalized
    : 'month';
}

function hydrateReportFilters(form, query) {
  if (!form) return;

  const reportType = query.get('report_type');
  if (reportType) {
    form.elements.report_type.value = normalizeReportType(reportType);
  }

  if (query.get('start_date')) {
    form.elements.start_date.value = query.get('start_date');
  }

  if (query.get('end_date')) {
    form.elements.end_date.value = query.get('end_date');
  }

  if (query.get('group_by')) {
    form.elements.group_by.value = normalizeGroupBy(query.get('group_by'));
  }

  document.querySelectorAll('[data-report-type]').forEach((node) => {
    const isActive = node.dataset.reportType === form.elements.report_type.value;
    node.classList.toggle('is-active', isActive);
    node.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
}

function formatReportType(value) {
  return String(value)
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
