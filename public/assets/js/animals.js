function apiRequest(url, options = {}) {
  return window.CatarmanApi.request(url, options);
}

function extractError(payload) {
  return window.CatarmanApi.extractError(payload);
}

function escapeHtml(value) {
  return window.CatarmanDom.escapeHtml(value);
}

document.addEventListener('DOMContentLoaded', () => {
  bindAnimalList();
  bindAnimalForm();
  bindAnimalTabs();
  bindStatusForm();
  bindPhotoUpload();
  bindAnimalPhotoCollections();
  bindScanner();
  loadTimeline();
});

function renderPhotoPreview(preview, files) {
  if (!preview) return;

  preview.innerHTML = '';
  Array.from(files || []).forEach((file) => {
    const reader = new FileReader();
    reader.onload = () => {
      const img = document.createElement('img');
      img.src = reader.result;
      preview.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

function createAnimalPhotoNode(photo, altText) {
  const img = document.createElement('img');
  img.src = photo.file_path.startsWith('/') ? photo.file_path : '/' + photo.file_path;
  img.alt = altText;
  return img;
}

function createAnimalPhotoActionButton(action, label, photoId, disabled = false, isDanger = false) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'animal-photo-action' + (isDanger ? ' is-danger' : '');
  button.textContent = label;
  button.dataset.animalPhotoAction = action;
  button.dataset.photoId = String(photoId);
  button.disabled = disabled;
  return button;
}

function normalizeAnimalPhotos(photos) {
  return (Array.isArray(photos) ? photos : [])
    .map((photo, index) => ({
      ...photo,
      id: Number(photo.id || 0),
      is_primary: index === 0 ? 1 : 0
    }))
    .filter((photo) => photo.id > 0 && typeof photo.file_path === 'string' && photo.file_path !== '');
}

function createAnimalPhotoCard(photo, index, total) {
  const isPrimary = Number(photo.is_primary || 0) === 1;
  const card = document.createElement('article');
  card.className = 'animal-photo-card';
  card.dataset.animalPhotoItem = '';
  card.dataset.photoId = String(photo.id ?? '');
  card.dataset.filePath = String(photo.file_path ?? '');
  card.dataset.isPrimary = isPrimary ? '1' : '0';
  card.draggable = true;

  card.appendChild(createAnimalPhotoNode(photo, 'Animal thumbnail'));

  const meta = document.createElement('div');
  meta.className = 'animal-photo-card-meta';

  const badge = document.createElement('span');
  badge.className = 'animal-photo-badge' + (isPrimary ? ' is-primary' : '');
  badge.textContent = isPrimary ? 'Primary' : 'Gallery';
  meta.appendChild(badge);

  const dragHandle = document.createElement('span');
  dragHandle.className = 'animal-photo-drag-handle';
  dragHandle.textContent = 'Drag to reorder';
  meta.appendChild(dragHandle);

  card.appendChild(meta);

  const actions = document.createElement('div');
  actions.className = 'animal-photo-card-actions';
  actions.appendChild(createAnimalPhotoActionButton('make-primary', 'Primary', photo.id, isPrimary));
  actions.appendChild(createAnimalPhotoActionButton('move-left', 'Left', photo.id, index === 0));
  actions.appendChild(createAnimalPhotoActionButton('move-right', 'Right', photo.id, index === total - 1));
  actions.appendChild(createAnimalPhotoActionButton('delete', 'Delete', photo.id, false, true));
  card.appendChild(actions);

  return card;
}

function findAnimalPhotoCollection(animalId) {
  if (!animalId) return null;
  return document.querySelector('[data-animal-photo-collection][data-animal-id="' + animalId + '"]');
}

function syncAnimalPhotoCollection(collection, photos) {
  if (!collection) return;

  const normalizedPhotos = normalizeAnimalPhotos(photos);
  const hasPhotos = normalizedPhotos.length > 0;
  const stage = collection.querySelector('[data-animal-photo-stage]');
  const grid = collection.querySelector('[data-animal-photo-grid]');
  const emptyState = collection.querySelector('[data-animal-photo-empty]');
  const heading = collection.querySelector('[data-animal-photo-heading]');

  if (stage) {
    if (hasPhotos) {
      stage.replaceChildren(createAnimalPhotoNode(normalizedPhotos[0], 'Animal photo'));
    } else {
      const emptyNode = document.createElement('div');
      emptyNode.className = 'animal-photo-empty';
      emptyNode.textContent = 'No photos uploaded';
      stage.replaceChildren(emptyNode);
    }
  }

  if (grid) {
    grid.replaceChildren(...normalizedPhotos.map((photo, index) => createAnimalPhotoCard(photo, index, normalizedPhotos.length)));
    grid.hidden = !hasPhotos;
  }

  if (emptyState) {
    emptyState.hidden = hasPhotos;
  }

  if (heading) {
    heading.hidden = !hasPhotos;
  }

  bindAnimalPhotoCollection(collection);
}

function readAnimalPhotoCollectionPhotos(collection) {
  const grid = collection.querySelector('[data-animal-photo-grid]');
  if (!grid) {
    return [];
  }

  return normalizeAnimalPhotos(Array.from(grid.querySelectorAll('[data-animal-photo-item]')).map((node) => ({
    id: Number(node.dataset.photoId || 0),
    file_path: node.dataset.filePath || '',
    is_primary: Number(node.dataset.isPrimary || 0),
  })));
}

function buildAnimalPhotoOrder(collection) {
  return readAnimalPhotoCollectionPhotos(collection)
    .map((photo) => Number(photo.id || 0))
    .filter((photoId) => photoId > 0);
}

function reorderAnimalPhotoIds(photoIds, photoId, action) {
  const ids = [...photoIds];
  const currentIndex = ids.indexOf(photoId);

  if (currentIndex === -1) {
    return ids;
  }

  if (action === 'make-primary') {
    ids.splice(currentIndex, 1);
    ids.unshift(photoId);
    return ids;
  }

  const targetIndex = action === 'move-left' ? currentIndex - 1 : currentIndex + 1;
  if (targetIndex < 0 || targetIndex >= ids.length) {
    return ids;
  }

  [ids[currentIndex], ids[targetIndex]] = [ids[targetIndex], ids[currentIndex]];
  return ids;
}

function moveAnimalPhotoBeforeTarget(photoIds, draggedPhotoId, targetPhotoId) {
  const ids = [...photoIds];
  const sourceIndex = ids.indexOf(draggedPhotoId);
  const targetIndex = ids.indexOf(targetPhotoId);

  if (sourceIndex === -1 || targetIndex === -1 || sourceIndex === targetIndex) {
    return ids;
  }

  ids.splice(sourceIndex, 1);
  const nextTargetIndex = ids.indexOf(targetPhotoId);
  ids.splice(nextTargetIndex, 0, draggedPhotoId);
  return ids;
}

function photoCollectionCsrfToken(collection) {
  return collection.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function clearAnimalPhotoDragState(collection) {
  delete collection.dataset.dragPhotoId;
  collection.querySelectorAll('[data-animal-photo-item]').forEach((card) => {
    card.classList.remove('is-dragging');
    card.classList.remove('is-drop-target');
  });
}

function setAnimalPhotoActionState(collection, isBusy) {
  collection.querySelectorAll('[data-animal-photo-action]').forEach((button) => {
    const initiallyDisabled = button.dataset.initiallyDisabled === 'true';
    button.disabled = isBusy || initiallyDisabled;
  });

  collection.querySelectorAll('[data-animal-photo-item]').forEach((card) => {
    card.draggable = !isBusy;
  });
}

async function persistAnimalPhotoOrder(collection, photoIds) {
  const animalId = collection.dataset.animalId;
  const csrfToken = photoCollectionCsrfToken(collection);
  const { data: result } = await apiRequest('/api/animals/' + animalId + '/photos/reorder', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json'
    },
    csrfToken,
    body: JSON.stringify({ photo_ids: photoIds })
  });

  if (result.error) {
    window.toast?.error('Photo reorder failed', extractError(result));
    return false;
  }

  const photos = Array.isArray(result.data) ? result.data : [];
  syncAnimalPhotoCollection(collection, photos);
  window.toast?.success('Photos reordered', result.message);
  return true;
}

async function handleAnimalPhotoAction(collection, button) {
  if (collection.dataset.photoBusy === 'true') {
    return;
  }

  const action = button.dataset.animalPhotoAction;
  const photoId = Number(button.dataset.photoId || 0);
  const animalId = collection.dataset.animalId;
  const csrfToken = photoCollectionCsrfToken(collection);

  collection.dataset.photoBusy = 'true';
  setAnimalPhotoActionState(collection, true);

  try {
    if (action === 'delete') {
      const { data: result } = await apiRequest('/api/animals/' + animalId + '/photos/' + photoId, {
        method: 'DELETE',
        csrfToken
      });

      if (result.error) {
        window.toast?.error('Photo delete failed', extractError(result));
        return;
      }

      const currentPhotos = readAnimalPhotoCollectionPhotos(collection);
      const nextPhotos = currentPhotos.filter((photo) => Number(photo.id || 0) !== photoId);
      syncAnimalPhotoCollection(collection, nextPhotos);
      window.toast?.success('Photo deleted', result.message);
      return;
    }

    const currentOrder = buildAnimalPhotoOrder(collection);
    const nextOrder = reorderAnimalPhotoIds(currentOrder, photoId, action);

    if (currentOrder.join(',') === nextOrder.join(',')) {
      return;
    }

    await persistAnimalPhotoOrder(collection, nextOrder);
  } catch (error) {
    window.toast?.error('Photo update failed', 'Unexpected error while updating the photo gallery.');
  } finally {
    clearAnimalPhotoDragState(collection);
    delete collection.dataset.photoBusy;
    setAnimalPhotoActionState(collection, false);
  }
}

function handleAnimalPhotoDragStart(collection, card, event) {
  if (!collection || collection.dataset.photoBusy === 'true') {
    event.preventDefault();
    return;
  }

  clearAnimalPhotoDragState(collection);
  collection.dataset.dragPhotoId = card.dataset.photoId || '';
  card.classList.add('is-dragging');

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', card.dataset.photoId || '');
  }
}

function handleAnimalPhotoDragOver(collection, card, event) {
  const draggedPhotoId = Number(collection.dataset.dragPhotoId || 0);
  const targetPhotoId = Number(card.dataset.photoId || 0);
  if (!draggedPhotoId || !targetPhotoId || draggedPhotoId === targetPhotoId) {
    return;
  }

  event.preventDefault();
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }

  collection.querySelectorAll('[data-animal-photo-item]').forEach((item) => {
    item.classList.toggle('is-drop-target', item === card);
  });
}

async function handleAnimalPhotoDrop(collection, card, event) {
  event.preventDefault();

  if (collection.dataset.photoBusy === 'true') {
    return;
  }

  const draggedPhotoId = Number(collection.dataset.dragPhotoId || 0);
  const targetPhotoId = Number(card.dataset.photoId || 0);
  if (!draggedPhotoId || !targetPhotoId || draggedPhotoId === targetPhotoId) {
    return;
  }

  const currentOrder = buildAnimalPhotoOrder(collection);
  const nextOrder = moveAnimalPhotoBeforeTarget(currentOrder, draggedPhotoId, targetPhotoId);
  if (currentOrder.join(',') === nextOrder.join(',')) {
    return;
  }

  collection.dataset.photoBusy = 'true';
  setAnimalPhotoActionState(collection, true);

  try {
    await persistAnimalPhotoOrder(collection, nextOrder);
  } catch (error) {
    window.toast?.error('Photo update failed', 'Unexpected error while updating the photo gallery.');
  } finally {
    clearAnimalPhotoDragState(collection);
    delete collection.dataset.photoBusy;
    setAnimalPhotoActionState(collection, false);
  }
}

function bindAnimalPhotoCollection(collection) {
  if (!collection) return;

  collection.querySelectorAll('[data-animal-photo-action]').forEach((button) => {
    if (button.dataset.photoActionBound === 'true') {
      return;
    }

    button.dataset.photoActionBound = 'true';
    button.dataset.initiallyDisabled = button.disabled ? 'true' : 'false';
    button.addEventListener('click', () => handleAnimalPhotoAction(collection, button));
  });

  collection.querySelectorAll('[data-animal-photo-item]').forEach((card) => {
    if (card.dataset.photoDragBound === 'true') {
      return;
    }

    card.dataset.photoDragBound = 'true';
    card.addEventListener('dragstart', (event) => handleAnimalPhotoDragStart(collection, card, event));
    card.addEventListener('dragover', (event) => handleAnimalPhotoDragOver(collection, card, event));
    card.addEventListener('dragleave', () => {
      card.classList.remove('is-drop-target');
    });
    card.addEventListener('drop', (event) => handleAnimalPhotoDrop(collection, card, event));
    card.addEventListener('dragend', () => clearAnimalPhotoDragState(collection));
  });
}

function bindAnimalPhotoCollections() {
  document.querySelectorAll('[data-animal-photo-collection]').forEach((collection) => {
    bindAnimalPhotoCollection(collection);
  });
}

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
    const { data: result } = await apiRequest('/api/animals?' + params.toString());
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
    renderPhotoPreview(preview, photoInput.files);
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const mode = form.dataset.mode;
    const token = form.querySelector('input[name="_token"]').value;

    try {
      let result;
      if (mode === 'create') {
        ({ data: result } = await apiRequest('/api/animals', {
          method: 'POST',
          csrfToken: token,
          body: new FormData(form)
        }));
      } else {
        const params = new URLSearchParams(new FormData(form));
        ({ data: result } = await apiRequest('/api/animals/' + form.dataset.animalId, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          csrfToken: token,
          body: params.toString()
        }));
      }

      if (!result || result.error) {
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
    const { data: result } = await apiRequest('/api/animals/' + form.dataset.animalId + '/status', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      csrfToken: token,
      body: new URLSearchParams(new FormData(form)).toString()
    });
    if (result.error) {
      window.toast?.error('Status update failed', extractError(result));
      return;
    }
    window.toast?.success('Status updated', result.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  });
}

function bindPhotoUpload() {
  const forms = document.querySelectorAll('.animal-photo-upload-form');
  if (!forms.length) return;

  forms.forEach((form) => {
    const photoInput = form.querySelector('[data-photo-upload-input]');
    const preview = form.querySelector('[data-photo-upload-preview]');

    photoInput?.addEventListener('change', () => {
      renderPhotoPreview(preview, photoInput.files);
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const token = form.querySelector('input[name="_token"]').value;
      const { data: result } = await apiRequest('/api/animals/' + form.dataset.animalId + '/photos', {
        method: 'POST',
        csrfToken: token,
        body: new FormData(form)
      });
      if (result.error) {
        window.toast?.error('Photo upload failed', extractError(result));
        return;
      }

      const photos = Array.isArray(result.data) ? result.data : [];
      const collection = findAnimalPhotoCollection(form.dataset.animalId);
      const supportsInlineUpdate = !!collection;

      if (supportsInlineUpdate) {
        syncAnimalPhotoCollection(collection, photos);
        if (photoInput) {
          photoInput.value = '';
        }
        if (preview) {
          preview.replaceChildren();
        }
      }

      window.toast?.success('Photos uploaded', result.message);

      if (!supportsInlineUpdate) {
        window.CatarmanApp?.reload?.() || window.location.reload();
      }
    });
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
      const { ok, data: result } = await apiRequest('/api/animals/scan/' + encodeURIComponent(value));
      if (ok) {
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

  const { ok, data: result } = await apiRequest('/api/animals/' + timeline.dataset.animalId + '/timeline');
  if (!ok) {
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
