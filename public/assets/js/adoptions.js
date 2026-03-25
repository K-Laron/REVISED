document.addEventListener('DOMContentLoaded', () => {
  bindAdoptionIndex();
  bindAdoptionShow();
});

function bindAdoptionIndex() {
  const page = document.getElementById('adoption-index-page');
  if (!page) return;

  const raw = document.getElementById('adoption-page-data')?.textContent || '{}';
  const pageData = JSON.parse(raw);
  const filterForm = document.getElementById('adoption-filter-form');
  const seminarForm = document.getElementById('adoption-seminar-form');
  const board = document.getElementById('adoption-board');
  const statGrid = document.getElementById('adoption-stat-grid');
  const seminarList = document.getElementById('adoption-seminar-list');
  const seminarCount = document.getElementById('adoption-seminar-count');
  const summary = document.getElementById('adoption-pipeline-summary');

  filterForm.addEventListener('submit', (event) => {
    event.preventDefault();
    loadApplications();
  });

  filterForm.addEventListener('change', () => loadApplications());
  filterForm.addEventListener('reset', () => setTimeout(() => loadApplications(), 0));

  seminarForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await fetch('/api/adoptions/seminars', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': pageData.csrfToken
      },
      body: new URLSearchParams(new FormData(seminarForm)).toString()
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Seminar create failed', extractError(result));
      return;
    }

    seminarForm.reset();
    window.toast?.success('Seminar created', result.message);
    renderSeminars(result.data || []);
    loadStats();
  });

  loadStats();
  loadApplications();
  loadSeminars();

  async function loadStats() {
    const response = await fetch('/api/adoptions/pipeline-stats', {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Pipeline load failed', extractError(result));
      return;
    }

    const stats = result.data || {};
    const items = Array.isArray(stats.statuses) ? stats.statuses : [];
    statGrid.innerHTML = '';

    items.forEach((item) => {
      const card = document.createElement('article');
      card.className = 'card adoption-stat-card';
      card.innerHTML = `
        <span class="field-label">${escapeHtml(item.label)}</span>
        <strong>${Number(item.count || 0)}</strong>
        <small class="text-muted">${escapeHtml(item.key.replace(/_/g, ' '))}</small>
      `;
      statGrid.appendChild(card);
    });

    if (items.length === 0) {
      statGrid.innerHTML = '<div class="card adoption-empty-state">No adoption stats available yet.</div>';
    }
  }

  async function loadApplications() {
    const params = new URLSearchParams(new FormData(filterForm));
    params.set('page', '1');
    params.set('per_page', '60');

    const response = await fetch('/api/adoptions?' + params.toString(), {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Application load failed', extractError(result));
      return;
    }

    const items = Array.isArray(result.data) ? result.data : [];
    summary.textContent = `${items.length} application(s) shown`;
    renderBoard(items, pageData.statusLabels || {});
  }

  async function loadSeminars() {
    const response = await fetch('/api/adoptions/seminars', {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Seminar load failed', extractError(result));
      return;
    }

    renderSeminars(Array.isArray(result.data) ? result.data : []);
  }

  function renderBoard(items, statusLabels) {
    board.innerHTML = '';
    const groups = new Map();

    Object.keys(statusLabels).forEach((key) => groups.set(key, []));
    items.forEach((item) => {
      if (!groups.has(item.status)) groups.set(item.status, []);
      groups.get(item.status).push(item);
    });

    groups.forEach((groupItems, status) => {
      const column = document.createElement('section');
      column.className = 'adoption-board-column';
      column.innerHTML = `
        <header>
          <strong>${escapeHtml(statusLabels[status] || titleCase(status))}</strong>
          <span>${groupItems.length}</span>
        </header>
      `;

      if (groupItems.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'adoption-empty-state';
        empty.textContent = 'No applications in this stage.';
        column.appendChild(empty);
      } else {
        groupItems.forEach((item) => {
          const card = document.createElement('a');
          card.className = 'adoption-application-card';
          card.href = `/adoptions/${item.id}`;
          card.innerHTML = `
            <strong>${escapeHtml(item.application_number)}</strong>
            <span>${escapeHtml(item.adopter_name || 'Unknown adopter')}</span>
            <span class="text-muted">${escapeHtml(item.animal_name || 'No animal assigned')}</span>
            <small>${Number(item.days_in_stage || 0)} day(s) in stage</small>
          `;
          column.appendChild(card);
        });
      }

      board.appendChild(column);
    });
  }

  function renderSeminars(items) {
    seminarCount.textContent = `${items.length} listed`;
    seminarList.innerHTML = '';

    if (items.length === 0) {
      seminarList.innerHTML = '<div class="adoption-empty-state">No seminars scheduled yet.</div>';
      return;
    }

    items.forEach((item) => {
      const card = document.createElement('article');
      card.className = 'adoption-seminar-card';
      card.innerHTML = `
        <strong>${escapeHtml(item.title)}</strong>
        <span>${formatDateTime(item.scheduled_date)}${item.end_time ? ' to ' + formatDateTime(item.end_time) : ''}</span>
        <span>${escapeHtml(item.location)}</span>
        <small>${Number(item.attendee_count || 0)} / ${Number(item.capacity || 0)} attendees</small>
      `;
      seminarList.appendChild(card);
    });
  }
}

function bindAdoptionShow() {
  const page = document.getElementById('adoption-show-page');
  if (!page) return;

  const raw = document.getElementById('adoption-page-data')?.textContent || '{}';
  const pageData = JSON.parse(raw);
  const applicationId = page.dataset.applicationId;
  const csrfToken = pageData.csrfToken;
  const interviews = Array.isArray(pageData.application?.interviews) ? pageData.application.interviews : [];

  const statusForm = document.getElementById('adoption-status-form');
  const rejectForm = document.getElementById('adoption-reject-form');
  const interviewForm = document.getElementById('adoption-interview-form');
  const seminarRegisterForm = document.getElementById('adoption-seminar-register-form');
  const attendanceForm = document.getElementById('adoption-attendance-form');
  const completionForm = document.getElementById('adoption-completion-form');

  statusForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    await submitForm(`/api/adoptions/${applicationId}/status`, 'PUT', new FormData(statusForm), 'Status updated');
  });

  rejectForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    await submitForm(`/api/adoptions/${applicationId}/reject`, 'PUT', new FormData(rejectForm), 'Application rejected');
  });

  interviewForm?.elements.selected_interview?.addEventListener('change', (event) => {
    const interviewId = event.target.value;
    const match = interviews.find((item) => String(item.id) === String(interviewId));
    if (!match) {
      interviewForm.reset();
      interviewForm.elements._token.value = csrfToken;
      return;
    }

    interviewForm.elements.interview_id.value = match.id;
    interviewForm.elements.scheduled_date.value = toDateTimeLocal(match.scheduled_date);
    interviewForm.elements.interview_type.value = match.interview_type || 'in_person';
    interviewForm.elements.status.value = match.status || 'scheduled';
    interviewForm.elements.location.value = match.location || '';
    interviewForm.elements.video_call_link.value = match.video_call_link || '';
    interviewForm.elements.conducted_by.value = match.conducted_by || '';
    interviewForm.elements.overall_recommendation.value = match.overall_recommendation || '';
    interviewForm.elements.pet_care_knowledge_score.value = match.pet_care_knowledge_score || '';
    interviewForm.elements.home_assessment_notes.value = match.home_assessment_notes || '';
    interviewForm.elements.interviewer_notes.value = match.interviewer_notes || '';
  });

  interviewForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(interviewForm);
    const interviewId = formData.get('interview_id');
    const endpoint = interviewId
      ? `/api/adoptions/interviews/${interviewId}`
      : `/api/adoptions/${applicationId}/interview`;
    const method = interviewId ? 'PUT' : 'POST';

    await submitForm(endpoint, method, formData, interviewId ? 'Interview updated' : 'Interview scheduled');
  });

  seminarRegisterForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(seminarRegisterForm);
    const seminarId = formData.get('seminar_id');
    if (!seminarId) {
      window.toast?.error('Registration failed', 'Select a seminar first.');
      return;
    }

    formData.append('application_id', applicationId);
    await submitForm(`/api/adoptions/seminars/${seminarId}/attendees`, 'POST', formData, 'Application registered');
  });

  attendanceForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(attendanceForm);
    const seminarId = formData.get('seminar_id');
    if (!seminarId) {
      window.toast?.error('Attendance update failed', 'Select a registered seminar first.');
      return;
    }

    formData.append('application_id', applicationId);
    await submitForm(`/api/adoptions/seminars/${seminarId}/attendance`, 'PUT', formData, 'Attendance updated');
  });

  completionForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    await submitForm(`/api/adoptions/${applicationId}/complete`, 'POST', new FormData(completionForm), 'Adoption completed');
  });

  async function submitForm(endpoint, method, formData, successTitle) {
    const response = await fetch(endpoint, {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': csrfToken
      },
      body: new URLSearchParams(formData).toString()
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error(successTitle + ' failed', extractError(result));
      return;
    }

    window.toast?.success(successTitle, result.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  }
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

function toDateTimeLocal(value) {
  if (!value) return '';
  return String(value).replace(' ', 'T').slice(0, 16);
}

function formatDateTime(value) {
  if (!value) return 'N/A';
  return new Date(String(value).replace(' ', 'T')).toLocaleString();
}

function titleCase(value) {
  return String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
