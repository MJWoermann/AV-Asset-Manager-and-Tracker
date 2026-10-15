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

Production upgrade (one command): `sudo bash scripts/update.sh` — see [docs/deploy-ubuntu-nginx.md](docs/deploy-ubuntu-nginx.md) §7 Updates.

## Features and usage

Staff-oriented overview of use cases, roles, and day-to-day workflows: [docs/features-and-usage.md](docs/features-and-usage.md).

## Production (Ubuntu + Nginx)

See [docs/deploy-ubuntu-nginx.md](docs/deploy-ubuntu-nginx.md). Production hardening and security review notes: [docs/security-hardening.md](docs/security-hardening.md).

## Hardware sizing

For a typical LAN LEMP host with **~10,000 assets** and **~20 concurrent users**: **4 vCPU, 16 GB RAM, ~100 GB SSD** system disk, plus separate attachment/backup storage (often NAS). Disk is driven by manuals and backup retention, not asset row count.

Full assumptions, minimum/comfortable tiers, and PHP/MariaDB tuning notes: [docs/hardware-sizing.md](docs/hardware-sizing.md).

## Roles

| Role | Capabilities |
|------|----------------|
| `admin` | Full access, user management |
| `inventory_manager` | Assets, lists, import/export, reports |
| `scanner` | Scan sessions, view inventory, edit asset details |

## REST API

Bearer tokens via Sanctum. See [docs/api.md](docs/api.md).

## Single sign-on (optional)

Google Workspace and Microsoft Entra (single-tenant) OAuth for existing users. See [docs/sso-setup.md](docs/sso-setup.md).

## Email integration (optional)

Developer-flagged Gmail / Outlook Graph / SMTP notifications for MAC onboarding and maintenance events, plus inbound helpdesk reply logging. See [docs/email-setup.md](docs/email-setup.md). Requires `schedule:run` (and optionally `queue:work`) in production.

```bash
curl -X POST /api/auth/token \
  -H "Accept: application/json" \
  -d "email=admin@example.com&password=...&device_name=cli"
```

## AI-assisted development

- [CLAUDE.md](CLAUDE.md) — conventions for Claude Code
- [.cursor/rules/](.cursor/rules/) — Cursor project rules

## Preview features and deferred work

Connections, RU visualiser, shelf tags, and stock/consumables ship as opt-in **Developer features** on each user’s Profile (see [features and usage](docs/features-and-usage.md)). Optional Google / Microsoft Entra SSO: [docs/sso-setup.md](docs/sso-setup.md).

Still deferred: offline scan sync.

## Licence

Proprietary — internal use.
