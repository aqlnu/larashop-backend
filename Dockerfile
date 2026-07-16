FROM php:8.2-apache

# Install ekstensi yang dibutuhkan Laravel & PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Aktifkan mod_rewrite untuk routing Laravel
RUN a2enmod rewrite

# Salin konfigurasi Apache
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Ubah document root ke folder public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
