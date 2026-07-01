# AI Handoff

## Product

Auto Workshop Manager is a modular, configurable vehicle management system for workshops covering mechanical, electrical, and bodywork operations. It can be easily branded (e.g., as "Total Drive Care") via environment configurations.

## Confirmed defaults

- Stack: Laravel 11 (unified backend and frontend)
- Frontend templates: Laravel Blade
- CSS system: TailwindCSS v4 (integrated via Vite)
- UI design style: Premium dark/glassmorphic custom layout
- Database: SQLite (v3) located at `database/database.sqlite`
- Database Timestamps: Standard Laravel timestamps (`created_at` and `updated_at`) on all database tables.
- Deployment: Docker Compose from the repository root
- Locations: one shop location for the first release
- Communication: FitSMS for SMS, Laravel Mail/SMTP for email
- Tax: No tax in v1. The `tax` field on the bills is optional to support numeric `0` values.
- Payroll: flexible salary slip setup
- Backups: cron-driven SQLite DB backup service
- Backup directory env: `BACKUP_DIR=./backups`
- Restore helper: `ops/backup/restore.sh ARCHIVE_PATH`

## Core modules

- Job cards with kanban workflow: `received-vehicle`, `on-going`, `blocked`, `testing`, `waiting-to-pickup`
- Multi-worker assignment
- Client and vehicle management
- Inventory and stock usage
- Billing and PDF export
- HR and salary slips
- Manager roles and module-based access control
- Comments and activity history on job cards
- Backup and recovery tooling
- Vehicle CRUD edits (make, model, year, plate, VIN, mileage)
- Vehicle Repair and Services History report with client-side toggle to print with or without prices
- Job Card Services (recording multiple tasks on a job card, auto-populating billing workspace labor)
- Data Insights Dashboard & Secure, Read-Only SQL Query Console (super-manager only)
- Inventory Purchase Batches (batch-specific stock, supplier tracking, FIFO recommendation with manual override)
- Statistics & Finance Dashboard (cash flow overview, payroll + stock expenditure, segment-wise COGS margins)

## Suggested next modules

- Estimates and quotations
- Warranty tracking
- Supplier and purchase order management
- Audit log
- Service reminders
- Advanced reporting


## Database Updates & Constraints
- **Vehicles & Job Cards Mileage**: Nullable integer fields. Vehicle mileage represents the highest known odometer reading; job card creations or updates with a higher mileage will trigger vehicle mileage updates. Lower values are ignored on the vehicle.
- **Job Card Services**: Table `job_card_services` maps `job_card_id` to individual service names and prices.
- **SQL Console Constraints**: The SQL console is protected using syntax rules: only queries matching `/^\s*select\s/i` are allowed, and dangerous modification keywords (`insert`, `update`, `delete`, `drop`, `alter`, etc.) are actively blocked. All queries are executed inside a safe try-catch block.
- **Job Card Assignments & Active Hours**: Table `job_card_assignments` maps `job_card_id` and `user_id` with `assigned_at` and `unassigned_at` timestamps. Active working hours are calculated only within `08:30` and `18:00` daily. When a worker is assigned for the first time, their `assigned_at` timestamp defaults to the job card's `created_at` timestamp to track the full ticket duration. Subsequent re-assignments (if removed and added back) default to `$now`. Historical assignments remain visible on the job card details page to ensure progress is not deleted.
- **Attendance Tracker Half-Day and N/A Support**: The `attendances` table supports status options `present`, `half_day` (worth `0.5` days attended), `absent`, `leave`, and `n/a` (which deletes/removes the record if it exists). The `attended_days` field on `payroll_slips` is stored as a decimal to allow half-day fractional values.
- **Configurable Prefix settings**: Configurable prefix string (defaulting to `TDC-`) is saved under the `job_card_prefix` key in the `settings` database table. The card number is generated as `[prefix][yymmddhhmm][xxx]` where `xxx` is a sequential sequence within the current minute.
- **Public API Status Endpoint**: A public GET/POST endpoint `/api/tickets/status` which accepts `ticket_id` (matched to ID or card number) and `phone` (matched normalized against client phone). It returns a JSON object containing the ticket's state, last email body, and last SMS text.
- **Clean Database Seeding**: Checked via `SEED_DEMO_DATA` environment variable. Setting to `false` skips demo records (shops, clients, vehicles, job cards, inventory, payroll slips) while preserving Super Admin credentials (`ADMIN_EMAIL`/`ADMIN_PASSWORD`), default settings, and base payroll categories.
- **Shop Locations Settings**: Exposes `POST /settings/shops` and `DELETE /settings/shops/{shop}` for UI management. Shop deletion is actively blocked if the shop is in use by any job cards.
- **Outbound SMS Phone Normalization**: Automatically normalizes numbers to Sri Lankan `947xxxxxxxx` format, stripping spaces, dashes, leading `+` signs, and translating leading `0` to country code `94` to comply with FitSMS delivery rules.
- **Docker Tagging & Versioning**: CI/CD build pushes on `main` tag images with both `latest` and `v1.0.${{ github.run_number }}` versions.
- **Custom Low Stock Alerts & Inventory Details**: Replaced the hardcoded low stock alerts limit with item-specific `low_stock_alert_qty` thresholds (setting to `0` disables alerts). Added `overflow-x-auto` horizontal scroll to prevent cramped inventory table layouts, and created an inventory detailed show page mapping editing, FIFO purchase batches, recent transactions, and price fluctuation SVG graphs.
- **Kanban Board Date Range Filters**: Refactored the Kanban board to default to showing today's work + older unfinished tickets. Added custom date range start/end filter inputs to narrow down completed/in-progress tickets within arbitrary timeframes.
- **Employee Archiving & Profile Directory (Soft Deactivation)**: Added `is_archived` boolean column to the `users` table. Setting to `true` excludes deactivated workers/managers from attendance trackers, payroll slip generation lists, and job card worker assignment selections. Includes a restore option to re-activate employees. Added a nullable `contact_number` field to the employee directory, exposing it during employee registration/edit flows and on the employee profile detail page.
- **Yearly Attendance Calendar View**: Renders a complete 12-month calendar grid on each employee's profile detail view. Highlights marked daily attendance statuses: Present (Emerald), Half Day (Amber), Absent (Red), and Approved Leave (Blue). Includes a year filter dropdown.
- **Customer & Employee Broadcast Messaging**: Added a dedicated outreach console allowing managers to filter customers by their last service date (last week, month, 3/6 months, range, all) or target active employees in directory. Supports broadcasting SMS (FitSMS) or Email (SMTP) updates with a development/testing Safe Mode that logs outgoing mock messages. Normalizes recipient phone numbers to standard country formats.
- **Double-Entry Bookkeeping Ledger**: Replaced simple cash metrics with a robust double-entry system (tables `accounts`, `journal_entries`, `journal_items`). Seeds a default Chart of Accounts (Cash Drawer `1000`, Bank Account `1010`, Accounts Receivable `1200`, Parts Inventory `1300`, Revenues, Expenses, etc.).
  - Automatically posts client billing invoices (AR debit, Revenues credit), Cost of Goods Sold (COGS debit, Parts Inventory credit), and payment receipts (Cash Drawer debit, AR credit).
  - Automatically posts stock batch purchases (Parts Inventory debit, Cash Drawer credit) and paid salary slips (Salaries Expense debit, Cash Drawer credit).
  - Enforces balanced transactions (Debits = Credits) on manual journal logs.
  - Dynamically calculates Share Value based on Book Equity (Assets - Liabilities) divided by configured total company shares.
  - Provides stream CSV download exports for Chart of Accounts, General Ledger, and Customer balances.
  - **Retroactive Data Sync Migration**: A subsequent migration [2026_06_30_120000_retroactive_import_batches_and_slips.php](file:///Users/nushan/Projects/TDC%20Laravel/database/migrations/2026_06_30_120000_retroactive_import_batches_and_slips.php) force-imports pre-existing stock purchase batches and paid payroll slips into the ledger.
- **Unified Statistics**: Integrated the Statistics & Finance dashboard directly with the double-entry bookkeeping ledger, computing cash flows and segment margins based on ledger account balances instead of raw table sums to keep both modules perfectly coherent.


## Working agreement

- Keep this file updated whenever module scope, external providers, or access rules change.
- Capture decisions here before implementing feature code so later work does not lose context.


