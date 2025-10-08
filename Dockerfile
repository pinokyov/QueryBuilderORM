FROM php:8.1-apache

# Install required system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Create necessary directories if they don't exist
RUN mkdir -p /var/www/html/public /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Configure Apache
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80
