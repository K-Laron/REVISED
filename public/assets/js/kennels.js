document.addEventListener('DOMContentLoaded', () => {
  bindKennelPage();
});

function bindKennelPage() {
  const page = document.getElementById('kennel-page');
  if (!page) return;

  const kennelUtils = window.CatarmanKennelUtils;
  const kennelRender = window.CatarmanKennelRender;
  const rawData = document.getElementById('kennel-page-data')?.textContent || '{}';
  const pageData = JSON.parse(rawData);
  const csrfToken = pageData.csrfToken || '';
  const assignableAnimals = Array.isArray(pageData.assignableAnimals) ? pageData.assignableAnimals : [];
  const canReadAnimals = Boolean(pageData.canReadAnimals);
  const canUpdateKennels = Boolean(pageData.canUpdateKennels);
  const existingKennelCodes = new Set(
    Array.isArray(pageData.existingKennelCodes)
      ? pageData.existingKennelCodes.map((code) => String(code))
      : []
  );

  const state = {
    kennels: [],
    selectedKennelId: null,
    view: 'grid'
  };

  const filterForm = document.getElementById('kennel-filter-form');
  const gridView = document.getElementById('kennel-grid-view');
  const listView = document.getElementById('kennel-list-view');
  const tableBody = document.getElementById('kennel-table-body');
  const drawer = document.getElementById('kennel-detail-drawer');
  const drawerTitle = document.getElementById('kennel-detail-title');
  const drawerSubtitle = document.getElementById('kennel-detail-subtitle');
  const drawerBody = document.getElementById('kennel-detail-body');
  const modal = document.getElementById('kennel-modal');
  const modalTitle = document.getElementById('kennel-modal-title');
  const kennelForm = document.getElementById('kennel-form');
  const deleteButton = document.getElementById('kennel-delete-button');
  const kennelCodeInput = kennelForm.elements.kennel_code;
  const zoneInput = kennelForm.elements.zone;
  const viewButtons = Array.from(document.querySelectorAll('[data-kennel-view]'));

  zoneInput.addEventListener('input', syncGeneratedKennelCode);

  function syncViewPanels(nextView = null) {
    if (nextView) {
      state.view = nextView;
    } else {
      const activeButton = viewButtons.find((button) => button.classList.contains('is-active'));
      if (activeButton?.dataset.kennelView) {
        state.view = activeButton.dataset.kennelView;
      }
    }

    viewButtons.forEach((button) => {
      const active = button.dataset.kennelView === state.view;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    gridView.hidden = state.view !== 'grid';
    listView.hidden = state.view !== 'list';
  }

  viewButtons.forEach((button) => {
    button.addEventListener('click', () => {
      syncViewPanels(button.dataset.kennelView);
    });
  });

  syncViewPanels();

  filterForm.addEventListener('change', loadKennels);
  filterForm.addEventListener('submit', (event) => {
    event.preventDefault();
    loadKennels();
  });
  filterForm.addEventListener('reset', () => {
    setTimeout(loadKennels, 0);
  });

  document.querySelector('[data-open-kennel-modal]')?.addEventListener('click', () => {
    openKennelModal();
  });

  document.querySelectorAll('[data-close-kennel-modal]').forEach((button) => {
    button.addEventListener('click', closeKennelModal);
  });

  document.querySelectorAll('[data-close-kennel-drawer]').forEach((button) => {
    button.addEventListener('click', closeDrawer);
  });

  kennelForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const kennelId = kennelForm.elements.id.value;
    const formData = new FormData(kennelForm);
    const method = kennelId ? 'PUT' : 'POST';
    const endpoint = kennelId ? `/api/kennels/${kennelId}` : '/api/kennels';

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
      window.toast?.error('Kennel save failed', kennelUtils.extractError(result));
      return;
    }

    window.toast?.success('Kennel saved', result.message);
    if (result?.data?.kennel_code) {
      existingKennelCodes.add(String(result.data.kennel_code));
    }
    closeKennelModal(false);
    await loadStats();
    await loadKennels();
  });

  deleteButton.addEventListener('click', async () => {
    const kennelId = kennelForm.elements.id.value;
    if (!kennelId) return;

    const response = await fetch(`/api/kennels/${kennelId}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    });

    const result = await response.json();
    if (!response.ok) {
      window.toast?.error('Kennel delete failed', kennelUtils.extractError(result));
      return;
    }

    window.toast?.success('Kennel deleted', result.message);
    closeKennelModal(false);
    closeDrawer();
    await loadStats();
    await loadKennels();
  });

  loadStats();
  loadKennels();

  async function loadStats() {
    const response = await fetch('/api/kennels/stats', {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();
    if (!response.ok) return;

    document.getElementById('kennel-stat-total').textContent = result.data.total ?? 0;
    document.getElementById('kennel-stat-available').textContent = result.data.available ?? 0;
    document.getElementById('kennel-stat-occupied').textContent = result.data.occupied ?? 0;
    document.getElementById('kennel-stat-maintenance').textContent = result.data.maintenance ?? 0;
    document.getElementById('kennel-stat-quarantine').textContent = result.data.quarantine ?? 0;
    document.getElementById('kennel-stat-rate').textContent = `${result.data.occupancy_rate ?? 0}%`;
  }

  async function loadKennels() {
    const params = new URLSearchParams(new FormData(filterForm));
    const response = await fetch('/api/kennels?' + params.toString(), {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Kennel load failed', kennelUtils.extractError(result));
      return;
    }

    state.kennels = Array.isArray(result.data) ? result.data : [];
    renderGrid();
    renderList();

    if (state.selectedKennelId !== null) {
      const selected = state.kennels.find((item) => Number(item.id) === Number(state.selectedKennelId));
      if (selected) {
        openDrawer(selected);
      } else {
        closeDrawer();
      }
    }
  }

  function renderGrid() {
    const grouped = kennelUtils.groupBy(state.kennels, 'zone');
    gridView.innerHTML = '';

    if (state.kennels.length === 0) {
      gridView.innerHTML = '<div class="kennel-detail-empty">No kennels matched the current filters.</div>';
      return;
    }

    Object.entries(grouped).forEach(([zone, items]) => {
      const section = document.createElement('section');
      section.className = 'kennel-zone-group';

      const title = document.createElement('div');
      title.className = 'kennel-zone-title';
      title.innerHTML = `
        <div>
          <h3>Zone ${kennelUtils.escapeHtml(zone)}</h3>
          <p class="text-muted">${items.length} kennel${items.length === 1 ? '' : 's'} in this zone</p>
        </div>
      `;
      section.appendChild(title);

      const grid = document.createElement('div');
      grid.className = 'kennel-zone-grid';

      items.forEach((kennel) => {
        const card = document.createElement('button');
        card.type = 'button';
        card.className = 'kennel-card';
        card.dataset.status = kennel.status;
        card.innerHTML = kennelRender.renderKennelCard(kennel);
        card.addEventListener('click', () => openDrawer(kennel));
        grid.appendChild(card);
      });

      section.appendChild(grid);
      gridView.appendChild(section);
    });
  }

  function renderList() {
    tableBody.innerHTML = '';

    state.kennels.forEach((kennel) => {
      const row = document.createElement('tr');
      const occupant = kennel.current_occupants[0] || null;
      row.innerHTML = `
        <td>
          <div class="stack">
            <strong class="kennel-card-code">${kennelUtils.escapeHtml(kennel.kennel_code)}</strong>
            <span class="text-muted">${kennelUtils.escapeHtml(kennel.type)} · ${kennelUtils.escapeHtml(kennel.size_category)}</span>
          </div>
        </td>
        <td><span class="kennel-status-pill" data-status="${kennelUtils.escapeHtml(kennel.status)}">${kennelUtils.escapeHtml(kennel.status)}</span></td>
        <td>
          ${occupant && canReadAnimals
            ? `<a class="kennel-table-occupant-link" href="/animals/${occupant.animal_id}"><strong>${kennelUtils.escapeHtml(occupant.animal_name || 'Unnamed Animal')}</strong><br><span class="text-muted mono">${kennelUtils.escapeHtml(occupant.animal_code)}</span></a>`
            : occupant
              ? `<div class="stack"><strong>${kennelUtils.escapeHtml(occupant.animal_name || 'Unnamed Animal')}</strong><span class="text-muted mono">${kennelUtils.escapeHtml(occupant.animal_code)}</span></div>`
              : '<span class="text-muted">Empty</span>'}
        </td>
        <td>${kennelUtils.escapeHtml(String(kennel.occupancy_count))} / ${kennelUtils.escapeHtml(String(kennel.max_occupants))}</td>
        <td>${kennelUtils.escapeHtml(kennel.zone)}</td>
        <td>${kennelUtils.escapeHtml(kennel.allowed_species)} · ${kennelUtils.escapeHtml(kennel.size_category)}</td>
      `;
      row.addEventListener('click', () => openDrawer(kennel));
      tableBody.appendChild(row);
    });
  }

  async function openDrawer(kennel) {
    state.selectedKennelId = Number(kennel.id);
    drawer.hidden = false;
    drawer.setAttribute('aria-hidden', 'false');
    drawerTitle.textContent = `${kennel.kennel_code} · ${kennel.zone}`;
    drawerSubtitle.textContent = `${kennel.type} kennel for ${kennel.allowed_species} · ${kennel.size_category}`;
    drawerBody.innerHTML = '<div class="kennel-detail-empty">Loading kennel activity…</div>';

    const [historyResponse, maintenanceResponse] = await Promise.all([
      fetch(`/api/kennels/${kennel.id}/history`, { headers: { Accept: 'application/json' } }),
      fetch(`/api/kennels/${kennel.id}/maintenance`, { headers: { Accept: 'application/json' } })
    ]);

    const historyResult = await historyResponse.json();
    const maintenanceResult = await maintenanceResponse.json();

    drawerBody.innerHTML = kennelRender.renderDrawerBody(
      kennel,
      historyResponse.ok ? historyResult.data : [],
      maintenanceResponse.ok ? maintenanceResult.data : [],
      {
        assignableAnimals,
        canReadAnimals,
        canUpdateKennels,
        csrfToken
      }
    );

    bindDrawerActions(kennel);
  }

  function closeDrawer() {
    state.selectedKennelId = null;
    drawer.hidden = true;
    drawer.setAttribute('aria-hidden', 'true');
  }

  function openKennelModal(kennel = null) {
    if (!drawer.hidden) {
      drawer.hidden = true;
      drawer.setAttribute('aria-hidden', 'true');
    }

    kennelForm.reset();
    kennelForm.elements._token.value = csrfToken;
    kennelForm.elements.id.value = kennel?.id || '';
    deleteButton.hidden = !kennel;
    modalTitle.textContent = kennel ? `Edit ${kennel.kennel_code}` : 'Add Kennel';
    kennelForm.dataset.autoCodeMode = kennel ? 'off' : 'create';

    if (kennel) {
      kennelForm.elements.kennel_code.value = kennel.kennel_code || '';
      kennelForm.elements.kennel_code.readOnly = false;
      kennelForm.elements.zone.value = kennel.zone || '';
      kennelForm.elements.row_number.value = kennel.row_number || '';
      kennelForm.elements.size_category.value = kennel.size_category || 'Small';
      kennelForm.elements.type.value = kennel.type || 'Indoor';
      kennelForm.elements.allowed_species.value = kennel.allowed_species || 'Dog';
      kennelForm.elements.max_occupants.value = kennel.max_occupants || 1;
      kennelForm.elements.status.value = kennel.status || 'Available';
      kennelForm.elements.notes.value = kennel.notes || '';
    } else {
      kennelForm.elements.kennel_code.readOnly = true;
      syncGeneratedKennelCode();
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeKennelModal(restoreDrawer = true) {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');

    if (!restoreDrawer || state.selectedKennelId === null) {
      return;
    }

    const selected = state.kennels.find((item) => Number(item.id) === Number(state.selectedKennelId));
    if (selected) {
      openDrawer(selected);
    }
  }

  function syncGeneratedKennelCode() {
    if (kennelForm.dataset.autoCodeMode !== 'create') {
      return;
    }

    const zoneToken = kennelUtils.extractZoneToken(zoneInput.value);
    if (!zoneToken) {
      kennelCodeInput.value = '';
      return;
    }

    kennelCodeInput.value = kennelUtils.generateKennelCode(zoneToken, existingKennelCodes);
  }

  function bindDrawerActions(kennel) {
    drawerBody.querySelector('[data-open-edit-kennel]')?.addEventListener('click', () => openKennelModal(kennel));

    drawerBody.querySelector('[data-assign-form]')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const form = event.currentTarget;

      const response = await fetch(`/api/kennels/${kennel.id}/assign`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-CSRF-TOKEN': csrfToken
        },
        body: new URLSearchParams(new FormData(form)).toString()
      });

      const result = await response.json();
      if (!response.ok) {
        window.toast?.error('Assignment failed', kennelUtils.extractError(result));
        return;
      }

      window.toast?.success('Animal assigned', result.message);
      await loadStats();
      await loadKennels();
    });

    drawerBody.querySelector('[data-release-form]')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const form = event.currentTarget;

      const response = await fetch(`/api/kennels/${kennel.id}/release`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-CSRF-TOKEN': csrfToken
        },
        body: new URLSearchParams(new FormData(form)).toString()
      });

      const result = await response.json();
      if (!response.ok) {
        window.toast?.error('Release failed', kennelUtils.extractError(result));
        return;
      }

      window.toast?.success('Animal released', result.message);
      await loadStats();
      await loadKennels();
    });

    drawerBody.querySelector('[data-maintenance-form]')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const form = event.currentTarget;

      const response = await fetch(`/api/kennels/${kennel.id}/maintenance`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-CSRF-TOKEN': csrfToken
        },
        body: new URLSearchParams(new FormData(form)).toString()
      });

      const result = await response.json();
      if (!response.ok) {
        window.toast?.error('Maintenance log failed', kennelUtils.extractError(result));
        return;
      }

      window.toast?.success('Maintenance logged', result.message);
      await loadStats();
      await loadKennels();
    });
  }

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeKennelModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      if (!modal.hidden) {
        closeKennelModal();
        return;
      }

      if (!drawer.hidden) {
        closeDrawer();
      }
    }
  });
}
