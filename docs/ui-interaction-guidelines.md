# UI interaction & project habits playbook

Portable defaults for product UI, view continuity, system learning, basic password handling, AI-assisted security habits, and the documentation set every serious project should maintain.

**How to reuse:** copy this file (and optionally `.cursor/rules/ui-interaction-guidelines.mdc`) into a new repo at kickoff. Treat sections as non-negotiable defaults unless the product explicitly overrides a rule.

Stack-agnostic rules come first. **In this project…** callouts show Laravel / Blade / Alpine / Tailwind adaptations in AV Asset Manager.

---

## 1. Simplified UI menus

Keep navigation short so operators reach core work without hunting through settings.

- Keep the **top-level bar short**: core operational destinations only.
- Put **settings / admin / account** items under the profile (or Account) menu — not as peer top-level links.
- On **small screens**, link to **hub** pages instead of dumping every sub-page into the drawer.
- Gate optional / power features behind **role and/or feature flags** so the default menu stays simple.
- Prefer a mobile pattern of: primary destinations → hub links → Account/Admin section — not a flat list of every CRUD index.

*In this project:* `resources/views/layouts/navigation.blade.php`; admin under profile (`/admin/*`); mobile hubs enforced by `tests/Feature/NavigationHubTest.php`. Operational items (Import, Data, Reports) stay in the main nav; settings stay in the profile dropdown.

---

## 2. Tables: filtering + customizability

Dense browse surfaces should be filterable and, where users spend real time, customizable per person.

- Prefer **per-user preferences** for view layout (columns, widget order/visibility) over one global layout for everyone.
- Data tables that users browse often should support **search and/or per-column filters**. When a column is hidden, clear that column’s filter (do not keep filtering on an invisible field).
- List/dropdown filters use **searchable** option lists. Choosing (or clearing) a list value **applies immediately** — do not require a separate Apply/Filter click for dropdowns. Keep an explicit Search submit for free-text fields. Provide a **Clear all filters** control whenever any filter is active.
- On **narrow viewports** (`max-width: 639px`), opening a searchable dropdown scrolls the **field top to the top of the viewport** so the option list stays visible (above the on-screen keyboard). Focus the search input with `preventScroll` so the browser does not undo that pin.
- **Site-wide** export/report column defaults may be admin-owned; day-to-day browse layout stays per-user.
- When adding a new dense list page, plan **filters + customize affordances up front** — do not ship a bare unfilterable grid as the long-term UX.
- Do **not** require column pickers on every admin CRUD index; apply them where browse density and frequency justify them.

*In this project:* `AssetTableColumns`, `AssetBrowseFilters`, `asset-column-picker` / `asset-column-filters` components; `users.preferences.asset_table_columns` and `dashboard_modules`. Admin export columns are org-owned under `/admin`. Mobile dropdown pinning: `pinDropdownFieldToViewportTop` / `focusDropdownSearch` in `resources/js/app.js` (searchable select, multi-select, import mapping, peer-asset picker).

---

## 3. Action button placement

Consistent action rows reduce mis-clicks and make multi-step flows predictable.

- In any **Cancel + primary** (or multi-step proceed) row: **secondary left, primary/proceed right**, row end-aligned (`flex justify-end gap-2` or equivalent).
- Multi-step flows (import, labels, scan close, wizards): the control that **advances** to the next page/step sits on the **right** of its action group.
- Header toolbars may keep **title left / actions right**; within an action cluster, the primary proceed control is **rightmost** among peers.
- Separate **destructive** actions spatially or by style so they are not adjacent twins of Save/Submit.
- Do not put primary Save/Submit on the left of Cancel in form footers or modal footers.

*In this project:* form footers and modal confirm rows should use Cancel (link or secondary) then primary button on the right. Prefer shared button components (`x-primary-button`, `x-secondary-button`, `x-danger-button`).

---

## 4. Visual clarity (contrast, spacing, in-field controls)

Every control and label must be readable and hittable, with a consistent layout so nothing important is hidden or cramped.

### Contrast

- Text, icons, borders, and focus rings must remain readable on both light and dark surfaces (aim for **WCAG AA-level** contrast for body text and controls).
- Use brand accent **sparingly** for emphasis — not for low-contrast body copy.
- Do not rely on **colour alone** for state (error/success); pair with text or icon.
- Placeholders and muted helper text stay secondary but still legible — never near-invisible grey-on-grey.

*In this project:* use `brand-*` tokens; verify dark-mode pairs (`dark:text-*` / `dark:bg-*`) whenever adding UI.

### Clear space around links and buttons

- Give interactive elements breathing room: consistent gap in action rows (`gap-2` / `gap-3` or equivalent).
- Avoid packing controls so tightly that mis-taps are likely (especially on mobile).
- Links in dense text need adequate line-height and tap targets; icon-only buttons need a clear hit area (padding), not a tiny icon with no padding.
- Separate primary actions from destructive ones spatially or by style.

### Buttons with text fields (including in-field overlays)

- Prefer form actions **beside or below** fields in a stable grid — not floating randomly over content.
- When a control is intentionally **inside** a text field (clear, reveal password, search submit, unit suffix):
  - Place it consistently (usually trailing edge).
  - **Reserve horizontal padding** on the input so typed text never sits under the button.
  - Keep the overlay control contrast-safe against the field background; do not cover validation messages.
  - Use the same pattern everywhere that pattern appears so the viewport feels consistent.
- Never stack unrelated primary/cancel buttons on top of inputs in a way that obscures labels, values, or errors.

### Scannable identifier fields (barcode Scan control)

Physical barcode fields must always offer in-field **Scan** (camera / wedge) when editable, and any search/filter that queries them must accept scan-to-fill.

- **Scannable:** `serial_number`, `tp_barcode`, `mac_address` (plus custom fields that look like MAC).
- **Typed only (no Scan):** `fmi_ast`, `rig_tag`.
- Use `<x-scannable-text-input>` / `<x-scannable-search>` and `App\Support\ScannableIdentifierFields`.
- Agent SOP: `.cursor/skills/scannable-identifier-fields/SKILL.md`; regression: `ScannableIdentifierFieldsComplianceTest`.

*In this project:* asset create/edit, assets/list column filters, repairs/connection-map/compare/unknown-scans/shelf-tag search surfaces.

---

## 5. Pop-ups / modals (viewport-safe)

Dialogs must stay usable on every viewport size.

- Dialogs open **centered in the viewport** (`fixed inset-0` + flex center, or equivalent).
- Never clip off-screen: max height roughly **viewport minus padding**; body scrolls **inside** the dialog if needed; horizontal margins on small screens.
- Prefer a **shared modal primitive** (or the same CSS contract) for new dialogs; custom overlays must match.
- Trap focus / Escape-to-close where the stack already supports it.
- Do **not** position dialogs relative to the click target if that can place them off-screen.

*In this project:* shared `x-modal` (`resources/views/components/modal.blade.php`) must be viewport-centered with max-height + internal scroll. Custom Alpine overlays (file preview, bulk confirm, rack re-parent, etc.) must follow the same contract.

---

## 6. View continuity (profiles + previous session)

The viewport and view configuration should feel stable — users return to a familiar layout, not a blank default every time.

### Tiered persistence

| Lifetime | Store | Examples of what belongs here |
|----------|--------|-------------------------------|
| Cross-device, durable | User profile / preferences JSON | Table columns, dashboard module order/visibility, theme, notification/noise toggles |
| Org-wide defaults | Settings / config (admin-owned) | Export column defaults, site policy |
| Shareable / bookmarkable list state | URL query string | Search, filters, sort; keep through pagination |
| Same browser tab / post-submit | `sessionStorage` (or equivalent) | Scroll position after create/update; short-lived UI restore |
| Device-only chrome | `localStorage` | Non-sensitive display toggles that need not sync across users |
| In-progress wizard | Server session | Multi-step import/upload drafts |
| Active work | Domain tables | e.g. open scan session |

### Rules

- Prefer **per-user preferences** for layout that people customize (columns, widgets, theme). **Merge keys**; never clobber the whole preferences blob when saving one setting.
- Prefer **URL query** for filters so refresh/share/back keep the same “viewport” into the data.
- After mutating actions on the same page, **restore scroll** (and other ephemeral view state) so the viewport does not jump.
- Do not restore a previous page’s scroll onto a **different** route; document intentional “jump to top” exceptions (e.g. landing on a newly created show page).
- Be explicit about what is **not** remembered (one-shot bulk forms) so learning features do not become creepy or wrong.

*In this project:* `users.preferences` (`asset_table_columns`, `dashboard_modules`, …), `users.theme`, GET `AssetBrowseFilters`, `SCROLL_RESTORE_KEY` in `resources/js/app.js`, scan session rows.

---

## 7. System learning and chained updates

The system should get better aligned with common use cases over time — by remembering successful human choices and chaining data renames — not by making users repeat the same mapping or break historical imports.

### 1. Remember successful workflow choices

- Persist the last good import/upload **column mapping** per user (or org, if appropriate).
- On the next upload, pre-select remembered mappings (normalized header matching), then fall back to heuristics.
- Learned suggestions are **defaults the user can override**.

*In this project:* `ImportService::rememberMapping` / `recalledMapping` / `suggestMappings` (session + durable per-user cache); covered by `tests/Feature/ImportAssetTest.php`.

### 2. Chain renames and merges (aliases)

- When deleting or migrating a catalogue entity (item type, manufacturer, location, …), require a **replacement** and record an alias/merge so old names still resolve on import and related rows stay consistent.
- Prefer alias tables at merge time over one-off data patches that forget the old label.

*In this project:* item-type delete → replacement + `ItemTypeAlias::recordMerge`; same pattern for manufacturers/models/locations; import resolves aliases (`ImportEntityAliasTest`).

### 3. Org learning vs personal learning

- **Personal:** last mapping, UI columns.
- **Org:** export column defaults, shared aliases that protect everyone’s imports.

### 4. Safety bounds

- Learning must not store secrets or raw file contents long-term beyond need.
- Clear wizard/session payloads after commit.
- Never silent irreversible remaps without confirmation when risk is high.
- Do **not** invent speculative ML features; stick to explicit memory of user/org choices and rename chains.

---

## 8. Password handling (basic criteria)

- Never store or log plaintext passwords; use framework hashing only.
- Enforce a **shared password rule set** (min length + complexity) on create, reset, profile change, and install — one source of truth.
- Confirm new passwords where users type them; require current password for self-service change when the stack supports it.
- Optional blank password on edit means **“leave unchanged,”** not “set empty.”
- Do not put passwords/secrets in the repo, templates, commits, or AI chat logs; env/secret store only.
- Throttle login / reset; never echo passwords back in validation messages or flash data.
- Audit trails must **redact** password fields.

*In this project:* `Password::defaults()` in `AppServiceProvider`; admin/user forms; `PasswordComplexityTest`; `AuditableObserver` strips password fields from audit payloads.

---

## 9. Avoid common AI coding security weaknesses

Short do/don’t checklist (defensive; no exploit recipes). Deeper host hardening belongs in each project’s hardening guide.

| Do | Don’t |
|----|--------|
| Authorize every mutating route (roles/policies) | Rely on “hidden UI” alone |
| Validate and authorize IDs; prevent IDOR on downloads/search | Trust client-supplied ownership |
| Escape output by default | Use raw HTML / `v-html` except when sanitized and exceptional |
| Keep secrets in env/secret stores | Commit secrets, fixtures with real credentials, or copy-pasteable prod examples |
| Use parameterized queries / ORM | Build SQL strings from user input |
| CSRF on cookie-session forms; safe cookie defaults in production | Disable CSRF “for convenience” in committed paths |
| Upload allow-lists, size limits, safe storage paths | User-controlled filesystem paths |
| Explicit mass-assignment / validated arrays only | Blind `$request->all()` into models |
| Keep auth strict in committed code | Weaken auth “for local testing” in shared paths |

*In this project:* see [security-hardening.md](security-hardening.md) for ops depth; Spatie roles + middleware/policies on mutating routes; upload allow-list (xlsx/csv/jpg/png/pdf/docx, 25MB).

---

## 10. Required documentation set (outlines)

Every serious project should maintain this portfolio. Content below is structural (what each guide must cover). Map exemplars in this repo for reuse.

| Guide | Purpose | Outline (minimum sections) | This repo exemplar |
|-------|---------|----------------------------|--------------------|
| **README / getting started** | First contact | What it is; prerequisites; quick local start; roles; links into the set | Root `README.md` |
| **Setup (local / install)** | Dev and first install | Environment, dependencies, `.env`, migrate/seed or install command, frontend build, first admin, smoke-test checklist | README + install notes in deploy guide |
| **Deployment** | Production cutover | Target OS/stack; web server; PHP/Node; DB; queue/cron; HTTPS/TLS; storage; backup/restore; upgrade path; go-live checklist | [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md), [hardware-sizing.md](hardware-sizing.md), [organizational-deployment.md](organizational-deployment.md), [self-signed-tls.md](self-signed-tls.md) |
| **Integration / feature setup** | Optional services | SSO, email, object storage, etc.—secrets in env only; enable/disable; test steps | [sso-setup.md](sso-setup.md), [email-setup.md](email-setup.md) |
| **User / operator guide** | Day-to-day use | Audience by role; glossary; getting around (menus); core workflows; customization (columns/dashboard); limits (e.g. online-only) | [features-and-usage.md](features-and-usage.md) |
| **Product roadmap** | Planned / preview / deferred | Status of developer-flagged modules, deferred hard limits, planned integrations | [roadmap.md](roadmap.md) |
| **API reference** (if applicable) | Integrators | Auth, endpoints, examples, error shapes | [api.md](api.md) |
| **Troubleshooting** | When things break | Symptom → checks → fix; auth/login; uploads; scan/camera HTTPS; queue/cron; mail; SSO; common env misconfigs | [troubleshooting.md](troubleshooting.md) |
| **Hardening / security** | Ops + developers | Threat model assumptions; password policy; secrets; TLS/cookies; roles; uploads; backups; AI/coding don’ts; post-deploy checklist | [security-hardening.md](security-hardening.md) |
| **UI / interaction + project habits playbook** | Designers, developers, AI agents | This document | `docs/ui-interaction-guidelines.md` |

### Documentation hygiene

- One index (README or docs index) that links every guide; avoid orphan docs.
- Separate **operator** language from **ops/deploy** language; do not bury user workflows only in deploy docs.
- Never put real secrets in examples; use placeholders.
- Keep a short **upgrade** / “existing install” note next to setup or deploy.
- Troubleshooting should be searchable by symptom (“camera won’t start”, “403 after login”, “queue stuck”).
- Hardening stays defensive and checklist-oriented; link out rather than duplicating full deploy steps.

---

## Quick conformity checklist (for audits)

Use when validating an app (or this reference implementation) against the playbook:

1. **Menus** — Top bar short; admin under profile; mobile hubs, not a flat dump.
2. **Tables** — Frequent browse surfaces have search/filters; per-user layout where claimed; no bare dense grids as the long-term UX for high-traffic lists.
3. **Actions** — Cancel/secondary left; primary/proceed right; destructive separated.
4. **Clarity** — Contrast OK in light/dark; gaps/tap targets OK; in-field controls pad inputs.
5. **Modals** — Centered, max-height, internal scroll, shared contract for custom overlays.
6. **View continuity** — Preferences merge; URL filters; scroll restore scoped to same route; intentional exceptions documented.
7. **Learning** — Mapping recall and rename aliases work; no secret retention; no speculative ML.
8. **Passwords** — Shared rules; no plaintext; redact audits; throttle auth.
9. **Security** — Authz on mutate; validate IDs; escape output; no secrets in repo; CSRF; safe uploads.
10. **Docs** — Portfolio linked from README; troubleshooting has a home; no orphan guides.
