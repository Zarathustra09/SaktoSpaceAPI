FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Enable Apache modules
RUN a2enmod rewrite

# Configure Apache to serve Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!<Directory /var/www/html>!<Directory ${APACHE_DOCUMENT_ROOT}>!g' /etc/apache2/apache2.conf /etc/apache2/sites-available/*.conf

# Install composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files and install deps (skip scripts since artisan doesn't exist yet)
COPY composer.json composer.lock /var/www/html/
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copy application code
COPY . .


RUN mkdir -p /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache \
 && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Default Laravel DB env (can be overridden by docker-compose)
ENV APP_ENV=local \
    APP_DEBUG=true \
    DB_CONNECTION=mysql \
    DB_HOST=host.docker.internal \
    DB_PORT=3306 \
    DB_DATABASE=saktospace \
    DB_USERNAME=root \
    DB_PASSWORD=

# Set proper permissions
RUN chgrp -R www-data /var/www/html/storage \
    && chgrp -R www-data /var/www/html/bootstrap/cache \
    && chmod -R g+w /var/www/html/storage \
    && chmod -R g+w /var/www/html/bootstrap/cache

# Install Node.js dependencies and build assets
RUN npm install && npm run build

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose Apache container port
EXPOSE 80

# Use custom entrypoint that runs migrations and seeds
ENTRYPOINT ["docker-entrypoint.sh"]
