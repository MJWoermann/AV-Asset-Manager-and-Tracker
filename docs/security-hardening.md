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

**P0/P1 hardening is implemented in code:** public registration defaults off (`ALLOW_REGISTRATION=false`), authenticated web/API access requires a Spatie app role (`admin`, `inventory_manager`, or `scanner`), API tokens are refused without a role, developer feature flags are admin-only, connection/RU writes are manager-gated, URL file downloads allow only `http`/`https`, and guest stock QR can be disabled with `STOCK_GUEST_ENABLED=false`.

**Email verification is intentionally not used** for access control. Isolated LEMP installs often have no outbound mail; `User` does not implement `MustVerifyEmail`. Admins create users (or run `app:install`) with `email_verified_at` set and a role assigned — no mail required.

---

## Findings (code and setup evaluation)

Severity reflects impact if the host is reachable by untrusted users (or a compromised staff account). On a tightly firewalled LAN with registration disabled, Critical items become lower priority but should still be fixed.

### Critical (P0 — mitigated)

| ID | Area | Status |
|----|------|--------|
| C1 | Public registration | **Mitigated** — `ALLOW_REGISTRATION` defaults false; register UI hidden; controller returns 404 when off |
| C2 | Email verification not enforced | **By design** — no `MustVerifyEmail`; access is role-gated, not mail-gated |
| C3 | Roleless authenticated access | **Mitigated** — web app group uses `role:admin,inventory_manager,scanner`; roleless users get 403 |

### High (P1 — mitigated / residual)

| ID | Area | Status |
|----|------|--------|
| H1 | API authorization incomplete | **Mitigated** — Sanctum group + token issuance require app roles |
| H2 | File download / search IDOR | **Pragmatic** — any app role may search/download/thumbnail; no per-file ownership (acceptable for this sensitivity) |
| H3 | External URL redirect | **Mitigated** — `download()` allows only `http`/`https` schemes before `redirect()->away()` |
| H4 | Self-service developer features | **Mitigated** — only admins may merge `features.*` on profile |
| H5 | Feature-gated writes without roles | **Mitigated** — connection/interface + RU placement writes require `admin` or `inventory_manager` |
| H6 | Web asset/file/scan mutations under-authorized | **Partially mitigated** — roleless users blocked by app-group role middleware; finer per-action role split remains product policy |

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

## Recommended hardening (operations)

Do these on every production host. Many are already sketched in [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md); this section expands and prioritizes them.

### 1. Network and TLS

- Prefer VPN / LAN-only access. UFW: allow SSH from admin networks only; allow HTTP/HTTPS only from trusted subnets.
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
- Retain audit_logs; never grant app UPDATE/DELETE on that table.

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
- Sanctum Bearer clients (mobile/scripts) must use the same reachable base URL and a user with an app role (`admin`, `inventory_manager`, or `scanner`).
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
2. **Deny users with zero roles** — Web dashboard/app group uses `role:admin,inventory_manager,scanner`. Logout remains under plain `auth`.
3. **Stop using `verified` for access** — Replaced with role middleware. No `MustVerifyEmail` by design (no mail dependency on isolated LAN).

### P1 — Authorize every data path (done for scoped items)

4. **API** — Sanctum group + `TokenController` require app roles.
5. **Web mutations** — Roleless users blocked at the app group; connection/RU writes manager-gated.
6. **Files** — App-role gate for search/download/thumbnail; URL redirects scheme-checked (`http`/`https` only).
7. **Feature flags** — Admins only may toggle developer features on profile.

### P2 — Abuse resistance and SSO

8. Throttle `POST /register` (if kept for non-prod) and `POST /forgot-password` like login/SSO.
9. Shared `Password::defaults()` for register / reset / profile / Admin → Users / `app:install` — **done** (min 8, uppercase, number or symbol). Optional further tightening (longer min, `mixedCase()`, both numbers and symbols, `uncompromised()`) is a server-side edit — see [Password complexity](#password-complexity).
10. SSO: store provider subject IDs; require verified email claims for Azure comparable to Google; keep domain allow-list non-empty in production.
11. Guest stock kill-switch — **done** (`STOCK_GUEST_ENABLED`); optional token expiry and tighter throttle remain open.

### P3 — Defence in depth

12. Restore path hardening: reject `..` / absolute paths; allow-list under `backups/`.
13. Encrypt backup archives at rest; document restore as break-glass.
14. Explicit CORS allow-list if any browser client calls `/api` cross-origin (default Sanctum/SPA setup should stay same-origin when possible).
15. Content sniffing / forced download headers for attachment responses.

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
| Create locations / manufacturers / models | ✓ | ✓ | ✓ | ✗ |
| Edit / delete / bulk locations & catalogue | ✓ | ✓ | — | ✗ |
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
