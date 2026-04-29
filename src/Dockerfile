# ============================================================
# Stage 1 – Build Laravel app (composer + node assets)
# ============================================================
FROM php:8.4-fpm AS app

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    tesseract-ocr \
    nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY src/ /var/www/

RUN if [ -f composer.json ]; then \
        composer install --no-interaction --no-dev --optimize-autoloader --no-scripts; \
    fi

# Build frontend assets if package.json present. Prefer `npm ci` when a
# lockfile exists, otherwise fall back to `npm install` so fresh clones
# without package-lock.json still build.
RUN if [ -f package.json ]; then \
        if [ -f package-lock.json ]; then \
            npm ci --no-audit --no-fund; \
        else \
            npm install --no-audit --no-fund; \
        fi \
        && npm run build \
        && rm -rf node_modules; \
    fi

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage 2>/dev/null || true \
    && chmod -R 755 /var/www/bootstrap/cache 2>/dev/null || true

# ============================================================
# Stage 2 – Final image: nginx + PHP-FPM + supervisord
# ============================================================
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    nginx supervisor gettext-base \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    tesseract-ocr \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# OPcache
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=8\n\
opcache.max_accelerated_files=10000\n\
opcache.revalidate_freq=2\n\
opcache.fast_shutdown=1\n\
opcache.enable_cli=1" > /usr/local/etc/php/conf.d/opcache.ini

# PHP-FPM tuning
RUN echo "pm.max_children = 20\n\
pm.start_servers = 5\n\
pm.min_spare_servers = 3\n\
pm.max_spare_servers = 8\n\
pm.max_requests = 500" > /usr/local/etc/php-fpm.d/zz-tuning.conf

# Application code
COPY --from=app /var/www /var/www

# All docker/* configs inlined (heredocs) instead of COPY-from-context.
# Reason: Railway's remote BuildKit was intermittently failing the
# `COPY docker/<file>` checksum step ("not found") even though the files were
# committed and pushed. Inlining sidesteps the build-context flakiness
# entirely. Keep these in sync with the on-disk files in docker/ for parity
# with local docker compose, which still bind-mounts them.

# nginx server template (envsubst'd at runtime to inject ${PORT}).
RUN cat > /etc/nginx/conf.d/default.conf.template <<'NGINX_EOF'
server {
    listen ${PORT};
    server_name _;
    index index.php index.html;
    root /var/www/public;
    client_max_body_size 20M;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 4;
    gzip_min_length 256;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffering on;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 16 16k;
        fastcgi_connect_timeout 300s;
        fastcgi_send_timeout 300s;
        fastcgi_read_timeout 300s;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX_EOF

# supervisord — runs php-fpm + nginx in the foreground.
RUN cat > /etc/supervisor/conf.d/supervisord.conf <<'SUPERVISORD_EOF'
[supervisord]
nodaemon=true
logfile=/var/log/supervisord.log
pidfile=/var/run/supervisord.pid
user=root

[program:php-fpm]
command=php-fpm --nodaemonize
autostart=true
autorestart=true
priority=5
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
priority=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
SUPERVISORD_EOF

# Entrypoint — keep in sync with docker/entrypoint.sh.
RUN cat > /usr/local/bin/entrypoint.sh <<'ENTRYPOINT_EOF'
#!/bin/sh
set -e

export PORT="${PORT:-80}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
echo "[entrypoint] Binding nginx to port: $PORT"
echo "[entrypoint] LOG_CHANNEL=$LOG_CHANNEL"

envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
echo "[entrypoint] Rendered nginx config:"
grep -E 'listen|server_name' /etc/nginx/conf.d/default.conf || true

nginx -t

cd /var/www

mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

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
fi

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:clear  || true
    php artisan route:clear   || true
    php artisan view:clear    || true
    rm -rf storage/framework/views/*.php 2>/dev/null || true
    php artisan config:cache  || true
    php artisan route:cache   || true
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
ENTRYPOINT_EOF

RUN rm -f /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/conf.d/default.conf \
    && mkdir -p /var/log/supervisor \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www

# Railway injects $PORT; default 80 for local docker compose.
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
