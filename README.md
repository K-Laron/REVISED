# Catarman Dog Pound & Animal Shelter Management System

Vanilla PHP 8.2 application scaffolded from the local PRD and implementation guide.

## Current Runtime Notes

- As of March 24, 2026, the application uses server-side PHP sessions for both browser and authenticated API access.
- JWT bearer-token authentication is not part of the current runtime and `firebase/php-jwt` is not installed.
- Treat this README and `IMPLEMENTATION_GUIDE.md` as the source of truth for current behavior. `PRD_Catarman_Dog_Pound.md` remains useful product history, but some original auth references are superseded by the implemented system.

## Local Setup

1. Copy `.env.example` to `.env` and update database and mail credentials.
2. Install dependencies with Composer.
3. Import `database_schema.sql` and `seeders.sql` into a MySQL 8 database named `catarman_shelter`.
4. Start the PHP development server:

```powershell
php -S localhost:8000 -t public
```

If you are upgrading an existing database instead of importing a fresh one, apply [C:\Users\TESS LARON\Desktop\REVISED\database\migrations\2026_03_25_000001_add_usernames_to_users.sql](/C:/Users/TESS%20LARON/Desktop/REVISED/database/migrations/2026_03_25_000001_add_usernames_to_users.sql) before restarting the app.

For local development only, it is fine to keep:

- `APP_URL=http://localhost:8000`
- `mail_delivery_mode=log_only`
- blank `TRUSTED_PROXIES`

Browser automation tooling such as Playwright browser caches is intentionally local-only and is not committed to this repository. Install or generate it in your own workspace when you need smoke tests.

## Quick Start Scripts

- Run [C:\Users\TESS LARON\Desktop\REVISED\start-app.vbs](/C:/Users/TESS%20LARON/Desktop/REVISED/start-app.vbs) to start the PHP server in the background and automatically open the adopter landing page at `/adopt` in your default browser.
- Run [C:\Users\TESS LARON\Desktop\REVISED\stop-app.vbs](/C:/Users/TESS%20LARON/Desktop/REVISED/stop-app.vbs) to stop that background PHP server.
- The scripts track the server PID in `storage/runtime/app-server.json` and write logs to `storage/runtime/`.

## Current Scope

The current codebase now includes the full shelter workflow stack:

- Authentication, RBAC, sessions, CSRF, and audit logging
- Dashboard, animals, kennels, medical, adoptions, billing, inventory, users, reports, and settings
- Public adopter portal, backups, restore controls, global search, and browser-validated smoke-tested flows

## Production Release Checklist

- Set a real `APP_URL` with `https://`.
- Generate and set a unique `APP_KEY`.
- Set `TRUSTED_PROXIES` if TLS terminates at Nginx, Apache, a load balancer, or another reverse proxy.
- Reduce `SESSION_LIFETIME` to `60` minutes or less for admin use.
- Replace the seeded admin password `ChangeMe@2025`.
- Configure SMTP if password-reset and notification email delivery must work outside local QA.
- Confirm `storage/`, `storage/logs/`, `storage/sessions/`, and `storage/backups/` are writable by the PHP runtime user.
- Rehearse backup creation and restore against a cloned database, not the live one.
- Run the browser smoke suite after deployment and after every environment change.

## Deployment Notes

- Prefer HTTPS at the edge and keep PHP behind the web server or reverse proxy.
- If the app runs behind a proxy, set `TRUSTED_PROXIES` to the proxy IP or CIDR so secure-cookie and client-IP detection stay correct.
- Keep `APP_DEBUG=false` in production.
- `mail_delivery_mode=log_only` is acceptable for local and staging use, but not for user-facing production recovery unless you have a documented manual reset process.
- Backups are available from `/settings`, but restore is destructive and should only be tested on a cloned database first.
