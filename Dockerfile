# Multi-stage build for Flix Platform
FROM php:8.0-apache

# Install required system dependencies for SQLite
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Enable required PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Enable Apache modules
RUN a2enmod rewrite && \
    a2enmod headers && \
    a2enmod remoteip

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY flix/ /var/www/html/

# Create required directories with proper permissions
RUN mkdir -p /var/www/html/uploads/workers && \
    mkdir -p /var/www/html/uploads/db && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/uploads 2>/dev/null || true

# Configure Apache for port 8080 and production
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf && \
    sed -i 's/:80/:8080/g' /etc/apache2/sites-available/000-default.conf && \
    sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Enable PHP error logging to stdout for debugging
RUN echo "error_log = /proc/self/fd/2" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini

# Configure Apache logging to stdout/stderr
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log && \
    ln -sf /proc/self/fd/2 /var/log/apache2/error.log

# Set environment variables
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_LOCK_DIR=/var/run/apache2 \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid \
    PORT=8080

# Expose port 8080 (Railway requirement)
EXPOSE 8080

# Add startup wrapper script to handle Apache initialization
RUN echo '#!/bin/bash\nset -e\necho "Starting Apache on port 8080..."\napache2-foreground' > /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

# Start Apache with proper foreground mode
CMD ["/usr/local/bin/start.sh"]
