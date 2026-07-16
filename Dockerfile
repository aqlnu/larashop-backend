FROM php:8.2-apache

# Install dependensi
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# FIX: Matikan semua MPM dan aktifkan prefork saja
RUN a2dismod mpm_event mpm_worker mpm_prefork && a2enmod mpm_prefork

# Aktifkan rewrite
RUN a2enmod rewrite

# Salin source code
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Sesuaikan document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
