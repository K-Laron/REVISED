(function (ns) {
  const DRAFT_KEY = 'catarman:adoption-draft';

  function formatErrors(errors) {
    const parts = [];
    Object.values(errors || {}).forEach((messages) => {
      if (Array.isArray(messages)) {
        parts.push(...messages);
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
      <article class="portal-status-card">
        <div class="cluster" style="justify-content: space-between;">
          <strong>${ns.escapeHtml(application.application_number)}</strong>
          <span class="badge badge-info">${ns.escapeHtml(application.status.replaceAll('_', ' '))}</span>
        </div>
        <p class="text-muted">${application.animal_name ? `${ns.escapeHtml(application.animal_name)}${application.animal_code ? ` • ${ns.escapeHtml(application.animal_code)}` : ''}` : 'Preference-based application'}</p>
        <p class="portal-card-meta">Created ${ns.escapeHtml(application.created_at)}</p>
        ${application.rejection_reason ? `<p class="text-muted">Reason: ${ns.escapeHtml(application.rejection_reason)}</p>` : ''}
      </article>
    `).join('');
  }

  async function refreshApplications() {
    const response = await fetch('/api/adopt/my-applications', {
      headers: { 'Accept': 'application/json' }
    });
    const result = await ns.parseResponse(response);
    renderApplications(result.data);
  }

  function saveDraft(form) {
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
      if (key !== '_token' && !(value instanceof File) && key !== 'valid_id_path[]') {
        data[key] = value;
      }
    });
    localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
  }

  function loadDraft(form) {
    try {
      const saved = localStorage.getItem(DRAFT_KEY);
      if (!saved) return;
      const data = JSON.parse(saved);
      Object.keys(data).forEach((key) => {
        const field = form.elements[key];
        if (field) {
          if (field.type === 'checkbox') {
            field.checked = data[key] === '1';
          } else {
            field.value = data[key];
          }
        }
      });
    } catch (e) {
      console.warn('Failed to load adoption draft:', e);
    }
  }

  ns.registerInitializer(function bindApplyForm(root) {
    const form = root.getElementById('portal-apply-form');
    if (!form || form.dataset.applyBound === 'true') return;
    form.dataset.applyBound = 'true';

    loadDraft(form);

    const steps = [...form.querySelectorAll('[data-stepper-step]')];
    const prevBtn = form.querySelector('[data-stepper-prev]');
    const nextBtn = form.querySelector('[data-stepper-next]');
    const submitBtn = form.querySelector('[data-stepper-submit]');
    const progressFill = root.querySelector('[data-stepper-fill]');
    const currentLabel = root.querySelector('[data-stepper-current]');
    const titleNode = root.getElementById('stepper-title');
    const descNode = root.getElementById('stepper-description');
    const errorNode = root.getElementById('portal-apply-errors');

    let currentStep = 1;

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

      requiredFields.forEach((field) => {
        if (!field.checkValidity()) {
          isValid = false;
          field.reportValidity();
        }
      });

      return isValid;
    };

    nextBtn?.addEventListener('click', () => {
      if (validateStep(currentStep)) {
        currentStep++;
        updateUI();
        saveDraft(form);
      }
    });

    prevBtn?.addEventListener('click', () => {
      currentStep--;
      updateUI();
    });

    form.addEventListener('input', () => saveDraft(form));

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (errorNode) errorNode.hidden = true;

      const formData = new FormData(form);
      const csrfToken = formData.get('_token');

      ['agrees_to_policies', 'agrees_to_home_visit', 'agrees_to_return_policy'].forEach((field) => {
        if (!formData.has(field)) formData.append(field, '0');
      });

      try {
        await ns.parseResponse(await fetch('/api/adopt/apply', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: formData
        }));

        window.toast?.success('Application submitted', 'Your application is now pending review.');
        localStorage.removeItem(DRAFT_KEY);
        form.reset();
        currentStep = 1;
        updateUI();
        await refreshApplications();
      } catch (error) {
        if (!errorNode) return;
        errorNode.hidden = false;
        errorNode.textContent = formatErrors(error.errors ?? {}) || error.message;
        window.toast?.error('Submission failed', errorNode.textContent);
      }
    });

    updateUI();
  });
})(window.CatarmanPortal);
