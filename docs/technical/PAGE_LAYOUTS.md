# Civic Ledger Page Layouts

## Shared Layout System

- Authenticated layout: [views/layouts/app.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/layouts/app.php)
- Public layout: [views/layouts/public.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/layouts/public.php)
- Shared design assets:
  - [public/assets/css/variables.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/variables.css)
  - [public/assets/css/layout.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/layout.css)
  - [public/assets/css/responsive.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/responsive.css)
  - [public/assets/css/dark-mode-overrides.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/dark-mode-overrides.css)

Typography:

- Headings: `Lexend`
- Body UI copy: `Source Sans 3`
- Technical labels and metrics: `JetBrains Mono`

## Internal Operations Shell

The authenticated shell currently provides:

- persistent left sidebar navigation
- soft-navigation runtime
- breadcrumb enhancement with clickable hierarchy links
- form draft recovery on breadcrumb-triggered navigation
- unread-only notification panel
- theme switching with first-paint handoff
- sidebar scroll-position persistence during shell swaps

Key files:

- [views/partials/sidebar.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/partials/sidebar.php)
- [views/partials/header.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/partials/header.php)
- [public/assets/js/core/app-shell.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/core/app-shell.js)
- [public/assets/js/core/app-breadcrumbs.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/core/app-breadcrumbs.js)
- [public/assets/js/core/app-navigation.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/core/app-navigation.js)
- [public/assets/js/notifications.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/notifications.js)

## Dashboard

- View: [views/dashboard/index.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/dashboard/index.php)
- Script: [public/assets/js/dashboard.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/dashboard.js)
- Styles: [public/assets/css/dashboard.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/dashboard.css)

Current dashboard composition:

- hero briefing with operator and breadcrumb
- KPI stat grid
- twelve-month intake line chart
- quick-actions deck
- enhanced kennel occupancy doughnut with center metric and breakdown rail
- recent activity split into feed plus digest
- adoption pipeline chart
- medical procedures chart

Data source:

- `GET /api/dashboard/bootstrap`

Charting:

- [public/assets/vendor/chart.js/chart.umd.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/vendor/chart.js/chart.umd.js)

## Global Search

- View: [views/search/index.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/search/index.php)
- Script: [public/assets/js/search.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/search.js)
- Styles: [public/assets/css/search.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/search.css)

Current layout traits:

- command-shell page intro
- search query band
- module chip filters
- secondary per-module filter grid
- empty/loading/no-results states
- ledger-style grouped result sections

## Settings

- View: [views/settings/index.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/settings/index.php)
- Script: [public/assets/js/settings.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/settings.js)
- Styles: [public/assets/css/settings.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/settings.css)

Current layout traits:

- runtime operations hero
- health and profile summary cards
- editable system configuration panel
- backup ledger with restore-policy note
- maintenance mode console
- readiness board
- operations notes panel

## Public Landing Page

- Public layout: [views/layouts/public.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/layouts/public.php)
- Portal styling: [public/assets/css/portal.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/portal.css)
- Portal script: [public/assets/js/portal.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/portal.js)

Current public surface:

- `/adopt` is the real public portal entry
- `/` is still a minimal placeholder welcome page in [views/welcome.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/welcome.php)
- public shell supports theme switching, responsive navigation, and handoff into either adopter pages or the authenticated system
