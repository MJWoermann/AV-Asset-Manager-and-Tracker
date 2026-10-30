# Project Learning Harvest

Review of AV Asset Manager (Laravel, Blade, Alpine, Tailwind, Sanctum, Spatie Permission, MariaDB with SQLite tests). History is short and dense: about 90 commits from 8–31 Oct 2026. Conclusions below were checked against current code or tests. Commit subjects are cited only where the subject or the patch was inspected. `.ai/rules` is absent. There is no root `SECURITY.md` and no `.github/workflows` tree.

`docs/ui-interaction-guidelines.md` already holds the UX corrections that were rewritten in this repo (short nav, profile admin, mobile hubs, URL filters, preference merges, scroll and back-stack rules, import memory, rename aliases, password rules, a security checklist, and a doc set). Those items are **KEEP**. This proposal does not restate them.

## Executive summary

The highest-value lessons for a shared template:

1. **Ship CI on day one** (Pint and PHPUnit). This repo has neither a workflow nor a Pint composer script, so formatting and the ~96 test classes are local-only.
2. **Automate the MySQL/MariaDB 64-character identifier rule.** It already lives in `.cursor/rules/mysql-identifier-length.mdc` after install failures, and PHPUnit’s in-memory SQLite will never fail those names. The Laravel migrations skill still recommends bare `constrained()`.
3. **Treat an append-only audit log as events plus database grants.** `AuditLog` only overrides instance `update()` and `delete()`. The regression test calls `update()` only.
4. **A limited partner role needs its own route group and a hard exclusion from the staff predicate**, plus object checks, because a permission name alone will not keep them out. `external_repairer` is the project instance; the role name stays here.
5. **Named invariants need behavioural tests.** Scanning a container expands children in `ScanService`, and comparison returns matched / inventory-only / scanned-only, but the suite never asserts child rows or those buckets. The comparison test asserts a PDF header.
6. **Keep the UI playbook.** The expensive UX rework (menus, filters that were reversed, scroll, mapping memory) is already written down. Add only the gaps: same-app return URLs, Blade/Alpine attribute binds, modal body scroll lock, and exact header match before fuzzy import guesses.

### Also worth taking forward

These four are part of the harvest to carry into the shared template. Each one is already specified later in this document.

1. **Same-app return URLs.** The rule and proposed wording are in Proposed playbook changes.
2. **API controllers must not extend web controllers.** The failure, the reflection test, and the classification are in Highest-value template improvements, item 6.
3. **Exact header match before fuzzy import guesses.** The playbook sentence is in Proposed playbook changes.
4. **Three small helpers: `SafeReturnUrl`, `ColumnPreference`, and `CsvFormula`.** Purpose, dependencies, and the tests required before adoption are in Proposed reusable code.

## Highest-value template improvements

### 1. Continuous integration for format and tests

- **Classification:** A
- **Confidence:** HIGH
- **Existing equivalent:** none in-repo. `composer.json` script `test` runs `config:clear` then `php artisan test`. `laravel/pint` is a dev dependency with no script. Glob of `.github/workflows` returned nothing.
- **Action:** ADD

A template that expects agents to run Pint and PHPUnit still ships unmarked formatting and unrun tests when nobody is watching. Five different Laravel apps all need this gate. SQLite-in-memory (`phpunit.xml`) stays the fast job. Identifier length is covered by the MariaDB job in item 2.

### 2. MariaDB identifier length as a check, not a memory

- **Classification:** E
- **Confidence:** HIGH
- **Existing equivalent:** `.cursor/rules/mysql-identifier-length.mdc` (KEEP the rule text). `.cursor/skills/laravel-best-practices/rules/migrations.md` still shows `$table->foreignId(...)->constrained()` with no 64-character warning (**CLARIFY**). `phpunit.xml` uses `DB_DATABASE=:memory:` SQLite.
- **Action:** AUTOMATE, and CLARIFY the skill so it does not contradict the rule

Commit `b53a8a0` (“Resolve Table Name lenght error on install”) and the rule’s citation of SQLSTATE 1059 match migrations that already use short names (`ntr_template_id_fk`, `ai_mm_iface_tpl_id_fk`, `item_types_dept_id_fk`, `ast_co_asset_ret_idx`). Agents who follow the skill’s `constrained()` example will recreate the install failure. A static scan of migration strings, or a CI job that runs `php artisan migrate` on MariaDB, is the check that survives a new chat.

### 3. Append-only history that Eloquent cannot quietly rewrite

- **Classification:** A (apps that keep an audit or ledger table)
- **Confidence:** HIGH
- **Existing equivalent:** `CLAUDE.md` / `AGENTS.md` hard rule; `docs/security-hardening.md` (“App design forbids update/delete; deploy guide can revoke DB UPDATE/DELETE”); `docs/ui-interaction-guidelines.md` §8 (redact passwords). **STRENGTHEN** the mechanism. Do not add a second “never update audit logs” sentence.
- **Action:** STRENGTHEN

`app/Models/AuditLog.php` throws from `update()` and `delete()` and has no `updating` / `deleting` model event. `tests/Feature/AssetCheckoutTest.php` `test_audit_logs_are_append_only_for_checkout` only calls `$log->update(...)`. Query-builder updates and deletes do not call those instance methods. `AuditableObserver` does redact `password` and `remember_token`, and it is registered for a subset of models (Asset, lists, locations, users, connections, interfaces). Domain events (checkout, repair, merge) write extra rows from services.

Template shape: throw from model events so `save()` on a dirty row is covered; keep a deploy step that revokes `UPDATE`/`DELETE` for the app user; test both `update()` and `delete()`; document that a query-builder write is a database-grant problem. Copying only the two method overrides would repeat the gap.

### 4. Limited partner access is a route group, not a permission label

- **Classification:** B
- **Confidence:** HIGH
- **Existing equivalent:** playbook §9 says “Authorize every mutating route” and “do not rely on hidden UI”. It does not say how a role that is allowed into the app stays out of the staff surface. **STRENGTHEN** §9. Project role name stays local (`av-asset-manager.mdc`).
- **Action:** STRENGTHEN

`routes/web.php` splits `middleware(['auth', 'operator'])` from the broad `staff` group, with comments that `external_repairer` stays out of staff. `User::hasStaffAccess()` returns false for that role even when the role holds permissions. `Gate::before` in `AppServiceProvider` lets `admin` pass operational abilities and returns null for feature abilities (`AccessPermissions::isFeature`). `routes/api.php` sits entirely behind `staff`. `tests/Feature/ExternalRepairerAuthorizationTest.php` and `RolePermissionTest::test_external_repairer_cannot_open_the_asset_index` lock the split. Commit `975e65c` removed a second copy of profile routes from the staff group after they had been registered twice.

Applies when any authenticated role may see only assigned work. Object checks still belong on those routes: this role’s department scope is intentionally off (`departmentScopeIds()` returns null), so `AssetController` must 403 unassigned assets. A new staff route with only `auth` will not be caught unless a test lists staff-only URLs.

### 5. Token abilities and role permissions are both required, at the same granularity

- **Classification:** A (apps that issue personal access tokens)
- **Confidence:** HIGH
- **Existing equivalent:** `docs/security-hardening.md` finding H1 is marked mitigated. `RolePermissionTest::test_api_delete_requires_role_permission_even_when_token_is_unrestricted` locks “`*` still 403”. **KEEP** that test pattern. **CLARIFY** ability names so a write ability does not authorize a delete permission.
- **Action:** KEEP the dual check, CLARIFY granularity

`routes/api.php` puts `permission:` and `abilities:` on each route. `DELETE /api/assets/{asset}` uses permission `assets:delete` and ability `assets:write`. `app/Support/ApiTokenAbilities.php` defines `assets:write` as “Create and update assets” and has no delete ability. Import routes use permission `import:run` with ability `assets:write`. A token minted for edits therefore satisfies the ability middleware on delete; only the Spatie permission stops it. That is safe today and easy to break when a future route copies the ability line and forgets the permission.

### 6. HTTP controllers and JSON controllers stay siblings

- **Classification:** E
- **Confidence:** HIGH
- **Existing equivalent:** `tests/Feature/Api/UnloadableApiControllersTest.php` `test_api_controllers_load_via_reflection`. Laravel routing skill shows controllers extending the base controller and does not forbid extending a web controller. **STRENGTHEN** the skill; **KEEP** the test idea.
- **Action:** STRENGTHEN

Commit `09771db` records the failure: JSON controllers that extended Blade controllers fataled on route reflection because signatures were incompatible. The fix moved shared behaviour to `app/Http/Controllers/Concerns/` and asserted the API classes extend the base `Controller` and do not extend `FileController`, `ConnectionTypeController`, `CustomFieldSetController`, or `RepairProcedureController`. Any app with a parallel web and API surface should inherit from the framework base and share a trait or a service.

### 7. Invariants in the agent guide need row-level tests

- **Classification:** B
- **Confidence:** HIGH
- **Existing equivalent:** `CLAUDE.md` testing mindset (child expand on rack scan; duplicate scan warns; comparison has three sections). Testing skill already says cover each decision and failure mode (**KEEP**). It does not say a file-magic or HTTP-200 assertion is the wrong oracle for a set comparison.
- **Action:** STRENGTHEN the testing skill

`ScanService::scanCode()` inserts children with `is_child_expand` true (`addItem(..., true)` around the child loop). A search of `tests/` found no `is_child_expand`. Duplicate behaviour is tested (`ScanSessionTest::test_duplicate_scan_warns_without_creating_second_item` and `test_duplicate_scan_still_updates_asset_status_from_session`) and is backed by a unique `['asset_list_id', 'asset_id']` on `asset_list_items`. `ComparisonReportPdfTest::test_authenticated_user_can_download_comparison_report_as_pdf` builds a matched asset, an inventory-only asset, and a scanned-only asset, then asserts the body starts with `%PDF`. `ListComparisonService::compare()` returns those three collections and is not asserted.

The missing tests are project work (classification G if someone files them only as AV cases). The template rule is the general one: if a guide names an outcome, the test must observe that outcome.

### 8. Tenant scope is an allow-list of models, fails closed without an actor, and forces a uniqueness review

- **Classification:** B (apps with a tenant or department boundary)
- **Confidence:** HIGH
- **Existing equivalent:** testing skill `rules/endpoint-tests.md` already prefers 404 over 403 when existence must stay hidden (**KEEP**). `DepartmentIsolationTest::test_non_admin_cannot_show_or_update_other_department_asset` matches that. No playbook section describes the scope itself.
- **Action:** ADD the rule to the Laravel security skill (new playbook section is not justified)

`app/Models/Concerns/BelongsToDepartment.php` applies global scope `department`. An empty id list becomes `whereRaw('0 = 1')`. A null id list means unrestricted. `Auth::user() === null` returns immediately, so the scope does not filter guests, jobs, or artisan. `Repair` has no `department_id` and no trait; staff with `repairs:read` can load any repair id. `Location` is shared on purpose (`DepartmentIsolationTest::test_shared_location_usable_across_departments`). Item types gained `department_id` in `2026_10_19_204350_add_department_id_to_item_types_table.php` while `name` and `slug` stayed globally unique from `2026_10_08_010200_create_item_types_and_custom_fields_tables.php`.

Template rule: every tenant-owned model uses the scope or sits on a written exception list; unauthenticated queries fail closed unless the call site uses an explicit bypass; adding `tenant_id` includes a pass over existing unique indexes. `tests/TestCase.php` `actingAs()` attaches a non-admin with no departments to slug `general` and sets the active department. That helper is convenient and can hide a missing membership. Document it beside the tenant tests.

### 9. One install command that migrates and seeds

- **Classification:** A
- **Confidence:** HIGH
- **Existing equivalent:** `app/Console/Commands/AppInstallCommand.php` calls `migrate --force` and `db:seed --force`. `composer.json` `setup` copies `.env`, generates a key, runs `migrate --force`, and builds front-end assets. It does not seed and does not call `app:install`.
- **Action:** CLARIFY docs and the composer script so a fresh checkout has roles and a first admin

Two boot paths that diverge after `git clone` produce “migrated but no roles” failures. Point `composer setup` at the same command operators use.

## Development accelerators

These are small, evidenced, and worth having in the starter before the first feature branch.

- **Shared web/API traits** (`app/Http/Controllers/Concerns/`, introduced when inheritance broke reflection). Depend on the framework base controller. Strip domain methods; keep the rule “sibling controllers, shared trait or service”.
- **Allow-listed column layout** — `app/Support/ColumnPreference.php` `resolve()` filters to an allow-list, falls back to defaults, and forces a required key first. Test: `AssetColumnPreferenceTest::test_name_column_is_always_retained`. See Proposed reusable code.
- **Same-app return URLs** — `app/Support/SafeReturnUrl.php` with `tests/Unit/SafeReturnUrlTest.php`. Playbook §9 does not mention open redirects.
- **CSV formula escape** — `app/Support/CsvFormula.php`, `tests/Unit/CsvFormulaTest.php`. Any export that users open in Excel.
- **Import oracle for calculated cells** — `ImportAssetTest::test_xlsx_import_uses_calculated_formula_results_not_formula_text` expects `Widget-Alpha` and `150`, and rejects a leading `=`. Pair with formula-injection escape on the way out.
- **Department context in `TestCase::actingAs()`** only when the template itself is multi-tenant, and only with the side effect documented (item 8).

Do not extract `PageSize::ALL`. `app/Support/PageSize.php` turns `per_page=all` into `count()` and paginates that many rows (options go to 500). API asset index caps are separate and lower. `docs/security-hardening.md` already tracks browse availability as open (A8–A10). **KEEP** that finding. Classification of unbounded “all” is **H**.

## Mistakes we should never repeat

### Compiled Blade views in git

**Classification H. Confidence HIGH.** `git ls-files storage/framework/views` listed 161 compiled PHP files. `storage/framework/views/.gitignore` is missing. `.gitignore` ignores `/storage/*.key` and `/storage/pail` and leaves compiled views trackable. The template gitignore should exclude `storage/framework/views/*.php`, and CI should fail when any are tracked. Compiled views are environment output. The CI step is how this H item is enforced; it is the same learning, not a second rule.

### Agent debug routes and log files left in the fix

**Classification H. Confidence HIGH.** `git log -S "debug-ebc14c"` shows the string added in `554a3a7` (“Fix Importer location duplicates”) and removed in `93aed17`. Commit `09771db`’s message records removal of a temporary `/__debug/agent-log` route, JS/Blade probes, and `file_put_contents` logging. Playbook §9 already says not to weaken auth in shared paths. Add a CI grep for `__debug` and for debug log filenames so the next instrumentation commit cannot land.

### Blade `:disabled` bound to an Alpine expression

**Classification C (skill). Confidence HIGH.** In `09771db`, `resources/views/assets/partials/checkout.blade.php` changed `:disabled="mode !== 'user'"` to `x-bind:disabled="mode !== 'user'"` after Blade compiled `mode` as a PHP constant and asset show returned 500. The same commit fixed nested quotes inside `old()` in Alpine `x-data`. `.cursor/skills/laravel-best-practices/rules/blade-views.md` does not mention this. Five Blade + Alpine apps will hit it. Apps without Alpine still need the fact that a leading colon on a Blade attribute is a PHP echo.

### API class extends the web class

Covered in highest-value item 6. **H** as a pattern to refuse; the durable control is the reflection test.

### Fuzzy column guess before an exact core-field match

**Classification B. Confidence HIGH.** `ImportService::suggestMappings()` comments “Prefer exact matches on core asset field keys / normalized labels” before later guesses. `ImportAssetTest::test_suggest_mappings_maps_snake_case_headings_to_core_asset_fields` locks it. Commit `09771db` describes short custom labels such as “Address” stealing identifier columns when snake_case headers (`item_type`, `ip_address`) were fuzzy-matched. Playbook §7 says remember mappings and use aliases. **CLARIFY** §7 with one sentence: exact normalized matches on the canonical field list win before fuzzy or custom-field guesses. Learned mappings remain overridable defaults (already stated).

### Main browse hides child rows

**Classification F. Confidence HIGH.** Commit `d352ac4` (“Asset child in searches fix”). `AssetSearchTest::test_assets_index_lists_and_searches_child_assets` is the stable contract. Parent pickers stayed limited to top-level records (same test file, parent-search case). General lesson: a hierarchical index that filters `parent_id` null will omit nested records from search; constrain the picker, not the browse, unless the product truly wants a roots-only list.

### Decoding only the on-screen crop

**Classification C. Confidence HIGH** for the final code; the intermediate crop commit was not re-read line by line. `resources/js/app.js` `fullFrameDecodeSize()` states the preview may crop and the decoder reads the full sensor frame (`sx: 0`, `sy: 0`, full `videoWidth` / `videoHeight`), with a long-edge cap. Commit `3f853bb` added aim-box cropping; `0097a43` (“Scnner viewport update”) removed that crop (net deletion in `app.js`). Camera barcode UIs should treat preview framing and decode region as separate decisions. This is outside the field scan-to-fill policy; add a short conditional note to the scannable-identifier skill, not a new skill.

### Unbounded page size

**Classification H.** See Development accelerators. Cap page size in the template helper.

### Two token systems as the default starter

**Classification H** as a template default. **Confidence HIGH** that this app runs both on different surfaces. `routes/api.php` groups REST routes with `auth:sanctum`. `routes/ai.php` mounts MCP with `auth:api`, states that Passport OAuth is the primary auth for that route, and states that Sanctum is not registered there. Ship one first-party token guard. Add a second only when a client cannot use the first, and record the guard in a feature test next to `routes/ai.php` (`tests/Feature/Mcp/AvAssetManagerMcpTest.php` is the local test file; this review confirmed the route file and did not re-open the test body).

## Proposed automated guardrails

| Check | Classification | Confidence | Notes |
| --- | --- | --- | --- |
| GitHub Actions (or equivalent): `vendor/bin/pint --test` and `php artisan test --compact` | A / E | HIGH | Nothing in-repo runs these |
| MariaDB service job: `php artisan migrate --force` on a fresh database | E | HIGH | Automates the existing 64-character rule. SQLite job stays for speed |
| Fail if `git ls-files` includes `storage/framework/views/*.php` | H | HIGH | Same learning as “compiled Blade views in git”. 161 files tracked today |
| Fail on `__debug` route fragments or committed `debug-*.log` | H | HIGH | Same learning as “agent debug routes”. Seen in `554a3a7` / `09771db` |
| Reflection or architecture test: API controllers do not subclass web controllers | E | HIGH | Port the assertion style of `UnloadableApiControllersTest` |
| Ledger model test calls `update()` and `delete()` and expects rejection | E | HIGH | Extend the checkout audit test’s idea; query-builder writes stay a DB-grant check |
| Auth starter test: login lockout returns after the configured attempts | E | MEDIUM | `LoginRequest` calls `RateLimiter::tooManyAttempts(..., 5)`. No test references `RateLimiter` or status 429. `docs/security-hardening.md` M2 already notes reset-route throttle gaps. **KEEP** the doc and add the lockout test |
| Optional later: route list test that a partner role’s middleware set is disjoint from staff-only routes | E | MEDIUM | Current tests hit representative URLs. A full route dump is brittle; prefer an explicit list of staff-only named routes |

Do not add a guardrail that re-implements playbook §1–§8 in a linter. Conformity there is review and the existing feature tests (`NavigationHubTest`, `AssetColumnPreferenceTest`).

## Proposed playbook changes

### Restricted partner routes

- **Proposed rule:** An authenticated role that must not see the main product surface lives on its own middleware group. The staff predicate returns false for that role even if a permission string is present. Controllers on the shared group enforce object assignment. Tests request a staff-only URL and an unassigned object.
- **Evidence:** `routes/web.php` comments; `User::hasStaffAccess()`; `ExternalRepairerAuthorizationTest`; `975e65c`.
- **Target:** `docs/ui-interaction-guidelines.md` §9 (new row in the Do/Don’t table) and the Laravel security skill. No new playbook.
- **Current equivalent:** §9 “Authorize every mutating route”.
- **Proposed wording:** “When a role may sign in but may only see assigned work, keep it off the broad staff middleware group and make the staff-access check reject that role even if it holds some permissions. Enforce assignment in the controller. Test a staff-only route and an unassigned record.”
- **Confidence:** HIGH
- **Classification:** B

### Same-app return URLs

- **Proposed rule:** A `return_to` (or equivalent) query value must be a relative same-app path. Reject protocol-relative URLs, backslashes, and embedded schemes.
- **Evidence:** `app/Support/SafeReturnUrl.php`; `tests/Unit/SafeReturnUrlTest.php`. Used when unknown-scan follow-up builds a create URL (`ScanService::unknownScanSummary()` sets `return_to` to the scan page).
- **Target:** playbook §9, one Do row.
- **Current equivalent:** none. Hardening doc H3 covers outbound download schemes (`http`/`https` only), which is a different control. **KEEP** H3.
- **Proposed wording:** “Accept user-supplied return paths only after a same-origin relative-URL check (leading single slash, no scheme, no backslash, length cap). Fall back to a named route when the check fails.”
- **Confidence:** HIGH
- **Classification:** B

### Modal body scroll lock

- **Proposed rule:** The shared modal locks document scroll and restores it on close. A custom overlay uses that primitive.
- **Evidence:** `resources/views/components/modal.blade.php` sets `document.body.dataset.avScrollLockY` and `overflow-y-hidden`, and the panel uses `max-h-[calc(100vh-3rem)] overflow-y-auto`. Playbook §5 requires centering, max height, and internal scroll, and says custom overlays must follow the same contract. It does not mention locking the page behind the dialog.
- **Target:** new sentence in §5. **STRENGTHEN**, do not add a second modal section.
- **Current equivalent:** §5 shared primitive paragraph.
- **Proposed wording:** “The shared modal locks body scroll for the whole time it is open and restores the previous scroll position on close. Custom dialogs call that component so they inherit the lock; copying only the centered box leaves the page free to scroll underneath.”
- **Confidence:** HIGH for the component. MEDIUM that every custom Alpine dialog in this repo already uses it (not every overlay was opened in this review).
- **Classification:** B

### Exact field match before fuzzy import guesses

- **Proposed rule:** When suggesting column maps, exact matches on the canonical field keys and labels win before fuzzy or user-defined field guesses.
- **Evidence:** `ImportService::suggestMappings()`; `ImportAssetTest::test_suggest_mappings_maps_snake_case_headings_to_core_asset_fields`; `09771db`.
- **Target:** playbook §7, under “Remember successful workflow choices”.
- **Current equivalent:** §7 memory and alias rules (**KEEP**).
- **Proposed wording:** “Auto-map exact normalized headers onto the canonical field list before any fuzzy or custom-field guess. Remembered mappings still pre-select and remain editable.”
- **Confidence:** HIGH
- **Classification:** B

No other playbook sections should change. Sections 1–4, 6, 8, and 10 match code that was corrected into its current shape (navigation, the assets-vs-other hidden-column split in `AssetColumnPreferenceTest::test_hiding_a_column_on_assets_keeps_its_filter` and `test_hiding_a_column_on_a_list_clears_its_filter`, button order, scan-to-fill field list, scroll exceptions, password defaults, doc portfolio). Hidden-column behaviour was revised during the project until the playbook stated two different rules for Assets and other lists. That text is the decision record. Leave it.

## Proposed Cursor skill changes

Skill edits below are how agents see a learning that already has one classification. They are not extra rules. Blade colon-binds and camera full-frame decode are the two items whose classification is C. The others point at the learning they implement.

### `laravel-best-practices` — `rules/migrations.md`

- **Trigger:** generating a foreign key or index on MySQL/MariaDB.
- **Proposed addition:** If `strlen("{table}_{column}_foreign")` (or the index equivalent) would exceed 64, pass an explicit short name. Point at the cursor rule. Bare `constrained()` remains fine when the generated name fits.
- **Evidence:** `.cursor/rules/mysql-identifier-length.mdc`; commit `b53a8a0`; short names in email, department, and checkout migrations.
- **Confidence:** HIGH
- **Classification:** E (same as highest-value item 2)

### `laravel-best-practices` — `rules/routing.md`

- **Trigger:** adding an API controller beside an existing web controller.
- **Proposed addition:** Extend the framework base controller. Share validation or queries through a trait or a service. Do not extend the web controller.
- **Evidence:** `09771db`; `UnloadableApiControllersTest`.
- **Confidence:** HIGH
- **Classification:** E (same as highest-value item 6)

### `laravel-best-practices` — `rules/eloquent.md` (or `rules/security.md` if eloquent stays structural)

- **Trigger:** a model that must be append-only.
- **Proposed addition:** Reject writes in `static::updating` and `static::deleting`. Overriding `update()` and `delete()` leaves `save()` and the query builder uncovered. Pair with a database privilege note and tests for both instance methods.
- **Evidence:** `AuditLog.php`; `AssetCheckoutTest::test_audit_logs_are_append_only_for_checkout`.
- **Confidence:** HIGH
- **Classification:** A (same as highest-value item 3)

### `laravel-best-practices` — `rules/blade-views.md`

- **Trigger:** Alpine (or any client) binding written with a leading colon inside a Blade template.
- **Proposed addition:** A leading colon is a Blade PHP attribute. Client bindings that must not become PHP use the explicit directive (`x-bind:disabled`, `x-bind:class`). Avoid nested quotes inside `old()` when the surrounding attribute uses the same quote style.
- **Evidence:** `09771db` checkout partial.
- **Confidence:** HIGH
- **Classification:** C

### `laravel-best-practices` — `rules/security.md`

- **Trigger:** return URLs, superuser gate bypass, tenant global scopes, extra protocols (MCP, jobs).
- **Proposed addition:** (1) same-app return URL helper; (2) a superuser `Gate::before` lists the abilities it does **not** grant (this app skips feature flags — state that as an exclusion list); (3) tenant global scopes fail closed when no user is authenticated, with an explicit bypass for trusted jobs; (4) a non-HTTP surface calls the same staff predicate and permission as the matching route.
- **Evidence:** `SafeReturnUrl`; `AppServiceProvider` `Gate::before`; `RolePermissionTest::test_developer_feature_comes_from_the_role_for_non_admins`; `BelongsToDepartment` null-user return; `app/Mcp/Concerns/AuthorizesMcpTools.php` `requireMcpUser()` / `requirePermission()`; `routes/ai.php` throttle and `auth:api`.
- **Confidence:** HIGH
- **Classification:** B (return URLs, partner/staff split, tenant fail-closed) and F (non-HTTP clients). The superuser exclusion list is part of the partner/staff learning: `Gate::before` grants operational abilities to `admin` and returns null for feature abilities.

The security skill already covers rate limits, uploads, and parameterized queries. Do not duplicate those paragraphs. `docs/security-hardening.md` remains the project threat model (**KEEP**).

### `testing-best-practices`

- **Trigger:** a guide or ticket names a domain outcome; a tenant test; a ledger test; MySQL-only constraints.
- **Proposed addition:** Assert the rows or buckets, not only status and file magic. Cross-tenant 404 stays as already written in `rules/endpoint-tests.md` (**KEEP**). Add: SQLite will not catch identifier-length or MySQL index-prefix failures; call that out when the change is a migration. Add: helpers that silently attach a tenant can hide authorization bugs — assert membership explicitly when the test’s point is isolation. Add: append-only tests call `delete()` as well as `update()`.
- **Evidence:** `ComparisonReportPdfTest`; missing `is_child_expand` coverage; `phpunit.xml`; `TestCase::ensureDepartmentContext()`; audit test.
- **Confidence:** HIGH
- **Classification:** B (same as highest-value item 7)

### `scannable-identifier-fields`

- **Trigger:** changing the stocktake camera decoder, not ordinary text fields.
- **Proposed addition:** Under a short “Camera decode” note: the on-screen preview may crop; both detectors should read the full frame, with an explicit size cap. Do not crop the decode buffer to the aim rectangle unless a test shows the code survives that mapping.
- **Evidence:** `fullFrameDecodeSize()` in `resources/js/app.js`; commits `3f853bb` and `0097a43`.
- **Confidence:** HIGH for the final function. The skill’s field matrix (which columns get a Scan button) stays as it is (**KEEP**); those names are project-specific and already duplicated on purpose between the skill and playbook §4.
- **Classification:** C

### `tailwindcss-development`, `deploying-to-cloud`, `infer-conventions`

**NO CHANGE.** Tailwind usage here is brand tokens and Blade components; the playbook already covers action rows, contrast, and modals. Cloud deploy was not the source of the corrections above. `infer-conventions` is a meta skill and did not miss a repeated local convention that the other skills should not own.

## Proposed reusable code

### Same-app return URL

- **Source:** `app/Support/SafeReturnUrl.php`, `tests/Unit/SafeReturnUrlTest.php`
- **Purpose:** reject open redirects on `return_to` style parameters
- **Dependencies:** none beyond PHP
- **Generalise:** drop any AV route names; keep the sanitize rules (string, trim, max length 2048, single leading slash, reject `//`, `://`, and backslash)
- **Tests before adoption:** the existing unit cases, plus one feature test that a hostile `return_to` falls back to a named route
- **Classification / confidence:** B, HIGH (same learning as the playbook return-URL change; this file is the implementation)

### Column allow-list

- **Source:** `app/Support/ColumnPreference.php`
- **Purpose:** per-user table columns with an allow-list, defaults, and one required column pinned first
- **Dependencies:** `illuminate/support` collections (easily rewritten without them)
- **Generalise:** the required key defaults to `name`; make that argument mandatory in the template so asset vocabulary is not special
- **Tests before adoption:** empty selection restores defaults; unknown keys drop; required key is reinserted first; saving one preference key does not wipe unrelated JSON keys (this app tests that for department visibility in `DepartmentIsolationTest::test_admin_visibility_preference_merge_preserves_other_keys`, and playbook §6 already requires merge-by-key)
- **Classification / confidence:** D, HIGH

### Spreadsheet formula escape

- **Source:** `app/Support/CsvFormula.php`, `tests/Unit/CsvFormulaTest.php`
- **Purpose:** prefix `=`, `+`, `-`, `@` so exported CSV/XLSX cells open as text
- **Dependencies:** none
- **Generalise:** call it on every user-controlled string written to a spreadsheet. Keep the import path that reads calculated values (`test_xlsx_import_uses_calculated_formula_results_not_formula_text`) as the inbound twin, implemented with the spreadsheet library the app already chose
- **Tests before adoption:** the unit table in `CsvFormulaTest`, including numeric `-2` becoming `'-2`
- **Classification / confidence:** D, HIGH

### Tenant global scope (pattern, not a copy-paste of the trait)

- **Source:** `app/Models/Concerns/BelongsToDepartment.php`
- **Purpose:** default queries to the current user’s tenant ids
- **Dependencies:** an authenticated user, a tenant id column, a membership relation, and a written bypass
- **Generalise:** rename department to tenant. Change the null-user branch to fail closed. Keep an explicit `withoutTenantScope()` for admin aggregates and trusted jobs. Do not copy “external repairer sees all departments”.
- **Tests before adoption:** other-tenant show/update is 404; empty membership sees nothing; guest query sees nothing; a listed exception model stays global; a bypass is asserted at each call site
- **Classification / confidence:** B, MEDIUM for extracting the trait (same learning as highest-value item 8). The rule is HIGH. Copying the trait unchanged would copy the unauthenticated bypass and the repair gap.

Import mapping recall and alias tables are real (`ImportService` remembered mappings, `ItemTypeAlias` tests) and already specified in playbook §7. Extract them only when a second app has spreadsheet import. They are not a day-one starter.

## Verified learnings

### Idempotent “add to this batch” still has side effects

- **Symptom:** a second scan warned, and callers assumed the row was untouched.
- **Root cause:** duplicate detection skips the insert and still applies session status (and, later, parent assignment with a confirm step).
- **Final correction:** unique `['asset_list_id', 'asset_id']` plus `duplicate: true` in the service response. Status still updates. Tests: `test_duplicate_scan_warns_without_creating_second_item`, `test_duplicate_scan_still_updates_asset_status_from_session`.
- **General lesson:** an idempotency key belongs in the database. Write down which side effects still run on the duplicate path, and test those side effects. The exists-then-insert check is not a lock; the unique index is what stops a double row.
- **Evidence:** `ScanService` duplicate branch; migration `2026_10_08_010400_create_lists_and_scan_tables.php`; tests above.
- **Affected surfaces:** any ingest into a set (cart lines, attendance, stocktake).
- **Applicability:** apps that both deduplicate and mutate related state on a repeat event.
- **Confidence:** HIGH
- **Classification:** F

### Tree identity includes the parent

- **Symptom:** the same location name under different parents collapsed to one row on import.
- **Root cause:** lookup keyed only by name.
- **Final correction:** `ImportService` matches name with `parent_id` (or `whereNull('parent_id')`). Commit subject `554a3a7`.
- **General lesson:** a natural key for a node in a tree includes its parent. Name uniqueness is per parent unless the product wants a global catalogue.
- **Evidence:** parent filter in `ImportService` (exact query around the location lookup). The debug log from that same commit is the separate H item above.
- **Affected surfaces:** import and “find or create” of hierarchical catalogues.
- **Applicability:** any tree edited by name from a spreadsheet or API.
- **Confidence:** HIGH for the current query. MEDIUM for the original bug narrative beyond the commit subject and the current parent filter.
- **Classification:** F

### Duplicate detection should ignore intentional containment

- **Symptom:** a parent and child that share an identifier were offered as a merge.
- **Root cause:** duplicate grouping treated shared identifiers as “same object” with no ancestry check.
- **Final correction:** `AssetDuplicateMergeTest::test_default_duplicate_search_excludes_parent_child_identifier_matches`. Several commits on 19 Oct rebuilt the checker (`5ab3a9a`, `9784e1a`, `4e485f9`, `2799533`); the test is the stable contract.
- **General lesson:** when records are allowed to nest and also to share an identifier, default duplicate/merge search skips ancestor/descendant pairs.
- **Evidence:** test name and commit subjects above. The service body was not re-read line by line in this pass.
- **Affected surfaces:** merge wizards on hierarchical inventories.
- **Applicability:** only apps that both nest rows and propose merges. Otherwise G.
- **Confidence:** MEDIUM
- **Classification:** F

### Public capability URLs: hash, revoke, throttle, kill switch; expiry left open

- **Symptom:** a printed token must change one object and must be revocable.
- **Root cause:** bearer tokens in URLs are passwords.
- **Final correction:** `asset_stock_tokens.token_hash` is a unique 64-character hash; `revoked_at` and `last_used_at` exist; there is no `expires_at` (`2026_10_15_100200_create_asset_stock_tokens_table.php`). Guest routes are throttled. `StockConsumablesFeatureTest::test_guest_token_can_mutate_only_that_asset_and_revoked_token_404s` covers scope and revoke. Hardening doc M1 records a kill switch.
- **General lesson:** store a hash, bind the token to one object, support revoke, throttle the public route, and provide a kill switch. Add `expires_at` when the capability should die without an operator. This project left expiry open, so the template should not copy “no expiry” as a recommendation.
- **Evidence:** migration columns; test name; `docs/security-hardening.md` guest-token row.
- **Affected surfaces:** guest or emailed action links.
- **Applicability:** any unauthenticated capability URL. The stock-QR product itself is G.
- **Confidence:** HIGH for the mechanism. MEDIUM for “always add expiry” (product-dependent).
- **Classification:** F

### Test helper joins a default tenant

- **Symptom:** feature tests pass for users who were never given a department.
- **Root cause:** `TestCase::actingAs()` calls `ensureDepartmentContext()`, which attaches slug `general` when a non-admin has zero departments, then sets the active department in session.
- **Final correction:** none. The helper is current behaviour. The `general` row comes from the departments migration, so `RefreshDatabase` creates it.
- **General lesson:** a base `actingAs()` that repairs tenant setup will mask missing membership. Isolation tests should assign tenants explicitly and avoid relying on the repair.
- **Evidence:** `tests/TestCase.php`.
- **Affected surfaces:** every feature test that calls `actingAs()`.
- **Applicability:** multi-tenant test suites.
- **Confidence:** HIGH
- **Classification:** F

### Non-HTTP tools are another API

- **Symptom:** a Copilot/MCP server could have exposed a second, weaker authorization path.
- **Root cause:** new protocols often reimplement checks.
- **Final correction:** `AuthorizesMcpTools` requires `hasStaffAccess()` and the same operational permission as the matching route. `external_repairer` is rejected. `routes/ai.php` uses Passport and `throttle:mcp`.
- **General lesson:** each new client (HTTP, token, MCP, job) calls the existing authorization predicate. Business rules stay in services. This app has no in-app LLM client in the MCP route or the authorizer that was read; do not invent one in the template.
- **Evidence:** `routes/ai.php`; `app/Mcp/Concerns/AuthorizesMcpTools.php`; MCP feature test path cited by the suite layout (`tests/Feature/Mcp/AvAssetManagerMcpTest.php` was not re-opened line by line; route and trait were).
- **Affected surfaces:** any tool gateway in front of the same models.
- **Applicability:** apps that add a non-HTTP client. Otherwise omit.
- **Confidence:** HIGH for the trait. MEDIUM for “no model client anywhere in JS or Blade” (that wider search was not repeated here).
- **Classification:** F

### Preference merges and URL filters

Already in playbook §2 and §6, with tests named above. **KEEP.** Classification of a new rule: none. Recorded here so a later harvest does not file a duplicate.

## Do not propagate

**G — keep in this product**

- Online-only PWA (`vite` precache of the shell, empty runtime cache). Rejecting offline scan sync was a product decision. A field-service template might want a queue. Do not ban offline sync in the shared starter.
- Identifier names and which ones are camera-scannable (`fmi_ast`, `rig_tag`, `tp_barcode`, MAC). The general rule “physical barcode fields get scan-to-fill; typed-only ids do not” is already in the playbook and the scannable skill. The column list is local.
- Brand tokens (`brand-*`, `#00adb7`).
- Role names `admin`, `inventory_manager`, `scanner`, `external_repairer`, and the repair/spare/checkout domain (return blocked while a linked repair is open is a business rule; the generalisation “a hand-off cannot close while linked work is open” is only worth a template example if the starter has both concepts).
- Guest QR stock, RU visualiser, Brother label sizes, email digest schedule, department row colours.
- Shared locations and manufacturers across departments. That is a catalogue decision, covered by a test, not a universal tenant rule.
- Stack pins (Spatie Permission, Sanctum, Maatwebsite Excel, html5-qrcode, Alpine, Tailwind, vite-plugin-pwa). They worked here. Promote a library only when this review found a lesson, which is the case for formula results and for “do not subclass the web controller”, not for the library choice itself.
- `Gate::before` granting every operational ability to a role named `admin`. The transferable piece is “superuser bypass has an exclusion list”, already in the security skill proposal. The role name and the feature-flag split are local.
- Repair rows without `department_id`. That is a hole to fix in this app if departments must isolate maintenance, not a pattern to copy.

**H — refuse in the template**

- Committing `storage/framework/views/*.php`.
- Debug routes, `file_put_contents` traces, and debug log files in product commits.
- API controllers extending web controllers.
- Blade `:disabled="alpineExpression"` (and the same colon pattern for other client-only bindings).
- `per_page=all` that loads the full filtered table into one paginator page.
- Sanctum and Passport both installed as the default token story.
- Copying `BelongsToDepartment`’s unauthenticated bypass.
- A `%PDF` prefix or HTTP 200 as the only oracle for a reconciliation. That is highest-value item 7 (classification B), listed here so a later template does not treat the current comparison test as the pattern to copy.
- Speculative ML for import mapping. Playbook §7 already forbids it. The working approach is remembered maps plus alias rows.
- A second copy of the UI playbook inside `.cursor/rules`. `ui-interaction-guidelines.mdc` correctly points at the doc. Leave that indirection.

## Recommended template changes

**P0 — safety and correctness**

1. Add CI: Pint and PHPUnit.
2. Add a MariaDB migrate job (or an equivalent identifier-length check) and align `rules/migrations.md` with the 64-character rule.
3. Specify append-only models as events plus revoked `UPDATE`/`DELETE`, with tests for `update()` and `delete()`.
4. Ignore compiled views and reject debug instrumentation in CI.
5. Document partner-role route groups and same-app return URLs in the security skill and playbook §9.

**P1 — quality and speed**

6. Point `composer setup` at the single install command that migrates and seeds.
7. Add the API-sibling controller rule and a reflection test skeleton.
8. Teach the testing skill to assert named invariants, and to distrust SQLite for MySQL identifier limits.
9. CLARIFY playbook §7 with exact-before-fuzzy header matching.
10. Add Blade colon-attribute guidance.
11. Extract `SafeReturnUrl`, `ColumnPreference`, and `CsvFormula` into the starter only after their tests move with them.
12. Tenant starter: fail closed without an actor; exception list; uniqueness review when `tenant_id` arrives. Do not copy the repair gap.

The four items grouped under “Also worth taking forward” in the executive summary are already in this plan: return URLs (item 5), API sibling controllers (item 7), exact-before-fuzzy (item 9), and the three helpers (item 11).

**P2 — useful**

13. Modal body-scroll sentence in playbook §5.
14. Camera full-frame note on the scannable skill.
15. Login lockout feature test in the auth starter.
16. Ability names that match the permission they guard (`assets:delete` with a delete ability, or a comment and test that the coarser ability is deliberate).

**P3 — optional**

17. Route-list test for partner vs staff middleware, if representative URL tests feel too easy to forget.
18. Spreadsheet-import package (memory, aliases, calculated values) when a second product needs it.
19. Expiry column on public capability tokens when the product wants unattended expiry.

## Proposed patch manifest

Do not apply this manifest in AV Asset Manager as part of this review. Paths are what a shared-template maintainer would change. Source evidence is this repo.

In the shared template:

- ADD `.github/workflows/tests.yml` — Pint and PHPUnit; MariaDB migrate job. Source gap: no workflow files here; `phpunit.xml` SQLite.
- ADD `scripts/check-mysql-identifiers.php` (or the MariaDB job above). Source: `.cursor/rules/mysql-identifier-length.mdc`.
- MODIFY `.gitignore` — ignore `storage/framework/views/*.php` and keep the directory. Source: 161 tracked compiled views.
- ADD CI step grepping for `__debug` and `debug-*.log`. Source: commits `554a3a7`, `93aed17`, `09771db`.
- MODIFY `docs/ui-interaction-guidelines.md` §5 (body scroll lock), §7 (exact header match), §9 (partner routes, return URLs). Source: this file’s playbook section.
- MODIFY `.cursor/skills/laravel-best-practices/rules/migrations.md`
- MODIFY `.cursor/skills/laravel-best-practices/rules/routing.md`
- MODIFY `.cursor/skills/laravel-best-practices/rules/eloquent.md` (append-only events)
- MODIFY `.cursor/skills/laravel-best-practices/rules/blade-views.md`
- MODIFY `.cursor/skills/laravel-best-practices/rules/security.md`
- MODIFY `.cursor/skills/testing-best-practices/SKILL.md` and `rules/endpoint-tests.md` only if the 404 guidance needs a pointer; the 404 rule itself is **NO CHANGE**
- MODIFY `.cursor/skills/scannable-identifier-fields/SKILL.md` — short camera-decode note. Field matrix **NO CHANGE**
- MODIFY `composer.json` `setup` to call the install command that seeds. Source: `composer.json` vs `app/Console/Commands/AppInstallCommand.php`
- ADD `app/Support/SafeReturnUrl.php` and unit test, copied from this repo and stripped of domain routes
- ADD `app/Support/ColumnPreference.php` and unit test
- ADD `app/Support/CsvFormula.php` and unit test
- ADD a feature-test skeleton “API controller does not extend web controller”
- ADD an append-only model skeleton that uses `updating`/`deleting` events
- NO CHANGE `docs/ui-interaction-guidelines.md` sections 1, 2, 3, 4, 6, 8, 10
- NO CHANGE `.cursor/rules/ui-interaction-guidelines.mdc` (it already delegates)
- NO CHANGE `.cursor/rules/mysql-identifier-length.mdc` wording, aside from a pointer from the skill
- NO CHANGE `.cursor/skills/tailwindcss-development/SKILL.md`
- NO CHANGE `.cursor/skills/deploying-to-cloud/SKILL.md`
- NO CHANGE `docs/security-hardening.md` role in this repo (project threat model, not the template). Template receives the generalised controls, not this LAN threat model

In this repo, later and separately from the template:

- ADD tests that scan a container and assert child `is_child_expand` rows
- ADD a comparison test that asserts which assets land in `matched`, `inventory_only`, and `scanned_only`
- EXTEND `test_audit_logs_are_append_only_for_checkout` to call `delete()`, and move immutability to model events if the product wants `save()` covered
- STOP tracking `storage/framework/views/*.php`
- DECIDE whether repairs are department-scoped; if yes, add `department_id` and the trait, and revisit item-type unique indexes
- DECIDE whether `assets:write` on `DELETE` is intentional; if yes, comment it next to `ApiTokenAbilities`

## Areas too thin to promote

- Whether every custom Alpine dialog misses body scroll lock. The shared modal implements the lock. A full overlay audit was not done.
- Manufacturer catalogue memory work (`94746a0`) was not re-read. Not promoted.
- Login and password-reset throttle gaps are already listed in `docs/security-hardening.md`. No new severity was measured here.
- Duplicate-merge ancestry logic inside the service was not re-read; only the test name and commit subjects were. That item stays F / MEDIUM.
- No issue tracker or PR discussion beyond commit messages and GitHub-style squash subjects (`#2`–`#15`) was available locally. Evolution stories that rest only on a commit subject were left out or marked MEDIUM.
