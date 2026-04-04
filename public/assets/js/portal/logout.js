(function (ns) {
  ns.registerInitializer(function bindLogout(root) {
    const button = root.querySelector('[data-portal-logout]');
    if (!button || button.dataset.logoutBound === 'true') {
      return;
    }

    button.dataset.logoutBound = 'true';

    button.addEventListener('click', async () => {
      try {
        const result = await ns.parseResponse(await fetch('/api/auth/logout', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': button.dataset.csrfToken || ''
          },
          body: JSON.stringify({})
        }));

        window.location.href = result.data.redirect;
      } catch (error) {
        window.toast?.error('Logout failed', error.message || 'Unable to end the current session.');
      }
    });
  });
})(window.CatarmanPortal);
