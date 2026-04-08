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

# nginx + supervisord config (template substituted at runtime)
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf.template
COPY docker/supervisord.conf   /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh      /usr/local/bin/entrypoint.sh
RUN rm -f /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/conf.d/default.conf \
    && mkdir -p /var/log/supervisor \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www

# Railway injects $PORT; default 80 for local docker compose.
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
