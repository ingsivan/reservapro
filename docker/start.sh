#!/bin/sh
# Starts ReservaPro in one container: web server, queue worker and scheduler.
# Good for a free demo; on a bigger setup run each process separately.
set -e

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    FRESH_DATABASE=1
fi

php artisan migrate --force

if [ "${FRESH_DATABASE:-0}" = "1" ] && [ "${SEED_DEMO_DATA:-true}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan optimize

php artisan queue:work --tries=1 --max-time=3600 &
php artisan schedule:work &

exec env PHP_CLI_SERVER_WORKERS="${WEB_WORKERS:-4}" php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
