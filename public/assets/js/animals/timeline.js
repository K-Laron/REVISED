(function (ns) {
  ns.registerInitializer(function bindTimeline(root) {
    const timeline = root.getElementById('animal-timeline');
    if (!timeline || timeline.dataset.timelineBound === 'true') {
      return;
    }

    timeline.dataset.timelineBound = 'true';

    async function loadTimeline() {
      const { ok, data: result } = await ns.apiRequest('/api/animals/' + timeline.dataset.animalId + '/timeline');
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
          <strong>${ns.escapeHtml(entry.title)}</strong>
          <span class="text-muted">${ns.escapeHtml(entry.date)}</span>
          <p>${ns.escapeHtml(entry.description || '')}</p>
        `;
        timeline.appendChild(row);
      });
    }

    loadTimeline().catch((error) => {
      console.error(error);
      timeline.innerHTML = '<div class="timeline-entry">Unable to load timeline.</div>';
    });
  });
})(window.CatarmanAnimals);
