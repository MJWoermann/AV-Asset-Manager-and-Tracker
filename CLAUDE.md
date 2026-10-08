# AV Asset Manager — Claude / AI coding guide

## Product

Self-hosted LEMP Progressive Web App for AV equipment stocktake and asset management (inspired by Asset Tiger / Snipe-IT). Phase 1 is an **online-only** stocktake MVP.

## Stack

- PHP 8.3+, Laravel (current secure release; originally planned as 11)
- Blade + Alpine.js + Tailwind CSS (TP brand tokens in `tailwind.config.js`)
- MariaDB in production; SQLite acceptable for local/dev
- Laravel Sanctum Bearer tokens for `/api/*`
- Spatie Laravel Permission roles: `admin`, `inventory_manager`, `scanner`
- Maatwebsite Excel for CSV/XLSX
- `html5-qrcode` for mobile barcode scanning
- `vite-plugin-pwa` — installable shell only; **no offline data sync**

## Domain glossary

| Term | Meaning |
|------|---------|
| Asset | Tracked equipment item |
| Parent / child | Child assets belong to at most one parent (e.g. gear in a rack). Children inherit location from parent |
| Item type | Template for assets (LX Fixture, Rack/RoadCase, …) |
| Custom field set | Reusable extra fields attached to item types |
| Inventory list | Source/expected stock table |
| Event list | Scanned/usage table (stocktake event) |
| Scan session | Active user session scanning into an event list, optionally compared to an inventory list |
| Audit log | Immutable append-only change history |

## Hard rules

1. **Never update or delete `audit_logs`.** `AuditLog` model throws on update/delete.
2. Do not put secrets in the repo (`.env`, credentials, tokens).
3. Do not implement Phase 2 features unless asked: Guest QR tokens, RU visualiser, maintenance file records, asset manuals/photos gallery, M365 SSO, offline scan sync.
4. Prefer indexed identifier lookups (`fmi_ast`, `tp_barcode`, `rig_tag`, `device_sn`, IP/MAC) over leading-wildcard `LIKE` for primary scan paths.
5. Comparison of large lists must use set-based SQL / keyed collections, not N+1 loops.
6. Uploads: jpg/png/pdf/docx/xlsx/csv, max 25MB. Phase 1 import focuses on xlsx/csv.
7. Brand: black/white primary; turquoise `#00adb7` sparingly for emphasis. Use Heroicons-style SVG or simple icons; Tailwind utilities with `brand-*` colours.
8. Match existing Laravel structure: controllers thin, services for scan/compare, Form Request validation where already used.

## Key paths

- Models: `app/Models/`
- Scan / compare: `app/Services/ScanService.php`, `ListComparisonService.php`
- Web routes: `routes/web.php`
- API: `routes/api.php`
- Seed / install: `php artisan app:install`
- Deploy: `docs/deploy-ubuntu-nginx.md`
- API reference: `docs/api.md`

## Local setup (Windows / any)

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
touch database/database.sqlite   # or configure MariaDB
php artisan app:install --email=admin@example.com --password=secret123
npm install && npm run build
php artisan serve
```

## Roles

| Role | Access |
|------|--------|
| admin | Full, including user management |
| inventory_manager | Lists, import/export, reports, CRUD assets/types |
| scanner | Scan, view inventory, edit asset details |

## Testing mindset

- Scan of a rack must expand children (indented / `is_child_expand`).
- Duplicate scan on same event list must warn, not double-insert.
- Comparison report has three sections: matched, inventory-only, scanned-only.
