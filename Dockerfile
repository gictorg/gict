# Use PHP 8.2 with Apache as base image
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP to prevent output issues
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "output_buffering = On" >> /usr/local/etc/php/conf.d/production.ini

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Add health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health-simple.php || exit 1

# Start Apache
CMD ["apache2-foreground"] 