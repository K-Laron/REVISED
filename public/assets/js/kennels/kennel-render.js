(function () {
  if (window.CatarmanKennelRender) {
    return;
  }

  const utils = window.CatarmanKennelUtils;

  function renderOccupantCard(occupant) {
    const media = occupant.primary_photo_path
      ? `<img src="/${occupant.primary_photo_path}" alt="">`
      : '<span aria-hidden="true">&#128062;</span>';

    return `
      <div class="kennel-occupant">
        <div class="kennel-occupant-meta">
          <div class="kennel-occupant-photo">${media}</div>
          <div>
            <strong>${utils.escapeHtml(occupant.animal_name || 'Unnamed Animal')}</strong><br>
            <span class="text-muted mono">${utils.escapeHtml(occupant.animal_code)}</span>
          </div>
        </div>
        <span class="text-muted">${utils.escapeHtml(occupant.species)} · ${utils.escapeHtml(occupant.size)} · ${utils.escapeHtml(String(occupant.days_housed))} day(s)</span>
      </div>
    `;
  }

  function renderKennelCard(kennel) {
    const occupant = kennel.current_occupants[0] || null;
    return `
      <div class="kennel-card-head">
        <div class="cluster" style="justify-content: space-between;">
          <strong class="kennel-card-code">${utils.escapeHtml(kennel.kennel_code)}</strong>
          <span class="kennel-status-pill" data-status="${utils.escapeHtml(kennel.status)}">${utils.escapeHtml(kennel.status)}</span>
        </div>
        <div class="text-muted">${utils.escapeHtml(kennel.type)} · ${utils.escapeHtml(kennel.size_category)}</div>
      </div>
      ${occupant ? renderOccupantCard(occupant) : '<div class="kennel-occupant"><strong>Empty</strong><span class="text-muted">Ready for assignment</span></div>'}
      <div class="cluster" style="justify-content: space-between;">
        <span class="text-muted">Capacity</span>
        <strong>${utils.escapeHtml(String(kennel.occupancy_count))} / ${utils.escapeHtml(String(kennel.max_occupants))}</strong>
      </div>
    `;
  }

  function renderDrawerBody(kennel, history, maintenanceLogs, options) {
    const { assignableAnimals, canReadAnimals, canUpdateKennels, csrfToken } = options;
    const occupant = kennel.current_occupants[0] || null;
    const assignableOptions = assignableAnimals
      .filter((animal) => utils.isAnimalCompatible(animal, kennel))
      .map((animal) => {
        const detail = `${animal.animal_id} · ${animal.name || 'Unnamed Animal'} · ${animal.species} · ${animal.size}`;
        const suffix = animal.current_kennel_code ? ` (Currently ${animal.current_kennel_code})` : '';
        return `<option value="${animal.id}">${utils.escapeHtml(detail + suffix)}</option>`;
      })
      .join('');

    return `
      <div class="kennel-detail-summary">
        <div class="kennel-detail-stat">
          <span class="field-label">Status</span>
          <strong>${utils.escapeHtml(kennel.status)}</strong>
        </div>
        <div class="kennel-detail-stat">
          <span class="field-label">Capacity</span>
          <strong>${utils.escapeHtml(String(kennel.occupancy_count))} / ${utils.escapeHtml(String(kennel.max_occupants))}</strong>
        </div>
        <div class="kennel-detail-stat">
          <span class="field-label">Allowed Species</span>
          <strong>${utils.escapeHtml(kennel.allowed_species)}</strong>
        </div>
        <div class="kennel-detail-stat">
          <span class="field-label">Notes</span>
          <strong>${utils.escapeHtml(kennel.notes || 'None')}</strong>
        </div>
      </div>

      <div class="kennel-drawer-block">
        <div class="cluster" style="justify-content: space-between;">
          <h4>Current Occupant</h4>
          ${canUpdateKennels ? '<button class="btn-secondary" type="button" data-open-edit-kennel>Edit Kennel</button>' : ''}
        </div>
        ${occupant
          ? `${renderOccupantCard(occupant)}${canReadAnimals ? `<a class="btn-secondary" href="/animals/${occupant.animal_id}">Open Animal Profile</a>` : ''}`
          : '<div class="text-muted">This kennel is currently empty.</div>'}
      </div>

      <div class="kennel-drawer-block">
        <h4>Assignment</h4>
        ${canUpdateKennels && kennel.status === 'Available'
          ? `
            <form class="kennel-inline-form" data-assign-form>
              <input type="hidden" name="_token" value="${utils.escapeHtml(csrfToken)}">
              <label class="field">
                <span class="field-label">Animal</span>
                <select class="select" name="animal_id" required>
                  <option value="">Select an animal</option>
                  ${assignableOptions}
                </select>
              </label>
              <label class="field">
                <span class="field-label">Transfer Reason</span>
                <input class="input" type="text" name="transfer_reason" placeholder="Optional note">
              </label>
              <button class="btn-primary" type="submit">Assign Animal</button>
            </form>
          `
          : `<div class="text-muted">${canUpdateKennels ? 'Kennel must be available before a new animal can be assigned.' : 'You do not have permission to change kennel assignments.'}</div>`}
      </div>

      <div class="kennel-drawer-block">
        <h4>Release</h4>
        ${canUpdateKennels && occupant
          ? `
            <form class="kennel-inline-form" data-release-form>
              <input type="hidden" name="_token" value="${utils.escapeHtml(csrfToken)}">
              <label class="field">
                <span class="field-label">Release Reason</span>
                <input class="input" type="text" name="transfer_reason" placeholder="Cleaning, transfer, or discharge">
              </label>
              <button class="btn-secondary" type="submit">Release Current Animal</button>
            </form>
          `
          : `<div class="text-muted">${canUpdateKennels ? 'No active occupant to release.' : 'You do not have permission to release kennel occupants.'}</div>`}
      </div>

      <div class="kennel-drawer-block">
        <h4>Maintenance Log</h4>
        ${canUpdateKennels
          ? `
            <form class="kennel-inline-form" data-maintenance-form>
              <input type="hidden" name="_token" value="${utils.escapeHtml(csrfToken)}">
              <label class="field">
                <span class="field-label">Type</span>
                <select class="select" name="maintenance_type" required>
                  <option value="Cleaning">Cleaning</option>
                  <option value="Disinfection">Disinfection</option>
                  <option value="Repair">Repair</option>
                  <option value="Inspection">Inspection</option>
                </select>
              </label>
              <label class="field">
                <span class="field-label">Scheduled Date</span>
                <input class="input" type="date" name="scheduled_date">
              </label>
              <label class="field">
                <span class="field-label">Completed At</span>
                <input class="input" type="datetime-local" name="completed_at">
              </label>
              <label class="field">
                <span class="field-label">Description</span>
                <textarea class="textarea" name="description" rows="3" placeholder="What needs to be done or what was completed?"></textarea>
              </label>
              <button class="btn-primary" type="submit">Save Maintenance Log</button>
            </form>
          `
          : '<div class="text-muted">You do not have permission to manage kennel maintenance.</div>'}
      </div>

      <div class="kennel-drawer-block">
        <h4>Assignment History</h4>
        <div class="kennel-drawer-list">
          ${history.length
            ? history.map((entry) => `
                <div class="kennel-drawer-entry">
                  <strong>${utils.escapeHtml(entry.animal_name || 'Unnamed Animal')} · ${utils.escapeHtml(entry.animal_code)}</strong>
                  <span class="text-muted">${utils.escapeHtml(entry.assigned_at)}${entry.released_at ? ` → ${utils.escapeHtml(entry.released_at)}` : ''}</span>
                  <span>${utils.escapeHtml(entry.transfer_reason || 'No transfer note')}</span>
                </div>
              `).join('')
            : '<div class="text-muted">No assignment history yet.</div>'}
        </div>
      </div>

      <div class="kennel-drawer-block">
        <h4>Maintenance History</h4>
        <div class="kennel-drawer-list">
          ${maintenanceLogs.length
            ? maintenanceLogs.map((entry) => `
                <div class="kennel-drawer-entry">
                  <strong>${utils.escapeHtml(entry.maintenance_type)}</strong>
                  <span class="text-muted">${utils.escapeHtml(entry.scheduled_date || entry.created_at)}</span>
                  <span>${utils.escapeHtml(entry.description || 'No description provided')}</span>
                </div>
              `).join('')
            : '<div class="text-muted">No maintenance logs yet.</div>'}
        </div>
      </div>
    `;
  }

  window.CatarmanKennelRender = {
    renderDrawerBody,
    renderKennelCard
  };
})();
