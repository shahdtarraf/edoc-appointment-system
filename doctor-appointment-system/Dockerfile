# Standalone image when the build context is this folder.
# Render production builds should use the repository-root Dockerfile instead.
FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo_mysql \
    && mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && printf '%s\n' \
        'session.save_path = "/var/lib/php/sessions"' \
        'session.cookie_httponly = 1' \
        'session.cookie_samesite = Lax' \
        'session.cookie_secure = 1' \
        'output_buffering = 4096' \
        'display_errors = Off' \
        'log_errors = On' \
        'expose_php = Off' \
        > /usr/local/etc/php/conf.d/edoc.ini \
    && a2enmod rewrite headers \
    && printf '%s\n' \
        '<Directory /var/www/html>' \
        '    Options -Indexes +FollowSymLinks' \
        '    AllowOverride All' \
        '    Require all granted' \
        '    DirectoryIndex index.html index.php' \
        '</Directory>' \
        > /etc/apache2/conf-available/edoc.conf \
    && a2enconf edoc \
    && printf '%s\n' \
        '#!/bin/bash' \
        'set -euo pipefail' \
        'PORT="${PORT:-80}"' \
        'sed -i "s/^Listen .*/Listen 0.0.0.0:${PORT}/" /etc/apache2/ports.conf' \
        'sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf' \
        'exec apache2-foreground' \
        > /usr/local/bin/start-apache.sh \
    && chmod +x /usr/local/bin/start-apache.sh

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && rm -f /var/www/html/sql_database_edoc.sql /var/www/html/Dockerfile /var/www/html/docker-compose.yml

ENV PORT=80

EXPOSE 80

CMD ["start-apache.sh"]
