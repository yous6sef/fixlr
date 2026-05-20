FROM php:8.1-cli

# Install Node.js and dependencies
RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    postgresql-client \
    libpq-dev \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy all files
COPY . /app

# Make start script executable
RUN chmod +x /app/start.sh

# Expose port
EXPOSE 8080

# Start command
CMD ["/app/start.sh"]
