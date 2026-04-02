document.addEventListener('DOMContentLoaded', async () => {
  if (!document.querySelector('[data-dashboard]')) {
    return;
  }

  const palette = () => {
    const styles = getComputedStyle(document.documentElement);
    return {
      textStrong: styles.getPropertyValue('--color-text-primary').trim(),
      text: styles.getPropertyValue('--color-text-secondary').trim(),
      textMuted: styles.getPropertyValue('--color-text-tertiary').trim(),
      border: styles.getPropertyValue('--color-border-light').trim(),
      surface: styles.getPropertyValue('--color-bg-elevated').trim(),
      primary: styles.getPropertyValue('--color-accent-primary').trim(),
      success: styles.getPropertyValue('--color-accent-success').trim(),
      warning: styles.getPropertyValue('--color-accent-warning').trim(),
      info: styles.getPropertyValue('--color-accent-info').trim(),
      danger: styles.getPropertyValue('--color-accent-danger').trim()
    };
  };

  const charts = {};
  const ACTIVITY_FEED_LIMIT = 6;
  const OCCUPANCY_CHART_ID = 'occupancy-chart';
  const occupancyStatusPriority = {
    Occupied: 0,
    Available: 1,
    Maintenance: 2,
    Quarantine: 3
  };
  const occupancyCenterLabel = {
    id: 'occupancyCenterLabel',
    afterDatasetsDraw(chart, _args, options) {
      const dataset = chart.getDatasetMeta(0)?.data?.[0];
      if (!dataset || !options) {
        return;
      }

      const ctx = chart.ctx;
      const x = dataset.x;
      const y = dataset.y;
      const innerRadius = Math.max(0, dataset.innerRadius - 10);
      const colors = palette();
      const styles = getComputedStyle(document.documentElement);
      const headingFont = styles.getPropertyValue('--font-family-heading').trim();
      const bodyFont = styles.getPropertyValue('--font-family-primary').trim();
      const monoFont = styles.getPropertyValue('--font-family-mono').trim();
      const headlineSize = Math.max(28, Math.round(dataset.innerRadius * 0.34));
      const labelSize = Math.max(10, Math.round(dataset.innerRadius * 0.12));
      const detailSize = Math.max(11, Math.round(dataset.innerRadius * 0.115));

      ctx.save();
      ctx.beginPath();
      ctx.fillStyle = alphaColor(colors.primary, document.documentElement.getAttribute('data-theme') === 'dark' ? 0.12 : 0.08);
      ctx.strokeStyle = alphaColor(colors.primary, document.documentElement.getAttribute('data-theme') === 'dark' ? 0.24 : 0.12);
      ctx.lineWidth = 1;
      ctx.arc(x, y, innerRadius, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();

      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.shadowColor = alphaColor(colors.primary, document.documentElement.getAttribute('data-theme') === 'dark' ? 0.28 : 0.12);
      ctx.shadowBlur = 24;
      ctx.fillStyle = colors.textStrong;
      ctx.font = `700 ${headlineSize}px ${headingFont}`;
      ctx.fillText(options.percentageLabel ?? '0%', x, y - 12);

      ctx.shadowBlur = 0;
      ctx.fillStyle = colors.text;
      ctx.font = `600 ${labelSize}px ${monoFont}`;
      ctx.fillText(options.label ?? 'occupied', x, y + 14);

      ctx.fillStyle = colors.textMuted;
      ctx.font = `500 ${detailSize}px ${bodyFont}`;
      ctx.fillText(options.detail ?? '', x, y + 34);
      ctx.restore();
    }
  };

  async function getJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) {
      throw new Error('Request failed: ' + url);
    }
    return response.json();
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function alphaColor(hex, alpha) {
    const value = String(hex ?? '').trim().replace('#', '');
    if (!/^[\da-f]{6}$/i.test(value)) {
      return hex;
    }

    const r = parseInt(value.slice(0, 2), 16);
    const g = parseInt(value.slice(2, 4), 16);
    const b = parseInt(value.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  function occupancyTone(label) {
    const colors = palette();
    const normalized = String(label ?? '').toLowerCase();

    if (normalized === 'occupied') {
      return colors.warning;
    }

    if (normalized === 'maintenance') {
      return colors.success;
    }

    if (normalized === 'quarantine') {
      return colors.danger;
    }

    if (normalized === 'available') {
      return colors.primary;
    }

    return colors.info;
  }

  function normalizeOccupancyPayload(payload) {
    const source = payload.datasets?.[0]?.data ?? [];
    const rows = payload.labels.map((label, index) => ({
      label,
      value: Number(source[index] ?? 0),
      priority: occupancyStatusPriority[label] ?? 100 + index
    }));

    rows.sort((left, right) => {
      if (left.priority !== right.priority) {
        return left.priority - right.priority;
      }

      if (left.value !== right.value) {
        return right.value - left.value;
      }

      return left.label.localeCompare(right.label);
    });

    const total = rows.reduce((sum, row) => sum + row.value, 0);
    const occupied = rows.find((row) => row.label.toLowerCase() === 'occupied')?.value ?? 0;
    const percent = total > 0 ? Math.round((occupied / total) * 100) : 0;

    return {
      labels: rows.map((row) => row.label),
      values: rows.map((row) => row.value),
      total,
      occupied,
      percent,
      rows: rows.map((row) => ({
        ...row,
        percent: total > 0 ? Math.round((row.value / total) * 100) : 0,
        color: occupancyTone(row.label)
      }))
    };
  }

  function renderOccupancySummary(model) {
    const root = document.getElementById('occupancy-summary');
    if (!root) {
      return;
    }

    root.innerHTML = `
      <article class="occupancy-summary-pill occupancy-summary-pill-primary">
        <span class="field-label">Live load</span>
        <strong>${escapeHtml(String(model.percent))}% occupied</strong>
        <span class="text-muted">${escapeHtml(String(model.occupied))} of ${escapeHtml(String(model.total))} kennels</span>
      </article>
      <article class="occupancy-summary-pill">
        <span class="field-label">Open capacity</span>
        <strong>${escapeHtml(String(Math.max(model.total - model.occupied, 0)))}</strong>
        <span class="text-muted">Available for assignment now</span>
      </article>
    `;
  }

  function renderOccupancyBreakdown(model) {
    const root = document.getElementById('occupancy-breakdown');
    if (!root) {
      return;
    }

    root.innerHTML = model.rows.map((row, index) => `
      <article class="occupancy-breakdown-chip${index === 0 ? ' is-active' : ''}" data-occupancy-breakdown-item="${index}">
        <div class="occupancy-breakdown-head">
          <span class="occupancy-breakdown-swatch" style="--occupancy-accent: ${row.color}"></span>
          <span class="field-label">${escapeHtml(row.label)}</span>
          <span class="mono">${escapeHtml(String(row.percent))}%</span>
        </div>
        <strong>${escapeHtml(String(row.value))}</strong>
        <div class="occupancy-breakdown-track">
          <span style="width: ${Math.max(row.percent, row.value > 0 ? 10 : 0)}%; --occupancy-accent: ${row.color}"></span>
        </div>
      </article>
    `).join('');
  }

  function setOccupancyBreakdownActive(index) {
    document.querySelectorAll('[data-occupancy-breakdown-item]').forEach((element) => {
      element.classList.toggle('is-active', String(index) === element.getAttribute('data-occupancy-breakdown-item'));
    });
  }

  function renderStats(items) {
    const root = document.getElementById('stats-grid');
    root.innerHTML = '';
    items.forEach((item) => {
      const card = document.createElement('article');
      card.className = 'card stat-card';
      card.innerHTML = `
        <div class="stat-label">${escapeHtml(item.label)}</div>
        <div class="stat-value mono">${escapeHtml(item.value)}</div>
        <div class="stat-meta">${escapeHtml(item.meta)}</div>
      `;
      root.appendChild(card);
    });
  }

  function renderLegacyActivity(items) {
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

  function renderActivityDigest(items) {
    const root = document.getElementById('activity-digest');
    if (!root) {
      return;
    }

    if (!items.length) {
      root.innerHTML = '';
      return;
    }

    const moduleCounts = new Map();
    items.forEach((item) => {
      const key = String(item.module ?? 'System');
      moduleCounts.set(key, (moduleCounts.get(key) ?? 0) + 1);
    });

    const topModules = [...moduleCounts.entries()]
      .map(([module, count]) => ({ module, count }))
      .sort((left, right) => {
        if (left.count !== right.count) {
          return right.count - left.count;
        }

        return left.module.localeCompare(right.module);
      })
      .slice(0, 3);

    const latest = items[0];
    const lead = topModules[0] ?? { module: 'System', count: items.length };

    root.innerHTML = `
      <div class="activity-digest-grid">
        <article class="activity-digest-card activity-digest-card-primary">
          <span class="field-label">Sample volume</span>
          <strong>${escapeHtml(String(items.length))} events</strong>
          <span class="text-muted">Latest record at ${escapeHtml(String(latest.created_at ?? 'now'))}</span>
        </article>
        <article class="activity-digest-card">
          <span class="field-label">Lead module</span>
          <strong>${escapeHtml(String(lead.module))}</strong>
          <span class="text-muted">${escapeHtml(String(lead.count))} records in the current sample</span>
        </article>
        <article class="activity-digest-card">
          <span class="field-label">Latest touch</span>
          <strong>${escapeHtml(String(latest.action ?? 'update'))}</strong>
          <span class="text-muted">${escapeHtml(String(latest.module ?? 'SYSTEM'))} / ${escapeHtml(String(latest.record_id ?? '-'))}</span>
        </article>
        <article class="activity-digest-card">
          <span class="field-label">Coverage</span>
          <strong>${escapeHtml(String(moduleCounts.size))} modules</strong>
          <span class="text-muted">Cross-surface activity captured below</span>
        </article>
      </div>
      <div class="activity-digest-modules">
        ${topModules.map((entry) => `
          <span class="activity-digest-chip">
            <span class="field-label">${escapeHtml(String(entry.module))}</span>
            <strong>${escapeHtml(String(entry.count))}</strong>
          </span>
        `).join('')}
      </div>
    `;
  }

  function renderActivity(items) {
    const root = document.getElementById('activity-list');
    root.innerHTML = '';
    if (!items.length) {
      root.innerHTML = '<div class="activity-item"><strong>No recent activity</strong><span class="text-muted">Audit log entries will appear here.</span></div>';
      renderActivityDigest([]);
      return;
    }

    const feedItems = items.slice(0, ACTIVITY_FEED_LIMIT);
    renderActivityDigest(items);

    feedItems.forEach((item) => {
      const row = document.createElement('div');
      row.className = 'activity-item';
      row.innerHTML = `
        <span class="field-label">${escapeHtml(item.module)}</span>
        <strong>${escapeHtml(item.action)}</strong>
        <span class="mono">${escapeHtml(item.record_id ?? '-')} / ${escapeHtml(item.created_at)}</span>
      `;
      root.appendChild(row);
    });
  }

  function mountOccupancyChart(payload) {
    const canvas = document.getElementById(OCCUPANCY_CHART_ID);
    const ctx = canvas.getContext('2d');
    const model = normalizeOccupancyPayload(payload);
    const colors = palette();

    renderOccupancySummary(model);
    renderOccupancyBreakdown(model);

    charts[OCCUPANCY_CHART_ID]?.destroy();
    charts[OCCUPANCY_CHART_ID] = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: model.labels,
        datasets: [{
          label: 'Kennels',
          data: model.values,
          backgroundColor: model.rows.map((row) => row.color),
          borderColor: colors.surface,
          borderWidth: 3,
          hoverOffset: 14,
          spacing: 3,
          cutout: '60%',
          borderRadius: 10
        }]
      },
      plugins: [occupancyCenterLabel],
      options: {
        maintainAspectRatio: false,
        layout: {
          padding: {
            top: 8,
            right: 12,
            bottom: 8,
            left: 12
          }
        },
        animation: {
          duration: 520
        },
        onHover: (_event, elements) => {
          canvas.style.cursor = elements.length ? 'pointer' : 'default';
          setOccupancyBreakdownActive(elements[0]?.index ?? 0);
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: colors.surface,
            borderColor: colors.border,
            borderWidth: 1,
            titleColor: colors.textStrong,
            bodyColor: colors.text,
            padding: 12,
            displayColors: false,
            callbacks: {
              label(context) {
                const value = Number(context.raw ?? 0);
                const percent = model.total > 0 ? Math.round((value / model.total) * 100) : 0;
                return `${context.label}: ${value} kennels (${percent}%)`;
              }
            }
          },
          occupancyCenterLabel: {
            percentageLabel: `${model.percent}%`,
            label: 'occupied',
            detail: `${model.occupied} of ${model.total} kennels`
          }
        }
      }
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
    const bootstrap = await getJson('/api/dashboard/bootstrap');
    const payload = bootstrap.data;

    renderStats(payload.stats);
    renderActivity(payload.activity);

    const colors = [palette().primary, palette().success, palette().warning, palette().info, palette().danger];
    mountChart('intake-chart', 'line', payload.charts.intake, colors);
    mountChart('adoption-chart', 'bar', payload.charts.adoptions, colors);
    mountOccupancyChart(payload.charts.occupancy);
    mountChart('medical-chart', 'bar', payload.charts.medical, colors);
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
