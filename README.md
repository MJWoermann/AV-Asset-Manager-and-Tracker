# AV Asset Manager & Tracker

Self-hosted Progressive Web App for AV equipment stocktake and asset management: local auth & roles (including external repairers), assets with parent/child racks, custom field templates, barcode scan-to-event, list comparison, unknown-scan follow-up (including link to existing asset), asset checkout/return (hire/repair/other), maintenance/repairs/spares, departments, CSV/Excel import-export, per-user asset table columns, audit History, Sanctum REST API, optional SSO and email digests, online-only installable PWA.

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

**Password requirement:** new passwords (including `app:install`, Admin → Users, registration, and password reset/change) must be at least **8 characters**, with **one uppercase letter** and **one number or symbol**. Example that meets the rule: `ChangeMeNow!`.

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

`php artisan migrate` applies additive schema changes such as `users.preferences` (JSON, nullable) and multi-department isolation (`departments`, `department_user`, and `department_id` on operational tables). Existing rows are backfilled into a default **General** department; non-admin users are attached to it. Assign users to departments under Admin → Users, and manage departments under Admin → Departments. Asset History uses existing `audit_logs` rows; new history entries store `department_id` when available.

Production upgrade (one command): `sudo bash scripts/update.sh` — see [docs/deploy-ubuntu-nginx.md](docs/deploy-ubuntu-nginx.md) §7 Updates.

## Features and usage

Staff-oriented overview of use cases, roles, and day-to-day workflows: [docs/features-and-usage.md](docs/features-and-usage.md).

Product roadmap (preview/developer features, deferred work, planned integrations): [docs/roadmap.md](docs/roadmap.md). Copilot Studio setup: [docs/mcp-copilot-studio.md](docs/mcp-copilot-studio.md).

Prepare messy vendor or legacy spreadsheets for import (field catalog and copy-paste AI prompts): [docs/import-spreadsheet-prep.md](docs/import-spreadsheet-prep.md).

## Troubleshooting

Symptom → checks → fix (login, camera/HTTPS, uploads, mail/queue, common env issues): [docs/troubleshooting.md](docs/troubleshooting.md).

UI / interaction habits for developers and AI agents: [docs/ui-interaction-guidelines.md](docs/ui-interaction-guidelines.md).

## Organizational rollout

People-and-process guide for sponsors and inventory leads (ADKAR, training, reinforcement so data stays current): [docs/organizational-deployment.md](docs/organizational-deployment.md). Complements the technical production guide below.

## Production (Ubuntu + Nginx)

See [docs/deploy-ubuntu-nginx.md](docs/deploy-ubuntu-nginx.md). Production hardening and security review notes: [docs/security-hardening.md](docs/security-hardening.md).

**Mobile scanning needs HTTPS.** Phone/tablet cameras only work in a secure context. On a LAN without public DNS, set up a private CA or self-signed cert and trust it on each device: [docs/self-signed-tls.md](docs/self-signed-tls.md).

## Hardware sizing

For a typical LAN LEMP host with **~10,000 assets** and **~20 concurrent users**: **4 vCPU, 16 GB RAM, ~100 GB SSD** system disk, plus separate attachment/backup storage (often NAS). Disk is driven by manuals and backup retention, not asset row count.

Full assumptions, minimum/comfortable tiers, and PHP/MariaDB tuning notes: [docs/hardware-sizing.md](docs/hardware-sizing.md).

## Roles

System roles are the defaults. Administrators edit their permissions (except the administrator role, which keeps full operational access) and create custom roles under **Admin → Roles** (profile menu and the admin hub). Each user has one role. Developer features for non-admins are granted on the role. Administrators still opt in on Profile → Developer features.

| Role | Seeded access |
|------|----------------|
| `admin` | Full operational access, users, roles, departments, email/SSO/storage settings |
| `inventory_manager` | Assets (including delete), lists, import/export, reports, repairs/spares/procedures. Unknown-scan purge and stock-movement edits stay off until granted |
| `scanner` | Scan sessions, view inventory, edit asset details, checkout/return |
| `external_repairer` | Maintenance hub + repairs assigned to them (and those assets’ show pages only) |

## REST API

Bearer tokens via Sanctum. Each call needs the role permission and a token ability. A `*` token does not override a missing role permission. Full reference: [docs/api.md](docs/api.md).

> **For AI assistants:** Share **`docs/api.md`** as the source of truth for this app’s REST API (auth, obtain/revoke tokens, abilities, endpoints, status codes, curl). It covers the stocktake/integrations surface under `/api`.

## Copilot Studio / M365 Copilot (MCP)

Connect Microsoft Copilot Studio to this app over Model Context Protocol (Streamable HTTP at `/mcp/av-assets`). Setup, OAuth Dynamic discovery, sample agent instructions, and Power Apps OpenAPI fallback: [docs/mcp-copilot-studio.md](docs/mcp-copilot-studio.md). Status: [docs/roadmap.md](docs/roadmap.md).

## Single sign-on (optional)

Google Workspace and Microsoft Entra (single-tenant) OAuth for existing users. See [docs/sso-setup.md](docs/sso-setup.md).

## Email integration (optional)

Developer-flagged Gmail / Outlook Graph / SMTP notifications for MAC onboarding and maintenance events, inbound helpdesk reply logging, and (when integration is enabled with credentials) configurable **warranty / test & tag / low-stock digests** via Admin → Email (`email:send-digests`, scheduled daily). See [docs/email-setup.md](docs/email-setup.md). Requires `schedule:run` (and optionally `queue:work`) in production.

```bash
curl -X POST /api/auth/token \
  -H "Accept: application/json" \
  -d "email=admin@example.com&password=...&device_name=cli"
```

## AI-assisted development

- [CLAUDE.md](CLAUDE.md) — conventions for Claude Code
- [.cursor/rules/](.cursor/rules/) — Cursor project rules

## Preview features and deferred work

Connections, RU visualiser, labels, asset **Stock**/consumables, and the email-integration flag ship as opt-in **Developer features** on each user’s Profile. Always-on areas include checkout, repairs, spares, departments, files/manuals, and unknown-scan link. Optional Google / Microsoft Entra SSO: [docs/sso-setup.md](docs/sso-setup.md) (existing users only — no JIT provisioning).

Full status table (preview, deferred, planned): [docs/roadmap.md](docs/roadmap.md). Copilot Studio setup: [docs/mcp-copilot-studio.md](docs/mcp-copilot-studio.md).

## Licence

Proprietary — internal use.
