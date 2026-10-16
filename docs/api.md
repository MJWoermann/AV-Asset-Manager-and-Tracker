# REST API

> **For AI assistants:** Paste or share this repo-relative path as the source of truth for the REST API: **`docs/api.md`**. It documents Sanctum Bearer auth, token obtain/revoke, Spatie role gates, token abilities (scopes), endpoints, status codes, and curl examples for the AV Asset Manager stocktake/integrations API.

## AI context (quick model)

- **Auth scheme:** `Authorization: Bearer {token}` (Laravel Sanctum personal access tokens). Unauthenticated API responses are **401** with `WWW-Authenticate: Bearer`.
- **Who may use the API:** Spatie roles `admin`, `inventory_manager`, or `scanner` (roleless accounts get **403** on token issue and authenticated routes).
- **What a token may call:** Sanctum **abilities** (scopes) such as `assets:read`, `scan`, or `*` for full access. Missing ability → **403** (authenticated but token lacks ability).
- **Not on this API:** Web-only preferences, audit History mutation, guest stock QR, and most admin UI settings.

Base URL: `/api`

## Authentication

Send the token on every authenticated request:

```http
Authorization: Bearer {token}
```

Unauthenticated requests to protected `/api/*` routes return:

```http
HTTP/1.1 401 Unauthorized
WWW-Authenticate: Bearer
Content-Type: application/json

{"message":"Unauthenticated."}
```

### Obtain a token

Two equivalent ways (same Sanctum tokens):

1. **Profile UI (recommended)** — Profile → API tokens: name, optional ability checkboxes, current password. The plaintext token is shown once.
2. **Password issue** — `POST /api/auth/token` (throttled 10/min).

#### `POST /api/auth/token`

```json
{
  "email": "admin@example.com",
  "password": "secret",
  "device_name": "integration",
  "abilities": ["assets:read", "lists:read"]
}
```

| Field | Required | Notes |
|-------|----------|--------|
| `email` | yes | User email |
| `password` | yes | Account password |
| `device_name` | yes | Label stored on the token |
| `abilities` | no | Array of allow-listed abilities; omit or `[]` → `["*"]` (full access) |

Success response includes `token`, `token_type` (`Bearer`), `abilities`, and `user` (id, name, email, roles).

```bash
curl -s -X POST "$APP_URL/api/auth/token" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@example.com","password":"secret","device_name":"curl"}'
```

Authenticated example:

```bash
curl -s "$APP_URL/api/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Revoke a token

- **Current token:** `DELETE /api/auth/token` (any valid Bearer token; no ability check).
- **Profile UI:** Revoke a named token from Profile → API tokens.
- **Ops:** delete rows in `personal_access_tokens` (see [security-hardening.md](security-hardening.md)).

## Status codes

| Status | Meaning |
|--------|---------|
| **401** | Missing/invalid token; `WWW-Authenticate: Bearer` on API JSON |
| **403** | Authenticated but Spatie role missing, **or** token lacks required ability (`{"message":"Authenticated but token lacks required ability.","abilities":[…]}`) |
| **422** | Validation failure (e.g. bad credentials on token issue, invalid body) |
| **429** | Rate limited (token issue 10/min; scan 60/min) |

Role checks and ability checks both return 403; inspect the message body to distinguish.

## Token abilities (scopes)

Allow-list is defined in `app/Support/ApiTokenAbilities.php`. Profile → API tokens and `POST /api/auth/token` use the same list. Spatie roles still gate *who* may hold a token; abilities gate *what that token may call*.

| Ability | Covers |
|---------|--------|
| `user:read` | `GET /api/user`, `PATCH /api/me/active-department` |
| `assets:read` / `assets:write` | Assets CRUD, bulk update, maintenance logs, import job API |
| `locations:read` / `locations:write` | Locations CRUD + bulk update |
| `catalog:read` / `catalog:write` | Manufacturers, models, item types, custom field sets |
| `lists:read` / `lists:write` / `lists:compare` | Lists CRUD + compare |
| `scan` / `scan-sessions` | Record scans; start/update/close sessions |
| `spares:read` / `spares:write` | Spares, groups, movements, supersede |
| `repairs:read` / `repairs:write` | Repairs workflow + procedures |
| `files:read` / `files:write` | File search, upload, link, download, destroy |
| `reports:read` / `reports:write` | Unknown scans, exports, dashboard stats; admin purge |
| `audit:read` | `GET /api/assets/{asset}/history` (read-only) |
| `admin:read` / `admin:write` | Users and departments |
| `connections:*`, `stock:*`, `labels:*`, `rigging:*` | Feature-flagged modules (same flags as web) |
| `email:ops` | Inbound email apply/reject when `email_integration` is on |
| `*` | Full access (default when abilities omitted on token issue) |

`DELETE /api/auth/token` does **not** require an ability.

## Conventions

- JSON create endpoints return **201** with the created resource (includes `id` for chaining).
- **Role gates** on mutating routes match the web UI (`routes/web.php`); abilities are an additional token scope.
- **Department context:** `PATCH /api/me/active-department` sets the active department in session (when present) and in `users.preferences.active_department_id` for stateless tokens. Creates on department-scoped models use that value.
- **Feature flags:** Routes under connections, stock, shelf tags, rigging, and inbound email return **404** when the user does not have the same feature enabled as on the web.

## Endpoints (summary)

Authenticated routes require `admin`, `inventory_manager`, or `scanner`. See `routes/api.php` for the canonical list.

| Area | Examples |
|------|----------|
| Auth | `POST /api/auth/token`, `DELETE /api/auth/token`, `GET /api/user` |
| Assets | `GET/POST/PUT/DELETE /api/assets`, `POST /api/assets/bulk-update`, `GET /api/assets/{id}/history`, maintenance log sub-routes |
| Lists & scan | `GET/POST/PUT/DELETE /api/lists`, `POST /api/lists/compare`, `POST /api/scan`, `POST /api/scan-sessions`, session patch/close |
| Locations | `GET/POST/PUT/DELETE /api/locations`, `POST /api/locations/bulk-update` |
| Catalogue | `/api/manufacturers`, nested models, `/api/item-types`, `/api/custom-field-sets` |
| Spares | `/api/spares`, `/api/spares/groups`, movements, supersede, chart |
| Repairs | `/api/repairs`, workflow actions, `/api/repair-procedures` |
| Reports | `/api/unknown-scans`, export, `GET /api/dashboard/stats`, `GET /api/export/assets` |
| Files | `/api/files/search`, upload, link, download (manager index/show/destroy) |
| Import | `GET/POST /api/import/status|upload|prepare|process` (inventory_manager+) |
| Admin | `/api/admin/users`, `/api/admin/departments` (admin only) |
| Email ops | `/api/email/inbound` when feature on |
| Connections | `/api/reports/connection-map`, asset interfaces/connections/buses, `/api/connection-types` |
| Stock | `/api/assets/{id}/stock-movements`, stock levels, token rotate/revoke |
| Labels | `/api/labels/layouts`, `GET /api/assets/{id}/label/pdf` |
| Rigging | `/api/rigging/events`, calculators compute + logs |

### AI agent recipes

1. **Stocktake:** `POST /api/scan-sessions` → `POST /api/scan` with `scan_session_id` → `POST /api/scan-sessions/{id}/close`.
2. **Location tree:** `POST /api/locations` with `parent_id` / `type` (site → level → room → rack).
3. **Import assets:** `POST /api/import/upload` → `POST /api/import/prepare` with `mapping` → `POST /api/import/process`.
4. **Scoped token:** Issue with explicit `abilities` array; use `*` only for full automation accounts.

## Web-only features (not on this API)

- Password change, SSO/storage/email **settings**, export column admin, UI preferences (columns, dashboard modules).
- Guest stock QR (`/stock/t/{token}`) and print HTML wizards.
- Audit log **mutation** (history is read-only via API).
- Profile API token UI is web-only, but tokens work on all `/api/*` routes above.
