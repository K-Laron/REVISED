(function () {
  if (window.CatarmanBreadcrumbs) {
    return;
  }

  const STORAGE_PREFIX = 'catarman:breadcrumb-draft:';
  const STORAGE_TTL_MS = 15 * 60 * 1000;
  const baselineSnapshots = new WeakMap();

  function currentDraftKey() {
    return STORAGE_PREFIX + window.location.pathname + window.location.search;
  }

  function getTrackedForms() {
    return Array.from(document.querySelectorAll('.content-area form')).filter((form) => form instanceof HTMLFormElement && form.dataset.breadcrumbDraft !== 'ignore');
  }

  function isTrackedControl(control) {
    if (!(control instanceof HTMLElement) || !('name' in control) || !control.name || control.disabled) {
      return false;
    }

    const tag = control.tagName.toLowerCase();
    if (!['input', 'textarea', 'select'].includes(tag)) {
      return false;
    }

    const type = (control.getAttribute('type') || '').toLowerCase();
    if (['button', 'submit', 'reset', 'image', 'file'].includes(type)) {
      return false;
    }

    if (type === 'hidden' && control.name === '_token') {
      return false;
    }

    return true;
  }

  function serializeControl(control, index) {
    if (!isTrackedControl(control)) {
      return null;
    }

    const tag = control.tagName.toLowerCase();
    const type = (control.getAttribute('type') || '').toLowerCase();

    if ((type === 'checkbox' || type === 'radio') && 'checked' in control) {
      return {
        index,
        name: control.name,
        kind: 'checked',
        value: control.value,
        checked: control.checked
      };
    }

    if (tag === 'select' && control.multiple) {
      return {
        index,
        name: control.name,
        kind: 'select-multiple',
        values: Array.from(control.selectedOptions).map((option) => option.value)
      };
    }

    return {
      index,
      name: control.name,
      kind: 'value',
      value: control.value
    };
  }

  function serializeForm(form) {
    return Array.from(form.elements)
      .map((control, index) => serializeControl(control, index))
      .filter(Boolean);
  }

  function snapshotForm(form) {
    return JSON.stringify(serializeForm(form));
  }

  function rememberFormBaselines() {
    getTrackedForms().forEach((form) => {
      baselineSnapshots.set(form, snapshotForm(form));
    });
  }

  function isDirtyForm(form) {
    return baselineSnapshots.get(form) !== snapshotForm(form);
  }

  function clearDraft() {
    try {
      sessionStorage.removeItem(currentDraftKey());
    } catch (error) {
      console.error(error);
    }
  }

  function saveDraft() {
    const dirtyForms = getTrackedForms().filter((form) => isDirtyForm(form));
    if (dirtyForms.length === 0) {
      clearDraft();
      return false;
    }

    const payload = {
      savedAt: Date.now(),
      forms: dirtyForms.map((form, formIndex) => ({
        formIndex,
        id: form.id || null,
        controls: serializeForm(form)
      }))
    };

    try {
      sessionStorage.setItem(currentDraftKey(), JSON.stringify(payload));
      return true;
    } catch (error) {
      console.error(error);
      return false;
    }
  }

  function findForm(target) {
    if (target.id) {
      const byId = document.getElementById(target.id);
      if (byId instanceof HTMLFormElement) {
        return byId;
      }
    }

    return getTrackedForms()[target.formIndex] || null;
  }

  function restoreControl(form, controlState) {
    const control = form.elements[controlState.index];
    if (!control || control.name !== controlState.name) {
      return;
    }

    if (controlState.kind === 'checked' && 'checked' in control && control.value === controlState.value) {
      control.checked = Boolean(controlState.checked);
      return;
    }

    if (controlState.kind === 'select-multiple' && control instanceof HTMLSelectElement && control.multiple) {
      const selected = new Set(controlState.values || []);
      Array.from(control.options).forEach((option) => {
        option.selected = selected.has(option.value);
      });
      return;
    }

    if ('value' in control && controlState.kind === 'value') {
      control.value = controlState.value ?? '';
    }
  }

  function restoreDraft() {
    let rawPayload = null;

    try {
      rawPayload = sessionStorage.getItem(currentDraftKey());
    } catch (error) {
      console.error(error);
      rememberFormBaselines();
      return;
    }

    if (!rawPayload) {
      rememberFormBaselines();
      return;
    }

    let payload = null;

    try {
      payload = JSON.parse(rawPayload);
    } catch (error) {
      console.error(error);
      clearDraft();
      rememberFormBaselines();
      return;
    }

    if (!payload || Date.now() - Number(payload.savedAt || 0) > STORAGE_TTL_MS) {
      clearDraft();
      rememberFormBaselines();
      return;
    }

    (payload.forms || []).forEach((formState) => {
      const form = findForm(formState);
      if (!form) {
        return;
      }

      (formState.controls || []).forEach((controlState) => restoreControl(form, controlState));
    });

    clearDraft();
    rememberFormBaselines();

    if (payload.forms?.length && window.toast?.info) {
      window.toast.info('Draft restored', 'Your in-progress form input was restored after breadcrumb navigation.');
    }
  }

  function isModifiedClick(event) {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
  }

  function handleBreadcrumbClick(event) {
    if (event.defaultPrevented || isModifiedClick(event)) {
      return;
    }

    const anchor = event.target.closest('[data-breadcrumb-link="true"][href]');
    if (!anchor) {
      return;
    }

    if (anchor.hasAttribute('download') || (anchor.getAttribute('target') || '').toLowerCase() === '_blank') {
      return;
    }

    saveDraft();
  }

  function bindDocumentEvents() {
    if (document.body.dataset.breadcrumbDraftBound === 'true') {
      return;
    }

    document.body.dataset.breadcrumbDraftBound = 'true';
    document.addEventListener('click', handleBreadcrumbClick, true);
  }

  function sync() {
    restoreDraft();
  }

  bindDocumentEvents();

  if (document.readyState === 'complete') {
    sync();
  } else {
    window.addEventListener('load', sync, { once: true });
  }

  window.CatarmanBreadcrumbs = {
    clearDraft,
    saveDraft,
    sync
  };
})();
