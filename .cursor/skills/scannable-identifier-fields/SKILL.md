---
name: scannable-identifier-fields
description: "Audit and enforce barcode scan-to-fill UX for asset identifier fields and identifier search boxes. Use whenever adding or changing asset forms, browse/search filters, column filters, label/shelf-tag setup, compare/repair/connection-map search, or fieldBarcodeScanner / scannable-search / scannable-text-input usage. Also use when the user asks whether a field should be scannable, or to systematically check pages for scan compliance."
license: MIT
metadata:
  author: av-asset-manager
---

# Scannable identifier fields

Keep camera / keyboard-wedge **Scan** controls aligned with what is actually printed as a barcode on kit.

## Policy (source of truth)

Canonical list: `App\Support\ScannableIdentifierFields`.

| Field | UI rule |
|-------|---------|
| `serial_number` | **Must** support Scan when editable; search boxes that query it **must** accept scan-to-fill |
| `tp_barcode` | Same |
| `mac_address` | Same (includes custom fields that look like MAC via `NotificationFieldCatalog::looksLikeMac`) |
| `fmi_ast` | Typed only — **do not** add Scan |
| `rig_tag` | Typed only — **do not** add Scan |
| `ip_address` | Optional; not required |

Shared Blade:

- Forms: `<x-scannable-text-input>` → Alpine `fieldBarcodeScanner`
- Search / filters: `<x-scannable-search>` (default `submitOnScan` true; client-only filters use `false` + `x-model`)

Stocktake `/scan` uses the dedicated `barcodeScanner` Alpine component (camera + wedge) — that page is compliant by design.

## When to run this skill

1. New or edited Blade forms with asset identifier inputs
2. New search / filter UIs over assets (global `q`, per-column filters, client-side row filters)
3. Backend search that claims to match barcodes but omits `serial_number` / `tp_barcode` / `mac_address`
4. Explicit audit: “check all pages are scannable”

## Systematic page audit

Work every row. For each surface: open the route (or read the Blade), confirm Scan chrome, confirm backend/haystack includes the scannable columns when the UI offers scan.

### Editable identifier inputs

| Surface | Route / view | Expect |
|---------|--------------|--------|
| Asset create / edit | `assets.create`, `assets.edit` → `assets/form.blade.php` | Scan on serial, TP barcode, MAC; no Scan on FMI AST#, RIG tag |
| Custom MAC fields | Same form (create onboarding + edit custom sets) | Scan when `looksLikeMac` |

### Identifier search / filter boxes

| Surface | Route / view | Expect |
|---------|--------------|--------|
| Assets index | `assets.index` | `x-scannable-search` on `q` |
| List show | `lists.show` | `x-scannable-search` on `q` |
| Column filters | `asset-column-filters` for `tp_barcode` / `serial_number` / `mac_address` | `x-scannable-search`; FMI/RIG plain inputs |
| Shelf-tag print setup | `labels.index` / `labels.print.create` | Server `q` **and** visible-row filter both scannable |
| Repairs index | `repairs.index` | Scannable `q`; asset match includes serial/TP/MAC |
| Connection map | `reports.connection-map` | Scannable search; asset match includes serial/TP/MAC |
| Compare results | `reports.compare` (after run) | Scannable client filter; haystack includes serial/MAC |
| Unknown scans | `reports.unknown-scans` | Scannable `q` (codes) |

### Out of scope

- Spare / file / name-only pickers that do not search asset barcode columns
- Display-only show pages

## Fix checklist

1. Prefer `<x-scannable-text-input>` / `<x-scannable-search>` over one-off camera markup
2. Use `ScannableIdentifierFields::isScannable()` / `::orWherePartialMatch()` — do not hard-code a divergent field list
3. Client-side filters: include `serial_number`, `tp_barcode`, and `mac_address` in the haystack JSON/fields
4. Add or extend `tests/Feature/ScannableIdentifierFieldsComplianceTest.php` when adding a new surface
5. Run `php artisan test --compact tests/Feature/ScannableIdentifierFieldsComplianceTest.php` (plus any touched form/search tests)

## Anti-patterns

- Adding Scan to FMI AST# or RIG Tag because they appear in `findByIdentifier`
- Offering Scan on a search box whose backend cannot match serial / TP / MAC
- Inventing a second Alpine scanner instead of `fieldBarcodeScanner`
- Shipping a dense asset browse page with identifier search but no Scan control
