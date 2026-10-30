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

install_php_dependencies() {
  echo "==> composer install"
  if run_as_deploy composer install --no-dev --optimize-autoloader --no-interaction; then
    return 0
  fi

  echo "==> composer scripts failed; installing dependencies without booting the app"
  run_as_deploy composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
  run_as_deploy composer dump-autoload --optimize --no-interaction --no-scripts
}

enable_maintenance() {
  # --render prerenders errors/503 before the rest of the update so visitors
  # see the branded page. Failure is non-fatal: a boot error must not abort
  # the update before dependencies are installed.
  php artisan down \
    --redirect=/ \
    --render="errors.503" \
    --retry=60 \
    --refresh=15 \
    || true
}

UPDATE_FINISHED=0
CLEANED_UP=0
cleanup() {
  if [[ "${CLEANED_UP}" -eq 1 ]]; then
    return 0
  fi
  CLEANED_UP=1
  # Always leave the site up — a leftover maintenance.php is a permanent 503
  # with nothing in nginx error.log. Trap EXIT so a mid-script failure still
  # clears maintenance mode. Run artisan as root: the deploy user cannot
  # always write storage/framework after those dirs are chowned to www-data.
  echo "==> Disabling maintenance mode"
  if ! php artisan up; then
    echo "Warning: php artisan up failed; removing maintenance files directly." >&2
  fi
  rm -f "${APP_ROOT}/storage/framework/down" \
    "${APP_ROOT}/storage/framework/maintenance.php"
  if [[ "${UPDATE_FINISHED}" -ne 1 ]]; then
    echo "Update stopped before it finished. Run this script again and let npm ci and npm run build complete." >&2
  fi
}
on_interrupt() {
  echo "==> Interrupted (${1}). Stopping the update." >&2
  cleanup
  exit 130
}
trap cleanup EXIT
trap 'on_interrupt INT' INT
trap 'on_interrupt TERM' TERM
trap 'on_interrupt HUP' HUP

echo "==> Enabling maintenance mode"
enable_maintenance

echo "==> git pull"
run_as_deploy git pull --ff-only

install_php_dependencies

if [[ ! -f "${APP_ROOT}/storage/framework/down" && ! -f "${APP_ROOT}/storage/framework/maintenance.php" ]]; then
  echo "==> Maintenance mode did not start; retrying after PHP dependencies are installed"
  enable_maintenance
fi

echo "==> npm ci && npm run build"
echo "    This step can take several minutes. Leave it running until it finishes."
set +e
run_as_deploy npm ci --no-fund --no-audit
npm_ci_status=$?
set -e
if [[ "$npm_ci_status" -ne 0 ]]; then
  echo "npm ci failed with exit ${npm_ci_status}. Exit 130 means the process received SIGINT (often Ctrl+C)." >&2
  exit "$npm_ci_status"
fi
set +e
run_as_deploy npm run build
npm_build_status=$?
set -e
if [[ "$npm_build_status" -ne 0 ]]; then
  echo "npm run build failed with exit ${npm_build_status}. Exit 130 means the process received SIGINT (often Ctrl+C)." >&2
  exit "$npm_build_status"
fi

echo "==> migrate"
run_as_deploy php artisan migrate --force

echo "==> ensure application roles"
run_as_deploy php artisan db:seed --class=RoleSeeder --force

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
UPDATE_FINISHED=1
