# Security hardening and best practices

This guide evaluates the current AV Asset Manager codebase and deploy/setup instructions for areas that could be abused, then recommends hardening steps for production (typically LAN LEMP). It is defensive guidance only — not a penetration-test report or exploit walkthrough.

Related docs: [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md), [sso-setup.md](sso-setup.md), [api.md](api.md).

---

## Threat model (assumed)

| Assumption | Implication |
|------------|-------------|
| Hosted on an internal LAN or VPN, optionally with TLS | Internet exposure raises severity of every finding below |
| Trusted staff roles: `admin`, `inventory_manager`, `scanner` | Privilege separation only works if users cannot self-enroll into full access |
| Attachments and backups may hold manuals, PO notes, and password hashes | Treat storage disks and backup archives as confidential |
| Guest Kanban QR may be printed and shared | Anyone with a live token URL can adjust that asset’s stock |

---

## Executive summary

The app already has useful controls: Spatie roles on many admin routes, Sanctum + throttles on API token issuance, login lockout, CSRF on web forms, UUID attachment paths, hashed guest stock tokens, SSO domain allow-lists, and append-only audit-log design.

The highest-risk gap for a production install is **open self-registration**: anyone who can reach `/register` can create an account with **no role**, and `User` does **not** implement `MustVerifyEmail`, so `auth` + `verified` middleware does not block them. Those accounts can use most authenticated web surfaces and obtain API tokens. Harden that first, then tighten authorization on API/file routes and production config.

---

## Findings (code and setup evaluation)

Severity reflects impact if the host is reachable by untrusted users (or a compromised staff account). On a tightly firewalled LAN with registration disabled, Critical items become lower priority but should still be fixed.

### Critical

| ID | Area | Evidence | Risk |
|----|------|----------|------|
| C1 | Public registration | `routes/auth.php` (`GET/POST register`); `RegisteredUserController` creates user + logs in with no role | Untrusted signup → inventory access |
| C2 | Email verification not enforced | `User` does not implement `MustVerifyEmail`; web group still uses `middleware(['auth', 'verified'])` | `verified` is a no-op; new accounts reach the dashboard immediately |
| C3 | Roleless authenticated access | Many web routes require only `auth`/`verified` (e.g. assets CRUD, scan, file download); API token issuance has no role check | Self-registered users inherit broad access until roles are assigned |

### High

| ID | Area | Evidence | Risk |
|----|------|----------|------|
| H1 | API authorization incomplete | `routes/api.php`: group is `auth:sanctum` only. `AssetApiController::store/update` and scan check roles; **`index`/`show` and list/compare do not** | Any token holder can enumerate assets and lists |
| H2 | File download / search IDOR | `FileController::download`, `thumbnail`, `search` — any authenticated user; no per-file ownership check | Enumerate file IDs → download all attachments |
| H3 | External URL redirect | `download()` uses `redirect()->away($file->external_url)` for URL attachments | Stored open redirect / phishing via attached URLs |
| H4 | Self-service developer features | Profile updates merge all `DeveloperFeatures` keys into `preferences`; `feature:` / `developer_feature:` middleware only check that flag | Any user can enable stock, connections, RU visualiser, etc. |
| H5 | Feature-gated writes without roles | Connection / interface mutations under `feature:asset_connections` lack `role:`; RU placements under `developer_feature:` only | Flag + any auth user → mutate connection graph / rack layout |
| H6 | Web asset/file/scan mutations under-authorized | `assets.store` / `update` / `bulk-update`, maintenance logs, uploads, scan — not wrapped in `role:` | Broader than intended role model if roleless users exist |

### Medium

| ID | Area | Evidence | Risk |
|----|------|----------|------|
| M1 | Guest stock always on | `StockTracking::guestSurfaceEnabled()` returns `true`; public `GET/POST /stock/t/{token}` with `throttle:30,1` | Leaked/printed QR = unauthenticated stock mutation until revoke |
| M2 | Registration / password-reset throttle gaps | Register unthrottled; `forgot-password` outside SSO throttle group; login uses 5-attempt lockout in `LoginRequest` | Signup spam; reset-email abuse |
| M3 | Weak first-admin password path | `app:install` / some admin user flows allow weaker `min:8` vs `Password::defaults()` on register | Weak break-glass admin |
| M4 | SSO email binding | `SocialAuthService` matches by email; Azure “verified” is weaker than Google’s `email_verified` check | IdP/email edge cases → wrong account session |
| M5 | Production defaults in docs | Deploy guide sets `SESSION_SECURE_COOKIE=false` for HTTP; `.env.example` has `APP_DEBUG=true` | Mis-copy into production → cookie theft on HTTP, stack traces if debug left on |
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

## Recommended hardening (operations)

Do these on every production host. Many are already sketched in [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md); this section expands and prioritizes them.

### 1. Network and TLS

- Prefer VPN / LAN-only access. UFW: allow SSH from admin networks only; allow HTTP/HTTPS only from trusted subnets.
- Terminate TLS (Certbot or corporate cert). After HTTPS:
  - `APP_URL=https://…`
  - `SESSION_SECURE_COOKIE=true`
  - Optionally HSTS in Nginx (`Strict-Transport-Security`).
- Add security headers beyond the example (`X-Frame-Options`, `X-Content-Type-Options`): `Referrer-Policy`, `Permissions-Policy`, and a Content-Security-Policy appropriate for Blade + Vite + scanner libs.
- Deny direct access to `/storage` blobs if you ever symlink user uploads there; this app should serve attachments only via authenticated `/files/{id}/download`.

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
- Rotate the install admin password immediately; use unique emails.
- Prefer SSO ([sso-setup.md](sso-setup.md)) with a strict domain allow-list for staff mailboxes; keep password login for break-glass admins only if needed.
- Graph / Google / Azure client secrets and QNAP SFTP/S3 keys: `.env` or OS secret store only; rotate on staff change.
- Sanctum tokens: treat like passwords; revoke on device loss (`DELETE /api/auth/token` or delete `personal_access_tokens` rows).

### 6. Guest Kanban / stock QR

- Treat printed QR URLs as bearer credentials for that asset’s stock.
- Rotate or revoke tokens when labels are lost or staff leave.
- Prefer short-lived campaigns; monitor `last_used_at` for unexpected use.
- Until a global kill-switch exists in code, restrict network access to the host if guest QR must be disabled.

### 7. Logging and monitoring

- Ship Nginx + Laravel logs off-box.
- Alert on spikes of `POST /register`, `POST /api/auth/token`, `POST /stock/t/*`, and 401/403 rates.
- Retain audit_logs; never grant app UPDATE/DELETE on that table.

---

## Recommended hardening (application changes)

These are the highest-value code/config changes relative to the findings above. Implement in priority order when you are ready to change the app (not all are present today).

### P0 — Close the open door

1. **Disable public registration in production**  
   - Remove or gate `register` routes (e.g. env `ALLOW_REGISTRATION=false`, or admin-invite only).  
   - Hide the Register link on the login page when disabled.
2. **Deny users with zero roles**  
   - Middleware or gate: authenticated users without `admin` / `inventory_manager` / `scanner` receive 403 on web + API (except maybe profile/logout while an admin assigns a role).
3. **Either implement `MustVerifyEmail` or stop using `verified` middleware**  
   - Do not rely on `verified` while `User` omits the interface.

### P1 — Authorize every data path

4. **API** — Apply `role:admin,inventory_manager,scanner` (or policies) to **all** Sanctum routes, including `GET /assets`, `GET /lists`, and compare. Require a role before issuing tokens in `TokenController`.
5. **Web mutations** — Align asset create/update/bulk, maintenance logs, scan writes, and file upload/link with the same role rules as destroy/import already use where appropriate.
6. **Files** — Authorize download/thumbnail/search (manager role, or user can access a linked asset/location). Prefer interstitial warning or host allow-list before `redirect()->away()` for external URLs.
7. **Feature flags** — Only admins may enable developer features (or assign via Admin → Users). Keep `role:` middleware **inside** feature route groups for all writes.

### P2 — Abuse resistance and SSO

8. Throttle `POST /register` (if kept for non-prod) and `POST /forgot-password` like login/SSO.
9. Use `Password::defaults()` (consider `uncompromised()`) for admin create/install passwords.
10. SSO: store provider subject IDs; require verified email claims for Azure comparable to Google; keep domain allow-list non-empty in production.
11. Guest stock: implement `guestSurfaceEnabled()` via env/config kill-switch; optional token expiry and tighter throttle.

### P3 — Defence in depth

12. Restore path hardening: reject `..` / absolute paths; allow-list under `backups/`.
13. Encrypt backup archives at rest; document restore as break-glass.
14. Explicit CORS allow-list if any browser client calls `/api` cross-origin (default Sanctum/SPA setup should stay same-origin when possible).
15. Content sniffing / forced download headers for attachment responses.

---

## Production checklist

Use alongside the checklist in the deploy guide.

### Before go-live

- [ ] Public `/register` disabled (or network-blocked and route removed)
- [ ] Only known users exist; each has exactly the roles they need
- [ ] First admin password rotated; no `admin@example.com` in production
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` correct
- [ ] HTTPS + `SESSION_SECURE_COOKIE=true` (or accepted risk documented for HTTP LAN)
- [ ] MariaDB localhost-only; least-privilege DB user; audit_logs UPDATE/DELETE revoked
- [ ] UFW / firewall restricts who can reach the host
- [ ] Nginx docroot is `public/`; `client_max_body_size 25M`; PHP upload limits match
- [ ] `storage` / `bootstrap/cache` permissions correct; no world-writable code tree
- [ ] SSO secrets and Graph/QNAP credentials only in `.env`; domain allow-list set if SSO on
- [ ] Guest stock tokens inventoried; unused tokens revoked
- [ ] Backup disk private; restore tested on staging; schedule + queue workers running if used
- [ ] `composer audit` clean (or findings accepted)
- [ ] Health check `/up` not exposing debug; monitoring on auth failures

### After each update

- [ ] `scripts/update.sh` or equivalent (`migrate --force`, caches, asset build)
- [ ] Re-apply audit_logs privilege revoke if DB grants were reset
- [ ] Confirm registration still disabled and `APP_DEBUG` still false
- [ ] Spot-check role middleware on new routes

---

## Role expectation matrix (target)

| Capability | admin | inventory_manager | scanner | No role |
|------------|:-----:|:-----------------:|:-------:|:-------:|
| View assets / lists | ✓ | ✓ | ✓ | ✗ |
| Create / update assets | ✓ | ✓ | ✓* | ✗ |
| Delete assets / manage users / SSO / storage | ✓ | — | — | ✗ |
| Import / export | ✓ | ✓ | — | ✗ |
| Scan into event lists | ✓ | ✓ | ✓ | ✗ |
| File library admin | ✓ | ✓ | — | ✗ |
| File download (linked) | ✓ | ✓ | ✓† | ✗ |
| Guest stock QR (unauthenticated) | token | token | token | token‡ |
| API Bearer token | ✓ | ✓ | ✓ | ✗ |

\* Product today allows scanners to edit asset details; keep or tighten deliberately.  
† Prefer scoping downloads to assets the user can already see.  
‡ By design when a valid token is presented; control via revoke + network + kill-switch.

---

## Ongoing practices

1. **Least privilege** — Prefer `scanner` over `inventory_manager` over `admin`.
2. **Dependency hygiene** — `composer update` / `npm update` on a cadence; watch Laravel security releases.
3. **Secret rotation** — DB password, `APP_KEY` (planned), OAuth secrets, Sanctum tokens on offboarding.
4. **Backup drills** — Quarterly restore test; treat dumps as production data.
5. **Change review** — New routes must declare `auth` + role/policy; never add public mutating endpoints without throttle + token/CSRF design.
6. **Do not weaken audit immutability** — No update/delete APIs for `AuditLog`; keep DB grants aligned.

---

## What this document does not cover

- Full OWASP ASVS or third-party penetration testing
- Host OS CIS benchmarks beyond the LEMP deploy guide
- Physical security of printed Kanban labels
- Legal/compliance retention of personal data in exports and backups

For deploy mechanics, see [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md). For OAuth IdP setup, see [sso-setup.md](sso-setup.md).
