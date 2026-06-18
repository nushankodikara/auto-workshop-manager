# Architecture Notes

## Initial service layout

- `app`: Laravel 11 application served via PHP 8.3 + Apache container. Houses both logic and frontend templates (Blade/TailwindCSS v4), using SQLite as database.
- `backup-cron`: Periodic cron job runner container running in the background. Triggers manual database and asset backup scripts via CLI.

## Proposed domain model

- Users and roles
- Shops and locations
- Clients
- Vehicles
- Job cards
- Job card comments and activity feed
- Inventory items and stock movements
- Job card line items and grouped bill lines
- Bills and PDF exports
- Payroll slips and payroll categories

## Early technical constraints

- The super manager must exist automatically on first boot.
- Managers should only access modules allowed by the super manager.
- Client notification events should be emitted when a job card changes state.
- Inventory must stay in sync with job card item assignment and bill generation.
