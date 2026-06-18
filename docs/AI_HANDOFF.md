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

## Working agreement

- Keep this file updated whenever module scope, external providers, or access rules change.
- Capture decisions here before implementing feature code so later work does not lose context.

