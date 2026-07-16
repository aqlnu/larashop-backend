FROM php:8.2-apache

# Install dependensi yang diperlukan
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# FIX: Matikan semua MPM yang ada dan aktifkan prefork saja untuk menghindari error AH00534
RUN a2dismod mpm_event mpm_worker mpm_prefork && a2enmod mpm_prefork

# Aktifkan mod_rewrite untuk Laravel
RUN a2enmod rewrite

# Salin kode ke direktori kerja
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Arahkan DocumentRoot ke folder 'public' milik Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
