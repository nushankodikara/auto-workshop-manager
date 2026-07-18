# Auto Workshop Manager

A modern, professional, and modular auto workshop management system built on **Laravel 11** and **TailwindCSS v4**, deployed with **Docker Compose**. It manages clients, vehicles (with odometer mileage constraints), repair job cards (with multi-technician and multi-service boards), inventory usage, invoices, pro-rated payroll calculation, and database backups.

---

## 🛠️ Prerequisites
* [Docker & Docker Compose](https://www.docker.com/get-started)
* [Git](https://git-scm.com/)

---

## 🚀 Quick Start (Running the Stack)

### 1. Build and Run the App
To compile assets and launch the application and cron backup containers in the background:
```bash
docker compose build
docker compose up -d
```
The application will be accessible at: **[http://localhost:8000](http://localhost:8000)**.

### 2. Reset the System (Clean Database & Re-Seeding)
If you want to wipe the SQLite database, clear persistent storage, apply Laravel migrations from scratch, and re-run all seeding scripts:
```bash
docker compose down -v && docker compose up -d
```

---

## 📂 Useful Laravel CLI Commands (via Docker Compose)

Since the application runs inside a docker container, run the commands using `docker compose exec app`:

### 1. Database Migrations & Seeding
* **Run Pending Migrations**:
  ```bash
  docker compose exec app php artisan migrate
  ```
* **Wipe Database & Run All Migrations from Scratch**:
  ```bash
  docker compose exec app php artisan migrate:fresh
  ```
* **Run Database Seeder**:
  ```bash
  docker compose exec app php artisan db:seed
  ```
* **Wipe Database, Migrate, and Seed (Recommended for local setup verification)**:
  ```bash
  docker compose exec app php artisan migrate:fresh --seed
  ```

### 2. Clearing Caches & Optimization
If configuration changes are not reflecting or tests are unexpectedly modifying your active development database, clear Laravel caches:
```bash
# Clear Configuration Cache
docker compose exec app php artisan config:clear

# Clear View Cache
docker compose exec app php artisan view:clear

# Clear All Application Cache
docker compose exec app php artisan cache:clear
```

### 3. Running Automated Tests
To run the PHPUnit test suite:
```bash
docker compose exec app php artisan test
```

### 4. Compiling Frontend Assets (Vite)
* **Compile Assets for Production**:
  ```bash
  docker compose exec app npm run build
  ```
* **Run Dev Server for Live Editing**:
  ```bash
  docker compose exec app npm run dev
  ```

### 5. Database Backup & Restore Operations
* **Run a Manual Database Backup**:
  ```bash
  docker compose exec app php artisan db:backup
  ```
* **Restore a Database Backup**:
  ```bash
  docker compose exec app php artisan db:restore {filename}
  ```
  *(Example: `docker compose exec app php artisan db:restore backup_2026-06-18_07-58-03.sqlite`)*

---

## 🔒 Default Login Credentials (after seeding)
* **Default Super Manager Account**:
  * **Email**: `admin@totaldrivecare.com`
  * **Password**: `TotalDriveCare@2026`
* **Default Manager Account**:
  * **Email**: `manager@workshop.com`
  * **Password**: `Password123!`
* **Default Technician Account**:
  * **Email**: `alex@workshop.com`
  * **Password**: `Password123!`

---

## 📜 Version History & Changelog

### v1.4.0 (2026-07-18) — Towing/Transportation & Ledger Integrity
* **Towing & Transportation:** Added provided vs. hired transportation fees on Job Cards with custom double-entry bookkeeping integrations.
* **Transportation Account Mapping:** Added settings validation and dropdown selections for Transportation Asset, Revenue, and Hire Expense accounts.
* **Maintenance Utilities:** Created a historical transportation data reconciliation utility in settings.
* **Eloquent Cascade Cleanups:** Added deleting hooks on Client, Vehicle, JobCard, and Bill to cascade Eloquent deletions and prevent orphaned journal entries in Cash Book or Accounts Receivable.
* **Duplicate Vehicle Merging:** Added duplicate vehicle finder and one-click merging tool.
* **Premium UX Drawers:** Converted Consumables modals into side-sliding drawers (slide-overs) to resolve layout rendering blur bugs.

### v1.3.0 (2026-07-17) — Consumables & Advanced Payments
* **Consumables Inventory:** Implemented consumables supply tracking, logs, and next-month order predictions.
* **Advanced Payments:** Added advanced payments record tracking for job cards with double-entry ledger integration.
* **Layout Enhancements:** Added collapsible sidebar drawer navigation, increased cell paddings for DataTables, and fixed timezone settings (Asia/Colombo).

### v1.2.0 (2026-07-03) — Outsourcing, Appointments & Payroll
* **Outsourcing:** Added outsourcing logs and misc parts to Job Cards with ledger billing integration.
* **Appointments Module:** Implemented appointment booking panel with auto-formatting and Carbon fallback fixes.
* **Payroll & Roles:** Integrated dynamic role permission manager and basic/overtime payroll processing.
* **Backups Console:** Integrated S3 cloud database backups and restore panel in settings.

### v1.1.0 (2026-06-25) — Tracker Sync API & Telemetry
* **Tracker Telemetry:** Integrated vehicle telemetry logs and live tracking details fetch.
* **Tracker Sync API:** Developed automatic sync on Client/Vehicle updates.
* **Client Deduplication:** Added duplicate clients check and profile merging utility.

### v1.0.0 (2026-06-18) — Initial Setup
* **CRM Core:** Initial database tables, auth routing, client directories, and basic repair job cards.

---

## ✍️ Changelog Maintenance Guidelines

To ensure release notes remain helpful and accurate for all team members and AI coding assistants:
1. **Semantic Versioning (SemVer):** Increment version numbers as follows:
   * **PATCH** (e.g., `v1.4.0` -> `v1.4.1`) for backwards-compatible bug fixes.
   * **MINOR** (e.g., `v1.4.0` -> `v1.5.0`) for new backwards-compatible features.
   * **MAJOR** (e.g., `v1.4.0` -> `v2.0.0`) for breaking changes.
2. **Commit Alignment:** Update the changelog *immediately* prior to tagging releases or merging feature branches into `main`.
3. **Include Key Files:** Note critical code files modified (e.g., migrations, double-entry service rules) to help downstream developers identify side-effects.
