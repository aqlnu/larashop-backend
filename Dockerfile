FROM php:8.2-fpm

# Install Nginx dan ekstensi PHP yang diperlukan
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Copy konfigurasi Nginx
COPY <<EOF /etc/nginx/sites-available/default
server {
    listen 8080;
    index index.php index.html;
    root /var/www/html/public;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
EOF

# Copy file proyek
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Gunakan port 8080 agar sesuai dengan Railway
ENV PORT=8080
EXPOSE 8080

# Jalankan Nginx dan PHP-FPM bersamaan
CMD nginx && php-fpm
