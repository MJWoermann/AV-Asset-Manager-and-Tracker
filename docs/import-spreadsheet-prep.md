# Prepare spreadsheets for import (with AI)

Audience: **inventory managers** and **admins** preparing a first asset load or a periodic sync from another system. This guide helps you reshape messy vendor or legacy exports **before** you upload them. It complements the in-app workflow in [features-and-usage.md](features-and-usage.md) §4 — it does not replace it.

You do not need a special template file in the product: import accepts free-form column headers and maps them on upload. Using the **recommended headers** below makes auto-mapping faster and reduces manual work.

---

## 1. Why use AI first

Legacy exports often mix location into one column, use cryptic product codes as the only “name,” or spell status and types inconsistently. Cleaning that in Excel by hand is slow.

A short session with ChatGPT, Claude, Copilot, Gemini, or Excel Copilot can:

- Rename columns to match what the import screen expects
- Fill **Name** and **Item Type** (required) from manufacturer, model, and identifiers
- Split combined location strings into **Location: Level**, **Location: Room**, and **Location: Rack**
- Normalize **Status** to allowed values
- Leave unknown identifier cells **empty** instead of inventing serial numbers or MAC addresses

The result: fewer blank required fields, better auto-map suggestions, and less time on the map step.

---

## 2. Quick workflow

1. **Export or copy** your source sheet (vendor report, old inventory, hire list export).
2. **Paste** the table (or describe the columns) into your AI tool with a **prompt from section 5** below.
3. **Review** the output — especially **Name**, **Item Type**, and any identifier columns (serial, FMI AST#, TP Barcode, RIG Tag #, MAC Address).
4. **Save** as `.xlsx` or `.csv` (first sheet only if you use Excel).
5. In the app: **Import** → upload → **map** columns → run a **dry run** if offered → fix issues → **commit**. Optionally add imported rows to an inventory or event list.

---

## 3. Field catalog

Use these **exact header strings** in row 1 when you can. The import screen uses fuzzy matching on labels like these.

| Recommended header | Required | Purpose | Example value |
|--------------------|----------|---------|---------------|
| Name | Yes | User-facing asset title (the only display name field) | `Shure SM58 — AST 88421` |
| Item Type | Yes | Category; matched or created by name (aliases apply) | `Microphone` |
| Description | No | Longer text about the asset | `Wireless handheld, channel 3` |
| Status | No | Asset status (see section below) | `available` |
| Manufacturer | No | Brand | `Shure` |
| Model | No | Model name or number | `SM58` |
| Serial Number | No | Device serial; also a duplicate match key | `SN-004821` |
| Purchase Date | No | Date acquired | `2022-03-15` |
| Warranty Expiry | No | End of warranty | `2025-03-15` |
| Supplier | No | Who sold or supplied it | `AV Supplier Ltd` |
| Replacement Cost | No | Numeric replacement value | `149.00` |
| FMI AST# | No | Internal AST identifier; duplicate match key | `88421` |
| TP Barcode | No | TP barcode; duplicate match key | `TP-001234` |
| RIG Tag # | No | Rig tag; duplicate match key | `RIG-77` |
| IP Address | No | Network address | `192.168.1.50` |
| MAC Address | No | Hardware MAC; duplicate match key | `00:1A:2B:3C:4D:5E` |
| Test & Tag Expiry | No | Electrical test & tag due date | `2026-06-01` |
| Quantity | No | Count when relevant | `1` |
| Notes | No | Free-form notes | `Spare capsule in case` |
| Location: Level | No | Site or building level (not a single “Location” column) | `Building A` |
| Location: Room | No | Room or store within the level | `AV Store` |
| Location: Rack | No | Rack, shelf, or bay | `Rack 12` |

### Duplicate match keys

If a row’s value matches an **existing** asset on any of these fields, import treats it as a duplicate (you choose update, replace, skip, or create new):

- Serial Number  
- FMI AST#  
- MAC Address  
- RIG Tag #  
- TP Barcode  

Keep one asset per real piece of kit; do not put the same serial or barcode on two rows unless you intend duplicate handling.

### Status values

Use the **slug** form in the spreadsheet (labels like “In Service” are also normalized when possible):

| Slug | Meaning |
|------|---------|
| `available` | Available |
| `in_service` | In Service |
| `on_event` | On Event |
| `maintenance` | Maintenance |
| `damaged` | Damaged |
| `disposed` | Disposed |
| `lost` | Lost |
| `reserved` | Reserved |

Invalid or blank status defaults to **available**.

### File rules

- **Types:** CSV, XLSX, or XLS (`.csv`, `.xlsx`, `.xls`)
- **Size:** up to **25 MB** per file
- **Sheets:** only the **first sheet** is read; move your data there or save a one-sheet file

### Extra columns from your source

Columns that do not match the table above can be mapped to an existing field on the map step, or to **Create new field** — they are stored under **Imported Fields** (internally prefixed as imported custom fields). Prefer mapping to standard columns when the meaning matches.

### Not importable via this spreadsheet

Plan these outside the bulk import file:

- **Parent / child** rack relationships  
- A single **Site-only** location column (use Level / Room / Rack, or set location in the app after import)  
- **RU height**, **min/max stock**, **department** assignment  
- **Spare parts** bulk load (no spare CSV import in Phase 1)

---

## 4. User-friendly Name rules

Ask the AI (or apply yourself) when building the **Name** column:

1. **Manufacturer + model** when both exist: e.g. `Panasonic PT-RZ12K`.
2. Otherwise **Item Type — short identifier**: last four characters of serial, FMI AST#, or TP Barcode, e.g. `Projector — 4821`.
3. Keep names **scannable in lists** — aim for roughly **80 characters or fewer**; avoid ALL CAPS dumps.
4. **Do not** put internal SKU noise in the title; keep SKUs and codes in **Manufacturer**, **Model**, or identifier columns.
5. When many identical models share a type, make names **unique enough**: append rack, room, or tag, e.g. `Shure SM58 — Rack 12`.

**Name** is the only user-facing title field — invest a few minutes here; it pays off in search and scan result lists.

---

## 5. Copy-paste prompts

Replace bracketed placeholders. Paste your table below the prompt or attach a file if the tool allows.

### Master convert prompt

```text
You are helping prepare a spreadsheet for import into an AV asset manager.

I will paste a table (or describe columns) from a legacy/vendor export.

Output a CSV with EXACTLY these header names in row 1 (use all columns; leave cells empty if unknown):
Name, Item Type, Description, Status, Manufacturer, Model, Serial Number, Purchase Date, Warranty Expiry, Supplier, Replacement Cost, FMI AST#, TP Barcode, RIG Tag #, IP Address, MAC Address, Test & Tag Expiry, Quantity, Notes, Location: Level, Location: Room, Location: Rack

Rules:
- Name and Item Type are REQUIRED on every row. Follow Name rules: prefer "{Manufacturer} {Model}"; else "{Item Type} — {last 4 of serial or FMI AST# or TP Barcode}"; keep under ~80 chars; no ALL CAPS; no SKU junk in Name.
- NEVER invent Serial Number, FMI AST#, TP Barcode, RIG Tag #, MAC Address, or IP Address. Only copy from source data; leave blank if missing.
- Normalize Status to one of: available, in_service, on_event, maintenance, damaged, disposed, lost, reserved. If unclear, use available.
- Split combined location strings into Location: Level, Location: Room, Location: Rack (three columns). Do not use a single "Location" column.
- Trim whitespace; uppercase MAC addresses as AA:BB:CC:DD:EE:FF when present.
- Do not add commentary—output only the CSV.

Source data:
[PASTE TABLE OR DESCRIBE COLUMNS HERE]
```

### Name-only prompt

```text
For each row below, write a single "Name" value for an AV asset list.

Rules:
- If Manufacturer and Model exist: "{Manufacturer} {Model}".
- Else: "{Item Type} — {last 4 chars of Serial Number, or FMI AST#, or TP Barcode}".
- If many rows share the same model, append location rack or RIG Tag # to keep names distinct.
- Max ~80 characters; title case or normal casing; no ALL CAPS; do not put serial/barcode in Name if it is already in dedicated columns—use only a short suffix for uniqueness.
- Do not invent identifiers.

Output two columns: original row number (or key), Name.

Data:
[PASTE ROWS WITH Manufacturer, Model, Item Type, Serial Number, FMI AST#, TP Barcode, Location: Rack, RIG Tag #]
```

### Item Type normalize prompt

```text
Map each vendor "category" or "product type" below to EXACTLY one label from this controlled list (do not invent new types unless I say so):

[LIST YOUR ITEM TYPES, e.g. Microphone, Wireless Mic, Mixer, Amplifier, Speaker, Projector, Display, Cable, Rack, Road Case, …]

Output: original category → Item Type (from the list). If no good match, suggest the closest list item and flag uncertain rows.

Source categories:
[PASTE UNIQUE CATEGORIES FROM EXPORT]
```

### Identifier cleanup prompt

```text
Clean identifier columns for an asset import. Columns: Serial Number, FMI AST#, TP Barcode, RIG Tag #, MAC Address, IP Address.

Rules:
- Trim spaces; remove stray quotes and tabs.
- MAC Address: normalize to uppercase AA:BB:CC:DD:EE:FF (colons). If format is invalid, leave blank—do not guess.
- Do NOT fabricate or guess any ID. Empty source → empty cell.
- Do not merge two different IDs into one cell.

Output the same columns, same row order, CSV format.

Data:
[PASTE TABLE]
```

### Location split prompt

```text
Split each location string into three columns: Location: Level, Location: Room, Location: Rack.

Examples:
- "Building A / AV Store / Rack 12" → Level: Building A, Room: AV Store, Rack: Rack 12
- "Warehouse > Shelf B3" → Level: Warehouse, Room: (empty or general store), Rack: Shelf B3
- "Site 2" only → Level: Site 2, Room and Rack empty

Do not invent room or rack names. If the string has only one part, put it in Level.

Output CSV with columns: [optional row id], Location: Level, Location: Room, Location: Rack

Data:
[PASTE LOCATION COLUMN]
```

---

## 6. Mini example

Illustrative CSV (not a downloadable template):

```csv
Name,Item Type,Description,Status,Manufacturer,Model,Serial Number,FMI AST#,TP Barcode,Location: Level,Location: Room,Location: Rack
Shure SM58,Microphone,Stage vocal mic,available,Shure,SM58,SN-004821,88421,TP-001234,Building A,AV Store,Rack 12
Panasonic PT-RZ12K,Projector,12K laser,maintenance,Panasonic,PT-RZ12K,PJ-99201,,,Building A,Theatre,FOH
XLR Cable 5m,Cable,Patch cable,available,,, ,,,Building A,AV Store,Shelf B3
```

---

## 7. Pre-flight checklist

Before upload:

- [ ] Every row has **Name** and **Item Type**
- [ ] No invented serials, barcodes, MACs, or FMI AST# values
- [ ] Duplicate match keys are intentional (same ID = same asset, or you plan skip/update on import)
- [ ] **Item Type** names are ones you accept being **auto-created** if they do not exist yet
- [ ] Data is on the **first sheet**; file is CSV/XLSX/XLS and under **25 MB**
- [ ] Status values normalized (or blank → available)
- [ ] Location split into Level / Room / Rack where you have location data

---

## 8. After upload

1. **Map** — confirm each spreadsheet column maps to the right field; override wrong remembered mappings if needed.
2. **Dry run** — when available, preview creates/updates/skips without committing.
3. **Duplicates** — choose per row or default action: update, replace, skip, or create new when match keys hit existing assets.
4. **Lists** — optionally add imported assets to an inventory or event list for the next stocktake.

Full UI walkthrough: [features-and-usage.md](features-and-usage.md) §4.

---

## 9. Limitations and related docs

| Topic | Where |
|-------|--------|
| Import / export in the app | [features-and-usage.md](features-and-usage.md) §4 |
| Upload rejected, mapping issues | [troubleshooting.md](troubleshooting.md) — Uploads / import |
| Cutover and data readiness before go-live | [organizational-deployment.md](organizational-deployment.md) §4 |

Phase 1 does not include spare-part spreadsheet import, offline scan sync, or parent/child rack relationships via bulk import — set those up in the app after load where needed.
