FROM php:8.2-fpm

# 1. Install sistem dependensi
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# 2. Setup Nginx (Pengganti Apache)
COPY --chown=www-data:www-data . /var/www/html
RUN echo 'server { \
    listen ${PORT}; \
    index index.php index.html; \
    root /var/www/html/public; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

# 3. Izin folder
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Jalankan Nginx dan PHP-FPM
CMD sed -i 's/${PORT}/'$PORT'/g' /etc/nginx/sites-available/default && nginx && php-fpm
