# Dashboard Intake Trend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the dashboard intake-trend card into an executive summary module using the existing intake chart payload.

**Architecture:** Keep the current `/api/dashboard/bootstrap` contract unchanged and derive executive intake metrics client-side from the existing `payload.charts.intake` series. Update the dashboard view to provide a dedicated summary shell, extend the dashboard stylesheet with intake-specific executive-card treatments, and refine the dashboard script so the intake card renders summary metrics, narrative insight text, and a calmer chart style.

**Tech Stack:** PHP views, Chart.js, vanilla JavaScript, dashboard CSS, PHPUnit view smoke tests

---

## File Map

- Modify: `C:\Users\TESS LARON\Desktop\REVISED\views\dashboard\index.php`
  - Add the intake executive summary shell above `#intake-chart`.
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\public\assets\js\dashboard.js`
  - Add intake-summary derivation helpers, DOM rendering, and intake-specific line-chart styling.
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\public\assets\css\dashboard.css`
  - Add intake executive-card layout, summary-rail, delta-pill, insight-line, and responsive rules.
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\DashboardViewTest.php`
  - Add shell and script assertions for the intake executive summary.
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\AppShellViewTest.php`
  - Add stylesheet assertions for the new intake executive surface and responsive rules.

### Task 1: Lock The Intake Executive Shell With Failing Tests

**Files:**
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\DashboardViewTest.php`
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\AppShellViewTest.php`

- [ ] **Step 1: Add a failing dashboard view test for the intake executive shell**

Insert these methods into `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\DashboardViewTest.php` after `testDashboardRendersEnhancedOccupancyChartShell()`:

```php
    public function testDashboardRendersIntakeExecutiveSummaryShell(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
            'csrfToken' => 'test-token',
        ]);

        self::assertStringContainsString('data-intake-summary-shell', $html);
        self::assertStringContainsString('id="intake-summary-metrics"', $html);
        self::assertStringContainsString('id="intake-summary-insight"', $html);
        self::assertStringContainsString('dashboard-intake-stage', $html);
    }

    public function testDashboardScriptDeclaresExecutiveIntakeSummaryHelpers(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/dashboard.js');

        self::assertStringContainsString('deriveIntakeSummaryModel', $script);
        self::assertStringContainsString('renderIntakeSummary', $script);
        self::assertStringContainsString('data-intake-summary-shell', $script);
        self::assertStringContainsString('intake-summary-insight', $script);
    }
```

- [ ] **Step 2: Add a failing stylesheet assertion for the intake executive card**

Insert this method into `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\AppShellViewTest.php` after `testDashboardStylesDeclareThemeAwareSurfaceOverrides()`:

```php
    public function testDashboardStylesDeclareIntakeExecutiveCardTreatments(): void
    {
        $stylesheet = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/dashboard.css');

        self::assertStringContainsString('.dashboard-intake-stage', $stylesheet);
        self::assertStringContainsString('.dashboard-intake-metrics', $stylesheet);
        self::assertStringContainsString('.dashboard-intake-delta', $stylesheet);
        self::assertStringContainsString('@media (max-width: 767px)', $stylesheet);
    }
```

- [ ] **Step 3: Run the narrow view tests to confirm failure**

Run:

```powershell
php vendor/bin/phpunit tests/Views/DashboardViewTest.php tests/Views/AppShellViewTest.php
```

Expected:

```text
FAILURES!
... data-intake-summary-shell ...
... deriveIntakeSummaryModel ...
... .dashboard-intake-stage ...
```

- [ ] **Step 4: Commit the failing-test checkpoint**

Run:

```bash
git add tests/Views/DashboardViewTest.php tests/Views/AppShellViewTest.php
git commit -m "test: lock intake executive dashboard shell"
```

### Task 2: Implement The Intake Executive Markup And Surface Styling

**Files:**
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\views\dashboard\index.php`
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\public\assets\css\dashboard.css`
- Test: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\DashboardViewTest.php`
- Test: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\AppShellViewTest.php`

- [ ] **Step 1: Add the intake executive summary shell in the dashboard view**

Replace the current intake card body in `C:\Users\TESS LARON\Desktop\REVISED\views\dashboard\index.php`:

```php
    <article class="card dashboard-chart-panel dashboard-chart-panel-featured">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Twelve-month intake</span>
                <h3>Intake Trend</h3>
            </div>
            <p class="text-muted">Monitor monthly arrivals to spot surges before capacity tightens.</p>
        </div>
        <canvas id="intake-chart"></canvas>
    </article>
```

with:

```php
    <article class="card dashboard-chart-panel dashboard-chart-panel-featured dashboard-intake-panel">
        <div class="dashboard-section-heading">
            <div>
                <span class="field-label">Twelve-month intake</span>
                <h3>Intake Trend</h3>
            </div>
            <p class="text-muted">Review the latest monthly intake posture before kennel pressure tightens.</p>
        </div>
        <div class="dashboard-intake-stage" data-intake-summary-shell>
            <div class="dashboard-intake-metrics" id="intake-summary-metrics" aria-live="polite">
                <article class="dashboard-intake-metric dashboard-intake-metric-primary">
                    <span class="field-label">Latest intake</span>
                    <strong class="mono">--</strong>
                    <span class="text-muted">Waiting for intake data</span>
                </article>
                <article class="dashboard-intake-metric">
                    <span class="field-label">Vs previous month</span>
                    <strong>--</strong>
                    <span class="text-muted">Delta will appear here</span>
                </article>
                <article class="dashboard-intake-metric">
                    <span class="field-label">12-month peak</span>
                    <strong>--</strong>
                    <span class="text-muted">Peak month will appear here</span>
                </article>
            </div>
            <p class="dashboard-intake-insight text-muted" id="intake-summary-insight" aria-live="polite">
                Intake summary will appear once the chart data loads.
            </p>
            <canvas id="intake-chart"></canvas>
        </div>
    </article>
```

- [ ] **Step 2: Add the intake executive surface and responsive CSS**

Append these rules near the existing dashboard chart-panel styles in `C:\Users\TESS LARON\Desktop\REVISED\public\assets\css\dashboard.css`:

```css
.dashboard-intake-panel {
  gap: var(--space-4);
}

.dashboard-intake-stage {
  display: grid;
  gap: var(--space-4);
  padding: var(--space-4);
  border-radius: calc(var(--radius-lg) - 6px);
  border: 1px solid var(--color-border-light);
  background:
    radial-gradient(circle at 14% 18%, rgba(125, 211, 252, 0.18), transparent 34%),
    linear-gradient(180deg, color-mix(in srgb, var(--color-bg-elevated) 82%, var(--color-bg-secondary)) 0%, color-mix(in srgb, var(--color-bg-secondary) 84%, var(--color-bg-elevated)) 100%);
  overflow: hidden;
}

.dashboard-intake-metrics {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) repeat(2, minmax(0, 0.9fr));
  gap: var(--space-3);
}

.dashboard-intake-metric {
  display: grid;
  gap: var(--space-1);
  padding: var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border-light);
  background: color-mix(in srgb, var(--color-bg-secondary) 42%, var(--color-bg-elevated));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.dashboard-intake-metric-primary strong {
  font-size: clamp(2.4rem, 3.2vw, 3.4rem);
  line-height: 0.92;
}

.dashboard-intake-insight {
  max-width: 54ch;
  margin: 0;
}

.dashboard-intake-delta {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.4rem 0.7rem;
  border-radius: 999px;
  border: 1px solid transparent;
  font-size: var(--font-size-xs);
  font-family: var(--font-family-mono);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

@media (max-width: 767px) {
  .dashboard-intake-metrics {
    grid-template-columns: 1fr;
  }

  .dashboard-intake-stage {
    padding: var(--space-3);
  }
}
```

- [ ] **Step 3: Run the view tests to confirm the shell now passes**

Run:

```powershell
php vendor/bin/phpunit tests/Views/DashboardViewTest.php tests/Views/AppShellViewTest.php
```

Expected:

```text
FAILURES!
... deriveIntakeSummaryModel ...
... renderIntakeSummary ...
```

The markup and stylesheet assertions should now pass. The remaining failures should only be the new script-helper assertions from Task 1, which will be satisfied in Task 3.

- [ ] **Step 4: Commit the view-and-style shell checkpoint**

Run:

```bash
git add views/dashboard/index.php public/assets/css/dashboard.css tests/Views/DashboardViewTest.php tests/Views/AppShellViewTest.php
git commit -m "feat: add executive intake summary shell"
```

### Task 3: Derive And Render Executive Intake Metrics In The Dashboard Script

**Files:**
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\public\assets\js\dashboard.js`
- Test: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\DashboardViewTest.php`

- [ ] **Step 1: Add intake-summary derivation helpers to the dashboard script**

Insert these helpers above `mountChart()` in `C:\Users\TESS LARON\Desktop\REVISED\public\assets\js\dashboard.js`:

```js
  function formatPercentChange(value) {
    if (!Number.isFinite(value)) {
      return null;
    }

    const rounded = Math.round(value);
    return `${rounded > 0 ? '+' : ''}${rounded}%`;
  }

  function deriveIntakeSummaryModel(payload) {
    const labels = Array.isArray(payload?.labels) ? payload.labels : [];
    const values = Array.isArray(payload?.datasets?.[0]?.data)
      ? payload.datasets[0].data.map((value) => Number(value ?? 0))
      : [];

    if (labels.length === 0 || values.length === 0 || labels.length !== values.length) {
      return {
        isValid: false,
        insight: 'Intake data is not available for this reporting window yet.',
      };
    }

    const latestIndex = values.length - 1;
    const latestValue = values[latestIndex] ?? 0;
    const previousValue = latestIndex > 0 ? values[latestIndex - 1] ?? null : null;
    const peakValue = Math.max(...values);
    const peakIndex = values.indexOf(peakValue);
    const change = previousValue === null ? null : latestValue - previousValue;
    const percentChange = previousValue && previousValue > 0
      ? (change / previousValue) * 100
      : null;
    const direction = change === null || change === 0 ? 'flat' : (change > 0 ? 'up' : 'down');

    let insight = 'Intake held steady month over month.';
    if (latestValue === 0 && peakValue === 0) {
      insight = 'Intake data is available, but no recent variation stands out.';
    } else if (direction === 'up') {
      insight = latestValue >= peakValue
        ? 'Intake accelerated this month and matched the annual high.'
        : 'Intake accelerated this month, approaching the annual high.';
    } else if (direction === 'down') {
      insight = 'Intake softened this month, easing below last month\\'s pace.';
    }

    return {
      isValid: true,
      latestLabel: String(labels[latestIndex] ?? 'Latest'),
      latestValue,
      previousValue,
      change,
      percentChange,
      direction,
      peakLabel: String(labels[peakIndex] ?? 'Peak'),
      peakValue,
      insight,
    };
  }
```

- [ ] **Step 2: Add intake-summary DOM rendering**

Insert this renderer below `renderOccupancyBreakdown()` in `C:\Users\TESS LARON\Desktop\REVISED\public\assets\js\dashboard.js`:

```js
  function renderIntakeSummary(payload) {
    const shell = document.querySelector('[data-intake-summary-shell]');
    const metrics = document.getElementById('intake-summary-metrics');
    const insight = document.getElementById('intake-summary-insight');

    if (!shell || !metrics || !insight) {
      return;
    }

    const model = deriveIntakeSummaryModel(payload);

    if (!model.isValid) {
      metrics.innerHTML = `
        <article class="dashboard-intake-metric dashboard-intake-metric-primary">
          <span class="field-label">Latest intake</span>
          <strong class="mono">--</strong>
          <span class="text-muted">No validated monthly series</span>
        </article>
      `;
      insight.textContent = model.insight;
      return;
    }

    const deltaLabel = model.change === null
      ? 'No comparison'
      : `${model.change > 0 ? '+' : ''}${model.change} ${formatPercentChange(model.percentChange) ?? ''}`.trim();

    const deltaToneClass = model.direction === 'up'
      ? 'is-up'
      : (model.direction === 'down' ? 'is-down' : 'is-flat');

    metrics.innerHTML = `
      <article class="dashboard-intake-metric dashboard-intake-metric-primary">
        <span class="field-label">Latest intake</span>
        <strong class="mono">${escapeHtml(String(model.latestValue))}</strong>
        <span class="text-muted">${escapeHtml(model.latestLabel)}</span>
      </article>
      <article class="dashboard-intake-metric">
        <span class="field-label">Vs previous month</span>
        <strong><span class="dashboard-intake-delta ${deltaToneClass}">${escapeHtml(deltaLabel)}</span></strong>
        <span class="text-muted">${model.previousValue === null ? 'Need at least two months to compare' : 'Month-over-month movement'}</span>
      </article>
      <article class="dashboard-intake-metric">
        <span class="field-label">12-month peak</span>
        <strong>${escapeHtml(String(model.peakValue))}</strong>
        <span class="text-muted">${escapeHtml(model.peakLabel)}</span>
      </article>
    `;

    insight.textContent = model.insight;
  }
```

- [ ] **Step 3: Refine `mountChart()` so the intake chart gets a calmer executive treatment**

Update the dataset mapping and options inside `mountChart()` in `C:\Users\TESS LARON\Desktop\REVISED\public\assets\js\dashboard.js`:

```js
      data: {
        labels: payload.labels,
        datasets: payload.datasets.map((dataset, index) => {
          const accent = colors[index % colors.length];
          const isIntakeChart = id === 'intake-chart' && type === 'line';

          return {
            ...dataset,
            borderColor: accent,
            backgroundColor: type === 'line'
              ? alphaColor(accent, isIntakeChart ? 0.16 : 0.2)
              : colors,
            tension: isIntakeChart ? 0.42 : 0.35,
            fill: type === 'line',
            borderWidth: isIntakeChart ? 3 : 2,
            pointRadius: isIntakeChart ? 0 : 3,
            pointHoverRadius: isIntakeChart ? 5 : 4,
            pointBackgroundColor: accent,
          };
        })
      },
```

and:

```js
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: id !== 'intake-chart',
            labels: {
              color: palette().text
            }
          },
          tooltip: {
            backgroundColor: palette().surface,
            borderColor: palette().border,
            borderWidth: 1,
            titleColor: palette().textStrong,
            bodyColor: palette().text,
            padding: 12,
            displayColors: false,
          }
        },
        scales: type === 'doughnut'
          ? {}
          : {
              x: {
                ticks: { color: palette().text },
                grid: { color: alphaColor(palette().border, id === 'intake-chart' ? 0.45 : 1) }
              },
              y: {
                beginAtZero: true,
                ticks: { color: palette().text },
                grid: { color: alphaColor(palette().border, id === 'intake-chart' ? 0.45 : 1) }
              }
            }
      }
```

- [ ] **Step 4: Render the intake summary during dashboard refresh**

Update `renderDashboard()` in `C:\Users\TESS LARON\Desktop\REVISED\public\assets\js\dashboard.js`:

```js
  function renderDashboard(payload) {
    dashboardPayload = payload;
    renderStats(payload.stats);
    renderActivity(payload.activity);
    renderIntakeSummary(payload.charts.intake);

    const colors = [palette().primary, palette().success, palette().warning, palette().info, palette().danger];
    mountChart('intake-chart', 'line', payload.charts.intake, colors);
    mountChart('adoption-chart', 'bar', payload.charts.adoptions, colors);
    mountOccupancyChart(payload.charts.occupancy);
    mountChart('medical-chart', 'bar', payload.charts.medical, colors);
  }
```

- [ ] **Step 5: Run the narrow view tests to confirm the script assertions now pass**

Run:

```powershell
php vendor/bin/phpunit tests/Views/DashboardViewTest.php tests/Views/AppShellViewTest.php
```

Expected:

```text
OK
```

- [ ] **Step 6: Commit the intake-summary script checkpoint**

Run:

```bash
git add public/assets/js/dashboard.js tests/Views/DashboardViewTest.php
git commit -m "feat: render executive dashboard intake summary"
```

### Task 4: Finish Theme States And Perform Manual Smoke Verification

**Files:**
- Modify: `C:\Users\TESS LARON\Desktop\REVISED\public\assets\css\dashboard.css`
- Test: `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\AppShellViewTest.php`

- [ ] **Step 1: Add explicit delta-state and theme-aware intake styles**

Append these rules near the existing dark-theme dashboard overrides in `C:\Users\TESS LARON\Desktop\REVISED\public\assets\css\dashboard.css`:

```css
.dashboard-intake-delta.is-up {
  color: var(--color-accent-primary);
  background: rgba(14, 165, 233, 0.12);
  border-color: rgba(14, 165, 233, 0.18);
}

.dashboard-intake-delta.is-down {
  color: #B45309;
  background: rgba(245, 158, 11, 0.14);
  border-color: rgba(245, 158, 11, 0.18);
}

.dashboard-intake-delta.is-flat {
  color: var(--color-text-secondary);
  background: color-mix(in srgb, var(--color-bg-secondary) 72%, var(--color-bg-elevated));
  border-color: var(--color-border-light);
}

[data-theme="dark"] .dashboard-intake-stage {
  background:
    radial-gradient(circle at 16% 18%, rgba(96, 165, 250, 0.22), transparent 36%),
    linear-gradient(180deg, rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0.6));
}

[data-theme="dark"] .dashboard-intake-metric {
  background: rgba(15, 23, 42, 0.38);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}
```

- [ ] **Step 2: Expand the stylesheet assertion to cover theme states**

Update `testDashboardStylesDeclareIntakeExecutiveCardTreatments()` in `C:\Users\TESS LARON\Desktop\REVISED\tests\Views\AppShellViewTest.php`:

```php
    public function testDashboardStylesDeclareIntakeExecutiveCardTreatments(): void
    {
        $stylesheet = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/dashboard.css');

        self::assertStringContainsString('.dashboard-intake-stage', $stylesheet);
        self::assertStringContainsString('.dashboard-intake-metrics', $stylesheet);
        self::assertStringContainsString('.dashboard-intake-delta.is-up', $stylesheet);
        self::assertStringContainsString('[data-theme="dark"] .dashboard-intake-stage', $stylesheet);
        self::assertStringContainsString('@media (max-width: 767px)', $stylesheet);
    }
```

- [ ] **Step 3: Run the targeted tests one more time**

Run:

```powershell
php vendor/bin/phpunit tests/Views/DashboardViewTest.php tests/Views/AppShellViewTest.php
```

Expected:

```text
OK
```

- [ ] **Step 4: Perform the manual dashboard smoke check**

Run the local app and verify the intake card manually:

```powershell
cmd /c scripts\\start-app.cmd
```

Open:

```text
http://127.0.0.1:8000/dashboard
```

Verify:

```text
1. The intake card shows three summary blocks above the chart.
2. The latest-intake number is visually dominant.
3. The delta pill changes tone and still includes readable text.
4. The insight sentence updates in the DOM above the chart.
5. The card remains readable at a mobile-width viewport.
6. The card remains legible in both light and dark theme states.
```

- [ ] **Step 5: Commit the final polish checkpoint**

Run:

```bash
git add public/assets/css/dashboard.css tests/Views/AppShellViewTest.php
git commit -m "style: polish executive intake dashboard card"
```
