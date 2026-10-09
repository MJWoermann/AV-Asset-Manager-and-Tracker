# AV Asset Manager & Tracker

Self-hosted Progressive Web App for AV equipment stocktake and asset management.

**Phase 1 (Stocktake MVP):** local auth & roles, assets with parent/child racks, custom field templates, barcode scan-to-event, list comparison reports, CSV/Excel import-export, per-user asset table columns, asset audit History tab, Sanctum REST API, online-only installable PWA.

## Requirements

- PHP 8.3+ with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql` or `pdo_sqlite`, `tokenizer`, `xml`, `zip`
- Composer 2
- Node.js 20.19+ and npm
- MariaDB 10.11+ (production) or SQLite (local)

## Quick start (local)

```bash
composer install
cp .env.example .env
php artisan key:generate

# SQLite (default local):
# ensure DB_CONNECTION=sqlite and database/database.sqlite exists

# Migrates schema (including users.preferences), seeds roles/reference data, creates admin
php artisan app:install --name="Administrator" --email=admin@example.com --password=ChangeMeNow!
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000` and sign in with the admin account.

No extra `.env` keys are required for column preferences or the asset History tab. Preferences default until each user saves columns from the **Columns** control on Assets or list pages.

## Upgrading an existing install

After pulling a new release (local or server):

```bash
composer install
npm ci && npm run build
php artisan migrate
php artisan optimize:clear   # or config/route/view cache in production — see deploy guide
```

`php artisan migrate` applies additive schema changes such as `users.preferences` (JSON, nullable). Existing users keep working with default columns until they customise them. Asset History uses existing `audit_logs` rows; no backfill is required.

Production upgrade steps: [docs/deploy-ubuntu-nginx.md](docs/deploy-ubuntu-nginx.md) §7 Updates.

## Production (Ubuntu + Nginx)

See [docs/deploy-ubuntu-nginx.md](docs/deploy-ubuntu-nginx.md).

## Roles

| Role | Capabilities |
|------|----------------|
| `admin` | Full access, user management |
| `inventory_manager` | Assets, lists, import/export, reports |
| `scanner` | Scan sessions, view inventory, edit asset details |

## REST API

Bearer tokens via Sanctum. See [docs/api.md](docs/api.md).

```bash
curl -X POST /api/auth/token \
  -H "Accept: application/json" \
  -d "email=admin@example.com&password=...&device_name=cli"
```

## AI-assisted development

- [CLAUDE.md](CLAUDE.md) — conventions for Claude Code
- [.cursor/rules/](.cursor/rules/) — Cursor project rules

## Deferred (Phase 2+)

Guest QR invitations, RU slot visualiser, maintenance records with files, manuals/photos gallery, Microsoft 365 SSO, offline scan sync.

## Licence

Proprietary — internal use.
