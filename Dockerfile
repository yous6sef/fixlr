# Multi-stage build for Flix Platform
FROM php:8.0-apache

# Enable required PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Enable mod_rewrite for clean URLs
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY flix/ /var/www/html/

# Configure Apache to use proper document root
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Set proper file permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 8080 (Railway requirement)
EXPOSE 8080

# Configure Apache to listen on 8080
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf && \
    sed -i 's/:80/:8080/g' /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]
