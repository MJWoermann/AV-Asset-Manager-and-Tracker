# Product roadmap

Planned and in-flight product work for **AV Asset Manager**. Day-to-day usage lives in [features-and-usage.md](features-and-usage.md). This file tracks what is **preview / developer-flagged**, **deferred**, and **planned** next.

Statuses:

| Status | Meaning |
|--------|---------|
| **Shipped (core)** | Always on for entitled roles |
| **Preview** | Shipped; opt-in per user under Profile → Developer features |
| **In progress** | Agreed direction; implementation and/or packaging underway |
| **Planned** | Agreed direction; not implemented yet |
| **Deferred** | Explicitly out of scope until requested |

---

## Preview / developer features

These capabilities ship in the product but stay hidden until each user enables them under **Profile → Developer features**. Related menu items and asset tabs stay hidden (and those URLs return “not found”) until enabled. See [features-and-usage.md — Preview features](features-and-usage.md#preview-features-optional).

Always-on areas (catalogue, scan, compare, files/manuals, maintenance logs, repairs, spares, departments, checkout, SSO, email *settings* when the email flag is on) do **not** need these toggles.

| Feature | Status | What it adds | Notes / hardening path |
|---------|--------|--------------|------------------------|
| **Asset connections** | Preview | Ports/interfaces on assets, cable/signal paths, Connection types under Data, Connection map report | Document rack/device wiring; feature-gated API under `/api` when enabled |
| **RU visualiser** | Preview | **Rack** tab: place child gear in rack-unit slots (front/rear) | Visual elevation for touring racks; web UI only today |
| **Label generator** | Preview | Layout editor + print/PDF from Assets, lists, or Reports → Labels | Warehouse shelf labels and bag tags; partial API (asset label PDF / layouts) |
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
