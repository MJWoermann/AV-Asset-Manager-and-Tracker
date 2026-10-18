# Product roadmap

Planned and in-flight product work for **AV Asset Manager**. Day-to-day usage lives in [features-and-usage.md](features-and-usage.md). This file tracks what is **preview / developer-flagged**, **deferred**, and **planned** next.

Statuses:

| Status | Meaning |
|--------|---------|
| **Shipped (core)** | Always on for entitled roles |
| **Preview** | Shipped. Administrators opt in on Profile → Developer features. Other users receive a feature when it is ticked on their role |
| **In progress** | Agreed direction; implementation and/or packaging underway |
| **Planned** | Agreed direction; not implemented yet |
| **Deferred** | Explicitly out of scope until requested |

---

## Preview / developer features

These capabilities ship in the product but stay hidden until an administrator enables them on their own profile, or ticks `features:{key}` on another user's role. The feature and the matching operational permission are both required. Related menu items and asset tabs stay hidden (and those URLs return “not found”) until both are on. See [features-and-usage.md — Preview features](features-and-usage.md#preview-features-optional).

Always-on areas (catalogue, scan, compare, files/manuals, maintenance logs, repairs, spares, departments, checkout, SSO, email *settings* when the email flag is on) do **not** need these toggles.

| Feature | Status | What it adds | Notes / hardening path |
|---------|--------|--------------|------------------------|
| **Asset connections** | Preview | Ports/interfaces on assets, cable/signal paths, Connection types under Data, Connection map report | Document rack/device wiring; feature-gated API under `/api` when enabled |
| **RU visualiser** | Preview | **Rack** tab: place child gear in rack-unit slots (front/rear) | Visual elevation for touring racks; web UI only today |
| **Label generator** | Preview | Layout editor + print/PDF from Assets, lists, or Reports → Labels | Warehouse shelf labels and bag tags; partial API (asset label PDF / layouts). Phone-to-kiosk QL-1100 printing is planned below |
| **Stock / consumables** | Preview | **Stock** tab on an asset: quantities, movements, min/max, charts; Kanban/QR | Counts consumables *on that asset* — distinct from Maintenance → Spares. Guest stock token URLs are rate-limited; use only where physical QR is trusted |
| **Email integration** | Preview | Admin → Email: outbound notifications, inbound helpdesk replies, optional digests | Requires credentials + scheduler; see [email-setup.md](email-setup.md) |

**Product intent for previews:** treat them as candidate core modules. Promotion to always-on (or org-wide admin defaults) should follow real deployment feedback, docs, and roles—not a silent flip of the developer flag.

---

## Deferred (explicitly not building yet)

| Item | Status | Why deferred |
|------|--------|--------------|
| **Offline scanning** with later sync | Deferred | PWA remains online-only by design; no IndexedDB / queued stocktake unless explicitly requested |
| **SSO JIT / automatic user provisioning** | Deferred | Google / Microsoft Entra SSO links **existing** accounts only; an admin must create the user first. See [sso-setup.md](sso-setup.md) |

---

## In progress

### Copilot Studio / M365 Copilot MCP

| Field | Detail |
|-------|--------|
| **Status** | In progress |
| **Why** | Let staff query and operate inventory from **Microsoft Copilot Studio** / M365 Copilot without the Blade UI |
| **Audience** | Inventory managers, scanners, admins already in Entra (same Spatie roles as the REST API; not `external_repairer`) |

**Direction**

- First-party **Model Context Protocol** server on the app: Streamable HTTP endpoint (`/mcp/av-assets`).
- **Per-user OAuth** via Laravel Passport so Copilot Studio can use **OAuth 2.0 → Dynamic discovery** (PKCE + DCR). Authorize UI reuses web login (including existing Entra SSO).
- Optional Sanctum Bearer / API-key path for service accounts and local debugging.
- **Coarse tools** over existing services (not a 1:1 REST mirror): lookup, stocktake session/scan/compare, unknown-scan link, asset create/update, repairs, spares, checkout/return.
- Add Sanctum **checkout / return** API parity (today web-only) so MCP and other integrators share one path.
- Compact tool responses; same department scoping, role gates, throttles, and immutable audit history as the rest of the app.

**Out of scope for the first MCP release**

- Offline sync, guest QR stock, print wizards, RU visualiser, SSO/storage/email *settings*, preferences UI, admin user CRUD via Copilot.
- Overloading Socialite `AZURE_*` or Graph `MICROSOFT_GRAPH_*` as the MCP authorization server (Passport on the app is the AS; Entra stays the user IdP via login).

**Dependencies (when implemented)**

- Promote `laravel/mcp` into production `require`; add `laravel/passport`.
- Implementation lands in sibling PRs; packaging/setup docs and OpenAPI are already in-repo.

**Related**

- Setup / Copilot Studio packaging: [mcp-copilot-studio.md](mcp-copilot-studio.md)
- OpenAPI (Power Apps custom-connector fallback): [mcp-av-assets.openapi.yaml](mcp-av-assets.openapi.yaml)
- REST surface today: [api.md](api.md)
- Operator overview: [features-and-usage.md](features-and-usage.md)

---

### Local network scan (IP / MAC reconcile)

| Field | Detail |
|-------|--------|
| **Status** | Planned |
| **Why** | Keep asset IP and MAC in sync with what is live on the LAN, and place matched gear on the right Network interface and IP-scope bus without hand entry |
| **Audience** | Admins and inventory managers (not scanners or external repairers for apply) |

**Direction**

- On-demand scan from the **app host** against admin-configured LAN CIDR(s) the host can reach (ARP / neighbor after a controlled ping sweep—or equivalent). No browser-side Wi‑Fi scan and no cloud agent.
- For each discovered IP↔MAC pair, match department-scoped assets and **review then apply**:
  1. **MAC known** → update `assets.ip_address` when missing or stale.
  2. **IP known, MAC empty** → fill `assets.mac_address` (canonical uppercase `AA:BB:CC:DD:EE:FF`).
  3. **Ensure a Network interface** → if the asset has none (Network connection type or conventional `LAN` / `Network` name), create a template on its **manufacturer model** (fallback: item type) and apply via existing interface sync.
  4. **Join an IP-scope Network bus** → derive network address scope from the IP (prefer the matching configured scan CIDR); find or create a Network device bus for that scope; join the interface so peers on the same subnet share one bus.
- Prefer Eloquent asset updates so History / audit stays intact. Do not overwrite a non-empty MAC with a different value, or move an interface already on another bus, without an explicit review choice.
- Depends on **Asset Connections** (Preview today). When built: enable buses on the Network connection type and give buses a durable **network address scope** (e.g. CIDR), not name-only matching.

**Out of scope for the first network-scan release**

- Continuous / scheduled background scanning; DHCP lease import; SNMP / NetBox / switch CAM APIs.
- Client PWA ARP; offline or remote sites with no L2/L3 path to the app host.
- Storing IP/MAC on interfaces; auto-creating point-to-point cable connections; auto-joining Dante/DMX buses from this scan.
- Resolving barcode scan by IP; silent MAC overwrite or silent bus moves.

**Dependencies (when implemented)**

- Host OS ARP/neighbor visibility (and same broadcast domain / routing as the scanned subnets).
- Asset Connections available; Network type with device buses; optional bus CIDR/scope field.
- Admin CIDR config, review UI, role gates, tests, and an operator/security note (LAN reconnaissance; trusted admins only).

**Related**

- Preview: Asset connections (this file) and [features-and-usage.md](features-and-usage.md)
- Corporate LAN context: [security-hardening.md](security-hardening.md)

---

### Mobile printing to a printer kiosk (QL-1100)

| Field | Detail |
|-------|--------|
| **Status** | Planned |
| **Why** | Print shelf labels from a phone (or any browser) onto the warehouse Brother QL-1100 without setting paper size and cut in Printer Properties on every job |
| **Audience** | Anyone who can print labels today (Label generator preview plus `labels:read`). Pairing and revoking the kiosk is for people who manage layouts (`labels:write`) |

**Direction**

- The QL-1100 stays attached to a Windows kiosk PC. A phone queues a label job; an always-on helper on that PC claims it and prints it. The helper runs in the logged-on Windows user session (the Brother driver and b-PAC client live there).
- Paper size comes from the label template saved for that DK roll. Cut travels with the job: cut each label on continuous tape (default), one cut at the end of the batch, or no cut on die-cut stock. Operators do not open the Windows print dialog for those settings.
- The helper uses the Brother b-PAC client (the same cut and media behaviour as [bpac-js](https://github.com/yeasir01/bpac-js)). The phone does not run b-PAC or the browser extension.
- Two artwork choices on a layout:
  - **Image (default):** keep designing the label in this app. The job is rendered at the layout size and placed in a one-time full-page image template per DK preset.
  - **P-touch template:** upload an `.lbx` made on a PC that has the QL-1100 driver. The helper fills named text, barcode, and QR objects from the asset or spare. A missing object name fails the job with a clear message.
- Continuous lengths vary (about 29–300 mm). Image jobs must send that length with the job. If b-PAC cannot change template length, continuous image jobs use the QL-1100 raster commands (width, length, and cut) so the size still does not depend on Printer Properties. Die-cut image jobs stay on b-PAC.
- Jobs are department-scoped. One helper claim per job. The phone shows queued, printing, printed, or failed, including a waiting message when the kiosk is offline. Browser print and PDF download stay available.
- The Labels page shows each kiosk as online or offline, with the last error. The printer token is shown once when the kiosk is added.

**Out of scope for the first kiosk release**

- Running [bpac-js](https://github.com/yeasir01/bpac-js) in the phone browser, or printing while nobody is logged on at the kiosk PC.
- A per-job Printer Properties step for DK size or cut.
- Offline queueing on the phone. The PWA stays online-only.
- Replacing the on-screen layout editor or the existing PDF download.

**Dependencies (when implemented)**

- Label generator (Preview).
- Kiosk PC: QL-1100 driver, Brother b-PAC client, helper started with that Windows user, and one `.lbx` per DK preset for image mode (or an uploaded template for template mode).
- Queue, claim, and heartbeat API; feature tests for permission, department, single claim, and cut choice. Real output is checked on the QL-1100.

**Related**

- Preview: Label generator (this file) and [features-and-usage.md](features-and-usage.md)
- b-PAC in the browser (reference only; the kiosk helper calls the client directly): [yeasir01/bpac-js](https://github.com/yeasir01/bpac-js)

---

## Core product (already shipped)

For orientation only—not a backlog. Full workflows: [features-and-usage.md](features-and-usage.md).

| Area | Status |
|------|--------|
| Assets, parent/child, locations, catalogue, custom fields | Shipped (core) |
| Lists, scan sessions, compare, unknown-scan link | Shipped (core) |
| Checkout / return (hire, repair, other) — web UI | Shipped (core) |
| Repairs, procedures, spares | Shipped (core) |
| Departments, audit History (read-only), per-user table columns / dashboard modules | Shipped (core) |
| Import / export, files/manuals | Shipped (core) |
| Sanctum REST API + ability scopes | Shipped (core) |
| Optional Google / Microsoft Entra SSO (existing users) | Shipped (core) |
| Online-only installable PWA shell | Shipped (core) |

---

## How to use this document

- Prefer **outcome** wording (what / why / status) over implementation tickets.
- When an In progress or Planned item ships, move it to Core or Preview and link any setup guide.
- Do not invent new learning/ML systems or offline sync here without an explicit product request (see project hard rules in `CLAUDE.md` / `AGENTS.md`).
