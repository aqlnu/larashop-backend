# Gunakan image PHP 8.2 dengan Apache
FROM php:8.2-apache

# 1. Install dependensi sistem yang dibutuhkan Laravel & PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# 2. FIX: Pastikan hanya satu MPM yang aktif untuk mencegah error AH00534
RUN a2dismod mpm_event mpm_worker mpm_prefork && a2enmod mpm_prefork

# 3. Aktifkan mod_rewrite untuk routing Laravel
RUN a2enmod rewrite

# 4. Salin semua file dari folder backend ke dalam container
COPY . /var/www/html

# 5. Sesuaikan DocumentRoot agar mengarah ke folder 'public'
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 6. Ubah konfigurasi port agar dinamis mengikuti port Railway
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 7. Berikan hak akses ke folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Set direktori kerja
WORKDIR /var/www/html

# 9. Jalankan Apache
CMD sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground
