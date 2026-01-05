# Use PHP 8.2 with Apache as base image
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mbstring curl zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP for your application
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/production.ini

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create uploads directory and set proper permissions
RUN mkdir -p /var/www/html/uploads \
    && mkdir -p /var/www/html/assets/generated_marksheets \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads \
    && chmod -R 777 /var/www/html/assets/generated_marksheets

# Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Add security headers
RUN echo "Header always set X-Content-Type-Options nosniff" >> /etc/apache2/conf-available/security.conf \
    && echo "Header always set X-Frame-Options DENY" >> /etc/apache2/conf-available/security.conf \
    && echo "Header always set X-XSS-Protection \"1; mode=block\"" >> /etc/apache2/conf-available/security.conf \
    && a2enconf security

# Expose port 80
EXPOSE 80

# Add health check
# HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
#     CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]