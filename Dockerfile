# Slika knjižare za produkciju (odeljak 16.4). Tri faze: PHP paketi,
# frontend i konačna slika, u kojoj nema ni Composer-a ni Node.js-a.

# --- 1. PHP paketi, bez razvojnih (Pest, Larastan, Pint) ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
        --no-interaction --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# --- 2. Frontend: Vite pravi public/build (odeljak 16.3) ---
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
# VITE_* promenljive Vite upisuje u JavaScript pri izgradnji, pa se
# zadaju ovde, kao argumenti, a ne pri pokretanju kontejnera.
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST=localhost
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
RUN npm run build

# --- 3. Aplikacija: PHP-FPM ---
FROM php:8.5-fpm-alpine AS app
RUN docker-php-ext-install pdo_mysql pcntl \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/knjizara.ini "$PHP_INI_DIR/conf.d/knjizara.ini"

WORKDIR /var/www/knjizara
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=frontend /app/public/build ./public/build

# Specifikacija API-ja kao statički fajl (odeljak 15.4). Scramble čita i
# šemu baze, pa se pravi nad privremenom SQLite bazom koja ne ostaje u slici.
RUN cp .env.example .env \
    && php artisan key:generate \
    && touch /tmp/build.sqlite && mkdir -p public/docs \
    && DB_DATABASE=/tmp/build.sqlite php artisan migrate --force \
    && DB_DATABASE=/tmp/build.sqlite \
        php artisan scramble:export --path=public/docs/api.json \
    && rm -f .env /tmp/build.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache public/docs

USER www-data
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/knjizara-entrypoint
ENTRYPOINT ["knjizara-entrypoint"]
CMD ["php-fpm"]

# --- 4. nginx sa javnim fajlovima iz slike aplikacije ---
FROM nginx:1.29-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/knjizara/public /var/www/knjizara/public
