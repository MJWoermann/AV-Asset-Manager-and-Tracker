# Troubleshooting

Symptom → checks → fix. Deeper setup lives in the linked guides; this page is the searchable index.

## Auth / login

| Symptom | Checks | Fix |
|---------|--------|-----|
| Cannot sign in with password | Caps lock; account exists; role assigned | Admin creates/resets user under Manage users. Password must meet complexity rules. |
| SSO button does nothing / errors | Provider enabled in Admin → SSO; redirect URI matches; client secrets in `.env` only | See [sso-setup.md](sso-setup.md). SSO signs in **existing** users only — admin must create the account first. |
| 403 after login | User role; developer feature flags on Profile | Inventory-only routes need `inventory_manager` or `admin`. Admin hubs need `admin`. |
| Locked out / too many attempts | Throttle on login/reset | Wait, then retry; admin can reset password. |

## Scan / camera

| Symptom | Checks | Fix |
|---------|--------|-----|
| Camera won’t start on phone/tablet | URL is **HTTPS**; cert trusted on the device | Browser camera APIs require a secure context. On LAN, use a private CA or self-signed cert: [self-signed-tls.md](self-signed-tls.md). |
| Camera fails on PC via `http://LAN-IP` but works on `localhost` | Address bar host | Same secure-context rule: `http://localhost` / `http://127.0.0.1` is allowed; plain `http://192.168.x.x` is not. Use the Scan page “Open via localhost” / “Open over HTTPS” links, or enable HTTPS ([self-signed-tls.md](self-signed-tls.md)). Do not rely on browser “insecure origins treated as secure” flags in production. |
| Camera works on desktop but not phone | Mixed content; HTTP bookmark | Open the HTTPS origin; reinstall the PWA shortcut if needed. |
| Barcode not found | Identifier fields (`fmi_ast`, `tp_barcode`, `rig_tag`, `device_sn`, etc.) | Check Unknown scans report; create or link the asset. |

## Uploads / import

| Symptom | Checks | Fix |
|---------|--------|-----|
| Upload rejected | File type and size | Allow-list: xlsx/csv/jpg/png/pdf/docx; max 25MB. |
| Import mapping looks wrong | Previous remembered mappings; header spelling | Override suggestions on the map step; successful mappings are remembered per user. |
| Old item type / manufacturer name fails | Entity was renamed or merged | Delete/merge records an alias so old names still resolve — see operator notes in [features-and-usage.md](features-and-usage.md). |

## Mail / queue / cron

| Symptom | Checks | Fix |
|---------|--------|-----|
| Test email fails | Admin → Email settings; `.env` secrets; outbound network | [email-setup.md](email-setup.md). |
| Notifications never send | `schedule:run` cron; optional `queue:work` | Production needs a scheduler entry; queued mail needs a worker. See [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md). |
| Queue stuck | Failed jobs table; worker process | Restart worker; inspect `failed_jobs`; fix env then retry. |

## Assets / UI

| Symptom | Checks | Fix |
|---------|--------|-----|
| Columns / dashboard modules reset | Signed-in user; preferences saved | Layout is per-user (`users.preferences`). Save Columns / Save modules again. |
| Page jumps after Save | Same URL after submit | Scroll restore only applies when pathname+query match; landing on a new show page jumps to top by design. |
| “Unable to locate file in Vite manifest” | Frontend not built | Run `npm run build` (or `npm run dev` locally). |

## Common env misconfigs

| Symptom | Checks | Fix |
|---------|--------|-----|
| Blank page / 500 after deploy | `APP_KEY`; `APP_DEBUG=false` in prod; storage permissions | See deploy guide § permissions and `.env`. |
| Assets 404 (CSS/JS) | `public/build` present; Nginx root points at `public` | Rebuild assets; fix web root. |
| Database connection refused | `DB_*` host/socket; MariaDB listening on localhost | Match `.env` to the install in [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md). |

## Related guides

- [features-and-usage.md](features-and-usage.md) — day-to-day operator workflows
- [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) — production install and updates
- [security-hardening.md](security-hardening.md) — hardening checklist
- [ui-interaction-guidelines.md](ui-interaction-guidelines.md) — UI / habits playbook for developers and agents
