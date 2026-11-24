
FROM php:8.3-apache

RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY e-bunge/e-Bunge/ ./

RUN mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html

ENV PORT=10000

EXPOSE 10000

CMD ["bash", "-lc", "sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
