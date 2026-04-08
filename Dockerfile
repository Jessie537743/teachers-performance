# ============================================================
# Stage 1 – PHP-FPM + Laravel application
# ============================================================
FROM php:8.4-fpm AS app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    tesseract-ocr \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure OPcache for performance
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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY src/ /var/www/

# Install dependencies (if vendor doesn't exist)
RUN if [ -f composer.json ]; then composer install --no-interaction --optimize-autoloader; fi

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage 2>/dev/null || true \
    && chmod -R 755 /var/www/bootstrap/cache 2>/dev/null || true

# ============================================================
# Stage 2 – Final image: nginx + PHP-FPM + supervisord
# ============================================================
FROM php:8.4-fpm

# Install nginx, supervisord, and runtime system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    tesseract-ocr \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure OPcache
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

# Copy built Laravel application from stage 1
COPY --from=app /var/www /var/www

# Copy nginx site configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Remove the default nginx site
RUN rm -f /etc/nginx/sites-enabled/default

# Copy supervisord configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Ensure log directories exist
RUN mkdir -p /var/log/supervisor

# nginx listens on 80 (HTTP); PHP-FPM on 9000 (internal only)
EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
