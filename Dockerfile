FROM php:8.3-apache

# PHP extensions (the base image already ships curl, mbstring, openssl, sodium, xml, ...)
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql bcmath intl zip pcntl opcache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Apache: serve Laravel's public/ directory and honour its .htaccess
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && a2enmod rewrite headers

# Behind the admin container's nginx proxy, take the client IP from
# X-Forwarded-For so rate limiting and request logs are per client
RUN printf '%s\n' \
        'RemoteIPHeader X-Forwarded-For' \
        'RemoteIPInternalProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16' \
        > /etc/apache2/conf-available/remoteip.conf \
    && a2enmod remoteip \
    && a2enconf remoteip

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini $PHP_INI_DIR/conf.d/99-quizlo.ini

WORKDIR /var/www/html

# Dev packages (faker etc.) are needed for `php artisan db:seed`; build with
# --build-arg WITH_DEV=false for a production image.
ARG WITH_DEV=false
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies first so this layer is cached until composer.* changes
COPY composer.json composer.lock ./
RUN if [ "$WITH_DEV" = "true" ]; then DEV_FLAG=""; else DEV_FLAG="--no-dev"; fi \
    && composer install $DEV_FLAG --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY . .

RUN if [ "$WITH_DEV" = "true" ]; then DEV_FLAG=""; else DEV_FLAG="--no-dev"; fi \
    && composer dump-autoload $DEV_FLAG --optimize --no-interaction \
    && sed -i 's/\r$//' docker/entrypoint.sh \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
