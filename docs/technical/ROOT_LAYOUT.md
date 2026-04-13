# Root Layout

## What Belongs At The Root

- Runtime entry files:
  - [composer.json](/C:/Users/TESS%20LARON/Desktop/REVISED/composer.json)
  - [composer.lock](/C:/Users/TESS%20LARON/Desktop/REVISED/composer.lock)
  - [package.json](/C:/Users/TESS%20LARON/Desktop/REVISED/package.json)
  - [package-lock.json](/C:/Users/TESS%20LARON/Desktop/REVISED/package-lock.json)
  - [phpunit.xml](/C:/Users/TESS%20LARON/Desktop/REVISED/phpunit.xml)
  - [.env.example](/C:/Users/TESS%20LARON/Desktop/REVISED/.env.example)
- Living system docs:
  - [README.md](/C:/Users/TESS%20LARON/Desktop/REVISED/README.md)
  - [ARCHITECTURE.md](/C:/Users/TESS%20LARON/Desktop/REVISED/ARCHITECTURE.md)
  - [API_ROUTES.md](/C:/Users/TESS%20LARON/Desktop/REVISED/API_ROUTES.md)
  - [IMPLEMENTATION_GUIDE.md](/C:/Users/TESS%20LARON/Desktop/REVISED/IMPLEMENTATION_GUIDE.md)
  - [VALIDATION_RULES.md](/C:/Users/TESS%20LARON/Desktop/REVISED/VALIDATION_RULES.md)
  - [PRD_Catarman_Dog_Pound.md](/C:/Users/TESS%20LARON/Desktop/REVISED/PRD_Catarman_Dog_Pound.md)
  - [system_summary.md](/C:/Users/TESS%20LARON/Desktop/REVISED/system_summary.md)
  - [llm_context.md](/C:/Users/TESS%20LARON/Desktop/REVISED/llm_context.md)
- Technical layout docs:
  - [PAGE_LAYOUTS.md](/C:/Users/TESS%20LARON/Desktop/REVISED/docs/technical/PAGE_LAYOUTS.md)
  - [ROOT_LAYOUT.md](/C:/Users/TESS%20LARON/Desktop/REVISED/docs/technical/ROOT_LAYOUT.md)
- Schema and seed files:
  - [database_schema.sql](/C:/Users/TESS%20LARON/Desktop/REVISED/database/database_schema.sql)
  - [seeders.sql](/C:/Users/TESS%20LARON/Desktop/REVISED/database/seeders.sql)
- Convenience launchers:
  - [scripts/start-app.ps1](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/start-app.ps1)
  - [scripts/stop-app.ps1](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/stop-app.ps1)

## What Should Not Stay At The Root

- manuscript drafts
- generated chapter files
- temporary exports
- tool caches
- ad-hoc one-off scripts that are not part of the app runtime
- screenshots, logs, and local scratch artifacts

Those should move into:

- a proper app directory like `scripts/`, `docs/`, or `storage/`, or
- a local quarantine folder such as `_for-deletion/YYYY-MM-DD-description/`

## Quarantine Rule

- If a file is clearly not part of the running app but you are not ready to delete it, move it into a visible `_for-deletion/` folder.
- Keep a `MANIFEST.md` inside the quarantine folder that explains:
  - what was moved
  - when it was moved
  - why it was quarantined
- `_for-deletion/` should remain local-only unless there is a deliberate reason to version it.

## Root Hygiene Checklist

- Before adding a new root file, ask:
  - Is this required to boot, configure, test, or understand the app?
  - Does it belong in an existing folder instead?
  - Is it historical or local-only clutter?
- If the answer is "local-only" or "not runtime-relevant", quarantine it instead of leaving it at the root.
