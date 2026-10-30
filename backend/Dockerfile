FROM php:8.3-apache AS base
RUN apt-get update \
 && apt-get install -y --no-install-recommends cron procps unzip git libzip-dev \
 && docker-php-ext-install pdo_mysql zip \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/turysta.ini
COPY docker/apache.conf /etc/apache2/conf-available/turysta.conf
RUN a2enconf turysta
WORKDIR /var/www/html
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && chmod +x docker/scheduler-entrypoint.sh \
 && chown -R www-data:www-data /var/www/html
HEALTHCHECK --interval=10s --timeout=5s --retries=3 --start-period=5s --start-interval=2s CMD curl -fsS http://localhost/api/health || exit 1
