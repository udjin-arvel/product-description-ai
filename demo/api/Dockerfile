FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json CHANGELOG.md README.md ./
COPY config ./config
COPY src ./src

COPY demo/api ./demo/api

WORKDIR /var/www/demo/api

RUN rm -f bootstrap/cache/*.php \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8000

CMD ["php", "-d", "max_execution_time=180", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
