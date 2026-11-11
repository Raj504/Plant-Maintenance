# PlantCare – Plant Maintenance & Operations Management System

A complete, self-contained Laravel 12 application for managing factory machinery,
maintenance requests, approvals, work orders, preventive maintenance, safety permits,
spare parts, daily plant logs, AMC contracts, statutory compliance and calibration.

* **Stack:** Laravel 12, PHP 8.2, MySQL / MariaDB, Blade + Bootstrap 5 (CDN), Chart.js, dompdf
* **No build step:** plain CSS/JS in `public/`, Bootstrap and Chart.js come from CDN. Vite / Tailwind are not used.
* **Every table is prefixed `pm_`** so the module can live inside an existing ERP database.

---

## Table of contents

1. [Quick start](#1-quick-start)
2. [Demo accounts](#2-demo-accounts)
2a. [All commands – reference](#2a-all-commands--reference)
3. [Project structure – where things are](#3-project-structure)
4. [Design decisions and why](#4-design-decisions-and-why)
5. [Database schema](#5-database-schema)
6. [RBAC – roles & permissions](#6-rbac--roles--permissions)
7. [Module guide](#7-module-guide)
8. [Business rules enforced in code](#8-business-rules-enforced-in-code)
9. [Automation / scheduler](#9-automation--scheduler)
10. [Reports & exports](#10-reports--exports)
11. [File uploads](#11-file-uploads)
12. [Testing](#12-testing)
13. [How to extend](#13-how-to-extend)
14. [Change log](#14-change-log)

---

## 1. Quick start

```bash
composer install
copy .env.example .env            # Windows   (cp on Linux/Mac)
php artisan key:generate
```

Edit `.env` – database section (XAMPP defaults shown):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plant_maintenance
DB_USERNAME=root
DB_PASSWORD=
FILESYSTEM_DISK=public
SEED_DEMO_DATA=true               # false on a real installation
```

```bash
php artisan migrate:fresh --seed  # creates all pm_* tables + roles + masters + demo factory data
php artisan storage:link          # makes uploaded files reachable at /storage/...
php artisan serve                 # http://localhost:8000
```

Scheduler (needed for automatic PM work orders and reminders):

```bash
php artisan schedule:work         # development – keeps running
# production cron:  * * * * * cd /path/to/project && php artisan schedule:run
```

You can also run every automated check by hand: `php artisan pm:run-schedulers`
(or the **Run Due Check** button on the PM Plans page).

## 2. Demo accounts

Created by `database/seeders/UserSeeder.php`. **Every account has the password `password`.**
The login page shows one-click buttons for them when `APP_ENV=local`.

| # | Email | Password | Name | Role | What they can do |
|---|---|---|---|---|---|
| 1 | admin@plant.com | password | System Admin | Super Admin | Everything |
| 2 | employee@plant.com | password | Ravi Operator | Employee / Requester | Raise and view own requests |
| 3 | inspector@plant.com | password | Imran Inspector | Maintenance Inspector | Step 2 – inspection |
| 4 | expert@plant.com | password | Tarun Expert | Technical Expert | Step 3 – technical assessment |
| 5 | supervisor@plant.com | password | Sunil Supervisor | Maintenance Supervisor | Assign, verify and close work orders, PM plans, permits |
| 6 | technician@plant.com | password | Tej Technician | Technician | Execute work orders, fill PM checklists, LOTO |
| 7 | approver@plant.com | password | Bhavna Approver | Budget / Admin Approver | Step 4 – approve / reject / send back, emergency authorisation |
| 8 | safety@plant.com | password | Sameer Safety | Safety Officer | Approve / close permits (PTW), LOTO |
| 9 | store@plant.com | password | Sheetal Store | Store Manager | Spares, stock ledger, issue / return, adjustments, purchase requisitions, vendors |
| 10 | operator@plant.com | password | Prakash Plant | Plant Operator | Daily plant logs, shift handover, running hours |
| 11 | compliance@plant.com | password | Chitra Compliance | Compliance Officer | Statutory compliance & calibration |
| 12 | manager@plant.com | password | Mahesh Manager | Maintenance Manager | All maintenance modules (no admin module) |
| 13 | management@plant.com | password | Meera Management | Management | Dashboard & reports, read-only |
| 14 | electrician@plant.com | password | Elan Electrician | Technician | Same as technician, member of the Electrical Team |

To change a password: log in → top-right menu → **My Profile**, or Admin → **Users** → edit.
On a real installation delete or deactivate these accounts after creating your own users.

## 2a. All commands – reference

Run every command from the project folder (`plant+project`, the folder that contains `artisan`).

### Installation (first time)

```bash
composer install                  # install PHP packages (Laravel, dompdf)
copy .env.example .env            # Windows;  cp .env.example .env  on Linux/Mac
php artisan key:generate          # sets APP_KEY in .env
```

Create the database in MySQL (phpMyAdmin or the command line), then put its name and credentials in `.env`:

```sql
CREATE DATABASE plant_maintenance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Database – migrations & seeding

```bash
php artisan migrate                      # create all tables (keeps existing data)
php artisan migrate:status               # show which migrations have run
php artisan migrate:fresh                # DROP everything and recreate all tables (empty)
php artisan migrate:fresh --seed         # drop, recreate AND seed (roles, masters, demo users, demo data)
php artisan migrate:rollback             # undo the last batch
php artisan db:seed                      # run all seeders on an existing database
```

Run a single seeder (safe to repeat – they use updateOrCreate / firstOrCreate):

```bash
php artisan db:seed --class=RolePermissionSeeder   # permissions + the 14 roles (run after adding a permission)
php artisan db:seed --class=MasterDataSeeder       # plant, departments, shifts, categories, vendors, teams, log types, checklists, settings
php artisan db:seed --class=UserSeeder             # the 14 demo accounts above
php artisan db:seed --class=DemoDataSeeder         # assets, spares + opening stock, BOM, PM plans, requests, work orders, AMC, compliance, calibration
```

`DemoDataSeeder` only runs when `SEED_DEMO_DATA=true` in `.env` (default) and only if there are no assets yet.
For a production installation set `SEED_DEMO_DATA=false`, run `php artisan migrate --seed`, log in as
`admin@plant.com`, create your real users, then deactivate the demo accounts.

### Running the application

```bash
php artisan serve                        # http://localhost:8000
php artisan serve --port=8080            # different port
php artisan storage:link                 # one time: makes uploads reachable at /storage/... (required for photos/documents)
```

### Scheduler / automation

```bash
php artisan pm:run-schedulers            # run every automated check once (PM due, overdue WOs, low stock, AMC, compliance)
php artisan pm:run-schedulers --only=pm  # only the preventive-maintenance due check
                                         #   other values: workorders | stock | amc | compliance
php artisan schedule:work                # development: runs the schedule in the foreground
php artisan schedule:list                # shows what is scheduled and when
```

Production cron (Linux) – one line, runs every minute and Laravel decides what is due:

```
* * * * * cd /path/to/plant+project && php artisan schedule:run >> /dev/null 2>&1
```

Windows Task Scheduler: create a task that runs `php artisan schedule:run` in the project folder every minute.

### Tests

```sql
CREATE DATABASE plant_maintenance_test;   -- one time; tests never touch plant_maintenance
```

```bash
php artisan test                                   # all tests
php artisan test --filter=SmokeRoutesTest          # every page + every report + exports
php artisan test --filter=MaintenanceWorkflowTest  # end-to-end business rules
```

### Maintenance / troubleshooting

```bash
php artisan route:list                   # all URLs with their permission middleware
php artisan optimize:clear               # clear config, route, view and cache files (after editing .env)
php artisan view:clear                   # if a Blade change does not show
php artisan config:cache                 # production only – cache config (run optimize:clear after .env edits)
php artisan tinker                       # interactive shell, e.g.  App\Models\Asset::count()
```

### Reset to a clean demo

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

Uploaded files live in `storage/app/public/pm/`; delete that folder too if you want a fully clean start.

## 3. Project structure

```
app/
  Console/Commands/RunSchedulers.php      pm:run-schedulers – all automated checks
  Helpers/helpers.php                     small global helpers (status_badge, money, flash_toast …)
  Http/Controllers/                       one controller per module, thin – they validate and call services
  Http/Controllers/Admin/                 users, roles, masters, settings, audit
  Http/Middleware/CheckPermission.php     permission:<name> route middleware (server-side RBAC)
  Http/Requests/                          Form Request validation for the bigger forms
  Models/                                 one Eloquent model per pm_* table
  Models/Concerns/HasAttachments.php      trait: uploads for any model
  Models/Concerns/HasHistory.php          trait: audit trail for any model
  Services/                               business logic (see below)
database/migrations/                      13 migrations, grouped by module, each documented
database/seeders/                         RolePermissionSeeder, MasterDataSeeder, UserSeeder, DemoDataSeeder
resources/views/
  layouts/app.blade.php                   header + sidebar + footer + toast
  partials/                               sidebar, header, footer, toast, attachments, history, upload-field
  <module>/index|form|show.blade.php      one folder per module
public/css/app.css  public/js/app.js      the only custom front-end files
routes/web.php                            all routes, grouped by module, permission on every route
routes/console.php                        schedule definition
tests/Feature/                            route smoke test + end-to-end workflow test
```

### Services (business logic lives here, not in controllers)

| Service | Purpose |
|---|---|
| `DocumentNumberService` | Generates `MR-2026-0001`, `WO-…`, `PTW-…` etc. with a row lock so numbers are never duplicated |
| `AttachmentService` | One place for all uploads: validation rule, storage path, metadata row |
| `NotificationService` | In-app notifications to a user or to everyone holding a permission; `dedupe_key` prevents duplicates |
| `MaintenanceRequestService` | Create / edit / re-submit / cancel requests |
| `ApprovalWorkflowService` | Inspection → assessment → budget approval → work order; emergency path |
| `WorkOrderService` | Assign → start → hold → complete → verify → close; cost calculation; asset status |
| `PreventiveMaintenanceService` | Next-due calculation, due detection, schedule + work order generation, PM completion |
| `StockLedgerService` | Every stock movement; row locking; negative-stock guard; purchase requisition |
| `PlantLogService` | Daily logs with configurable readings, consumption, abnormal flag, audit of edits |
| `ComplianceService` | Compliance / calibration inspections, due-date roll-forward, status refresh |
| `ReportExportService` | CSV (Excel) and PDF export of any report |

## 4. Design decisions and why

| Decision | Why |
|---|---|
| **Own simple RBAC (4 tables) instead of a package** | Easy to read and change; nothing hidden. `User::hasPermission()` + one middleware + `Gate::before` so `@can('pm.assets.view')` works in Blade. |
| **Permissions on every route** (`->middleware('permission:…')`) | The specification requires server-side checks, not just hidden buttons. The sidebar hides links as a convenience only. |
| **One polymorphic `pm_attachments` table** instead of a documents table per module | Every module needs uploads (asset manuals, complaint photos, before/after repair photos, certificates…). One table + one trait + one partial view keeps it consistent. The `category` column says what kind of document it is. |
| **One polymorphic `pm_history` table** for the audit trail | Same reason. `$model->logHistory('approved', 'remarks', 'from', 'to')` is one line and every "show" page includes the same timeline partial. |
| **Workflow records are inserted, never updated** (inspections, assessments, approvals, stock transactions, PM logs) | The spec says history must never disappear and approvers must not overwrite earlier decisions. Re-submission just adds another row. |
| **Stock balance = sum of the ledger** | `pm_spares.current_stock` is a cache written only by `StockLedgerService` inside the same transaction, after a `SELECT … FOR UPDATE` on the spare row, so two users cannot over-issue. `pm:run-schedulers` re-syncs it from the ledger. |
| **Configurable daily log types** (`pm_log_types.fields` JSON) | Coal, energy, AHU and ETP registers all need different readings. Admin defines the fields; the entry form renders them dynamically. Common meter fields (opening / closing / consumption) are real columns so they can be reported on. |
| **Capacity log is its own table** (`pm_capacity_logs`) | The capacity report ("200 kg / 8 h rated, ran 5 h, produced 100 kg → 80 %") needs hours run + output per run. Rated capacity lives on the asset. |
| **`Route::pattern()` numeric constraint on every model parameter** | So `/requests/create` is never swallowed by `/requests/{maintenanceRequest}` regardless of route order. |
| **Models use `$guarded = ['id']`** | Shorter models. Safe because controllers only pass `$request->validated()` / explicitly validated arrays. |
| **QR code rendered in the browser** (qrcodejs CDN) | No PHP imaging dependency. The QR links to `/assets/scan/{code}` which opens the asset page. |
| **CSV instead of a heavy Excel package** | CSV opens directly in Excel, has a UTF-8 BOM so ₹ renders, and needs no extra dependency. PDF uses dompdf. |
| **Bootstrap 5 via CDN, no Vite** | Requested: plain Blade + Bootstrap that anyone can edit without a build pipeline. |
| **Soft deletes only on masters** (assets, requests, work orders, plans, spares) | Transactional rows (approvals, stock, PM logs) are never deleted. Users are deactivated, never deleted. |

## 5. Database schema

Migrations are in `database/migrations/2026_01_01_0000NN_*.php`; each file has a header comment explaining its tables.

| Group | Tables |
|---|---|
| Users | `users` (+ employee_code, department_id, designation, phone, avatar, is_active) |
| RBAC | `pm_roles`, `pm_permissions`, `pm_permission_role`, `pm_role_user` |
| Masters | `pm_plants`, `pm_departments`, `pm_categories` (type: asset/maintenance/spare/problem/compliance), `pm_shifts`, `pm_vendors`, `pm_teams`, `pm_team_user`, `pm_settings`, `pm_sequences` |
| Assets | `pm_assets` (parent_id = hierarchy, rated_capacity / capacity_unit / capacity_hours), `pm_asset_readings`, `pm_capacity_logs` |
| Cross-cutting | `pm_attachments` (polymorphic), `pm_history` (polymorphic audit), `pm_notifications` |
| Requests | `pm_requests`, `pm_request_inspections`, `pm_technical_assessments`, `pm_technical_assessment_spares`, `pm_approvals` |
| Work orders | `pm_work_orders`, `pm_work_order_tasks`, `pm_work_order_labour`, `pm_work_order_verifications` |
| Preventive | `pm_checklists`, `pm_checklist_items`, `pm_plans`, `pm_schedules`, `pm_logs`, `pm_log_items` |
| Safety | `pm_safety_permits`, `pm_loto_records` |
| Inventory | `pm_spares`, `pm_stock_transactions`, `pm_asset_bom`, `pm_purchase_requests` |
| Operations | `pm_log_types`, `pm_daily_logs`, `pm_shift_handovers` |
| AMC | `pm_amc_contracts`, `pm_amc_assets`, `pm_amc_visits`, `pm_vendor_services` |
| Compliance | `pm_compliances`, `pm_compliance_records`, `pm_calibrations`, `pm_calibration_records` |

Differences from the suggested schema in the specification, and why:

* `pm_asset_documents` → folded into `pm_attachments` (category = manual / drawing / warranty …).
* `pm_stock` → named `pm_stock_transactions` to make clear it is a ledger. Includes `balance_after` and `issue_transaction_id` (returns point to their issue).
* `pm_pm_plans` / `pm_pm_checklists` … → shortened to `pm_plans`, `pm_checklists`, `pm_schedules`, `pm_logs`.
* `pm_amc` → `pm_amc_contracts` + `pm_amc_assets` (a contract covers many assets) + `pm_amc_visits` (scheduled visits, missed-visit alerts).
* `pm_compliance_records` / `pm_calibration_records` added so old certificates are retained.
* `pm_capacity_logs` added for the machine capacity report.

Indexes exist on every status, date, asset, department and document-number column.

## 6. RBAC – roles & permissions

* Permissions are strings like `pm.requests.approve`, grouped by module. The full list is in
  `database/seeders/RolePermissionSeeder.php::permissions()` – add one there and re-run the seeder.
* A user can hold several roles; permissions are the union.
* `super-admin` always passes. `maintenance-manager` has everything except the admin module.
* **Server side:** every route carries `->middleware('permission:…')`. Multiple allowed: `permission:a|b`.
  Controllers add ownership checks where needed (e.g. an employee can open only their own requests, a technician can act only on work orders assigned to them or their team).
* **Blade:** `@can('pm.stock.issue')` works because `Gate::before` in `AppServiceProvider` delegates `pm.*` abilities to `User::hasPermission()`.
* Admin → *Roles & Permissions* lets the administrator edit any role's permission set with checkboxes; changes apply on the next request.

## 7. Module guide

### Dashboard
15 summary cards (each links to the filtered list), 6 charts (monthly breakdowns & cost, work-order status, department requests, asset downtime, PM vs corrective, spare consumption) and 4 "needs attention" tables. Filters: date range, plant, department.

### Assets
Registry with filters, hierarchy tree (parent → child, any depth), QR label, running-hour readings, production/capacity log, spare parts BOM with live stock and shortage flag, documents by type, full maintenance history, PM plans, compliance and AMC links, audit tab. Status changes automatically (breakdown when a "machine stopped" request is raised, under maintenance when a work order is created, back to the status chosen at verification).

### Maintenance requests (complaints)
`submitted → inspection → technical assessment → budget approval → work order created → in progress → verification pending → closed`

Each step is a panel on the request page that only appears for the right role and status. Rejection and send-back require remarks. Sent-back requests are re-submitted by the requester or the expert, keeping all previous decisions. Approved cost is stored separately from estimated cost.
**Emergency:** a user with `pm.requests.emergency` can bypass the sequence; a work order is created immediately, the request is flagged, and a retrospective review must be recorded.

### Work orders
Generated automatically on approval (or by the PM scheduler, or manually for small jobs). Assignment → start (blocked if a permit is required but not active) → on hold → complete (actual problem, work done, before/after photos, downtime, vendor cost) → supervisor verification (operation, safety, quality must all be ticked; can send back) → closed. Tabs for parts (from the stock ledger), labour, tasks, photos, permits, verifications, vendor service, history. Actual cost = parts + labour + other, recalculated on completion. Printable work order sheet.

### Preventive maintenance
Plans per asset/component with trigger **calendar**, **running hours** or **both (whichever first)**. Frequencies daily → yearly or custom days. Checklist templates. The scheduler creates one `pm_schedules` row + work order per due cycle; a plan with an open schedule is skipped, so combined triggers never duplicate. Completing the checklist stores results permanently, updates the asset running hours and recalculates next due date (completed + interval days) and next due hours (hours at completion + interval hours).

### Safety – PTW & LOTO
Permit types electrical / hot work / height / confined space / heavy equipment / hazardous area. `draft → issued → active (safety officer approval) → closed`. LOTO rows per energy source with lock/tag numbers, isolated-by, verified-by, de-isolated-by. A permit cannot be closed while any isolation is in place; a permit-required work order cannot start without an active permit. Printable permit.

### Spares & inventory
Spare master with min/reorder levels, BOM per asset, ledger with opening / receipt / issue / return / adjustment in / out. Issue and return are always against a work order; over-issue and over-return are blocked. Low-stock notifications. Purchase requisitions (pending → approved → ordered → received, receipt posts a stock receipt automatically).

### Daily plant logs & shift handover
Five seeded log types (equipment, fuel, energy, AHU, water/ETP) – add more under Admin → Masters → Log Types. Consumption is computed, abnormal entries are flagged, edits to meter readings are written to the audit trail, operators can edit only their own entries for 2 days. Handover pre-fills open work orders, breakdown machines and spare shortages; the receiving person must acknowledge.

### AMC & vendors
Contracts with covered assets, SLA hours, auto-scheduled visits by frequency, missed-visit and expiry alerts, service history with SLA inputs. Vendor rating (0–100) = response 20 + resolution 20 + quality 30 + attendance 15 + no-repeat-failure 15.

### Compliance & calibration
Current dates on the parent row, every inspection / calibration as a child record with its certificate. Status valid / due soon / overdue / failed refreshed daily. "Send for calibration" marks the instrument under calibration.

### Reports
35 reports in six groups with date / department / asset filters, CSV (Excel), PDF and print. See §10.

### Administration
Users (roles, teams, activate/deactivate – never deleted), roles & permissions matrix, masters (plants, departments, categories, shifts, teams, log types), settings (company name, currency, alert windows, labour rate), audit trail viewer.

## 8. Business rules enforced in code

| Rule | Where |
|---|---|
| Unique asset code / request no / WO no / permit no / contract no | DB unique indexes + `DocumentNumberService` row lock |
| Approval steps must happen in order | `ApprovalWorkflowService::assertStatus()` |
| Rejection / send-back need remarks | Form validation (`remarks required`) |
| Every decision recorded, never overwritten | `pm_approvals` insert-only; `pm_history` |
| Work order cannot close without verification; all three checks required | `WorkOrderController::verify()` |
| Stock cannot go negative; return ≤ issued | `StockLedgerService::record()` with `lockForUpdate()` |
| PM combined trigger generates one job per cycle | `PreventiveMaintenanceService::generateDueSchedules()` checks `openSchedule` |
| PM completion updates next due | `PreventiveMaintenanceService::recordCompletion()` |
| Permit required before work starts; permit cannot close with isolation in place | `WorkOrderService::start()`, `SafetyPermitController::close()` |
| Permit requirement cannot be switched off at assignment | `WorkOrderController::assign()` |
| Compliance / calibration history retained | child `*_records` tables |
| Audit of important edits | `HasHistory` trait, `changes` JSON on edits |
| Reports respect permissions | `pm.reports.view` middleware; employee request list is scoped to own |

## 9. Automation / scheduler

`php artisan pm:run-schedulers` (daily 06:00, PM section every 4 hours – see `routes/console.php`):

1. PM due check by date (look-ahead `pm.pm_lookahead_days`) and by running hours → schedule + work order + notification.
2. PM overdue reminders.
3. Overdue work-order alerts to the technician and supervisors.
4. Stock re-sync from ledger + low-stock alerts.
5. AMC: expire contracts past end date, expiry reminders (`pm.amc_expiry_alert_days`), missed vendor visits.
6. Compliance & calibration status refresh + due / overdue reminders (`pm.due_soon_days`).

Every alert has a `dedupe_key` (per record per day/week) so repeated runs never create duplicates.

## 10. Reports & exports

`ReportController::REPORTS` lists every report; each is one `build…()` method returning `columns + rows (+ totals)`.
The same array feeds the HTML table, `?export=csv` and `?export=pdf`.

**Machine capacity report** (`/reports/machine-capacity`):

```
rated per hour  = rated_capacity / capacity_hours      200 kg / 8 h  = 25 kg/h
expected output = rated per hour × hours run           25 × 5        = 125 kg
actual per hour = actual output / hours run            100 / 5       = 20 kg/h
efficiency      = actual / expected                    100 / 125     = 80 %
```

Rated capacity is set on the asset; runs are recorded on the asset page → *Capacity Log* tab. The totals block gives the period output capacity per machine.

## 11. File uploads

* Allowed: jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, csv, txt – max 10 MB (`AttachmentService::rule()`).
* Stored under `storage/app/public/pm/<model>/<id>/`, served via `php artisan storage:link`.
* Upload is available on: assets (documents by type, photo), requests (complaint photos), inspections, assessments, work orders (before / after / documents), PM completion (evidence), permits (JSA), daily logs, AMC (contract), compliance & calibration records (certificates), user avatar, spare image.
* Deleting a file is allowed for the uploader or the module's manage permission (`AttachmentController`).

## 12. Testing

Tests run against a separate MySQL database `plant_maintenance_test` (see `phpunit.xml`). Create it once:

```sql
CREATE DATABASE plant_maintenance_test;
```

```bash
php artisan test
```

* `SmokeRoutesTest` – logs in as super admin and opens every screen and every report (+ CSV and PDF export).
* `MaintenanceWorkflowTest` – full request → closure flow including send-back and re-submission, stock over-issue / over-return guards, cost calculation, verification gate, permit-blocked start with LOTO close rule, PM next-due calculation and scheduler idempotence, emergency bypass, employee permission scoping.

## 13. How to extend

* **New permission:** add to `RolePermissionSeeder::permissions()`, run `php artisan db:seed --class=RolePermissionSeeder`, use it in `routes/web.php` and `@can`.
* **New report:** add a key to `ReportController::REPORTS` and a `build<Key>()` method. Export works automatically.
* **New log type:** Admin → Masters → Log Types (no code).
* **New document category for assets:** edit the select in `resources/views/assets/show.blade.php` (Documents tab).
* **Email notifications:** `NotificationService::notifyUser()` is the single place to add a mail / SMS send.
* **ERP integration:** stock movements all pass through `StockLedgerService::record()`; purchase requisitions are in `pm_purchase_requests`; users are Laravel's `users` table.

## 14. Change log

* **v1.0 (Nov 2026)** – Initial complete system: all 18 modules of the specification, RBAC, 13 migrations, seeders with demo factory data, scheduler command, 35 reports with CSV/PDF/print, feature tests.
