(function (ns) {
  ns.registerInitializer(function bindStatusForm(root) {
    const form = root.querySelector('.animal-status-form');
    if (!form || form.dataset.statusBound === 'true') {
      return;
    }

    form.dataset.statusBound = 'true';

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const token = form.querySelector('input[name="_token"]')?.value || '';
      const { data: result } = await ns.apiRequest('/api/animals/' + form.dataset.animalId + '/status', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        csrfToken: token,
        body: new URLSearchParams(new FormData(form)).toString()
      });

      if (result.error) {
        window.toast?.error('Status update failed', ns.extractError(result));
        return;
      }

      window.toast?.success('Status updated', result.message);
      window.CatarmanApp?.reload?.() || window.location.reload();
    });
  });
})(window.CatarmanAnimals);
