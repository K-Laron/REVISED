document.addEventListener('DOMContentLoaded', () => {
  bindAnimalList();
  bindAnimalForm();
  bindAnimalTabs();
  bindStatusForm();
  bindPhotoUpload();
  bindScanner();
  loadTimeline();
});

function bindAnimalList() {
  const page = document.getElementById('animal-list-page');
  if (!page) return;

  const canUpdateAnimals = page.dataset.canUpdate === 'true';
  const form = document.getElementById('animal-filter-form');
  const tableBody = document.getElementById('animal-table-body');
  const cardList = document.getElementById('animal-card-list');
  const summary = document.getElementById('animal-pagination-summary');
  const controls = document.getElementById('animal-pagination-controls');
  let currentPage = 1;

  async function load(pageNumber = 1) {
    currentPage = pageNumber;
    const params = new URLSearchParams(new FormData(form));
    params.set('page', pageNumber);
    params.set('per_page', 20);
    const response = await fetch('/api/animals?' + params.toString(), {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();
    const items = Array.isArray(result.data) ? result.data : [];
    const meta = result.meta || {};

    tableBody.innerHTML = '';
    cardList.innerHTML = '';

    items.forEach((animal) => {
      const statusBadge = badgeForStatus(animal.status);
      const media = animal.primary_photo_path
        ? `<img src="/${animal.primary_photo_path}" alt="">`
        : `<span>📷</span>`;
      const breed = animal.breed_name || animal.breed_other || 'Unknown';
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>
          <div class="animal-cell">
            <div class="animal-cell-media">${media}</div>
            <div>
              <strong>${escapeHtml(animal.name || 'Unnamed Animal')}</strong><br>
              <span class="text-muted mono">${escapeHtml(animal.animal_id)}</span>
            </div>
          </div>
        </td>
        <td>${escapeHtml(animal.species)} · ${escapeHtml(breed)}</td>
        <td>${escapeHtml(animal.gender)}</td>
        <td><span class="badge ${statusBadge}">${escapeHtml(animal.status)}</span></td>
        <td>${escapeHtml(new Date(animal.intake_date).toLocaleDateString())}</td>
        <td>
          <div class="animal-actions">
            <a class="btn-secondary" href="/animals/${animal.id}">View</a>
            ${canUpdateAnimals ? `<a class="btn-secondary" href="/animals/${animal.id}/edit">Edit</a>` : ''}
            <button class="btn-secondary" type="button" data-qr-preview data-qr-src="/api/animals/${animal.id}/qr" data-qr-name="${escapeHtml(animal.name || 'Unnamed Animal')}" data-qr-code="${escapeHtml(animal.animal_id)}" data-qr-download="/api/animals/${animal.id}/qr/download">QR</button>
          </div>
        </td>
      `;
      tableBody.appendChild(row);

      const card = document.createElement('article');
      card.className = 'card animal-card';
      card.innerHTML = `
        <div class="animal-cell">
          <div class="animal-cell-media">${media}</div>
          <div>
            <strong>${escapeHtml(animal.name || 'Unnamed Animal')}</strong><br>
            <span class="text-muted mono">${escapeHtml(animal.animal_id)}</span>
          </div>
        </div>
        <div class="cluster">
          <span>${escapeHtml(animal.species)} · ${escapeHtml(breed)}</span>
          <span class="badge ${statusBadge}">${escapeHtml(animal.status)}</span>
        </div>
        <div class="animal-actions">
          <a class="btn-secondary" href="/animals/${animal.id}">View</a>
          ${canUpdateAnimals ? `<a class="btn-secondary" href="/animals/${animal.id}/edit">Edit</a>` : ''}
          <button class="btn-secondary" type="button" data-qr-preview data-qr-src="/api/animals/${animal.id}/qr" data-qr-name="${escapeHtml(animal.name || 'Unnamed Animal')}" data-qr-code="${escapeHtml(animal.animal_id)}" data-qr-download="/api/animals/${animal.id}/qr/download">QR</button>
        </div>
      `;
      cardList.appendChild(card);
    });

    const total = meta.total || 0;
    const perPage = meta.per_page || 20;
    const totalPages = meta.total_pages || 1;
    const start = total === 0 ? 0 : ((meta.page - 1) * perPage) + 1;
    const end = Math.min(total, (meta.page * perPage));
    summary.textContent = `Showing ${start}-${end} of ${total}`;

    controls.innerHTML = '';
    const previous = document.createElement('button');
    previous.className = 'btn-secondary';
    previous.type = 'button';
    previous.textContent = 'Previous';
    previous.disabled = currentPage <= 1;
    previous.addEventListener('click', () => load(currentPage - 1));
    controls.appendChild(previous);

    const next = document.createElement('button');
    next.className = 'btn-secondary';
    next.type = 'button';
    next.textContent = 'Next';
    next.disabled = currentPage >= totalPages;
    next.addEventListener('click', () => load(currentPage + 1));
    controls.appendChild(next);
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    load(1);
  });

  form.addEventListener('change', () => load(1));
  form.addEventListener('reset', () => setTimeout(() => load(1), 0));

  load();
}

function bindAnimalForm() {
  const form = document.getElementById('animal-form');
  if (!form) return;

  const photoInput = form.querySelector('[data-photo-input]');
  const preview = form.querySelector('[data-photo-preview]');
  const speciesSelect = form.querySelector('[data-breed-species]');
  const breedSelect = form.querySelector('[data-breed-select]');
  const intakeTypeSelect = form.querySelector('[data-intake-type]');
  const locationField = form.querySelector('[data-location-found-field]');
  const surrenderField = form.querySelector('[data-surrender-reason-field]');
  const broughtBySection = form.querySelector('[data-brought-by-section]');
  const authoritySection = form.querySelector('[data-authority-section]');

  function updateBreedOptions() {
    const species = speciesSelect.value;
    Array.from(breedSelect.options).forEach((option) => {
      if (!option.dataset.species) return;
      option.hidden = option.dataset.species !== species;
    });
    if (breedSelect.selectedOptions[0]?.hidden) {
      breedSelect.value = '';
    }
  }

  function updateConditionalFields() {
    const intakeType = intakeTypeSelect.value;
    const showLocationField = intakeType === 'Stray';
    const showSurrenderField = intakeType === 'Owner Surrender';
    const showBroughtBySection = ['Owner Surrender', 'Confiscated', 'Transfer'].includes(intakeType);
    const showAuthoritySection = ['Stray', 'Confiscated'].includes(intakeType);

    toggleConditionalSection(locationField, showLocationField);
    toggleConditionalSection(surrenderField, showSurrenderField);
    toggleConditionalSection(broughtBySection, showBroughtBySection);
    toggleConditionalSection(authoritySection, showAuthoritySection);
  }

  speciesSelect?.addEventListener('change', updateBreedOptions);
  intakeTypeSelect?.addEventListener('change', updateConditionalFields);
  updateBreedOptions();
  updateConditionalFields();

  photoInput?.addEventListener('change', () => {
    preview.innerHTML = '';
    Array.from(photoInput.files || []).forEach((file) => {
      const reader = new FileReader();
      reader.onload = () => {
        const img = document.createElement('img');
        img.src = reader.result;
        preview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const mode = form.dataset.mode;
    const token = form.querySelector('input[name="_token"]').value;

    try {
      let response;
      if (mode === 'create') {
        response = await fetch('/api/animals', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': token
          },
          body: new FormData(form)
        });
      } else {
        const params = new URLSearchParams(new FormData(form));
        response = await fetch('/api/animals/' + form.dataset.animalId, {
          method: 'PUT',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-TOKEN': token
          },
          body: params.toString()
        });
      }

      const result = await response.json();
      if (!response.ok) {
        window.toast?.error('Animal save failed', extractError(result));
        return;
      }

      window.toast?.success('Animal saved', result.message);
      window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
    } catch (error) {
      window.toast?.error('Animal save failed', 'Unexpected error while saving the animal.');
    }
  });
}

function toggleConditionalSection(section, isVisible) {
  if (!section) return;

  section.hidden = !isVisible;
  section.querySelectorAll('input, select, textarea, button').forEach((field) => {
    if (field.type === 'hidden') {
      return;
    }

    field.disabled = !isVisible;
  });
}

function bindAnimalTabs() {
  document.querySelectorAll('[data-tab-target]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-tab-target]').forEach((node) => {
        node.classList.remove('is-active');
        node.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.tab-panel').forEach((node) => {
        node.classList.remove('is-active');
        node.hidden = true;
      });
      button.classList.add('is-active');
      button.setAttribute('aria-selected', 'true');
      const panel = document.getElementById(button.dataset.tabTarget);
      if (!panel) return;
      panel.classList.add('is-active');
      panel.hidden = false;
    });
  });
}

function bindStatusForm() {
  const form = document.querySelector('.animal-status-form');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const token = form.querySelector('input[name="_token"]').value;
    const response = await fetch('/api/animals/' + form.dataset.animalId + '/status', {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': token
      },
      body: new URLSearchParams(new FormData(form)).toString()
    });
    const result = await response.json();
    if (!response.ok) {
      window.toast?.error('Status update failed', extractError(result));
      return;
    }
    window.toast?.success('Status updated', result.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  });
}

function bindPhotoUpload() {
  const form = document.querySelector('.animal-photo-upload-form');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const token = form.querySelector('input[name="_token"]').value;
    const response = await fetch('/api/animals/' + form.dataset.animalId + '/photos', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: new FormData(form)
    });
    const result = await response.json();
    if (!response.ok) {
      window.toast?.error('Photo upload failed', extractError(result));
      return;
    }
    window.toast?.success('Photos uploaded', result.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  });
}

function bindScanner() {
  const modal = document.getElementById('qr-scanner-modal');
  if (!modal) return;

  const openButton = document.querySelector('[data-open-scanner]');
  const closeButton = document.querySelector('[data-close-scanner]');
  const manualButton = document.getElementById('manual-qr-submit');
  const manualInput = document.getElementById('manual-qr-value');
  let scanner;

  async function resolveScan(value) {
    if (!value) return;
    try {
      const response = await fetch('/api/animals/scan/' + encodeURIComponent(value), {
        headers: { Accept: 'application/json' }
      });
      const result = await response.json();
      if (response.ok) {
        window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
        return;
      }
    } catch (error) {
      console.error(error);
    }

    const fallbackTarget = '/animals/' + encodeURIComponent(value);
    window.CatarmanApp?.navigate?.(fallbackTarget) || (window.location.href = fallbackTarget);
  }

  async function openScanner() {
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    if (window.Html5Qrcode && !scanner) {
      scanner = new Html5Qrcode('qr-reader');
      try {
        await scanner.start(
          { facingMode: 'environment' },
          { fps: 10, qrbox: 220 },
          async (decodedText) => {
            await scanner.stop();
            await resolveScan(decodedText);
          }
        );
      } catch (error) {
        document.getElementById('qr-reader').innerHTML = '<div class="animal-photo-empty">Camera unavailable. Use manual entry.</div>';
      }
    }
  }

  async function closeScanner() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    try {
      if (scanner?.isScanning) {
        await scanner.stop();
      }
    } catch (error) {
      console.error(error);
    }
  }

  openButton?.addEventListener('click', openScanner);
  closeButton?.addEventListener('click', closeScanner);
  manualButton?.addEventListener('click', () => resolveScan(manualInput.value.trim()));
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeScanner();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeScanner();
    }
  });
}

async function loadTimeline() {
  const timeline = document.getElementById('animal-timeline');
  if (!timeline) return;

  const response = await fetch('/api/animals/' + timeline.dataset.animalId + '/timeline', {
    headers: { Accept: 'application/json' }
  });
  const result = await response.json();
  if (!response.ok) {
    timeline.innerHTML = '<div class="timeline-entry">Unable to load timeline.</div>';
    return;
  }

  timeline.innerHTML = '';
  const entries = Array.isArray(result.data) ? result.data : [];

  entries.forEach((entry) => {
    const row = document.createElement('div');
    row.className = 'timeline-entry';
    row.innerHTML = `
      <strong>${escapeHtml(entry.title)}</strong>
      <span class="text-muted">${escapeHtml(entry.date)}</span>
      <p>${escapeHtml(entry.description || '')}</p>
    `;
    timeline.appendChild(row);
  });
}

function badgeForStatus(status) {
  return ({
    'Available': 'badge-success',
    'Under Medical Care': 'badge-warning',
    'In Adoption Process': 'badge-info',
    'Adopted': 'badge-success',
    'Deceased': 'badge-danger',
    'Transferred': 'badge-info',
    'Quarantine': 'badge-warning'
  })[status] || 'badge-info';
}

function extractError(result) {
  if (!result?.error) return 'Request failed.';
  if (typeof result.error.message === 'string') return result.error.message;
  return 'Request failed.';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
