# Root Layout

Date: 2026-04-02

Purpose:
- Keep the repository root readable as an application repository.
- Prevent manuscript, export, and temporary tooling artifacts from mixing with runtime files.
- Preserve a simple quarantine path for local cleanup without deleting files.

## What Belongs At The Root

Keep only high-signal project files and top-level app folders here:

- Runtime and source folders:
  - `src/`
  - `config/`
  - `routes/`
  - `views/`
  - `public/`
  - `storage/`
  - `database/`
  - `tests/`
  - `docs/`
- Dependency and environment folders:
  - `vendor/`
  - `node_modules/`
  - `.codex/`
  - `.composer/`
- Core root files:
  - `.env`
  - `.env.example`
  - `.gitignore`
  - `.htaccess`
  - `composer.json`
  - `composer.lock`
  - `package.json`
  - `package-lock.json`
  - `phpunit.xml`
  - `README.md`
  - `PAGE_LAYOUTS.md`
  - `API_ROUTES.md`
  - `ARCHITECTURE.md`
  - `IMPLEMENTATION_GUIDE.md`
  - `PRD_Catarman_Dog_Pound.md`
  - `VALIDATION_RULES.md`
  - `system_summary.md`
  - `llm_context.md`
  - `database_schema.sql`
  - `seeders.sql`
  - `start-app.vbs`
  - `stop-app.vbs`

## What Should Not Stay At The Root

Move these into a dedicated working folder or quarantine them under `_for-deletion/`:

- Manuscript files:
  - chapter `.docx` and `.md` files
  - abstract, appendix, CV, questionnaire, and similar capstone artifacts
- Generated exports:
  - temporary PDFs
  - rendered diagrams
  - chapter generation output
  - browser screenshots and tool output
- One-off local utilities:
  - ad-hoc scripts made for a single local task
  - cookies or session dumps
  - failed command output files
- Tool temp folders:
  - `.tmp/`
  - `output/`
  - old local worktree leftovers when no longer needed

## Quarantine Rule

Use this path for non-system clutter you do not want to delete yet:

- `_for-deletion/YYYY-MM-DD-description/`

Current quarantine:

- `_for-deletion/2026-04-02-root-cleanup/`

Rules:

- Move files there instead of deleting them when you are unsure.
- Add a `MANIFEST.md` describing what was moved and why.
- Keep `_for-deletion/` visible in the workspace.
- Ignore `_for-deletion/` locally through `.git/info/exclude` unless you explicitly want to version it.

## Root Hygiene Checklist

Before adding a new root file, ask:

1. Is this required to run, configure, test, or document the current system?
2. Is this meant for long-term team use rather than one-time local work?
3. Would a new developer expect to find this at the root?

If the answer is `no`, do not leave it at the root.
