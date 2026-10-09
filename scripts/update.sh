#!/usr/bin/env bash
# Production update for AV Asset Manager (Ubuntu / Nginx / PHP-FPM).
# Usage (from anywhere):
#   sudo bash /var/www/av-asset-manager/scripts/update.sh
# Or after making executable:
#   sudo /var/www/av-asset-manager/scripts/update.sh

set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WEB_USER="${WEB_USER:-www-data}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.5-fpm}"
DEPLOY_USER="${SUDO_USER:-${USER}}"

cd "$APP_ROOT"

echo "==> Updating AV Asset Manager in ${APP_ROOT}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run with sudo so ownership and PHP-FPM reload can be applied." >&2
  exit 1
fi

if [[ ! -f artisan ]]; then
  echo "No artisan file found in ${APP_ROOT}; aborting." >&2
  exit 1
fi

run_as_deploy() {
  if [[ -n "${SUDO_USER:-}" && "${SUDO_USER}" != "root" ]]; then
    sudo -u "$SUDO_USER" -H "$@"
  else
    "$@"
  fi
}

echo "==> Enabling maintenance mode"
run_as_deploy php artisan down --retry=60 || true

cleanup() {
  echo "==> Disabling maintenance mode"
  run_as_deploy php artisan up || true
}
trap cleanup EXIT

echo "==> git pull"
run_as_deploy git pull --ff-only

echo "==> composer install"
run_as_deploy composer install --no-dev --optimize-autoloader --no-interaction

echo "==> npm ci && npm run build"
run_as_deploy npm ci --no-fund --no-audit
run_as_deploy npm run build

echo "==> migrate"
run_as_deploy php artisan migrate --force

echo "==> cache config / routes / views"
run_as_deploy php artisan config:cache
run_as_deploy php artisan route:cache
run_as_deploy php artisan view:cache

echo "==> storage link + writable dirs"
run_as_deploy php artisan storage:link --quiet 2>/dev/null || true
chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [[ -d database ]]; then
  chown -R "${WEB_USER}:${WEB_USER}" database
  chmod 775 database
  if [[ -f database/database.sqlite ]]; then
    chmod 664 database/database.sqlite
  fi
fi

# Keep deploy user able to pull / run artisan next time
if [[ -n "${DEPLOY_USER}" && "${DEPLOY_USER}" != "root" ]]; then
  chown -R "${DEPLOY_USER}:${WEB_USER}" .
  # Restore web-writable paths after broad chown
  chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
  if [[ -d database ]]; then
    chown -R "${WEB_USER}:${WEB_USER}" database
  fi
fi

echo "==> reload ${PHP_FPM_SERVICE}"
systemctl reload "${PHP_FPM_SERVICE}"

echo "==> Update complete"
