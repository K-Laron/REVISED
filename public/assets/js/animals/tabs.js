(function (ns) {
  ns.registerInitializer(function bindAnimalTabs(root) {
    const buttons = root.querySelectorAll('[data-tab-target]');
    if (!buttons.length) {
      return;
    }

    buttons.forEach((button) => {
      if (button.dataset.tabBound === 'true') {
        return;
      }

      button.dataset.tabBound = 'true';
      button.addEventListener('click', () => {
        root.querySelectorAll('[data-tab-target]').forEach((node) => {
          node.classList.remove('is-active');
          node.setAttribute('aria-selected', 'false');
        });
        root.querySelectorAll('.tab-panel').forEach((node) => {
          node.classList.remove('is-active');
          node.hidden = true;
        });
        button.classList.add('is-active');
        button.setAttribute('aria-selected', 'true');
        const panel = root.getElementById(button.dataset.tabTarget);
        if (!panel) {
          return;
        }

        panel.classList.add('is-active');
        panel.hidden = false;
      });
    });
  });
})(window.CatarmanAnimals);
