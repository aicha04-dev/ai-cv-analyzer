FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install -j$(nproc) intl pdo_pgsql zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Production environment
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Create a minimal .env so Symfony Runtime does not fail
RUN printf '%s\n' \
    'APP_ENV=prod' \
    'APP_DEBUG=0' \
    > .env

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts

RUN mkdir -p var/cache var/log public/uploads \
    && chown -R www-data:www-data var public/uploads \
    && chmod -R 775 var public/uploads

RUN printf '%s\n' \
    '<VirtualHost *:80>' \
    '    DocumentRoot /var/www/html/public' \
    '' \
    '    <Directory /var/www/html/public>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["sh", "-c", "chown -R www-data:www-data var public/uploads && php bin/console doctrine:migrations:migrate --no-interaction --env=prod && apache2-foreground"]