# REST API

Base URL: `/api`  
Authentication: `Authorization: Bearer {token}` (Laravel Sanctum)

## Obtain token

`POST /api/auth/token`

```json
{
  "email": "admin@example.com",
  "password": "secret",
  "device_name": "integration"
}
```

Response includes `token`. Revoke with `DELETE /api/auth/token` (authenticated).

## Endpoints

| Method | Path | Roles | Description |
|--------|------|-------|-------------|
| GET | `/api/user` | any auth | Current user + roles |
| GET | `/api/assets` | any auth | Paginated assets (`?q=&per_page=`) |
| GET | `/api/assets/{id}` | any auth | Asset detail |
| POST | `/api/assets` | admin, inventory_manager, scanner | Create asset |
| PUT | `/api/assets/{id}` | admin, inventory_manager, scanner | Update asset |
| GET | `/api/lists` | any auth | All lists |
| GET | `/api/lists/{id}` | any auth | List with items |
| POST | `/api/lists/compare` | any auth | Body: `scanned_list_id`, `inventory_list_id` |
| POST | `/api/scan` | admin, inventory_manager, scanner | Body: `scan_session_id`, `code`, `quantity?` |

Rate limits: token endpoint 10/min; scan 60/min.

Audit history is not exposed for mutation via API.
