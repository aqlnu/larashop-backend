FROM php:8.2-apache

# 1. Install dependensi
RUN apt-get update && apt-get install -y libpq-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# 2. HARD RESET APACHE
# Menghapus semua file konfigurasi MPM yang ada untuk mencegah konflik
RUN rm -rf /etc/apache2/mods-enabled/*.load /etc/apache2/mods-enabled/*.conf \
    && a2enmod mpm_prefork rewrite

# 3. Pengaturan DocumentRoot ke folder public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 4. Salin kode & berikan akses
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Konfigurasi Port Dinamis
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

WORKDIR /var/www/html

# 6. Jalankan Apache dengan perintah langsung
CMD sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground
