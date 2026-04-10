#!/bin/sh
set -e

# Railway injects $PORT at runtime; default to 8080.
export PORT="${PORT:-8080}"
echo "[entrypoint] Binding nginx to port: $PORT"

# Render nginx config with the runtime port.
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
echo "[entrypoint] Rendered nginx config:"
grep -E 'listen|server_name' /etc/nginx/conf.d/default.conf || true

# Fail fast if nginx config is invalid.
nginx -t

cd /var/www

# Ensure storage + bootstrap/cache are writable (Railway volumes start empty).
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache
touch storage/logs/laravel.log

# Run migrations (idempotent). Set RUN_MIGRATIONS=false to skip.
if [ "${RUN_MIGRATIONS:-true}" = "true" ] && [ -n "${DB_HOST:-}" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force

    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        echo "[entrypoint] Running seeders..."
        php artisan db:seed --force
    fi
fi

# Cache config/routes/views for production performance.
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache  || true
    php artisan route:cache   || true
    php artisan view:cache    || true
fi

# Fix ownership AFTER artisan commands so any files they created as root
# get handed to www-data (which is what php-fpm workers run as).
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Hand off to supervisord (php-fpm + nginx).
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
