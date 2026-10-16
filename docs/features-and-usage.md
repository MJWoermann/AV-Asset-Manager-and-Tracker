# Features and usage

> Staff documentation lives in the [project wiki](https://github.com/MJWoermann/AV-Asset-Manager-and-Tracker/wiki).

A practical guide for staff evaluating or using **AV Asset Manager** — what it is for, who uses which parts, and how everyday work fits together.

This document is written for inventory managers, scanners, and admins. Technical notes are kept short and appear where they help you understand limits or setup.

---

## What this app is for

AV Asset Manager is a self-hosted web app for tracking AV equipment and running stocktakes. Think of it as a shared inventory system plus a barcode-scanning workflow: you keep a catalogue of gear, build lists for jobs or warehouses, scan on site, then compare what you expected with what you found.

**Typical use cases**

| Use case | Who | Outcome |
|----------|-----|---------|
| Warehouse or venue stocktake | Scanners + inventory managers | Event list of what was seen; compare report vs expected inventory |
| Find and update a piece of kit | Any role | Search by barcode, AST#, serial, etc.; edit details or location |
| Prepare a show / hire list | Inventory managers | Build or import a list; export for others |
| Track faults and service | Scanners and managers | Maintenance notes on the asset; optional file attachments |
| Print shelf or bag labels | Inventory managers | Layout templates → PDF tags from selected assets or a list |
| Count consumables (cables, tape, etc.) | Staff or guest link | Stock levels, movements, optional QR without login |

The app can be **installed on a phone or tablet** like a Progressive Web App (PWA). It still needs a network connection for scanning and saving — there is no offline stocktake queue yet.

---

## Key ideas (glossary)

| Term | Plain meaning |
|------|----------------|
| **Asset** | One tracked item (fixture, amp, case, cable kit, etc.) with identifiers, status, location, and notes |
| **Parent / child** | Gear inside a rack or road case: children belong to one parent and usually inherit its location |
| **Item type** | Template for an asset (e.g. LX Fixture, Rack). Controls extra fields and optional features such as rack layout or stock counting |
| **Custom field set** | Extra fields you define (voltage, wattage, …) and attach to item types |
| **Location** | Where kit lives — sites, rooms, storage areas (hierarchical) |
| **Inventory list** | “What we expect” — the source or warehouse list for a stocktake |
| **Event list** | “What we scanned” — the list filled during a stocktake or job |
| **Scan session** | Your active scanning job: which event list to write to, and optionally which inventory list to check against |
| **Unknown scan** | A code that did not match any asset — parked for follow-up |
| **History** | Read-only log of important changes to an asset (cannot be edited or deleted) |

---

## Who can do what (roles)

Access is controlled by three roles. Your administrator assigns these when creating your account.

| Capability | Admin | Inventory manager | Scanner |
|------------|:-----:|:-----------------:|:-------:|
| Dashboard, Scan, view/edit assets | ✓ | ✓ | ✓ |
| Delete assets | ✓ | ✓ | |
| View lists; use Reports | ✓ | ✓ | ✓ |
| Create locations, manufacturers, and models | ✓ | ✓ | ✓ |
| Edit/delete locations & catalogue; create/edit lists, item types, field sets | ✓ | ✓ | |
| Import / export spreadsheets | ✓ | ✓ | |
| File library (central index & delete) | ✓ | ✓ | |
| Attach files on assets / maintenance | ✓ | ✓ | ✓ |
| Add maintenance logs | ✓ | ✓ | ✓ |
| Delete maintenance logs | ✓ | ✓ | |
| Manage users, SSO, storage/backup, export columns | ✓ | | |

**Signing in**

- Email and password by default.
- Optional **Google** or **Microsoft** sign-in for existing users (admin configures this). New people are not auto-created via SSO — an admin must create the account first.

**Passwords**

When you set or change a password (first login reset, Profile → Update Password, or when an admin creates your account), it must meet these rules:

- At least **8 characters**
- At least **one uppercase letter** (A–Z)
- At least **one number** (0–9) **or** **one symbol** (for example `!`, `@`, `#`, `-`)

Examples that work: `Stocktake1`, `Warehouse!`. Examples that do not: `password`, `Password`, `pass1234`.

---

## Getting around

Main navigation (desktop and phone menu):

| Menu | What you find |
|------|----------------|
| **Dashboard** | Shortcuts and a snapshot of inventory / recent changes |
| **Scan** | Start or continue a stocktake session |
| **Assets** | Full catalogue — search, filters, columns, bulk actions |
| **Lists** | Inventory and event lists; open a list to browse its members |
| **Reports** | Compare lists, unknown scans, and (if enabled) connection map and labels |
| **Data** | Locations, item types, field sets, files, plus optional tools (connection types) |
| **Import** | Spreadsheet import (managers) |
| **Admin links** | Export columns, Storage, SSO (admins only) |
| **Profile** | Your name/email, theme, and optional preview features |

On **Assets** and when browsing a **list**, use **Columns** to show or hide fields. Your choice is saved for your user and shared between those screens.

**Dashboard**

| Desktop | Mobile |
|---------|--------|
| ![Dashboard desktop](images/features/dashboard-desktop.png) | ![Dashboard mobile](images/features/dashboard-mobile.png) |

**Phone menu** (hamburger)

![Mobile navigation menu](images/features/mobile-nav-menu.png)

**Profile** (theme, scan sounds, developer preview flags)

| Desktop | Mobile |
|---------|--------|
| ![Profile desktop](images/features/profile-desktop.png) | ![Profile mobile](images/features/profile-mobile.png) |

---

## Core workflows

### 1. Stocktake (scan → compare)

This is the main day-of-event workflow.

1. Open **Scan**.
2. Start a session: choose the **event list** (where scans go). Optionally pick an **inventory list** so you can see live “on inventory / not on inventory” style feedback.
3. Optionally set a default **location** or **status** for assets you touch during the session.
4. Scan with the device camera, or type/paste a code.
5. When a match is found, the asset is added to the event list.
   - Scanning the same asset again on that list **warns** you — it does not create a duplicate row.
   - Scanning a **rack or container** can also bring in its **child** assets.
6. Codes that do not match anything become **unknown scans** (see Reports).
7. When finished, close the session. Use **Reports → Compare** to produce a formal three-way report:

   - **Matched** — on both scanned and inventory lists  
   - **Inventory only** — expected but not scanned  
   - **Scanned only** — found but not on the inventory list  

   Export the report as Excel, CSV, or PDF as needed.

**Tips for reliable scans**

- Prefer barcodes and IDs that match fields such as FMI AST#, TP barcode, RIG tag, serial number, or MAC.
- The app looks up exact identifiers first, then falls back to broader search (name, IP, and similar). Clean barcodes in the database make stocktakes faster and more accurate.
- You need network access while scanning; work is saved on the server as you go.
- On phones and tablets, open the app over **HTTPS**. The camera will not work on plain HTTP to a LAN server. Admins: see [self-signed-tls.md](self-signed-tls.md) for certificates and installing trust on iOS/Android.

**Scan**

| Desktop | Mobile |
|---------|--------|
| ![Scan desktop](images/features/scan-desktop.png) | ![Scan mobile](images/features/scan-mobile.png) |

**Compare lists**

| Desktop | Mobile |
|---------|--------|
| ![Compare desktop](images/features/reports-compare-desktop.png) | ![Compare mobile](images/features/reports-compare-mobile.png) |

**Unknown scans**

| Desktop | Mobile |
|---------|--------|
| ![Unknown scans desktop](images/features/reports-unknown-scans-desktop.png) | ![Unknown scans mobile](images/features/reports-unknown-scans-mobile.png) |

### 2. Find and maintain an asset

1. Open **Assets** (or a list).
2. Search or filter, then open the asset.
3. Use the tabs:

   | Tab | Purpose |
   |-----|---------|
   | **Details** | Core fields, location, parent, custom fields |
   | **Files** | Manuals, photos, PDFs, links |
   | **Maintenance** | Faults, service visits, notes |
   | **History** | Who changed what, and when (read-only) |
   | **Connections** / **Rack** / **Stock** | Optional modules — see [Preview features](#preview-features-optional) |

4. Save changes. Important updates appear on the History tab and on the dashboard’s recent activity.

**Bulk actions** on Assets or a list let you update status, location, parent, or list membership for many rows at once. Deleting assets is limited to managers and admins.

**Assets catalogue**

| Desktop | Mobile |
|---------|--------|
| ![Assets desktop](images/features/assets-desktop.png) | ![Assets mobile](images/features/assets-mobile.png) |

**Asset details**

| Desktop | Mobile |
|---------|--------|
| ![Asset details desktop](images/features/asset-show-desktop.png) | ![Asset details mobile](images/features/asset-show-mobile.png) |

**Maintenance tab**

| Desktop | Mobile |
|---------|--------|
| ![Maintenance desktop](images/features/asset-maintenance-desktop.png) | ![Maintenance mobile](images/features/asset-maintenance-mobile.png) |

**History tab**

| Desktop | Mobile |
|---------|--------|
| ![History desktop](images/features/asset-history-desktop.png) | ![History mobile](images/features/asset-history-mobile.png) |

### 3. Lists for jobs and warehouses

- **Inventory managers** create and name lists (inventory vs event).
- Anyone with access can open a list and work with its members much like the Assets screen.
- Export a list to Excel or CSV for sharing outside the app.
- During a stocktake, the event list is the “scanned” side of Compare; the inventory list is the “expected” side.

**Lists index**

| Desktop | Mobile |
|---------|--------|
| ![Lists desktop](images/features/lists-desktop.png) | ![Lists mobile](images/features/lists-mobile.png) |

**List members** (example inventory list)

| Desktop | Mobile |
|---------|--------|
| ![List show desktop](images/features/list-show-desktop.png) | ![List show mobile](images/features/list-show-mobile.png) |

### 4. Import and export spreadsheets

**Import** (managers): upload Excel or CSV → map columns (your mapping can be remembered) → preview → process. Useful for first-time loads and periodic syncs from other systems. Common match keys include serial number, FMI AST#, MAC, RIG tag, and TP barcode. Prep messy sheets with AI: [import-spreadsheet-prep.md](import-spreadsheet-prep.md).

**Export**: all assets or a specific list. Admins can configure which columns appear under **Export columns**.

**File size / types for uploads generally:** jpg, png, webp, pdf, docx, xlsx, csv — up to **25 MB** per file.

**Import**

| Desktop | Mobile |
|---------|--------|
| ![Import desktop](images/features/import-desktop.png) | ![Import mobile](images/features/import-mobile.png) |

**Export columns** (admin)

| Desktop | Mobile |
|---------|--------|
| ![Export columns desktop](images/admin/admin-export-columns-desktop.png) | ![Export columns mobile](images/admin/admin-export-columns-mobile.png) |

### 5. Locations and templates

- **Locations** — organise sites and storage. Scanners can view and create locations; managers edit, delete, and bulk-update.
- **Manufacturers / models** — catalogue brands and model names. Scanners can view and create; managers edit and delete.
- **Item types** — define categories of gear and which custom field sets apply.
- **Field sets** — build reusable extra fields once, then attach them to types so every asset of that type gets the same form.

Getting types and fields right early makes import, scanning, and reports much cleaner.

**Locations**

| Desktop | Mobile |
|---------|--------|
| ![Locations desktop](images/features/locations-desktop.png) | ![Locations mobile](images/features/locations-mobile.png) |

**Item types**

| Desktop | Mobile |
|---------|--------|
| ![Item types desktop](images/features/item-types-desktop.png) | ![Item types mobile](images/features/item-types-mobile.png) |

### 6. Files and manuals

- Attach uploads or external URLs on an asset (and related records such as maintenance).
- Managers also have a central **Files** library under Data for searching and housekeeping.
- Storage can be local disk or another configured backend (admin **Storage** settings) — ask your admin where files physically live.

**File library**

| Desktop | Mobile |
|---------|--------|
| ![Files desktop](images/features/files-desktop.png) | ![Files mobile](images/features/files-mobile.png) |

### 7. Maintenance logging

On an asset’s **Maintenance** tab, record faults, service work, or notes, and attach supporting files. Scanners can add entries; managers can remove them when appropriate. This is the day-to-day place for “what’s wrong with this unit?” without burying it in free-text notes alone.

### 8. Reports beyond Compare

| Report | Use when |
|--------|----------|
| **Compare** | Formal stocktake outcome vs expected list |
| **Unknown scans** | Codes that failed lookup — add notes, export, clean up; admins can clear or dedupe in bulk |
| **Connection map** | Visual map of how kit is linked (preview feature) |

---

## Preview features (optional)

Some capabilities are shipped but turned on **per user** under **Profile → Developer features**. Until you enable them, related menu items and asset tabs stay hidden (and those URLs return “not found”).

| Feature | What it adds | Good for |
|---------|--------------|----------|
| **Asset connections** | Ports/interfaces on assets, link cable/signal paths, Connection types under Data, Connection map report | Documenting how racks and devices are wired |
| **RU visualiser** | **Rack** tab: place child gear in rack-unit slots (front/rear) | Visual rack elevation for touring racks |
| **Label generator** | Layout editor + print/PDF from Assets, lists, or Reports → Labels | Warehouse shelf labels and bag tags |
| **Stock / consumables** | **Stock** tab: quantities, movements, min/max, charts; Kanban/QR tags for managers | Counting cables, consumables, spare packs |

**Guest stock link:** managers can issue a token URL so someone can adjust a tracked stock item **without logging in** (rate-limited). Use only where you trust the physical QR (e.g. on a label in a controlled store).

Ask an admin before enabling preview features in production if your organisation has a staged rollout.

**RU visualiser** (Rack tab on a container asset)

| Desktop | Mobile |
|---------|--------|
| ![Rack desktop](images/features/asset-rack-desktop.png) | ![Rack mobile](images/features/asset-rack-mobile.png) |

---

## Admin and IT (brief)

These items matter for evaluation and operations; day-to-day scanners rarely need them.

| Topic | Summary |
|-------|---------|
| **Users** | Admins create accounts and assign roles; new passwords must meet the complexity rules above |
| **SSO** | Optional Google Workspace / Microsoft Entra sign-in for existing users; see [sso-setup.md](sso-setup.md) |
| **Storage & backup** | Choose where attachments live; run backups of data + files from Storage / ops commands |
| **REST API** | Bearer tokens (Profile UI or password issue) with optional ability scopes for integrations (assets, lists, compare, scan). See [api.md](api.md) |
| **Hosting** | PHP/Laravel app; MariaDB in production; Ubuntu + Nginx guide in [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) |
| **PWA** | Installable shortcut and cached shell assets; **data operations require being online** |
| **Audit history** | Append-only for compliance/trust — History cannot be altered after the fact |

**Users**

| Desktop | Mobile |
|---------|--------|
| ![Users desktop](images/admin/admin-users-desktop.png) | ![Users mobile](images/admin/admin-users-mobile.png) |

**Storage & backup**

| Desktop | Mobile |
|---------|--------|
| ![Storage desktop](images/admin/admin-storage-desktop.png) | ![Storage mobile](images/admin/admin-storage-mobile.png) |

---

## Suggested day-one path

If you are evaluating the product or onboarding a team:

1. **Admin** creates users with the right roles (and SSO if required).
2. **Managers** set up **locations**, **item types**, and **field sets**.
3. **Import** or create a sample of assets; print or confirm barcodes match identifier fields.
4. Build one **inventory list** and one empty **event list**.
5. On a phone, open **Scan**, run a short session, then open **Reports → Compare**.
6. Open an asset and walk through **Files**, **Maintenance**, and **History**.
7. Optionally enable one preview feature (e.g. labels or stock) and try it on a non-critical type.

That path covers the core value: catalogue → scan → compare → keep records current.

---

## What is not included yet

- **Offline scanning** with later sync when the network returns  
- Automatic **user provisioning** purely from SSO (accounts are created by an admin first)

Everything else described above is available in the current product; preview modules simply need enabling on each user’s profile.

---

## Related documents

| Document | Audience |
|----------|----------|
| [README](../README.md) | Install and upgrade overview |
| [Organizational deployment (ADKAR)](organizational-deployment.md) | Sponsors and inventory leads — rollout, training, sustainment |
| [Deploy (Ubuntu + Nginx)](deploy-ubuntu-nginx.md) | Server setup |
| [REST API](api.md) | Integrators |
| [SSO setup](sso-setup.md) | Admins enabling Google / Microsoft login |
