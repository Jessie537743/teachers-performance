#!/bin/sh
set -e

# Railway injects $PORT at runtime; default to 80 for local docker.
export PORT="${PORT:-80}"
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
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Run migrations (idempotent). Set RUN_MIGRATIONS=false to skip.
if [ "${RUN_MIGRATIONS:-true}" = "true" ] && [ -n "${DB_HOST:-}" ]; then
    echo "[entrypoint] Running central migrations..."
    php artisan migrate \
        --database=central \
        --path=database/migrations/central \
        --force 2>&1 \
        || echo "[entrypoint] WARNING: Central migration failed — check logs above."

    echo "[entrypoint] Seeding central data (idempotent)..."
    php artisan db:seed --class=CentralSeeder --force 2>&1 \
        || echo "[entrypoint] WARNING: Central seeder failed — check logs above."

    if [ "${RUN_TENANT_MIGRATIONS:-true}" = "true" ]; then
        echo "[entrypoint] Running tenant migrations across all tenants..."
        php artisan tenants:migrate --force 2>&1 \
            || echo "[entrypoint] WARNING: Tenant migrations failed — check logs above."
    fi
fi

# Clear stale caches, then re-cache for production performance.
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:clear  || true
    php artisan route:clear   || true
    php artisan view:clear    || true
    # Also nuke compiled Blade files directly in case view:clear misses them
    rm -rf storage/framework/views/*.php 2>/dev/null || true
    php artisan config:cache  || true
    php artisan route:cache   || true
    # Skip view:cache — custom pagination views under vendor/ are not resolved
    # correctly when pre-compiled; let Blade compile on-demand instead.
    # php artisan view:cache
fi

# Hand off to supervisord (php-fpm + nginx).
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
