#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Display usage instructions if archive path is missing
if [ -z "$1" ]; then
    echo "Usage: $0 <backup_filename_or_path>"
    echo "Example: $0 backup_2026-06-18_12-00-00.sqlite"
    exit 1
fi

BACKUP_FILE="$1"

echo "========================================="
echo "  WORKSHOP MANAGER DATABASE RESTORE      "
echo "========================================="
echo "Target Backup: $BACKUP_FILE"
echo "Running restore command in docker container..."

# Check if the container is running
if ! docker compose ps | grep -q "tdc-laravel-app"; then
    echo "Error: The 'app' container (tdc-laravel-app) is not running."
    echo "Please start the stack with 'docker compose up -d' first."
    exit 1
fi

# Run the Artisan restore command inside the app container without interaction
docker compose exec -T app php artisan db:restore "$BACKUP_FILE" --no-interaction

echo "Database restore completed successfully."
