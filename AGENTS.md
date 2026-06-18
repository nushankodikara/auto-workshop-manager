# AI Agent & Developer Integration Guide

Welcome to **Auto Workshop Manager**! This guide describes the AI agent pair-programming setup, the documentation architecture, commands to run, test, and document the application stack, and the Git/CI/CD release flow.

---

## 📂 Documentation Structure

All system architectures, product decisions, and developer handoff documentations are located in the [docs](file:///Users/nushan/Projects/TDC%20Laravel/docs) directory:

1. **[AI_HANDOFF.md](file:///Users/nushan/Projects/TDC%20Laravel/docs/AI_HANDOFF.md)**: Product scope, confirmed default services (Laravel 11, TailwindCSS v4, FitSMS, etc.), and outstanding bootstrap items.
2. **[ARCHITECTURE.md](file:///Users/nushan/Projects/TDC%20Laravel/docs/ARCHITECTURE.md)**: Service layout, database mappings, and core logic constraints.
3. **[FEATURES.md](file:///Users/nushan/Projects/TDC%20Laravel/docs/FEATURES.md)**: Scope breakdown and suggested rollout order for next modules.

---

## 🛠️ Git Branching & CI/CD Release Flow

To ensure stability and automated delivery:

### 1. Branch Strategy
- **`main`**: Production-ready branch. Only push ready, fully tested changes here. Direct pushes to `main` trigger the automated Docker build/push CI/CD pipeline.
- **`development`**: Main development integration branch. All active feature development and testing should be merged here first.
- **Feature Branches** (`feature/*`): Created for individual tasks (e.g., `feature/billing`, `feature/sms-alerts`) and merged into `development` for testing.

### 2. GitHub Actions CI/CD Pipeline
We use the reusable workflow at `nushankodikara/reusable-workflows` to build and upload the Docker image to Docker Hub.
The workflow configuration is defined at `.github/workflows/docker-build-push.yml` and triggers automatically on pushes to `main`.

---

## 🛠️ Management & Deployment Commands

Here are the key commands to build, start, reset, and inspect the dockerized system:

### 1. Build and Run
To build the Laravel app and backup cron daemon containers:
```bash
docker compose build
```
To run the stack in the background:
```bash
docker compose up -d
```

### 2. Reset the System (Clean Database & Re-Seeding)
If you want to wipe the SQLite database, clear persistent storage, apply Laravel migrations from scratch, and re-run all seeding scripts:
```bash
docker compose down -v && docker compose up -d
```

### 3. Check Service Logs
To view the output of the Laravel application or the backup cron container to debug:
```bash
docker compose logs app --tail=50
docker compose logs backup-cron --tail=50
```

### 4. Laravel CLI Admin Actions (Artisan)
To run Artisan operations directly inside the running app container (e.g., seeding, clearing cache, checking routes):
```bash
docker compose exec app php artisan route:list
docker compose exec app php artisan db:seed
```

### 5. Database Backups
To run manual backups or restore backups:
```bash
# Manual Backup
docker compose exec app php artisan db:backup

# Manual Restore
docker compose exec app php artisan db:restore {filename}
# Or via shell helper:
./ops/backup/restore.sh ./backups/{filename}
```

---

## ✍️ Documentation Protocol

To keep the codebase maintainable for future developers and AI coding assistants:
1. **Document Before coding**: Always write down scope changes, API endpoints, or database structures in [docs/AI_HANDOFF.md](file:///Users/nushan/Projects/TDC%20Laravel/docs/AI_HANDOFF.md) before implementing them.
2. **Use Descriptive Diffs**: Maintain a clean Git history and document any schema changes inside migrations.
