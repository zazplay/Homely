# Laravel API on FrankenPHP: web server + PHP in one process (like `node server.js`).
FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions pdo_pgsql intl zip gd bcmath opcache

# Production php.ini + limits for base64 photo uploads (base64 is ~1.33x the file size)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf "post_max_size=100M\nupload_max_filesize=20M\nmemory_limit=512M\nopcache.enable_cli=1\nopcache.validate_timestamps=0\n" > "$PHP_INI_DIR/conf.d/zz-app.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

WORKDIR /app

# Dependencies first: this layer is cached until composer.lock changes
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && chmod +x docker/start.sh

# Render passes PORT (10000 by default)
ENV PORT=10000
EXPOSE 10000

CMD ["docker/start.sh"]
