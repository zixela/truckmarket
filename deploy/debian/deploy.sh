#!/usr/bin/env bash
#
# TruckMarket - release the latest code on an already provisioned server (see provision.sh).
# Run as root:  bash deploy.sh          (BRANCH=main APP_DIR=/var/www/truckmarket are the defaults)
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/truckmarket}"
APP_USER="${APP_USER:-deploy}"
PHP_VERSION="${PHP_VERSION:-8.4}"
BRANCH="${BRANCH:-main}"

[[ $EUID -eq 0 ]] || { echo "run as root" >&2; exit 1; }
run_app() { sudo -u "$APP_USER" -H env -C "$APP_DIR" "$@"; }

# Whatever happens below, bring the site back up.
trap 'run_app php artisan up >/dev/null 2>&1 || true' EXIT

run_app php artisan down --retry=30
run_app git fetch --prune origin
run_app git checkout "$BRANCH"
run_app git pull --ff-only origin "$BRANCH"

run_app composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
run_app npm ci --no-audit --no-fund
run_app npm run build

run_app php artisan migrate --force
run_app php artisan optimize:clear
run_app php artisan optimize
run_app php artisan filament:optimize
run_app php artisan translations:sync      # new lang/*.php keys -> admin-editable translations table

systemctl reload "php${PHP_VERSION}-fpm"  # opcache.validate_timestamps=0: new code needs a reload
run_app php artisan queue:restart
systemctl restart truckmarket-queue

echo "Deployed $(run_app git rev-parse --short HEAD) on ${BRANCH}"
