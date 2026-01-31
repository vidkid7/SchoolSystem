#!/bin/bash
# Run this on deploy: migrations, cache, storage link.
# Make executable: chmod +x railway/init-app.sh
set -e

php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
