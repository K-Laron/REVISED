(function (ns) {
  function renderPhotoPreview(preview, files) {
    if (!preview) {
      return;
    }

    preview.replaceChildren();
    Array.from(files || []).forEach((file) => {
      const reader = new FileReader();
      reader.onload = () => {
        const image = document.createElement('img');
        image.src = reader.result;
        preview.appendChild(image);
      };
      reader.readAsDataURL(file);
    });
  }

  function createAnimalPhotoNode(photo, altText) {
    const image = document.createElement('img');
    const path = String(photo.file_path || '');
    image.src = path.startsWith('/') ? path : '/' + path;
    image.alt = altText;
    return image;
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
        file_path: String(photo.file_path || ''),
        is_primary: index === 0 ? 1 : Number(photo.is_primary || 0),
      }))
      .filter((photo) => photo.id > 0 && photo.file_path !== '');
  }

  function createAnimalPhotoCard(photo, index, total) {
    const isPrimary = Number(photo.is_primary || 0) === 1;
    const card = document.createElement('article');
    card.className = 'animal-photo-card';
    card.dataset.animalPhotoItem = '';
    card.dataset.photoId = String(photo.id);
    card.dataset.filePath = String(photo.file_path);
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
    if (!animalId) {
      return null;
    }

    return document.querySelector('[data-animal-photo-collection][data-animal-id="' + animalId + '"]');
  }

  function readAnimalPhotoCollectionPhotos(collection) {
    const grid = collection?.querySelector('[data-animal-photo-grid]');
    if (!grid) {
      return [];
    }

    return normalizeAnimalPhotos(
      Array.from(grid.querySelectorAll('[data-animal-photo-item]')).map((node) => ({
        id: Number(node.dataset.photoId || 0),
        file_path: node.dataset.filePath || '',
        is_primary: Number(node.dataset.isPrimary || 0),
      }))
    );
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
    ids.splice(ids.indexOf(targetPhotoId), 0, draggedPhotoId);
    return ids;
  }

  function photoCollectionCsrfToken(collection) {
    return collection?.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
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

  function syncAnimalPhotoCollection(collection, photos) {
    if (!collection) {
      return;
    }

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

  async function persistAnimalPhotoOrder(collection, photoIds) {
    const animalId = collection.dataset.animalId;
    const { data: result } = await ns.apiRequest('/api/animals/' + animalId + '/photos/reorder', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      csrfToken: photoCollectionCsrfToken(collection),
      body: JSON.stringify({ photo_ids: photoIds })
    });

    if (result.error) {
      window.toast?.error('Photo reorder failed', ns.extractError(result));
      return false;
    }

    syncAnimalPhotoCollection(collection, Array.isArray(result.data) ? result.data : []);
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

    collection.dataset.photoBusy = 'true';
    setAnimalPhotoActionState(collection, true);

    try {
      if (action === 'delete') {
        const { data: result } = await ns.apiRequest('/api/animals/' + animalId + '/photos/' + photoId, {
          method: 'DELETE',
          csrfToken: photoCollectionCsrfToken(collection)
        });

        if (result.error) {
          window.toast?.error('Photo delete failed', ns.extractError(result));
          return;
        }

        const nextPhotos = readAnimalPhotoCollectionPhotos(collection).filter((photo) => Number(photo.id || 0) !== photoId);
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
      console.error(error);
      window.toast?.error('Photo update failed', 'Unexpected error while updating the photo gallery.');
    } finally {
      clearAnimalPhotoDragState(collection);
      delete collection.dataset.photoBusy;
      setAnimalPhotoActionState(collection, false);
    }
  }

  function handleAnimalPhotoDragStart(collection, card, event) {
    if (collection.dataset.photoBusy === 'true') {
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
      console.error(error);
      window.toast?.error('Photo update failed', 'Unexpected error while updating the photo gallery.');
    } finally {
      clearAnimalPhotoDragState(collection);
      delete collection.dataset.photoBusy;
      setAnimalPhotoActionState(collection, false);
    }
  }

  function bindAnimalPhotoCollection(collection) {
    if (!collection) {
      return;
    }

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
      card.addEventListener('drop', (event) => {
        handleAnimalPhotoDrop(collection, card, event).catch((error) => {
          console.error(error);
          window.toast?.error('Photo update failed', 'Unexpected error while updating the photo gallery.');
        });
      });
      card.addEventListener('dragend', () => clearAnimalPhotoDragState(collection));
    });
  }

  function bindAnimalPhotoCollections(root) {
    root.querySelectorAll('[data-animal-photo-collection]').forEach((collection) => {
      bindAnimalPhotoCollection(collection);
    });
  }

  function createPhotoUploadSubmitHandler(options) {
    return async function handlePhotoUploadSubmit(event) {
      event?.preventDefault?.();

      const { animalId, csrfToken, formFactory, onSuccess } = options;
      const { data: result } = await ns.apiRequest('/api/animals/' + animalId + '/photos', {
        method: 'POST',
        csrfToken,
        body: formFactory()
      });

      if (result.error) {
        window.toast?.error('Photo upload failed', ns.extractError(result));
        return;
      }

      await onSuccess?.(result);
      window.toast?.success('Photos uploaded', result.message);
    };
  }

  function bindPhotoUpload(root) {
    root.querySelectorAll('.animal-photo-upload-form').forEach((form) => {
      if (form.dataset.photoUploadBound === 'true') {
        return;
      }

      form.dataset.photoUploadBound = 'true';
      const photoInput = form.querySelector('[data-photo-upload-input]');
      const preview = form.querySelector('[data-photo-upload-preview]');
      const collection = findAnimalPhotoCollection(form.dataset.animalId);
      const handler = createPhotoUploadSubmitHandler({
        animalId: form.dataset.animalId,
        csrfToken: form.querySelector('input[name="_token"]')?.value || '',
        formFactory: () => new FormData(form),
        onSuccess: async (result) => {
          if (!collection) {
            window.CatarmanApp?.reload?.() || window.location.reload();
            return;
          }

          syncAnimalPhotoCollection(collection, Array.isArray(result.data) ? result.data : []);
          if (photoInput) {
            photoInput.value = '';
          }
          preview?.replaceChildren();
        },
      });

      photoInput?.addEventListener('change', () => {
        renderPhotoPreview(preview, photoInput.files);
      });

      form.addEventListener('submit', (event) => {
        handler(event).catch((error) => {
          console.error(error);
          window.toast?.error('Photo upload failed', 'Unexpected error while uploading photos.');
        });
      });
    });
  }

  ns.renderPhotoPreview = renderPhotoPreview;
  ns.syncAnimalPhotoCollection = syncAnimalPhotoCollection;
  ns.bindAnimalPhotoCollection = bindAnimalPhotoCollection;
  ns.bindAnimalPhotoCollections = bindAnimalPhotoCollections;
  ns.bindPhotoUpload = bindPhotoUpload;
  ns.createPhotoUploadSubmitHandler = createPhotoUploadSubmitHandler;

  ns.registerInitializer(function initializePhotoUpload(root) {
    bindAnimalPhotoCollections(root);
    bindPhotoUpload(root);
  });
})(window.CatarmanAnimals);
