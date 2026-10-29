# Copilot Studio / M365 Copilot — MCP packaging

Connect **Microsoft Copilot Studio** (and M365 Copilot agents that use the same tool surface) to **AV Asset Manager** over the Model Context Protocol (MCP).

> **Status:** Shipped. The MCP server is at `/mcp/av-assets`. Passport is the OAuth authorization server. Checkout and return are also on the Sanctum API (`assets:write`). Set `MCP_REDIRECT_DOMAINS` before Copilot Studio dynamic client registration.

Related:

- Product status: [roadmap.md — Core product](roadmap.md#core-product-already-shipped)
- REST API (Sanctum): [api.md](api.md)
- Optional Entra / Google **user** SSO: [sso-setup.md](sso-setup.md)
- Power Apps connector fallback OpenAPI: [mcp-av-assets.openapi.yaml](mcp-av-assets.openapi.yaml)

---

## What this MCP is for

Staff already in Microsoft 365 can **query and operate inventory** from Copilot Studio / M365 Copilot without opening the Blade UI:

| Capability | Typical ask |
|------------|-------------|
| Lookup | “Find asset by FMI / barcode / SN” |
| Stocktake | Start or continue a scan session, record a scan, run compare |
| Unknown scans | Link an unmatched barcode to an existing asset |
| Repairs / spares | Status, notes, procedure steps (permission-gated) |
| Checkout / return | Hire, repair, or other hand-off (`assets:write`) |

Tools are **coarse wrappers** over existing app services (scan, compare, checkout, repairs)—not a 1:1 mirror of every `/api/*` route. Each tool checks the same operational permission as the matching web and REST route. Custom roles are included when they hold that permission. Department scoping, throttles, and **immutable** audit history match the web app and REST API.

**Audience:** staff with operational permissions. Not `external_repairer`.

---

## Prerequisites

| Requirement | Why |
|-------------|-----|
| **HTTPS `APP_URL`** | Copilot Studio / Power Platform only call HTTPS endpoints. Set `APP_URL` to the public (or VPN-reachable) origin users and Power Platform use — scheme, host, and port must match. |
| **Laravel Passport keys** | The app is the OAuth 2.0 **authorization server** for MCP (PKCE + Dynamic Client Registration). Run `php artisan passport:keys` on the app. Do not point Copilot at Entra app registration client IDs as the MCP AS. |
| **`MCP_REDIRECT_DOMAINS`** | Comma-separated redirect origins Copilot Studio may register. An empty list refuses every dynamic client. Do not set this to `*`. |
| **Reachable from Power Platform** | The host must accept inbound HTTPS from Microsoft’s connector egress (internet or approved private connectivity). LAN-only installs without a tunnel/proxy cannot complete the onboarding wizard. |
| **Staff accounts** | Users who connect must already exist and hold the operational permissions for the tools they will call. Optional Entra SSO on the **login / authorize** page signs them into the *app*; it does not replace Passport. |

Camera / PWA HTTPS notes for scanners still apply to the web UI ([self-signed-tls.md](self-signed-tls.md)); Copilot Studio itself does not use the device camera.

---

## Server URL

**Transport:** Streamable HTTP only (Copilot Studio no longer supports SSE for MCP).

```text
https://{APP_URL}/mcp/av-assets
```

Example: if `APP_URL=https://assets.example.com`, the MCP server URL is:

```text
https://assets.example.com/mcp/av-assets
```

Use this exact path in the Copilot Studio wizard **Server URL** field and in the custom-connector OpenAPI (`POST /mcp/av-assets`). Do not invent alternate MCP paths.

---

## Authentication

### Primary: OAuth 2.0 — Dynamic discovery

In the Copilot Studio MCP onboarding wizard, choose:

1. **Authentication type:** OAuth 2.0  
2. **OAuth 2.0 type:** **Dynamic discovery**

That mode uses discovery metadata + **PKCE** + **Dynamic Client Registration (DCR)** against **this application’s Passport** authorization server. You should not paste a static client ID/secret for the happy path.

**Authorize / login page**

- Users complete consent on the app’s authorize UI.
- If Microsoft Entra (or Google) SSO is enabled for the install, those buttons appear on the **same login page** the authorize flow uses — staff sign in with their IdP, then return to Copilot with an access token issued by **Passport**.
- Entra remains the **user identity provider** for web login. Passport remains the **authorization server** for MCP tokens.

**Do not** configure Graph `MICROSOFT_GRAPH_*` or Socialite `AZURE_*` (or Google SSO client IDs) as the MCP authorization server. Those variables are for outbound Graph mail / optional SSO login only — see [sso-setup.md](sso-setup.md) and [email-setup.md](email-setup.md).

### Secondary: API key / Sanctum Bearer (service accounts)

For service accounts, local debugging, or environments where Dynamic discovery is unavailable:

| Mode | How |
|------|-----|
| **API key (wizard)** | If the shipped MCP accepts a header API key, choose **API key** in the wizard and set the header name to match the implementation (commonly `Authorization` with a Bearer value, or a dedicated header documented with the server). |
| **Sanctum personal access token** | Issue a token from Profile → API tokens or `POST /api/auth/token` ([api.md](api.md)). Prefer least-privilege abilities (`assets:read`, `scan`, `repairs:read`, …) instead of `*`. |

Per-user OAuth is preferred for interactive Copilot agents so actions run as the signed-in staff member (roles + department preferences apply).

---

## Copilot Studio onboarding (wizard)

Recommended path — [Microsoft Learn: Connect an existing MCP server](https://learn.microsoft.com/en-us/microsoft-copilot-studio/mcp-add-existing-server-to-agent):

1. Open your agent → **Tools**.
2. **Add a tool** → **New tool** → **Model Context Protocol**.
3. Fill in:
   - **Server name** — e.g. `AV Asset Manager`
   - **Server description** — short summary the orchestrator uses to decide when to call tools (see sample instructions below)
   - **Server URL** — `https://{APP_URL}/mcp/av-assets`
4. **Authentication:** OAuth 2.0 → **Dynamic discovery** (primary).
5. **Create** → continue through discovery / registration.
6. On **Add tool**, **Create a new connection**, sign in as a staff user with an eligible role, then **Add to agent**.

### Fallback: Power Apps custom connector

If the wizard is blocked by org policy or you need a shared connector:

1. In Power Apps → **Custom connectors** → **New custom connector** → **Import OpenAPI file**.
2. Import [mcp-av-assets.openapi.yaml](mcp-av-assets.openapi.yaml) (edit `host` to your APP_URL hostname first).
3. Complete security to match OAuth Dynamic discovery / PKCE as supported by your Power Platform environment, or API key for service accounts.
4. In Copilot Studio: **Add a tool** → **Custom connector** and select the connector.

The OpenAPI marks `POST /mcp/av-assets` with `x-ms-agentic-protocol: mcp-streamable-1.0` so Copilot Studio treats it as Streamable MCP.

---

## Sample agent instructions

Paste into the agent’s instructions (adjust names/lists for your site). Keep tool use explicit and role-safe.

### Server description (wizard)

```text
AV Asset Manager inventory for stocktake, barcode lookup, repairs, spares, and checkout.
Use for FMI/barcode/serial lookup, scan sessions, list compare, unknown-scan linking,
repair status, and hire/repair checkout — not for admin settings, SSO config, or audit edits.
```

### Stocktake recipe

```text
When the user starts or continues a stocktake:
1. Confirm which event list (and optional inventory list) to use.
2. Open or resume a scan session for that event list.
3. For each barcode they provide, record a scan; warn on duplicates — do not double-insert.
4. If a code is unknown, offer to link it to an existing asset after they confirm the match.
5. When they ask for a summary, run compare and report matched, inventory-only, and scanned-only sections briefly.
Prefer compact answers; cite FMI / barcode / location when useful.
```

### Lookup by barcode

```text
When asked to find gear, search by FMI AST, TP barcode, rig tag, device serial, or IP/MAC as provided.
Return identifier, description, location (and parent rack if a child), status, and department when available.
Do not invent assets. If multiple matches, list them and ask which one.
```

### Repairs

```text
For repair questions, show status, assignee, linked asset identifiers, and recent notes the user is allowed to see.
Update or create repairs only when the user clearly asks and their role allows it.
Never claim to change audit history. external_repairer-style limited access is not available via this MCP.
```

### Checkout / return

```text
For hire, repair, or other hand-off: confirm asset, purpose, assignee (user or guest), and expected return.
Block return while a linked repair is still open; after repair complete, prefer auto-return behaviour already used by the app.
If checkout tools are unavailable, tell the user to use the web Checkout UI and do not fabricate a success.
```

---

## Data policy / connector notes (Power Platform)

- MCP access in Copilot Studio goes through **Power Platform connectors**. Tenant **data loss prevention (DLP) / data policies** that block custom connectors also block this MCP until an admin allows the connector (or the wizard-created connection) in the right policy group.
- Classify the connector with other **business / non-blockable** connectors your inventory tools use; do not pair it with consumer connectors if policy forbids that combination.
- Admins: [Configure a data policy](https://learn.microsoft.com/en-us/power-platform/admin/wp-data-loss-prevention) in the Power Platform admin center.
- Expect org review for **outbound HTTPS** to your `APP_URL` and for **OAuth** to the app’s Passport endpoints.

---

## Security

| Control | Behaviour |
|---------|-----------|
| **Permissions** | Each tool requires the same operational permission as the matching web and REST route. Roleless accounts, accounts with no operational permission, and `external_repairer` are refused. Acting on another user's scan session still requires the `admin` role, matching the scan API. |
| **Audit logs** | Append-only. MCP must never update or delete `audit_logs`. History is read-only. |
| **Department context** | Tools honour the user’s active department (`users.preferences.active_department_id` / session), same as [api.md](api.md). Creates stay in that department. |
| **Throttles** | Expect rate limits comparable to the API (e.g. scan and token issue). Agents should retry politely on HTTP 429. |
| **Least privilege** | Prefer OAuth per user; for Sanctum/API-key bots, issue narrow abilities and rotate tokens. |
| **Secrets** | No Passport keys, client secrets, or Sanctum tokens in git or agent instruction text. |
| **Out of scope via MCP** | Offline sync, guest QR stock, print/RU wizards, SSO/storage/email *settings*, preferences UI, admin user CRUD — use the web admin UI. |

Production hardening checklist: [security-hardening.md](security-hardening.md).

---

## Implementation notes (for sibling PRs)

Docs-only packaging lives here. When implementing:

- Streamable HTTP endpoint at `/mcp/av-assets` only.
- Passport as MCP AS with discovery + DCR + PKCE for Copilot **Dynamic discovery**.
- Optional Sanctum Bearer / API-key path for service accounts.
- Checkout/return Sanctum parity so MCP and REST share one path.
- Keep tool responses compact; reuse `ScanService`, `ListComparisonService`, `AssetCheckoutService`.

Until those land, Copilot Studio connection attempts against a stock install will fail — use this guide once the MCP feature is deployed on your host.
