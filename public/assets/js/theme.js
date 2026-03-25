(function () {
  function syncThemeChrome(theme) {
    const themeColor = theme === 'dark' ? '#020617' : '#f8fafc';
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', themeColor);
    document.querySelector('meta[name="msapplication-TileColor"]')?.setAttribute('content', themeColor);
  }

  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = saved || (prefersDark ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', theme);
  syncThemeChrome(theme);

  window.CatarmanTheme = window.CatarmanTheme || {};
  window.CatarmanTheme.syncThemeChrome = syncThemeChrome;
})();

function bindThemeToggle() {
  const syncThemeChrome = window.CatarmanTheme?.syncThemeChrome || function () {};
  const toggle = document.getElementById('theme-toggle');
  if (!toggle || toggle.dataset.themeBound === 'true') return;

  toggle.dataset.themeBound = 'true';

  toggle.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    syncThemeChrome(next);
    window.dispatchEvent(new CustomEvent('theme:changed', { detail: { theme: next } }));
  });
}

window.CatarmanTheme = window.CatarmanTheme || {};
window.CatarmanTheme.init = bindThemeToggle;

if (!window.CatarmanTheme.mediaBound) {
  window.CatarmanTheme.mediaBound = true;
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (!localStorage.getItem('theme')) {
      const theme = event.matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', theme);
      window.CatarmanTheme?.syncThemeChrome?.(theme);
      window.dispatchEvent(new CustomEvent('theme:changed', { detail: { theme } }));
    }
  });
}

document.addEventListener('DOMContentLoaded', bindThemeToggle);
