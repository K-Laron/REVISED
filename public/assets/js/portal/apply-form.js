(function (ns) {
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
    if (!list) {
      return;
    }

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

  ns.registerInitializer(function bindApplyForm(root) {
    const form = root.getElementById('portal-apply-form');
    if (!form || form.dataset.applyBound === 'true') {
      return;
    }

    form.dataset.applyBound = 'true';

    const errorNode = root.getElementById('portal-apply-errors');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (errorNode) {
        errorNode.hidden = true;
      }

      const formData = new FormData(form);
      const csrfToken = formData.get('_token');

      ['agrees_to_policies', 'agrees_to_home_visit', 'agrees_to_return_policy'].forEach((field) => {
        if (!formData.has(field)) {
          formData.append(field, '0');
        }
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
        form.reset();
        await refreshApplications();
      } catch (error) {
        if (!errorNode) {
          return;
        }

        errorNode.hidden = false;
        errorNode.textContent = formatErrors(error.errors ?? {}) || error.message;
        window.toast?.error('Submission failed', errorNode.textContent);
      }
    });
  });
})(window.CatarmanPortal);
