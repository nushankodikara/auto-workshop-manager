# Feature Roadmap

## Core modules already in scope

- Job cards and kanban workflow
- Client and vehicle management
- Inventory and stock usage
- Billing and PDF generation
- HR payroll slips
- Manager access control
- Notifications and comments
- Backups and recovery

## Useful modules to add next

- Estimates and quotations before a job card is approved
- Warranty tracking for completed repairs
- Supplier and purchase order management
- Audit log for manager actions and sensitive changes
- Service reminders for repeat customers and maintenance intervals
- Advanced reporting for revenue, inventory movement, and technician utilization
- Customer history timeline across vehicles and job cards
- Internal tasks and workshop announcements

## Rollout order suggestion

1. Job cards, clients, vehicles, and access control.
2. Inventory, billing, and PDF output.
3. Notifications, comments, and audit logs.
4. HR payroll and reporting.
5. Estimates, warranty, suppliers, and reminders.

---

## Laravel Implementation Structure

- **Routing & Controllers**: Web routes mapped to dedicated resource controllers (e.g., `JobCardController`, `ClientController`, `InventoryController`).
- **Views**: Modular Blade templates using TailwindCSS v4 for modern styling (dark mode, glassmorphism, responsive grid grids).
- **Notifications**: Triggered via Eloquent model events (e.g. when `JobCard` status updates) calling external APIs asynchronously.
- **Backups & Database**: Handled via Artisan custom commands scheduled using Laravel's task scheduler or standard cron container.
