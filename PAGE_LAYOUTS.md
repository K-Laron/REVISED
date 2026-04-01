# Civic Ledger Page Layouts

This document describes the current implemented page structure after the Civic Ledger flagship UI pass. It covers layout responsibilities only; routes, controllers, and API contracts are documented separately.

## Shared Layout System

- `views/layouts/app.php` renders the authenticated operations shell.
- `views/layouts/public.php` renders the public adopter-facing shell.
- Both layouts expose the same `data-ui-theme="civic-ledger"` marker and shared typography stack.
- `Lexend` is reserved for headings and navigation, `Source Sans 3` for body copy, and `JetBrains Mono` for operational metadata such as IDs, timestamps, badges, and counters.

## Internal Operations Shell

- The left rail is the persistent navigation spine for authenticated users.
- The top command bar surfaces the current page title, user context, and high-signal utility actions.
- Breadcrumb trails are enhanced centrally in the authenticated shell so prior path segments are clickable and accidental breadcrumb navigation can restore in-progress form input from session storage.
- Shared shell styling lives in `public/assets/css/components.css`, `public/assets/css/layout.css`, and `public/assets/css/responsive.css`.

## Dashboard

- `views/dashboard/index.php` is structured as an executive briefing surface.
- The page is organized into a KPI band, a command/action deck, chart panels, and a recent activity ledger.
- `public/assets/js/dashboard.js` continues to own chart and activity rendering without changing endpoint contracts.

## Global Search

- `views/search/index.php` is structured as a search command center.
- The page pairs a query-focused command shell with module filters, guidance states, and ledger-style grouped results.
- `public/assets/js/search.js` retains the existing request flow and renders results into the updated surface.

## Settings

- `views/settings/index.php` is structured as an operations console.
- The page groups configuration areas into readiness, profile, and backup-ledger zones while preserving the existing forms, IDs, and status hooks.
- `public/assets/js/settings.js` still drives the current settings, backup, and readiness interactions.

## Public Landing Page

- `views/portal/landing.php` remains the public flagship entry point for adopters.
- The hero, trust ribbon, featured proof card, and featured-animal carousel now share the Civic Ledger system with a warmer public tone.
- `public/assets/js/portal.js` preserves the current CTA and carousel behavior while updating the visual state classes used by the redesigned slides.
