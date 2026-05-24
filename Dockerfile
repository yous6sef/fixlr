# Build for Flix Platform
FROM php:8.0-apache

# Install required system dependencies for SQLite
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Fix Apache MPM conflict: directly manage module symlinks
# Remove all conflicting MPM module symlinks, keep only mpm_prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf && \
    ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
    ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf && \
    a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY flix/ /var/www/html/

# Create required directories with proper permissions
RUN mkdir -p /var/www/html/uploads/workers && \
    mkdir -p /var/www/html/uploads/db

# Configure Apache for port 8080
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf && \
    sed -i 's/:80/:8080/g' /etc/apache2/sites-available/000-default.conf

# Set proper file permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/uploads

# Configure Apache logging to stdout/stderr
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log && \
    ln -sf /proc/self/fd/2 /var/log/apache2/error.log

# Set environment variables
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_LOCK_DIR=/var/run/apache2 \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid

# Expose port 8080 (Railway requirement)
EXPOSE 8080

# Start Apache in foreground
CMD ["apache2-foreground"]

# Start Apache in foreground
CMD ["apache2-foreground"]
