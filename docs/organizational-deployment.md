# Organizational deployment (ADKAR)

A people-and-process guide for sponsors, inventory leads, and warehouse managers rolling out **AV Asset Manager**. Technical go-live is not adoption: this document is how the inventory stays accurate after day one.

Day-to-day click paths live in [features-and-usage.md](features-and-usage.md) (especially its [Suggested day-one path](features-and-usage.md#suggested-day-one-path)). Server install belongs with IT — see the Prerequisites box below.

---

## Prerequisites (IT)

Point your systems team at existing docs. Do **not** treat this guide as a server install manual.

| Need | Document |
|------|----------|
| LEMP install, Nginx, upgrades | [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) |
| HTTPS for phone/tablet camera scan | [self-signed-tls.md](self-signed-tls.md) (LAN / private CA) |
| Optional Google / Microsoft login | [sso-setup.md](sso-setup.md) |
| Hardening and backup posture | [security-hardening.md](security-hardening.md) |
| Host sizing (~10k assets / ~20 users) | [hardware-sizing.md](hardware-sizing.md) |

**Phase 1 facts IT and sponsors should share**

- Online-only PWA: installable shortcut, but scanning and saving need a network connection.
- Mobile camera scan requires **HTTPS** (secure context).
- **No self-registration.** Admins create accounts and assign roles.
- Optional SSO signs in **existing** users only — it does not auto-provision new people.

---

## 1. Purpose, success criteria, and RACI

### Why this guide exists

AV Asset Manager replaces spreadsheet drift and “ask the person who last moved the case” with a shared catalogue, scan sessions, and compare reports. Success is not “the URL works.” Success is named owners, clean identifiers, one real stocktake completed, and a cadence so locations and status stay current.

### Success criteria (beyond “app is online”)

| Criterion | What “done” looks like |
|-----------|------------------------|
| Roles assigned | Every active user has `admin`, `inventory_manager`, or `scanner` (see [roles](features-and-usage.md#who-can-do-what-roles)) |
| Champions named | At least one peer coach per site or shift who can unblock scanners |
| Master data ready | Locations, item types, and identifier/barcode fields populated for the pilot scope |
| First real stocktake | Scan session → event list → [Reports → Compare](features-and-usage.md#1-stocktake-scan--compare) on live kit |
| Ownership & cadence | Named owners for location/status updates and a recurring tidy / spot-check ritual |

### Brief RACI

| Role | Accountable for | ADKAR focus |
|------|-----------------|-------------|
| **Sponsor** (ops / warehouse lead) | Mandate, blockers, celebrating clean compares; shutting down shadow spreadsheets after cutover | Desire, Reinforcement |
| **Inventory lead** | Locations, types, lists, import cutover, unknown-scan backlog | Knowledge, Ability |
| **Champions** | Peer coaching during hypercare; first live stocktakes | Desire, Ability |
| **Scanners** | Field updates during/after moves; honest unknown scans | Ability, Reinforcement |
| **IT / Admin** | Host, TLS, user accounts, optional SSO, backups, offboarding | Prerequisites, Reinforcement hygiene |

Glossary terms used below (**asset**, **inventory list**, **event list**, **scan session**, **History**) match [features-and-usage.md](features-and-usage.md#key-ideas-glossary).

---

## 2. ADKAR — Awareness

### Why the change

Without a shared system:

- Kit goes missing between venues, trucks, and stores.
- Spreadsheets diverge within days of a show or hire.
- Stocktakes take too long and still leave “inventory only” / “scanned only” gaps unexplained.

AV Asset Manager gives one catalogue, barcode-driven stocktakes, and an immutable **History** trail so changes are visible and trusted.

### Copy-ready: all-staff email / Slack

```text
Subject: We’re moving stocktakes and asset locations into AV Asset Manager

From [date], [warehouse / venue / team] will use AV Asset Manager as the shared
record for kit location and stocktakes.

Why: fewer lost items, faster stocktakes, and one place to look instead of
competing spreadsheets.

What you need to do:
- Wait for your account (an admin will create it — there is no self-signup).
- Attend the short training for your role (invite follows).
- After cutover, update location/status in the app when kit moves — not only in chat.

Questions: [champion name] or [inventory lead]. IT issues: [IT contact].
```

### Copy-ready: manager talking points (2-minute standup)

1. Spreadsheet stocktakes and “whoever last touched it” tracking are costing us time and lost kit.
2. AV Asset Manager is our shared catalogue + barcode stocktake tool. Phone install is fine; you still need network and HTTPS for the camera.
3. Accounts are created by admins. Roles: scanners run sessions; managers own lists/import/compare; admins own users and server settings.
4. After cutover, this app is the record of truth for location and status. Chat is for coordination, not the master list.
5. Champions: [names]. First live stocktake: [date / aisle / case family].

### FAQ (Awareness)

| Question | Answer |
|----------|--------|
| Does it work offline? | No. Phase 1 is online-only. The PWA is an installable shell; scans and saves need connectivity. |
| Why HTTPS on phones? | Browser camera APIs require a secure context. IT must provide TLS (public cert or trusted self-signed / private CA). |
| Who gets which role? | **Scanner** — scan and edit details. **Inventory manager** — lists, import/export, compare, types/locations. **Admin** — users, storage, SSO, export columns. |
| Can I sign up myself? | No. An admin creates your account. SSO (if enabled) only works after that. |
| Can we edit History? | No. History is append-only for trust and compliance. |

---

## 3. ADKAR — Desire

### What’s in it for each role (WIIFM)

| Role | Benefit |
|------|---------|
| **Scanner** | Faster aisle walks; duplicate-scan warnings; rack scans can expand children; less “is this on the list?” guesswork |
| **Inventory manager** | Import/export, compare reports (matched / inventory-only / scanned-only), bulk updates, clear unknown-scan follow-up |
| **Admin** | Controlled access, optional SSO for existing users, storage/backup hooks, export column policy |

### Champion network and early wins

- Name **champions** before training at scale — people others already ask for help.
- Pick a **small, visible win**: one warehouse aisle, one road-case family, or one hire package with clean barcodes.
- Leaders remove blockers: paid time for training, barcode cleanup, device HTTPS trust, and permission to stop maintaining the old sheet after cutover.

### Record of truth

After cutover for the pilot scope, **AV Asset Manager is the system of record** for location and status. Parallel spreadsheets for that scope become temporary crutches during soft launch only — then they are retired (see Reinforcement).

---

## 4. Data readiness (before training at scale)

Training on dirty data burns trust. Gate large Knowledge sessions until this checklist is green for the pilot scope.

| Gate | Ready when… |
|------|-------------|
| **Location tree** | Sites / rooms / stores exist and match how staff talk about places |
| **Item types & field sets** | Types cover the pilot kit; custom fields only where you will actually use them |
| **Identifier strategy** | Primary scan targets are filled and consistent — e.g. AST# (`fmi_ast`), TP barcode, RIG tag, serial, MAC — so lookups hit indexes instead of guesswork |
| **Import / cutover** | Spreadsheet columns mapped; a test import succeeded; duplicates and blank IDs cleaned — see [import-spreadsheet-prep.md](import-spreadsheet-prep.md) for AI-assisted prep |
| **Lists for first stocktake** | One **inventory list** (expected) and one empty **event list** prepared |
| **Optional modules** | Labels, stock/consumables, connections, RU visualiser stay **off** until the core scan → compare habit sticks; stage them deliberately later |

Detail for how-to: [features-and-usage.md](features-and-usage.md) (catalogue, import, lists). Preview / developer features: same doc, optional modules section.

---

## 5. ADKAR — Knowledge (train)

Link deep how-to to [features-and-usage.md](features-and-usage.md); use this section as curriculum design, not a second manual.

### Role-based curricula

**All users**

- Sign-in (password rules; optional SSO if enabled)
- Dashboard orientation
- Find an asset (search / identifiers)
- Optional: install the PWA shortcut on phone or tablet

**Scanners**

- Start / continue a **scan session** (event list ± inventory list)
- Duplicate scan **warns** — does not double-insert
- Parent / child expand when scanning a rack or container
- **Unknown scans** — park codes that do not match; do not invent assets on the floor
- Maintenance notes when kit is faulty or in service

**Inventory managers**

- Locations, item types, custom field sets
- Inventory vs event lists; import / export
- **Reports → Compare** three-way report and export
- Bulk updates; unknown-scan follow-up queue
- Optional later: shelf/bag labels, stock levels (after core habit is solid)

**Admins**

- Users and roles; offboarding
- Pointers only: Storage / backup, SSO, export columns ([Admin and IT brief](features-and-usage.md#admin-and-it-brief))
- Guest stock QR policy **if** you enable stock preview — treat tokens like physical keys

### Suggested training sequence

1. **Demo** — sponsor or inventory lead walks catalogue → scan → compare on a projector.
2. **Guided practice** — trainees use non-critical or sample data with a champion present.
3. **Supervised first live stocktake** — real aisle or case family; champion on the floor; compare report reviewed the same day.

Agenda templates: [Communication & training appendix](#8-communication--training-appendix).

---

## 6. ADKAR — Ability (roll out)

Align phases with the product’s [Suggested day-one path](features-and-usage.md#suggested-day-one-path).

| Phase | Goal | Exit gate |
|-------|------|-----------|
| **1. Pilot** | One site/team; clean identifier data; champions trained | Successful scan session + Compare on pilot kit; unknown scans triaged |
| **2. Soft launch** | Parallel with old process briefly if needed | Scanners prefer the app; inventory lead trusts compare output |
| **3. Cutover** | Stocktakes and day-to-day asset edits for that scope live in the app | Shadow spreadsheet for that scope retired; owners named |
| **4. Hypercare** | Champions on-call for first events / shows | FAQ and blockers logged; backlog of unknown scans under control |

### Practical Ability gates

- Phones/tablets use **HTTPS** and can open the camera scanner.
- Barcode values on labels match the identifier fields the scan path targets.
- Inventory + event lists prepared **before** the first real session (not during).
- At least one admin available to create accounts and reset access during hypercare.

---

## 7. ADKAR — Reinforcement (prevent stale data)

Adoption fails quietly when locations drift and nobody owns the tidy-up.

### Ownership

| Trigger | Who updates |
|---------|-------------|
| Kit moved between sites / stores | Scanner or assigned location owner — **Details** in-app |
| Repair / service | Person logging the work — **Maintenance** (+ Files if needed) |
| Hire-out / return | Inventory lead or designated hire owner |
| Unknown scan after a stocktake | Inventory lead clears or creates the asset |

### Cadence

- **Weekly:** location / status tidy for active areas; skim unknown-scan backlog.
- **Monthly:** spot-check stocktake on a critical inventory list; review Compare gaps.
- **Post-show / post-event:** Compare ritual before kit is declared “home.”

### Metrics (health signals)

- Unknown-scan backlog age and count
- Assets missing location or primary identifier
- Days since last stocktake on critical lists
- History activity on high-value assets (movement without silence)

### Recognition and accountability

- Celebrate clean compares and closed unknown-scan queues.
- After cutover, shut down spreadsheet shadow systems for that scope (archive, don’t “just keep both forever”).
- Sponsor reviews metrics in the same forum as other ops KPIs.

### Admin hygiene

- Offboard leavers promptly; review roles quarterly.
- Backup awareness: Storage settings and deploy/hardening docs (IT owns the mechanism; sponsor confirms recovery expectations exist).

### 30 / 60 / 90 day checklist

**By day 30**

- [ ] Pilot stocktake completed with Compare reviewed
- [ ] Champions active; FAQ from hypercare documented
- [ ] Weekly tidy owner named
- [ ] Unknown-scan queue assigned

**By day 60**

- [ ] Soft launch complete for agreed scope; cutover date set or done
- [ ] Shadow spreadsheet retired or dated sunset
- [ ] Monthly spot-check on calendar
- [ ] Optional modules still deferred unless deliberately staged

**By day 90**

- [ ] Cutover reinforced; metrics reviewed with sponsor
- [ ] Role review and offboarding check done
- [ ] Next expansion scope (another aisle / site) agreed with data-readiness gate
- [ ] Decision recorded on labels / stock / other preview features

---

## 8. Communication & training appendix

### Launch email

```text
Subject: AV Asset Manager — go-live for [scope]

We are live for [warehouse aisle / team / venue] from [date].

Your role: [scanner / inventory manager / admin]
Sign-in: [URL]
Help on the floor: [champion]
How-to guide: ask your champion, or see the Features and usage doc your admin shared.

Remember: update location and status in the app when kit moves.
```

### Reminder email (soft launch → cutover)

```text
Subject: Reminder — [scope] cutover [date]

From [date], AV Asset Manager is the record of truth for location and status
for [scope]. Please stop updating the old spreadsheet for that kit.

Still blocked? Tell [champion] today so we can fix barcodes, access, or lists
before cutover.
```

### Hypercare Slack / Teams snippet

```text
Hypercare this week for AV Asset Manager ([scope]).
Camera/HTTPS issues → [IT]
Scan / list / compare questions → [champion]
Account access → [admin]
Unknown codes after a session → don’t invent assets; leave as unknown scan
and flag [inventory lead].
```

### One-pager: “How we use AV Asset Manager here”

| We… | By… |
|-----|-----|
| Create accounts | Admins only (no self-signup; SSO only for existing users) |
| Run stocktakes | Scanners via Scan → event list; managers prepare inventory lists |
| Judge the stocktake | Reports → Compare (matched / inventory-only / scanned-only) |
| Update after moves | Details (location/status) in the app the same day |
| Log faults | Maintenance on the asset |
| Trust the past | History is read-only — no editing the audit trail |
| Add fancy modules later | Labels, stock, connections, RU — only when core habit is solid |

### Training agenda — scanners (30 minutes)

| Mins | Topic |
|------|--------|
| 0–5 | Why we’re here; HTTPS / online-only; who to ask |
| 5–10 | Find an asset; open Details |
| 10–20 | Guided scan session (duplicate warn, parent/child, unknown) |
| 20–25 | When to add Maintenance |
| 25–30 | Practice + Q&A; next live stocktake date |

### Training agenda — inventory managers (60 minutes)

| Mins | Topic |
|------|--------|
| 0–10 | Awareness + Desire: record of truth, data gates |
| 10–25 | Locations, types, field sets; identifier strategy |
| 25–40 | Lists, import/export, prepare inventory + event lists |
| 40–50 | Live or sample Compare report; unknown-scan follow-up |
| 50–55 | Bulk updates; what scanners will do on the floor |
| 55–60 | Cadence (weekly/monthly/post-event); Q&A |

### Full RACI table

| Activity | Sponsor | Inventory lead | Champion | Scanner | IT / Admin |
|----------|:-------:|:--------------:|:--------:|:-------:|:----------:|
| Mandate & cutover date | A | C | C | I | I |
| User accounts & roles | I | C | I | I | A/R |
| TLS / hosting / backups | I | I | I | I | A/R |
| Locations, types, import | C | A/R | C | I | C |
| Identifier / barcode cleanup | C | A | R | C | I |
| Training delivery | A | R | R | I | C |
| First live stocktake | A | R | R | R | C |
| Unknown-scan clearance | I | A/R | C | C | I |
| Weekly tidy / monthly spot-check | A | R | C | R | I |
| Retire shadow spreadsheets | A | R | C | I | I |
| Optional module rollout | A | R | C | I | C |

*R = Responsible, A = Accountable, C = Consulted, I = Informed.*

---

## 9. Workspace media — AI image prompts (appendix)

Printable posters, tent cards, and shelf copy reinforce Awareness, Desire, Knowledge, and Reinforcement on the floor. Paste the prompts below into an image generator (or brief a designer). Keep typography large enough to read from aisle distance; leave empty space for your org name, go-live date, champion names, and app URL or QR.

**Brand for all prompts:** black and white primary; charcoal greys for secondary text; turquoise `#00adb7` only for thin accents, icons, or a single emphasis word — match TP / AV Asset Manager product styling. Flat or lightly textured industrial warehouse aesthetic; no purple gradients, no cluttered dashboards, no fictional app screens with invented buttons.

### How to use

1. Generate → print → laminate if the warehouse is dusty or wet.
2. Replace `[Org]`, `[URL]`, `[champion]`, and dates with your real values (or leave blank and write on after print).
3. Prefer one message per piece. Do not invent product features beyond what this guide and [features-and-usage.md](features-and-usage.md) describe.

---

### 9.1 Launch / Awareness poster — why we are changing

| | |
|--|--|
| **Purpose** | Awareness — make the reason for cutover visible before training |
| **Format** | A3 portrait poster (or A2 in large bays) |
| **Placement** | Warehouse entrance, goods-in desk, tea room |

```text
Design a clean A3 warehouse launch poster for AV Asset Manager stocktake software.
Layout: large bold black headline at top, short supporting sentence, three simple
icon-and-text rows, thin turquoise #00adb7 accent line under the headline only,
mostly black/white/charcoal, generous white space, no photos of people, no fake
UI screenshots. Industrial but minimal — suitable for a professional AV warehouse.

Headline: “One shared record for kit”
Subhead: “We’re moving stocktakes and locations into AV Asset Manager.”
Three rows with simple line icons:
1. Less lost kit between venues, trucks, and stores
2. Faster stocktakes with barcode scan sessions
3. One place to look — not competing spreadsheets
Footer in small charcoal text: “Accounts by admin · Online when you scan · Ask [champion]”
Leave a blank rectangle at bottom-right labelled “Go-live: ________” for handwriting.
```

---

### 9.2 How-to poster — scan session steps

| | |
|--|--|
| **Purpose** | Knowledge / Ability — remind scanners of the live stocktake path |
| **Format** | A3 landscape or portrait poster |
| **Placement** | Next to labelled aisles used for stocktakes; scan staging tables |

```text
Create an A3 step-by-step how-to poster titled “Stocktake scan — do this”
for AV Asset Manager. Black/white primary, turquoise #00adb7 only on step numbers
in small circles. Numbered list 1–5 with short verbs, large readable type for
warehouse distance, flat icons (phone, barcode, list, warning triangle, chart).
No inventing extra app features. Steps:
1. Open Scan and start or continue your session (event list ready)
2. Scan barcodes that match our identifiers (AST#, TP barcode, RIG tag, serial…)
3. Duplicate scan? Heed the warning — do not force a double count
4. Unknown code? Leave it as an unknown scan — do not invent an asset on the floor
5. Manager runs Reports → Compare (matched / inventory-only / scanned-only)
Footer: “Need network + HTTPS for camera · Online-only · Help: [champion]”
Minimal industrial poster style, high contrast, ample margins for lamination.
```

---

### 9.3 How-to poster — when to update an asset

| | |
|--|--|
| **Purpose** | Knowledge / Reinforcement — day-to-day edits, not only stocktake day |
| **Format** | A3 portrait poster |
| **Placement** | Dispatch / returns desk; repair bay |

```text
Design an A3 decision poster: “When kit moves, update the app.”
AV Asset Manager branding: black headline, white background, charcoal body,
turquoise #00adb7 used sparingly on checkmark icons only. Four clear rows:
• Moved site / store / room → update Details (location / status) same day
• Faulty or in service → log Maintenance on the asset
• Hire-out or return → inventory lead or hire owner updates
• After a stocktake unknown → inventory lead clears or creates the asset
Bottom banner in black: “Chat coordinates. The app is the record.”
Small footer: “History is read-only — changes are appended, not edited.”
Clean warehouse signage look, no stock photos, no cluttered cards.
```

---

### 9.4 Desire / champion reminder card

| | |
|--|--|
| **Purpose** | Desire — peer coaching visibility and WIIFM during hypercare |
| **Format** | A5 or A6 card (print double-sided if useful) |
| **Placement** | Champion pockets, shift handover folders, soft-launch welcome packs |

```text
Design a simple A5 peer-champion reminder card for rolling out AV Asset Manager.
Front: bold black title “Floor champion”, turquoise #00adb7 underline, short line
“Ask me about scan, lists, and Compare.” Space for handwritten name and phone/Slack.
Back: three bullet benefits in charcoal — faster aisle walks; duplicate-scan
warnings; less “is this on the list?” guesswork. Tiny footer: “Hypercare: unblock
scanners · escalate HTTPS/camera to IT · escalate accounts to admin.”
Minimal black/white card design, no illustrations of people, high contrast print.
```

---

### 9.5 Reinforcement poster — record of truth

| | |
|--|--|
| **Purpose** | Reinforcement — retire shadow spreadsheets after cutover |
| **Format** | A3 poster |
| **Placement** | Near the old whiteboard / shared-drive reminder area; ops office |

```text
Create a bold A3 reinforcement poster for warehouse cutover.
Huge black headline: “AV Asset Manager is the record of truth”
Subhead in charcoal: “For [scope], stop updating the old spreadsheet.”
One turquoise #00adb7 accent bar. Three short black rules:
• Location and status live in the app
• Spreadsheets for this scope are archived after cutover
• Still blocked? Tell [champion] today
Footer: “Sponsor reviews clean compares with other ops KPIs.”
Stark, high-contrast, poster-as-policy look — not playful, not cluttered.
```

---

### 9.6 Reinforcement poster — update after moves

| | |
|--|--|
| **Purpose** | Reinforcement — prevent location drift between stocktakes |
| **Format** | A3 or A4 poster |
| **Placement** | Loading bay, truck bay doors, cage/store exits |

```text
Design a high-visibility A3/A4 poster for an AV equipment loading bay.
Headline in large black type: “Moved it? Update it.”
One sentence: “Update location and status in AV Asset Manager the same day.”
Simple icon of a road case and a phone (line art, black). Thin turquoise #00adb7
corner accent only. Footer charcoal: “Weekly tidy owner: ________”
Industrial safety-sign energy but on-brand black/white, not yellow hazard stripes.
Empty space for org logo stamp.
```

---

### 9.7 Reinforcement poster — unknown scans

| | |
|--|--|
| **Purpose** | Reinforcement / Ability — honest unknowns beat invented assets |
| **Format** | A3 poster |
| **Placement** | Scan staging table; inventory lead desk |

```text
Create an A3 poster titled “Unknown scan? Don’t invent the asset.”
AV Asset Manager style: black/white, turquoise #00adb7 on the word “Don’t” only.
Body copy in short lines:
• Park codes that do not match — leave as unknown scan
• Flag [inventory lead] after the session
• Managers clear the queue or create the asset properly
• Inventing kit on the floor breaks Compare
Footer: “Honest unknowns beat a fake catalogue.”
Clean typography-led warehouse poster, generous margins, no fake UI mockups.
```

---

### 9.8 Desk tent card — hypercare contacts

| | |
|--|--|
| **Purpose** | Ability / Desire — hypercare “who to ask” without opening email |
| **Format** | A4 folded tent (two A5 faces) or pre-scored tent card |
| **Placement** | Pack desks, goods-in, champion stations during first 1–2 weeks |

```text
Design a printable A4 landscape desk tent card (fold in half) for AV Asset Manager
hypercare. Side A heading “Hypercare this week” in black, turquoise #00adb7 thin
rule. Three labelled blank lines for handwriting: Camera/HTTPS → IT ________ ;
Scan / list / Compare → champion ________ ; Accounts → admin ________ .
Side B heading “Unknown codes” with short rule: leave as unknown scan — do not
invent assets — flag inventory lead. Minimal black/white office tent, large type,
no decorative flourishes.
```

---

### 9.9 Shelf / bay label strip — copy for print or label maker

| | |
|--|--|
| **Purpose** | Knowledge — tie physical bays to the catalogue language staff use |
| **Format** | Long shelf strip or 50–100 mm label tape; text-first (generator optional) |
| **Placement** | Bay ends matching your location tree names |

```text
Design a simple horizontal shelf-end label strip for warehouse bays using
AV Asset Manager. Black text on white, small turquoise #00adb7 left tick mark.
Show example layout fields as editable templates (not filled with fake SKUs):
Line 1 (bold): LOCATION: [Site / Room / Store name]
Line 2: Stocktake list: [inventory list name]
Line 3 (small): Scan into event list · update Details if kit leaves this bay
Keep it printable at ~300×60 mm, high contrast, no barcodes invented — leave a
blank rectangle marked “Optional QR to app URL”.
```

*Text-only alternative (label maker, no AI image):*

```text
[LOCATION NAME]
Update in AV Asset Manager when kit leaves this bay
Stocktake: Scan → event list · Help: [champion]
```

---

### 9.10 QR-to-app reminder (poster or tent)

| | |
|--|--|
| **Purpose** | Awareness / Ability — shorten path to sign-in on phones |
| **Format** | A4 poster or A5 tent with large QR placeholder |
| **Placement** | Entrance, locker area, scan staging; anywhere phones are unlocked for work |

```text
Design an A4 “Open AV Asset Manager” reminder with a large empty square in the
centre labelled “Paste QR to [URL] here” (do not generate a real QR code).
Black headline, charcoal subtext: “Installable PWA shortcut is fine — you still
need network. Camera scan needs HTTPS.” Turquoise #00adb7 thin frame around the
QR placeholder only. Footer: “No self-signup — admin creates accounts.”
Minimal black/white print, warehouse-safe contrast, space for org name.
```

---

## 10. Related documents

| Document | Use it for |
|----------|------------|
| [features-and-usage.md](features-and-usage.md) | Roles, glossary, workflows, day-one path |
| [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) | Server install and upgrades |
| [self-signed-tls.md](self-signed-tls.md) | HTTPS for mobile camera on LAN |
| [sso-setup.md](sso-setup.md) | Optional Google / Microsoft sign-in |
| [security-hardening.md](security-hardening.md) | Production hardening |
| [hardware-sizing.md](hardware-sizing.md) | Host capacity planning |
| [README](../README.md) | Project overview and local quick start |
