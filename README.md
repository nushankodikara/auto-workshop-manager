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
