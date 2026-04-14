(function (ns) {
  const DRAFT_KEY = 'catarman:adoption-draft';
  const STEP_KEY = 'catarman:adoption-step';
  const FILE_LIMIT = 5;

  function formatErrors(errors) {
    const parts = [];
    Object.values(errors || {}).forEach((messages) => {
      if (Array.isArray(messages)) {
        parts.push(...messages);
      } else if (typeof messages === 'string') {
        parts.push(messages);
      }
    });
    return parts.join(' ');
  }

  function renderApplications(applications) {
    const list = document.getElementById('portal-my-applications-list');
    if (!list) return;

    if (!Array.isArray(applications) || applications.length === 0) {
      list.innerHTML = '<p class="text-muted">No applications submitted yet.</p>';
      return;
    }

    list.innerHTML = applications.map((application) => `
      <article class="portal-status-card animate-fade-in" style="animation-delay: 0.1s">
        <div class="cluster" style="justify-content: space-between;">
          <strong style="font-family: var(--font-family-mono);">${ns.escapeHtml(application.application_number)}</strong>
          <span class="badge badge-info" style="font-size: var(--font-size-xs);">${ns.escapeHtml(application.status.replaceAll('_', ' '))}</span>
        </div>
        <div class="stack" style="gap: var(--space-1); margin-top: var(--space-2);">
          <p class="text-muted" style="font-size: var(--font-size-sm);">${application.animal_name ? `${ns.escapeHtml(application.animal_name)}${application.animal_code ? ` • ${ns.escapeHtml(application.animal_code)}` : ''}` : 'General interest application'}</p>
          <p class="portal-card-meta" style="font-size: var(--font-size-xs);">Received ${ns.escapeHtml(application.created_at)}</p>
        </div>
        ${application.rejection_reason ? `<div class="card" style="margin-top: var(--space-3); background: var(--color-bg-secondary); padding: var(--space-2) var(--space-3); border-color: var(--color-border);"><p class="text-muted" style="font-size: var(--font-size-xs); font-style: italic;">Note: ${ns.escapeHtml(application.rejection_reason)}</p></div>` : ''}
      </article>
    `).join('');
  }

  async function refreshApplications() {
    try {
        const response = await fetch('/api/adopt/my-applications', {
            headers: { 'Accept': 'application/json' }
        });
        const result = await ns.parseResponse(response);
        renderApplications(result.data);
    } catch (e) {
        console.error('Failed to refresh applications list:', e);
    }
  }

  function saveDraft(form, currentStep) {
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
      // Don't save files, tokens, or security fields
      if (key !== '_token' && !(value instanceof File) && key !== 'valid_id_path[]') {
        data[key] = value;
      }
    });
    localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
    localStorage.setItem(STEP_KEY, String(currentStep));
  }

  function loadDraft(form) {
    try {
      const saved = localStorage.getItem(DRAFT_KEY);
      if (!saved) return 1;
      const data = JSON.parse(saved);
      Object.keys(data).forEach((key) => {
        const field = form.elements[key];
        if (field) {
          if (field.type === 'checkbox') {
            field.checked = data[key] === '1';
          } else if (field.tagName === 'SELECT') {
            const opt = Array.from(field.options).find(o => o.value === data[key]);
            if (opt) field.value = data[key];
          } else {
            field.value = data[key];
          }
        }
      });
      return parseInt(localStorage.getItem(STEP_KEY) || '1', 10);
    } catch (e) {
      console.warn('Failed to load adoption draft:', e);
      return 1;
    }
  }

  function renderFilePreview(input, container) {
    if (!container) return;
    const files = Array.from(input.files);
    
    if (files.length === 0) {
      container.innerHTML = '';
      container.hidden = true;
      return;
    }

    container.hidden = false;
    container.innerHTML = `
      <div class="stack" style="gap: var(--space-2); margin-top: var(--space-3);">
        <strong style="font-size: var(--font-size-xs); text-transform: uppercase; color: var(--color-accent-primary);">Selected Attachments (${files.length})</strong>
        <div class="stack" style="gap: var(--space-2);">
          ${files.map(file => `
            <div class="cluster" style="justify-content: space-between; background: var(--color-bg-secondary); padding: var(--space-2) var(--space-3); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
              <div class="cluster" style="gap: var(--space-2);">
                <span style="font-size: var(--font-size-sm);">${ns.escapeHtml(file.name)}</span>
                <span class="text-muted" style="font-size: var(--font-size-xs);">${(file.size / 1024).toFixed(1)} KB</span>
              </div>
            </div>
          `).join('')}
        </div>
        ${files.length > FILE_LIMIT ? `
          <p class="text-error" style="font-size: var(--font-size-xs);">Caution: You have selected more than ${FILE_LIMIT} files. Please reduce the count.</p>
        ` : ''}
      </div>
    `;
  }

  function showSuccessCard(card, appNumber) {
    card.innerHTML = `
      <div class="stack animate-fade-in" style="padding: var(--space-8); text-align: center; gap: var(--space-6);">
        <div class="badge badge-success" style="width: fit-content; margin: 0 auto; padding: var(--space-2) var(--space-4); border-radius: var(--radius-full); box-shadow: var(--shadow-sm);">
          Submission Successful
        </div>
        <div class="stack" style="gap: var(--space-2);">
          <h2 style="font-size: 2.25rem; letter-spacing: -0.02em;">Application Received</h2>
          <p class="text-muted" style="font-size: var(--font-size-lg);">Your request <strong>${ns.escapeHtml(appNumber)}</strong> has been successfully queued for review.</p>
        </div>
        <div class="card" style="background: var(--color-bg-secondary); border-style: dashed; padding: var(--space-5); text-align: left; transition: none;">
          <strong style="display: block; margin-bottom: var(--space-2); color: var(--color-accent-primary); font-family: var(--font-family-mono); text-transform: uppercase; font-size: var(--font-size-xs);">Next Steps</strong>
          <ul class="portal-note-list" style="margin: 0; padding-left: 1.25rem;">
            <li>Shelter personnel will verify your household suitability and ID documentation.</li>
            <li>You will receive a notification (Bell icon) if an interview or home visit is needed.</li>
            <li>Upon approval, you'll be invited to a Pet Care Seminar.</li>
          </ul>
        </div>
        <div class="cluster" style="justify-content: center; gap: var(--space-3); margin-top: var(--space-4);">
          <button class="btn-primary" onclick="window.CatarmanNavigation.reload()">Submit Another</button>
          <a class="btn-secondary" href="/adopt">Portal Home</a>
        </div>
      </div>
    `;
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  ns.registerInitializer(function bindApplyForm(root) {
    const form = root.getElementById('portal-apply-form');
    if (!form || form.dataset.applyBound === 'true') return;
    form.dataset.applyBound = 'true';

    const steps = [...form.querySelectorAll('[data-stepper-step]')];
    const prevBtn = form.querySelector('[data-stepper-prev]');
    const nextBtn = form.querySelector('[data-stepper-next]');
    const submitBtn = form.querySelector('[data-stepper-submit]');
    const progressFill = root.querySelector('[data-stepper-fill]');
    const currentLabel = root.querySelector('[data-stepper-current]');
    const titleNode = root.getElementById('stepper-title');
    const descNode = root.getElementById('stepper-description');
    const errorNode = root.getElementById('portal-apply-errors');
    const fileInput = form.querySelector('input[type="file"]');
    const filePreview = root.getElementById('id-upload-preview');

    let currentStep = loadDraft(form);

    const updateUI = () => {
      steps.forEach((step, i) => {
        step.hidden = (i + 1) !== currentStep;
      });

      const activeStepEl = steps[currentStep - 1];
      if (titleNode) titleNode.textContent = activeStepEl.dataset.stepTitle;
      if (descNode) descNode.textContent = activeStepEl.dataset.stepDesc;

      if (prevBtn) prevBtn.hidden = currentStep === 1;
      if (nextBtn) nextBtn.hidden = currentStep === steps.length;
      if (submitBtn) submitBtn.hidden = currentStep !== steps.length;

      if (progressFill) progressFill.style.width = `${(currentStep / steps.length) * 100}%`;
      if (currentLabel) currentLabel.textContent = currentStep;

      window.scrollTo({ top: form.offsetTop - 120, behavior: 'smooth' });
    };

    const validateStep = (stepIdx) => {
      const stepEl = steps[stepIdx - 1];
      const requiredFields = stepEl.querySelectorAll('[required]');
      let isValid = true;

      for (const field of requiredFields) {
        if (!field.checkValidity()) {
          isValid = false;
          field.reportValidity();
          break; // Show only one at a time for focus
        }
      }

      // Special check for file limit on step 4
      if (stepIdx === 4 && fileInput) {
        if (fileInput.files.length > FILE_LIMIT) {
          window.toast?.error('Upload Limit', `You can only upload up to ${FILE_LIMIT} files.`);
          isValid = false;
        }
      }

      return isValid;
    };

    nextBtn?.addEventListener('click', () => {
      if (validateStep(currentStep)) {
        currentStep++;
        updateUI();
        saveDraft(form, currentStep);
      }
    });

    prevBtn?.addEventListener('click', () => {
      currentStep--;
      updateUI();
      saveDraft(form, currentStep);
    });

    form.addEventListener('input', () => saveDraft(form, currentStep));
    fileInput?.addEventListener('change', () => renderFilePreview(fileInput, filePreview));

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!validateStep(currentStep)) return;
      
      if (errorNode) errorNode.hidden = true;
      if (submitBtn) submitBtn.disabled = true;

      const formData = new FormData(form);
      const csrfToken = formData.get('_token');

      // Sync checkbox booleans for backend
      ['agrees_to_policies', 'agrees_to_home_visit', 'agrees_to_return_policy'].forEach((field) => {
        if (!formData.has(field)) formData.append(field, '0');
      });

      try {
        const responseData = await ns.parseResponse(await fetch('/api/adopt/apply', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: formData
        }));

        localStorage.removeItem(DRAFT_KEY);
        localStorage.removeItem(STEP_KEY);
        
        showSuccessCard(form.closest('.card'), responseData.data?.application?.application_number || 'APP-PENDING');
        await refreshApplications();
      } catch (error) {
        if (submitBtn) submitBtn.disabled = false;
        if (!errorNode) return;
        errorNode.hidden = false;
        errorNode.textContent = formatErrors(error.errors ?? {}) || error.message;
        window.toast?.error('Submission Failed', errorNode.textContent);
      }
    });

    // Public reset method for the "Start Over" hatch
    window.CatarmanPortal.clearApplyDraft = () => {
        if (confirm('Are you sure you want to clear your draft and start over?')) {
            localStorage.removeItem(DRAFT_KEY);
            localStorage.removeItem(STEP_KEY);
            window.CatarmanNavigation.reload();
        }
    };

    updateUI();
    if (fileInput) renderFilePreview(fileInput, filePreview);
  });
})(window.CatarmanPortal);
