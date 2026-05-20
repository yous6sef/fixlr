FROM php:8.1-cli

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y \
    postgresql-client \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy all files
COPY . /app

# Expose port
EXPOSE 8080

# Start command
CMD ["bash", "start.sh"]
