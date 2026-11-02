#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:?Set APP_DIR to the Laravel release directory}"
BACKUP_DIR="${BACKUP_DIR:-$APP_DIR/storage/backups}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

cd "$APP_DIR"
if [[ "${APP_ENV:-}" != "production" || "${APP_DEBUG:-true}" != "false" ]]; then
  echo "Refusing deployment: APP_ENV must be production and APP_DEBUG must be false." >&2
  exit 1
fi
mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d-%H%M%S)"

# Keep the site in maintenance mode until migrations and smoke checks complete.
$PHP_BIN artisan down --render="errors::503" --retry=60 || true
trap '$PHP_BIN artisan up || true' EXIT

: "${DB_HOST:?Set DB_HOST before deployment}"
: "${DB_DATABASE:?Set DB_DATABASE before deployment}"
: "${DB_USERNAME:?Set DB_USERNAME before deployment}"
mysqldump --single-transaction --quick --routines --triggers \
  -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"${DB_PASSWORD:-}" "$DB_DATABASE" \
  | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"

$COMPOSER_BIN install --no-dev --prefer-dist --optimize-autoloader --no-interaction
$PHP_BIN artisan migrate --force
$PHP_BIN artisan storage:link || true
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan queue:restart || true

$PHP_BIN artisan up
trap - EXIT
echo "Production deployment completed: $STAMP"
