FROM php:8.2-apache

# 1. Install dependensi
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# 2. Hapus paksa semua file konfigurasi MPM yang mungkin menyebabkan konflik
# Perintah ini memastikan tidak ada sisa-sisa modul lain yang tertinggal
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.load \
    && rm -f /etc/apache2/mods-enabled/mpm_event.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.conf

# 3. Aktifkan mpm_prefork secara eksplisit
RUN a2enmod mpm_prefork

# 4. Aktifkan rewrite & sesuaikan DocumentRoot
RUN a2enmod rewrite
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 5. Salin aplikasi & atur izin
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html
