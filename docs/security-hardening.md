# Security hardening and best practices

This guide evaluates the current AV Asset Manager codebase and deploy/setup instructions for areas that could be abused, then recommends hardening steps for production (typically LAN LEMP). It covers both **authorization / confidentiality** and **availability** (intentional or accidental actions that crash, hang, or slow the host for other users). It is defensive guidance only — not a penetration-test report or exploit walkthrough.

**Code baseline for the availability review:** git commit `1c18108` (*Make Cloud Agent installs boot Laravel*, 2026-10-18). Re-check this section after large import/export/scan/storage changes.

Related docs: [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md), [hardware-sizing.md](hardware-sizing.md), [sso-setup.md](sso-setup.md), [api.md](api.md).

---

## Threat model (assumed)

| Assumption | Implication |
|------------|-------------|
| Hosted on an internal LAN or VPN, optionally with TLS | Internet exposure raises severity of every finding below |
| Trusted staff roles: `admin`, `inventory_manager`, `scanner` | Privilege separation only works if users cannot self-enroll into full access; **trusted users can still DoS the shared host** via heavy features |
| Attachments and backups may hold manuals, PO notes, and password hashes | Treat storage disks and backup archives as confidential |
| Guest Kanban QR may be printed and shared | Anyone with a live token URL can adjust that asset’s stock |
| Shared LEMP host (~20 concurrent users, PHP-FPM pool) | One long FPM/worker job or disk-fill path affects scanners and stocktake for everyone — see [hardware-sizing.md](hardware-sizing.md) |

---

## Executive summary

The app already has useful controls: Spatie roles on many admin routes, Sanctum + throttles on API token issuance, login lockout, CSRF on web forms, UUID attachment paths, hashed guest stock tokens, SSO domain allow-lists, and append-only audit-log design.

**P0/P1 hardening is implemented in code:** public registration defaults off (`ALLOW_REGISTRATION=false`), authenticated web/API access requires an operational role permission (roleless accounts get 403), API tokens cannot exceed that role, developer features for non-admins come from the role, connection/RU writes require `connections:write` plus the feature grant, URL file downloads allow only `http`/`https`, and guest stock QR can be disabled with `STOCK_GUEST_ENABLED=false`.

**Email verification is intentionally not used** for access control. Isolated LEMP installs often have no outbound mail; `User` does not implement `MustVerifyEmail`. Admins create users (or run `app:install`) with `email_verified_at` set and a role assigned — no mail required.

**Availability:** Auth and role gates are in good shape. **A1–A7 are mitigated in code** (debug sinks removed; import/export/compare caps; prepared rows off-session; backup/migrate unique + queued by default; `DB_QUEUE_RETRY_AFTER=3900`). Admins tune limits with `php artisan app:performance-limits` — see [Managing performance limits](#managing-performance-limits). **A8–A10 remain open recommendations** (scan UI, browse side-loads, search wildcards). Details: [Availability and multi-user impact](#availability-and-multi-user-impact).

---

## Findings (code and setup evaluation)

Severity reflects impact if the host is reachable by untrusted users (or a compromised staff account). On a tightly firewalled LAN with registration disabled, Critical items become lower priority but should still be fixed.

### Critical (P0 — mitigated)

| ID | Area | Status |
|----|------|--------|
| C1 | Public registration | **Mitigated** — `ALLOW_REGISTRATION` defaults false; register UI hidden; controller returns 404 when off |
| C2 | Email verification not enforced | **By design** — no `MustVerifyEmail`; access is role-gated, not mail-gated |
| C3 | Roleless authenticated access | **Mitigated** — staff routes require at least one operational permission; roleless users get 403 |

### High (P1 — mitigated / residual)

| ID | Area | Status |
|----|------|--------|
| H1 | API authorization incomplete | **Mitigated** — Sanctum routes require a role permission and a token ability; `*` on a token does not override a missing permission |
| H2 | File download / search IDOR | **Pragmatic** — `files:read` may search/download/thumbnail; no per-file ownership (acceptable for this sensitivity) |
| H3 | External URL redirect | **Mitigated** — `download()` allows only `http`/`https` schemes before `redirect()->away()` |
| H4 | Self-service developer features | **Mitigated** — only admins toggle their own flags; other users receive developer features from a role, which they cannot edit |
| H5 | Feature-gated writes without permission | **Mitigated** — feature-gated writes require the feature grant and the operational permission (for example `connections:write`) |
| H6 | Web asset/file/scan mutations under-authorized | **Mitigated** — route `permission:` checks follow `AccessPermissions`; roleless users still get 403 |

### Medium

| ID | Area | Evidence | Risk |
|----|------|----------|------|
| M1 | Guest stock kill-switch | **Mitigated** — `STOCK_GUEST_ENABLED` (default true) via `StockTracking::guestSurfaceEnabled()`; set false to 404 guest routes | Keep tokens revoked when unused |
| M2 | Registration / password-reset throttle gaps | Register unthrottled; `forgot-password` outside SSO throttle group; login uses 5-attempt lockout in `LoginRequest` | Signup spam; reset-email abuse |
| M3 | Weak first-admin password path | **Mitigated** — `Password::defaults()` (min 8, one uppercase, one number or symbol) applies to register, reset, profile update, Admin → Users, and `app:install` | Rotate install password; tighten further via [Password complexity](#password-complexity) |
| M4 | SSO email binding | `SocialAuthService` matches by email; Azure “verified” is weaker than Google’s `email_verified` check | IdP/email edge cases → wrong account session |
| M5 | Production defaults in docs | Deploy guide targets HTTPS + `SESSION_SECURE_COOKIE=true`; temporary HTTP bootstrap is documented; `.env.example` has `APP_DEBUG=true` | Mis-copy into production → cookie theft on HTTP, stack traces if debug left on; mobile camera needs HTTPS ([self-signed-tls.md](self-signed-tls.md)) |
| M6 | Backup / restore sensitivity | SQL dumps include password hashes; restore is destructive CLI/admin path | Stolen backup ≈ offline credential attack + data loss if misused |
| M7 | Trusted proxies unset | No explicit `trustProxies` in `bootstrap/app.php` | Wrong client IP behind reverse proxy → broken rate limits / audit IP |
| M8 | Upload MIME depth | `config/attachments.php` allow-list + `mimes` validation; good UUID storage paths | Residual MIME spoofing; downloads should stay `Content-Disposition: attachment` |

### Low / informational (generally OK)

| Area | Notes |
|------|--------|
| Guest token design | `Str::random(48)`, SHA-256 at rest, rotate/revoke; guest form includes `@csrf` |
| SSO no JIT provisioning | Signs in existing users only (good once registration is closed) |
| SSO domain allow-list | Empty domains → SSO UI hidden; domain check enforced when enabled |
| Session defaults | Database driver, `http_only` true, `same_site` lax, JSON serialization |
| Audit logs | App design forbids update/delete; deploy guide can revoke DB UPDATE/DELETE |
| Health `/up` | Liveness only; keep non-sensitive |
| Blade escaping | `{!! !!}` only in highlight helper after `e()` |
| MariaDB grants | Deploy guide uses least-privilege app user + localhost bind |
| Nginx | Docroot `public/`, denies dotfiles, hides `X-Powered-By`, 25M body limit |

---

## Availability and multi-user impact

Focus: actions a user (or automated client) can take — intentionally or by accident — that **crash a worker**, **fill disk**, **hold DB locks**, or **starve PHP-FPM / queue workers**, so other users experience timeouts or a dead app. Severity assumes a shared LAN host with a finite FPM pool (deploy guide baseline `pm.max_children = 40`).

This is not an exploit cookbook. Soft limits are admin-tunable for large warehouses — see [Managing performance limits](#managing-performance-limits).

### What already helps

| Control | Notes |
|---------|--------|
| Upload size | `config/attachments.php` / validation ~25MB; Nginx `client_max_body_size 25M` when deploy guide is followed |
| Guest stock | `throttle:30,1` + `STOCK_GUEST_ENABLED` kill-switch |
| API scan | `throttle:60,1` on `POST /api/scan` |
| API token mint | `throttle:10,1` on `POST /api/auth/token` |
| Login lockout | 5 attempts via `LoginRequest` RateLimiter |
| Scheduled backup | `withoutOverlapping(180)` + `onOneServer()` when `BACKUP_DISK` is set |
| Default queue | `QUEUE_CONNECTION=database` (not `sync`); `DB_QUEUE_RETRY_AFTER` defaults to **3900** |
| Named throttles | `import`, `export`, `compare`, `backup` rate limiters (tunable) |
| Soft row/list caps | `import_max_rows`, `export_max_rows`, `compare_max_list_items` via `PerformanceLimits` |
| Scan identifiers | Equality lookups on indexed fields before expensive search fallback |

### Critical / High (availability)

| ID | Area | Status | Notes |
|----|------|--------|-------|
| A1 | Debug log sink (route) | **Mitigated** | `POST /__debug/agent-log` and JS/Blade callers removed |
| A2 | Debug log sink (import/asset paths) | **Mitigated** | `ImportService` / `AssetController` app-root `file_put_contents` debug blocks removed |
| A3 | Import row cap | **Mitigated** | `import_max_rows` (default 10 000) enforced in `ImportService::readRows()`; oversize sheets rejected with raise hint |
| A4 | Import session + long transaction | **Mitigated** | Prepared rows stored under `storage/app/imports/prepared/`; session/API cache keep only the path; commit uses `import_commit_chunk` transactions |
| A5 | Export unbounded | **Mitigated** | `export_max_rows` (default 25 000) checked before asset/list/unknown-scan Excel downloads; `throttle:export` |
| A6 | List compare unbounded | **Mitigated** | `compare_max_list_items` (default 15 000) per list before load; `throttle:compare` |
| A7 | Backup / migrate queue hygiene | **Mitigated** | Jobs `ShouldBeUnique`; UI defaults force queue (`backup_force_queue` / `migrate_force_queue`); `backup_default_keep` (default 7) required; `DB_QUEUE_RETRY_AFTER` default 3900; `throttle:backup` |
| A8 | Scan UI loads full event list | **Open — recommendation** | See [A8–A10 recommendations](#a8a10-recommendations-not-yet-implemented) |
| A9 | Asset browse filter side-loads | **Open — recommendation** | See below |
| A10 | Search leading wildcards | **Open — recommendation** | See below |

### A8–A10 recommendations (not yet implemented)

These remain the highest residual browse/scan costs for large catalogues. Prefer operational workarounds until code lands.

| ID | Recommendation | Why | Interim ops |
|----|----------------|-----|-------------|
| **A8** | Paginate or “recent N” for scan page event items and unknown scans; add `throttle` on web `POST /scan/{scan}` aligned with API (60/min); optional per-session unknown-scan cap/dedupe | Full `->get()` of a warehouse-sized event list on every scan refresh + unthrottled web scan can starve FPM/DB during stocktake | Split stocktakes into multiple event lists; avoid one 10k-row scan session ([hardware-sizing.md](hardware-sizing.md)) |
| **A9** | Replace full parent/location/type/manufacturer/model filter payloads with typeahead / lazy endpoints; cache `Location::selfAndDescendantIds` (or materialize paths) | Index pages stay O(catalogue) even when the asset table is paginated | Keep location/manufacturer trees lean; do not open Assets index as a “dashboard refresh” spam |
| **A10** | Cap search `q` length and token count; prefer prefix/`FULLTEXT` for free-text; keep identifier equality first on scan (already done) | Leading `%…%` OR across many columns + relations burns shared MariaDB CPU | Train staff to search by barcode/FMI/serial when possible; avoid multi-word fuzzy searches during peak scan |

### Managing performance limits

Soft limits live in `config/performance.php` (env `PERF_*`) and can be overridden at runtime in the `settings` table without a code deploy.

**Show effective limits (SSH on the app host):**

```bash
cd /var/www/av-asset-manager   # adjust path
sudo -u www-data php artisan app:performance-limits show
```

**Raise a limit for a large warehouse (example: 40 000-asset export):**

```bash
sudo -u www-data php artisan app:performance-limits set export_max_rows 50000
sudo -u www-data php artisan app:performance-limits set import_max_rows 20000
sudo -u www-data php artisan app:performance-limits set compare_max_list_items 25000
```

**Reset one key or all overrides back to config/.env:**

```bash
sudo -u www-data php artisan app:performance-limits reset export_max_rows
sudo -u www-data php artisan app:performance-limits reset
```

| Key | Default | Purpose |
|-----|---------|---------|
| `import_max_rows` | 10000 | Max spreadsheet data rows accepted on import |
| `import_commit_chunk` | 250 | Rows per DB transaction during import commit |
| `export_max_rows` | 25000 | Max rows for asset / list / unknown-scan Excel export |
| `compare_max_list_items` | 15000 | Max items per list for online compare / PDF / API |
| `backup_default_keep` | 7 | Retention count when Admin/CLI backup omits `--keep` |
| `backup_force_queue` | true | Admin UI always queues backups (hides sync checkbox) |
| `migrate_force_queue` | true | Admin UI always queues attachment migrations |
| `throttle_import_per_minute` | 10 | Named `throttle:import` |
| `throttle_export_per_minute` | 10 | Named `throttle:export` |
| `throttle_compare_per_minute` | 10 | Named `throttle:compare` |
| `throttle_backup_per_minute` | 3 | Named `throttle:backup` (backup + migrate) |

**Compatibility with large data sets**

- Prefer raising limits gradually and watching PHP-FPM busy workers, MariaDB slow queries, and RAM ([hardware-sizing.md](hardware-sizing.md) — 512M `memory_limit` if full-list compare/import is routine).
- Very large imports: split CSVs under `import_max_rows`, or raise the limit and run off-peak; commit chunking already reduces lock duration.
- Full-catalogue export above `export_max_rows`: raise the limit or export filtered list subsets.
- Compare of two huge lists: raise `compare_max_list_items` or compare smaller event batches.
- Env defaults (`.env` / `PERF_*`) apply until an artisan `set` stores an override; after `reset`, config/env wins again. Overrides survive deploys (stored in DB).
- Queue workers: `php artisan queue:work --timeout=3600` (or higher) with `DB_QUEUE_RETRY_AFTER=3900` (or higher than timeout).

**Allow sync backup/migrate only in break-glass** (not recommended on shared hosts):

```bash
sudo -u www-data php artisan app:performance-limits set backup_force_queue false
sudo -u www-data php artisan app:performance-limits set migrate_force_queue false
```

### Medium (availability)

| ID | Area | Evidence | Risk to other users | Mitigation |
|----|------|----------|---------------------|------------|
| A11 | Bulk asset/location update | `asset_ids` / `location_ids` arrays have **no `max:`**; single DB transaction; notes may be unbounded `string`. Location bulk may reload full hierarchy per selected id. | “Select all” (~10k) → long locks, attachment cleanup on deletes, FPM timeouts. | Cap selection (e.g. 500); chunk transactions; `max` on notes; throttle. |
| A12 | Missing throttles on heavy routes | **Partially mitigated** — named `import` / `export` / `compare` / `backup` limiters added. Still open: file upload/bulk, bulk update, web scan, global `throttleApi()`. | Residual parallel abuse on unthrottled paths. | Finish remaining limiters; optional baseline API throttle. |
| A13 | File upload memory | `FileAttachmentService::processUpload()` reads full file into memory; GD decode for images; bulk upload up to 20 × 25MB. No upload throttle. | Concurrent bulk uploads exhaust FPM memory. | Stream to disk; pixel/dimension limits; throttle; queue image processing. |
| A14 | API list show unbounded | `AssetListApiController::show()` loads all `items.asset` (web show paginates at 50). | Multi‑MB JSON and heavy hydration for large event lists. | Paginate items; optional include with limits. |
| A15 | Dashboard / connection map | `DashboardStatsService::stats()` runs fresh aggregates every hit (multiple COUNTs). Connection map `buildGraph()` can `->get()` large filtered graphs. | Concurrent dashboard opens compete with scan/search; dense connection fleets spike CPU/RAM. | Short-TTL cache; keep expensive modules off by default; require focus filters on maps. |
| A16 | Guest stock quantity | Routes throttled 30/min; `quantity` is `integer\|min:0` with **no max** (notes capped). | Valid QR holder can set huge stock counts (data integrity + downstream report cost). | `max` on quantity; optional lower throttle; token expiry; kill-switch when unused. |
| A17 | Inbound email poll | Schedule every minute, `withoutOverlapping(10)`; up to 25 messages; body capped 10k but identifier tokenization can run many lookups; notifications send synchronously in the poll. | Hung Graph/IMAP can contend for DB/CPU; second poll after 10 minutes if the first hangs. | Cap tokens tried; Http timeouts on Graph; queue notification sends; align overlap TTL with poll wall-clock. |
| A18 | Sanctum token growth | API mint throttled; profile token create has no dedicated throttle; no per-user token cap. | Scripted minting grows `personal_access_tokens` and auth lookup cost. | Max tokens per user; throttle profile create; revoke-oldest policy. |
| A19 | Password-reset / register throttle gaps | `forgot-password` / `reset-password` lack route throttles (broker has per-email delay). Register unthrottled when enabled. | Email spray or signup spam (register only if `ALLOW_REGISTRATION=true`). | Route throttle like login/SSO; keep registration off in production. |
| A20 | Custom field / notes size | Custom field values and some asset text fields validated as `string` without `max`; DB `text`. | Large strings inflate backups, asset show, and search. | `max:…` on notes, description, custom values. |
| A21 | Queue worker ops gap | **Mostly mitigated** — `retry_after` default 3900; force-queue defaults; unique jobs. Still need correct supervisor `--timeout` in production. | Mis-set worker timeout can kill long backups mid-run. | Document/ship supervisor with `--timeout=3600`; alert on queue depth. |

### Low / informational (availability)

| Area | Notes |
|------|--------|
| External file URLs | Download redirects away; app does **not** proxy remote bytes (good against server-side bandwidth DoS / SSRF fetch). |
| Preferences JSON | Column/module/domain writers use allow-lists; low DoS risk. |
| Recursive scan expansion | Rack scan expands **one** child level only — no unbounded grandchild recursion. |
| Regex hang | Audited search/scan paths use `preg_quote` / simple digit strips — no clear ReDoS. |
| Duplicate scan | Same asset on an event list warns rather than double-inserting. |
| Health `/up` | Liveness only; keep non-sensitive. |

### Priority order (remaining)

1. **Slim scan + asset browse + search** (A8–A10) — see recommendations above.
2. **Cap bulk and uploads; finish throttles** (A11–A14, A18–A20).
3. Keep performance limits reviewed after catalogue growth ([Managing performance limits](#managing-performance-limits)).

---

## Recommended hardening (operations)

Do these on every production host. Many are already sketched in [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md); this section expands and prioritizes them.

### 1. Network and TLS

- Prefer VPN / LAN-only access. UFW: allow SSH from admin networks only; allow HTTP/HTTPS only from trusted subnets. Network restriction reduces anonymous abuse but **does not** stop authenticated staff from overloading import/export/backup on a shared host.
- **HTTPS is required for mobile camera scanning and reliable PWA install** (secure context). Prefer a corporate internal CA or Certbot when you have a public name; otherwise follow **[self-signed-tls.md](self-signed-tls.md)** (private CA, Nginx `443`, device trust on iOS/Android).
- After HTTPS:
  - `APP_URL=https://…`
  - `SESSION_SECURE_COOKIE=true`
  - Optionally HSTS in Nginx (`Strict-Transport-Security`) once every client uses HTTPS and trusts the cert.
- Add security headers beyond the example (`X-Frame-Options`, `X-Content-Type-Options`): `Referrer-Policy`, `Permissions-Policy`, and a Content-Security-Policy appropriate for Blade + Vite + scanner libs.
- Deny direct access to `/storage` blobs if you ever symlink user uploads there; this app should serve attachments only via authenticated `/files/{id}/download`.

For a typical TP corporate WiFi layout (staff reach the host on the LAN; the host only talks outbound for SSO and email), see **[Corporate LAN / staff WiFi deployment](#corporate-lan--staff-wifi-deployment)** below.

### 2. Application environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-host.example

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
# SESSION_ENCRYPT=true   # optional extra layer if session store is shared

LOG_LEVEL=warning
```

- Never commit `.env`. Rotate `APP_KEY` only with a planned session/token invalidation.
- Run `php artisan config:cache`, `route:cache`, `view:cache` after deploy (`scripts/update.sh`).
- Configure trusted proxies if Nginx (or another LB) sits in front of PHP-FPM so `Request::ip()` and rate limits are accurate.
- Keep `composer install --no-dev` on production; run `composer audit` (and `npm audit`) on a schedule.

### 3. Database

- Dedicated `av_app` user; **not** root; bind MariaDB to `127.0.0.1`.
- Strong unique password; store only in `.env` / secret store.
- After migrate, revoke `UPDATE`/`DELETE` on `audit_logs` (see deploy guide §2).
- Regular DB + attachment backups to a private disk; encrypt backup media at rest; test restore on a staging host.
- Prefer MariaDB in production over SQLite (SQLite file permissions and concurrency are harder to harden).

### 4. Filesystem and process isolation

- Web root must remain `…/public`.
- `storage` and `bootstrap/cache` writable by `www-data` (or pool user) only as needed; app code owned by deploy user, not world-writable.
- PHP-FPM: disable dangerous functions if policy allows (`exec`, `passthru`, etc. — confirm no package needs them); set `upload_max_filesize` / `post_max_size` to 25M to match Nginx.
- QNAP/SharePoint: scope credentials to the attachment prefix; never expose raw NAS or Graph URLs to browsers (downloads already proxy through the app).

### 5. Accounts and secrets

- Create users only via Admin → Users (or `app:install` for the first admin).
- New passwords must be ≥8 characters with one uppercase letter and one number or symbol (enforced in code — see [Password complexity](#password-complexity)).
- Rotate the install admin password immediately; use unique emails.
- Prefer SSO ([sso-setup.md](sso-setup.md)) with a strict domain allow-list for staff mailboxes; keep password login for break-glass admins only if needed.
- Graph / Google / Azure client secrets and QNAP SFTP/S3 keys: `.env` or OS secret store only; rotate on staff change.
- Sanctum tokens: treat like passwords; create/revoke from Profile → API tokens or `POST`/`DELETE /api/auth/token`; scope with abilities (see [api.md](api.md)); on device loss revoke via Profile, `DELETE /api/auth/token`, or delete `personal_access_tokens` rows.

### Password complexity

Default policy (all password set/change paths, including `app:install`):

- Minimum **8** characters
- At least **one uppercase** letter
- At least **one number or one symbol**

Policy lives in `app/Providers/AppServiceProvider.php` inside `Password::defaults(...)`. There is no Admin UI toggle — tightening it is a **server-side code change** (SSH/terminal on the host, or a normal deploy of an edited file).

**Increase requirements on a live LEMP host (terminal):**

```bash
# 1. SSH to the app host and go to the app root (adjust path)
cd /var/www/av-asset-manager

# 2. Edit the defaults callback
sudo -u www-data nano app/Providers/AppServiceProvider.php
# (or edit as the deploy user that owns the tree)
```

In `boot()`, strengthen the rule, for example:

```php
Password::defaults(function () {
    return Password::min(12)           // longer minimum
        ->mixedCase()                  // require upper and lower
        ->numbers()                    // require a digit
        ->symbols()                    // require a symbol (and, not or)
        ->uncompromised()              // reject known-breached passwords (needs outbound HTTPS)
        ->rules([
            // keep or remove the custom “uppercase + number-or-symbol” closure
            // if you fully switch to the built-in chain above
        ]);
});
```

Useful Laravel helpers:

| Method | Effect |
|--------|--------|
| `Password::min(n)` | Raise minimum length |
| `->mixedCase()` | At least one upper and one lower |
| `->numbers()` | At least one digit |
| `->symbols()` | At least one symbol |
| `->uncompromised()` | Check against haveibeenpwned-style API (host needs outbound HTTPS) |
| `->rules([...])` | Custom closures / rule objects (current default uses this for “number **or** symbol”) |

```bash
# 3. Clear caches and reload PHP so the change is picked up
php artisan optimize:clear
sudo systemctl reload php8.3-fpm   # adjust PHP-FPM unit name for your host
```

Notes:

- Existing users keep their current passwords until they next change or reset them; stricter rules apply to **new** passwords only.
- After you tighten rules, update install examples and staff guidance so new accounts are not rejected.
- Prefer committing the change in git and deploying with `scripts/update.sh` on production so the host does not drift from the repo. Direct `nano` on the server is fine for an emergency policy bump — fold it back into source control afterward.
- `uncompromised()` calls an external breach list; on air-gapped LAN hosts, skip it or ensure the host can reach the API.

### 6. Guest Kanban / stock QR

- Treat printed QR URLs as bearer credentials for that asset’s stock.
- Rotate or revoke tokens when labels are lost or staff leave.
- Prefer short-lived campaigns; monitor `last_used_at` for unexpected use.
- Disable the surface with `STOCK_GUEST_ENABLED=false` when unused; still restrict who can reach the host on the network (see corporate LAN section).

### 7. Logging and monitoring

- Ship Nginx + Laravel logs off-box.
- Alert on spikes of `POST /register`, `POST /api/auth/token`, `POST /stock/t/*`, and 401/403 rates.
- Alert on disk use under the app root (watch for unexpected `debug-*.log`), PHP-FPM busy workers, MariaDB slow queries, and queue depth / failed jobs.
- Retain audit_logs; never grant app UPDATE/DELETE on that table.

### 8. Availability operations

- Tune soft caps with `php artisan app:performance-limits` when catalogues exceed defaults ([Managing performance limits](#managing-performance-limits)).
- Ensure `queue:work --timeout=3600` (or higher) and `DB_QUEUE_RETRY_AFTER=3900` (or higher than timeout).
- Prefer multiple smaller event lists over one warehouse-sized scan list until A8 is implemented.
- After deploy, confirm no `debug-*.log` files appear in the application root.
- Size host RAM and FPM as in [hardware-sizing.md](hardware-sizing.md); raise `memory_limit` toward 512M if you raise compare/import limits for large data sets.

---

## Corporate LAN / staff WiFi deployment

Goal for a typical self-hosted LEMP install on the corporate network:

| Direction | What should work |
|-----------|------------------|
| **Inbound (staff → host)** | Web UI, PWA install/update shell, Sanctum `/api/*`, optional guest stock QR (`/stock/t/…`), and file downloads via the app (`/files/{id}/download`) — all reachable from staff WiFi (phones and laptops) |
| **Outbound (host → internet)** | Only what SSO and email need (Google/Microsoft Entra OAuth; SMTP and/or Microsoft Graph mail). Everything else stays LAN-local |

Do **not** put MariaDB or SSH on the staff WiFi path. Bind the DB to localhost; restrict SSH to admin/jump subnets. Keep `ALLOW_REGISTRATION=false` (default) and follow the rest of this guide.

### Inbound: staff reach the app, not the database

1. **Listen on the LAN interface** — Nginx serves `80`/`443` on the host’s corporate NIC (see [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) §4 / `nginx.example`). Prefer a stable hostname over a raw IP in `server_name` and `APP_URL`.
2. **Firewall allow from staff WiFi only** — Replace the broad “allow 80 from anywhere” example with source subnets your IT assigns to corporate WiFi (and wired LAN if needed). Placeholders only — use your real CIDRs:

```bash
# Example pattern (replace with your staff / admin subnets)
sudo ufw default deny incoming
sudo ufw default allow outgoing   # tighten outbound separately (below)

# SSH: admin / jump hosts only — not the whole WiFi pool
sudo ufw allow from STAFF_ADMIN_SUBNET_CIDR to any port 22 proto tcp

# App HTTP/HTTPS: staff WiFi (+ optional wired) only
sudo ufw allow from STAFF_WIFI_SUBNET_CIDR to any port 80 proto tcp
sudo ufw allow from STAFF_WIFI_SUBNET_CIDR to any port 443 proto tcp
# sudo ufw allow from STAFF_WIRED_SUBNET_CIDR to any port 80 proto tcp
# sudo ufw allow from STAFF_WIRED_SUBNET_CIDR to any port 443 proto tcp

sudo ufw enable
sudo ufw status numbered
```

3. **Do not expose MariaDB or SSH to WiFi broadly** — Keep `bind-address = 127.0.0.1` for MariaDB. Never open `3306` on the WiFi ACLs. SSH from admin subnets (or VPN jump) only.
4. **Optional mDNS / DNS hostname** — Avahi/`*.local` or an internal A/AAAA record (e.g. `av-assets.corp.example`) so phones and laptops resolve the same name. Whatever staff type in the browser must match `APP_URL` (scheme + host + port).
5. **HTTPS on the LAN (required for phones/tablets that scan)** — Prefer a corporate internal CA cert or split-horizon public name so browsers trust TLS. If neither is available, use a private CA / self-signed setup and install the CA on every scanner device — **[self-signed-tls.md](self-signed-tls.md)**. After HTTPS: `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, and optional HSTS. HTTP-only LAN is a last resort for laptop-only access; it does **not** satisfy mobile camera scanning and must be treated as an accepted risk with `SESSION_SECURE_COOKIE=false`.

Nginx can also deny by source (same idea as UFW):

```nginx
# Inside the server { } block — replace with your staff CIDRs
allow STAFF_WIFI_SUBNET_CIDR;
# allow STAFF_WIRED_SUBNET_CIDR;
deny all;
```

### `APP_URL` and name resolution

- Set `APP_URL` to the URL staff actually use on corporate WiFi (e.g. `https://av-assets.corp.example` or `http://YOUR_HOSTNAME`).
- PWA start URL, Sanctum clients, guest QR links, email deep links, and OAuth callbacks all derive from that value. If phones cannot resolve or reach `APP_URL`, install/API/QR flows break even when a laptop using a different URL works.
- After changing `APP_URL`, run `php artisan config:cache` (or `scripts/update.sh`) and re-check SSO redirect URIs in Google/Entra.

### SSO: IdP is on the internet; callbacks hit your LAN URL

Staff browsers talk to Google / Microsoft Entra on the public internet, then the IdP redirects the browser back to `{APP_URL}/auth/google/callback` or `{APP_URL}/auth/azure/callback`.

- Register those **exact** callback URLs in the IdP (see [sso-setup.md](sso-setup.md)).
- The redirect target must be reachable from the staff device on WiFi — usually the LAN hostname, or the same hostname via **split DNS** (internal resolver returns the private host IP; internet clients need not reach the app).
- The LEMP host itself needs **outbound HTTPS** to the IdP token endpoints during the OAuth code exchange. That is expected internet use; it is not the same as exposing the app inbound to the public internet.

### Email: outbound only

Notifications / Graph mail need outbound connectivity from the host (or from the configured mail path): SMTP (commonly `587`/`465`) and/or Microsoft Graph (`https://graph.microsoft.com`). See [email-setup.md](email-setup.md). No inbound mail ports need to be opened on the LEMP host for typical SMTP/Graph send (IMAP poll, if used, is also outbound from the host).

### Outbound: limit the host to SSO + email

Default UFW “allow outgoing” is convenient for install/`apt`, but a hardened production host can deny general internet egress and allow only what you need. Coordinate with IT so package updates still work (HTTP proxy, maintenance window, or temporary allow).

Illustrative pattern (hostnames/ports vary; confirm with your IdP and mail docs):

```bash
# After install/updates are done — example tighten (adjust to your policy)
sudo ufw default deny outgoing
sudo ufw allow out on lo
sudo ufw allow out to STAFF_WIFI_SUBNET_CIDR   # optional: LAN replies / internal DNS
# DNS (use your corporate resolvers)
sudo ufw allow out to CORP_DNS_IP port 53
# SSO + Graph / general HTTPS to IdP and Microsoft/Google endpoints
sudo ufw allow out to any port 443 proto tcp
# SMTP if not using Graph-only mail
sudo ufw allow out to any port 587 proto tcp
# sudo ufw allow out to any port 465 proto tcp
```

Prefer an egress allow-list or corporate proxy over “any:443” when IT can supply destination ranges. Block unnecessary outbound from the app host (no general browsing, no unexpected cloud APIs). Attachment disks that stay on NAS/local mounts need only LAN routes — keep QNAP/SFTP on the internal network; SharePoint Graph is another intentional outbound HTTPS exception if you use that disk.

### Guest QR and Sanctum on WiFi

- Guest Kanban QR URLs are absolute links based on `APP_URL`. Scanners and phones on staff WiFi must resolve and reach that host; if guest QR is unused, set `STOCK_GUEST_ENABLED=false`.
- Sanctum Bearer clients (mobile/scripts) must use the same reachable base URL and a user whose role includes the route permission. A token ability cannot widen that role.
- Printed QR tokens remain bearer credentials for that asset’s stock — rotate/revoke when labels are lost; network restriction does not replace token hygiene.

### Quick checklist (corporate WiFi)

- [ ] Staff WiFi (and phones) can open `APP_URL` for UI, PWA, API, files, and guest QR if enabled
- [ ] UFW/nginx allow HTTP(S) only from staff subnets; SSH only from admin/jump; MariaDB localhost-only
- [ ] Hostname/DNS (or mDNS) stable; `APP_URL` matches what browsers use; config cached
- [ ] SSO redirect URIs registered to that `APP_URL`; host can reach IdP outbound; callbacks return to LAN/split-DNS URL
- [ ] Email outbound only (SMTP and/or Graph); no needless internet egress from the host
- [ ] `ALLOW_REGISTRATION=false`; optional `STOCK_GUEST_ENABLED=false`; rest of [Production checklist](#production-checklist)

---

## Recommended hardening (application changes)

These are the highest-value code/config changes relative to the findings above. Implement in priority order when you are ready to change the app (not all are present today).

### P0 — Close the open door (done)

1. **Disable public registration in production** — `ALLOW_REGISTRATION=false` (default); register returns 404; Register link hidden when disabled. Create users via Admin → Users or `app:install`.
2. **Deny users with zero roles** — Staff routes require at least one operational permission (`admin` passes all of those). A role with no permissions, and a user with no role, cannot open the staff app. Logout remains under plain `auth`. External repairers stay on their assignment-scoped routes.
3. **Stop using `verified` for access** — Replaced with permission middleware. No `MustVerifyEmail` by design (no mail dependency on isolated LAN).

### P1 — Authorize every data path (done for scoped items)

4. **API** — Sanctum group requires staff access. Each route also checks a permission and a token ability.
5. **Web mutations** — Permission middleware on mutating routes. Connection and RU writes need `connections:write` and the feature grant.
6. **Files** — `files:read` for search/download/thumbnail; `files:manage` for the library. URL redirects are scheme-checked (`http`/`https` only).
7. **Feature flags** — Only admins toggle their own flags. Other users receive developer features from a role. Feature-gated writes need that grant and the operational permission.

### P2 — Abuse resistance and SSO

8. Throttle `POST /register` (if kept for non-prod) and `POST /forgot-password` like login/SSO.
9. Shared `Password::defaults()` for register / reset / profile / Admin → Users / `app:install` — **done** (min 8, uppercase, number or symbol). Optional further tightening (longer min, `mixedCase()`, both numbers and symbols, `uncompromised()`) is a server-side edit — see [Password complexity](#password-complexity).
10. SSO: store provider subject IDs; require verified email claims for Azure comparable to Google; keep domain allow-list non-empty in production.
11. Guest stock kill-switch — **done** (`STOCK_GUEST_ENABLED`); optional token expiry, quantity `max`, and tighter throttle remain open.

### P2b — Availability / multi-user

See [Availability and multi-user impact](#availability-and-multi-user-impact).

16. **Remove leftover agent debug instrumentation** — **done** (A1/A2).
17. **Import bounds** — **done** (`import_max_rows`, prepared files, `import_commit_chunk`).
18. **Export / compare bounds** — **done** for row/list caps + throttles (queued Excel/PDF and API list pagination still optional follow-ups).
19. **Backup / migrate safety** — **done** (force queue defaults, unique jobs, retention default, `DB_QUEUE_RETRY_AFTER`).
20. **Throttles** — **partial** (import/export/compare/backup); still open: web scan, uploads, global API.
21. **Browse/scan memory** — **open** (A8–A10 recommendations).
22. **Bulk / string caps** — **open** (`max` on bulk ID arrays, notes, custom field values, guest quantity).
23. **Admin limit CLI** — **done** (`php artisan app:performance-limits`).

### P3 — Defence in depth

12. Restore path hardening: reject `..` / absolute paths; allow-list under `backups/`.
13. Encrypt backup archives at rest; document restore as break-glass.
14. Explicit CORS allow-list if any browser client calls `/api` cross-origin (default Sanctum/SPA setup should stay same-origin when possible).
15. Content sniffing / forced download headers for attachment responses.
23. Cap Sanctum tokens per user; queue outbound notifications off the request/email-poll path.
24. Explicit Http timeouts on all Graph/SharePoint calls; inbound email token-lookup cap.

---

## Production checklist

Use alongside the checklist in the deploy guide.

### Before go-live

- [ ] `ALLOW_REGISTRATION=false` (default; leave false in production)
- [ ] Optional: `STOCK_GUEST_ENABLED=false` if Kanban guest QR is not needed
- [ ] Only known users exist; each has exactly the roles they need
- [ ] First admin password meets complexity rules and is rotated; no `admin@example.com` in production
- [ ] Optional: password policy tightened beyond defaults if required by org policy ([Password complexity](#password-complexity))
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` correct
- [ ] HTTPS + `SESSION_SECURE_COOKIE=true`; mobile CA trust verified for scanning ([self-signed-tls.md](self-signed-tls.md)) — or accepted risk documented for HTTP laptop-only LAN
- [ ] MariaDB localhost-only; least-privilege DB user; audit_logs UPDATE/DELETE revoked
- [ ] UFW / firewall restricts who can reach the host (staff WiFi subnets for HTTP/HTTPS; admin-only SSH; no DB on WiFi)
- [ ] Corporate LAN: `APP_URL` resolvable on staff WiFi; outbound limited to SSO + email (see [Corporate LAN section](#corporate-lan--staff-wifi-deployment))
- [ ] Nginx docroot is `public/`; `client_max_body_size 25M`; PHP upload limits match
- [ ] `storage` / `bootstrap/cache` permissions correct; no world-writable code tree
- [ ] SSO secrets and Graph/QNAP credentials only in `.env`; domain allow-list set if SSO on
- [ ] Guest stock tokens inventoried; unused tokens revoked
- [ ] Backup disk private; restore tested on staging; schedule + queue workers running if used
- [ ] `queue:work` timeout and `DB_QUEUE_RETRY_AFTER` sized for backup/migrate jobs (≥ job `$timeout`, retry_after larger still)
- [ ] No leftover `debug-*.log` / `__debug/agent-log` in production tree (or accepted temporary with APP_DEBUG gate)
- [ ] `composer audit` clean (or findings accepted)
- [ ] Health check `/up` not exposing debug; monitoring on auth failures, disk, FPM busy, queue depth

### After each update

- [ ] `scripts/update.sh` or equivalent (`migrate --force`, caches, asset build)
- [ ] Re-apply audit_logs privilege revoke if DB grants were reset
- [ ] Confirm registration still disabled and `APP_DEBUG` still false
- [ ] Spot-check permission middleware on new routes
- [ ] Spot-check that heavy new routes have throttle and/or size caps (import, export, compare, upload, backup)

---

## Role expectation matrix (target)

| Capability | admin | inventory_manager | scanner | No role |
|------------|:-----:|:-----------------:|:-------:|:-------:|
| View assets / lists | ✓ | ✓ | ✓ | ✗ |
| Create / update assets | ✓ | ✓ | ✓* | ✗ |
| Delete assets | ✓ | ✓ | — | ✗ |
| Manage users / roles / departments | ✓ | — | — | ✗ |
| SSO / storage / export columns | ✓ | — | — | ✗ |
| Create locations / manufacturers / models | ✓ | ✓ | ✓ | ✗ |
| Edit / delete / bulk locations & catalogue | ✓ | ✓ | — | ✗ |
| Import / export | ✓ | ✓ | — | ✗ |
| Scan into event lists | ✓ | ✓ | ✓ | ✗ |
| File library admin | ✓ | ✓ | — | ✗ |
| File download (linked) | ✓ | ✓ | ✓† | ✗ |
| Guest stock QR (unauthenticated) | token | token | token | token‡ |
| API Bearer token | ✓ | ✓ | ✓ | ✗ |

Custom roles are an explicit permission set chosen under Admin → Roles. A user with no role still has no access. The four columns above are the seeded system roles. Administrators can change inventory manager, scanner, and external repairer without a code change. `reports:purge` and `stock:adjust` start off for inventory manager.

\* Product today allows scanners to edit asset details; keep or tighten deliberately.  
† Prefer scoping downloads to assets the user can already see.  
‡ By design when a valid token is presented; control via revoke + network + kill-switch.

---

## Ongoing practices

1. **Least privilege** — Prefer a narrow custom role or `scanner` over `admin`.
2. **Dependency hygiene** — `composer update` / `npm update` on a cadence; watch Laravel security releases.
3. **Secret rotation** — DB password, `APP_KEY` (planned), OAuth secrets, Sanctum tokens on offboarding.
4. **Backup drills** — Quarterly restore test; treat dumps as production data.
5. **Change review** — New routes must declare `auth` + permission middleware; never add public mutating endpoints without throttle + token/CSRF design. Also ask: can this load unbounded rows, hold a long DB transaction, or fill disk?
6. **Do not weaken audit immutability** — No update/delete APIs for `AuditLog`; keep DB grants aligned.
7. **No production debug sinks** — Do not leave agent/session file appenders under the app root or on authenticated routes.

---

## What this document does not cover

- Full OWASP ASVS or third-party penetration testing
- Host OS CIS benchmarks beyond the LEMP deploy guide
- Physical security of printed Kanban labels
- Legal/compliance retention of personal data in exports and backups
- Load-test numbers for a specific warehouse (use [hardware-sizing.md](hardware-sizing.md) as a starting point and measure on staging)

For deploy mechanics, see [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md). For OAuth IdP setup, see [sso-setup.md](sso-setup.md).
