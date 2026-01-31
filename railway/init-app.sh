#!/bin/bash
# Run this on deploy: migrations, cache, storage link.
# Make executable: chmod +x railway/init-app.sh
set -e

# Clear any stale cache first so config is built from current env
php artisan optimize:clear

php artisan migrate --force
php artisan storage:link 2>/dev/null || true

php artisan config:cache
# Do not run route:cache so route names (e.g. login, admin.district.dashboard) always resolve
# php artisan route:cache
php artisan view:cache
