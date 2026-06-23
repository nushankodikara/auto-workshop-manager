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

## Working agreement

- Keep this file updated whenever module scope, external providers, or access rules change.
- Capture decisions here before implementing feature code so later work does not lose context.


