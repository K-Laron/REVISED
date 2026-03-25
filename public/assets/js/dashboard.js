document.addEventListener('DOMContentLoaded', async () => {
  if (!document.querySelector('[data-dashboard]')) {
    return;
  }

  const palette = () => {
    const styles = getComputedStyle(document.documentElement);
    return {
      text: styles.getPropertyValue('--color-text-secondary').trim(),
      border: styles.getPropertyValue('--color-border-light').trim(),
      primary: styles.getPropertyValue('--color-accent-primary').trim(),
      success: styles.getPropertyValue('--color-accent-success').trim(),
      warning: styles.getPropertyValue('--color-accent-warning').trim(),
      info: styles.getPropertyValue('--color-accent-info').trim(),
      danger: styles.getPropertyValue('--color-accent-danger').trim()
    };
  };

  const charts = {};

  async function getJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) {
      throw new Error('Request failed: ' + url);
    }
    return response.json();
  }

  function renderStats(items) {
    const root = document.getElementById('stats-grid');
    root.innerHTML = '';
    items.forEach((item) => {
      const card = document.createElement('article');
      card.className = 'card stat-card';
      card.innerHTML = `
        <div class="stat-label">${item.label}</div>
        <div class="stat-value mono">${item.value}</div>
        <div class="stat-meta">${item.meta}</div>
      `;
      root.appendChild(card);
    });
  }

  function renderActivity(items) {
    const root = document.getElementById('activity-list');
    root.innerHTML = '';
    if (!items.length) {
      root.innerHTML = '<div class="activity-item"><strong>No recent activity</strong><span class="text-muted">Audit log entries will appear here.</span></div>';
      return;
    }

    items.forEach((item) => {
      const row = document.createElement('div');
      row.className = 'activity-item';
      row.innerHTML = `
        <strong>${item.module} · ${item.action}</strong>
        <span class="text-muted">Record ${item.record_id ?? '-'} · ${item.created_at}</span>
      `;
      root.appendChild(row);
    });
  }

  function mountChart(id, type, payload, colors) {
    const canvas = document.getElementById(id);
    const ctx = canvas.getContext('2d');

    charts[id]?.destroy();
    charts[id] = new Chart(ctx, {
      type,
      data: {
        labels: payload.labels,
        datasets: payload.datasets.map((dataset, index) => ({
          ...dataset,
          borderColor: colors[index % colors.length],
          backgroundColor: type === 'line'
            ? colors[index % colors.length] + '33'
            : colors,
          tension: 0.35,
          fill: type === 'line'
        }))
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: {
              color: palette().text
            }
          }
        },
        scales: type === 'doughnut'
          ? {}
          : {
              x: {
                ticks: { color: palette().text },
                grid: { color: palette().border }
              },
              y: {
                beginAtZero: true,
                ticks: { color: palette().text },
                grid: { color: palette().border }
              }
            }
      }
    });
  }

  async function loadDashboard() {
    const [stats, intake, adoption, occupancy, medical, activity] = await Promise.all([
      getJson('/api/dashboard/stats'),
      getJson('/api/dashboard/charts/intake'),
      getJson('/api/dashboard/charts/adoptions'),
      getJson('/api/dashboard/charts/occupancy'),
      getJson('/api/dashboard/charts/medical'),
      getJson('/api/dashboard/activity')
    ]);

    renderStats(stats.data);
    renderActivity(activity.data);

    const colors = [palette().primary, palette().success, palette().warning, palette().info, palette().danger];
    mountChart('intake-chart', 'line', intake.data, colors);
    mountChart('adoption-chart', 'bar', adoption.data, colors);
    mountChart('occupancy-chart', 'doughnut', occupancy.data, colors);
    mountChart('medical-chart', 'bar', medical.data, colors);
  }

  document.querySelectorAll('[data-quick-link]').forEach((button) => {
    button.addEventListener('click', () => {
      const href = button.getAttribute('data-quick-link');
      window.CatarmanApp?.navigate?.(href) || (window.location.href = href);
    });
  });

  window.addEventListener('theme:changed', () => {
    loadDashboard().catch((error) => {
      console.error(error);
    });
  });

  loadDashboard().catch((error) => {
    console.error(error);
    window.toast?.error('Dashboard load failed', 'Unable to load dashboard data.');
  });
});
