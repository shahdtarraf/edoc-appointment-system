FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo_mysql \
    && mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && printf '%s\n' \
        'session.save_path = "/var/lib/php/sessions"' \
        'session.cookie_httponly = 1' \
        'session.cookie_samesite = Lax' \
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
    && a2enconf edoc

COPY docker/start-apache.sh /usr/local/bin/start-apache.sh
RUN chmod +x /usr/local/bin/start-apache.sh

COPY doctor-appointment-system/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html

ENV PORT=80

EXPOSE 80

CMD ["start-apache.sh"]
