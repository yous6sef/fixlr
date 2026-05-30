# Build for Flix Platform - Using Nginx + PHP-FPM (eliminates Apache MPM issues entirely)
FROM php:8.0-fpm-alpine

# Install required system dependencies including Nginx
RUN apk add --no-cache \
    nginx \
    sqlite \
    sqlite-dev \
    supervisor \
    curl

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Create PHP-FPM config for production
RUN cat > /usr/local/etc/php-fpm.d/www.conf << 'EOF'
[www]
user = nobody
group = nobody
listen = 127.0.0.1:9000
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
EOF

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY flix/ /var/www/html/

# Create required directories with proper permissions
RUN mkdir -p /var/www/html/uploads/workers && \
    mkdir -p /var/www/html/uploads/db && \
    chown -R nobody:nobody /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/uploads

# Create Nginx configuration
RUN mkdir -p /etc/nginx/conf.d && \
    cat > /etc/nginx/nginx.conf << 'EOF'
user nobody;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;
    sendfile on;
    keepalive_timeout 65;
    gzip on;
    
    server {
        listen 8080 default_server;
        server_name _;
        root /var/www/html;
        index index.php index.html;

        # Security headers
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-XSS-Protection "1; mode=block" always;
        server_tokens off;

        # URL rewriting for clean URLs
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        # PHP-FPM routing
        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_buffer_size 128k;
            fastcgi_buffers 4 256k;
            fastcgi_busy_buffers_size 256k;
        }

        # Health check
        location = /status.php {
            access_log off;
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }
}
EOF

# Create supervisord config to manage both nginx and php-fpm
RUN mkdir -p /etc/supervisor/conf.d && \
    cat > /etc/supervisor/conf.d/services.conf << 'EOF'
[supervisord]
nodaemon=true
logfile=/var/log/supervisord.log

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

# Create status.php for health checks
RUN echo '<?php header("Content-Type: text/plain"); echo "OK"; ?>' > /var/www/html/status.php

# Expose port 8080 (Railway requirement)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=10s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -f http://localhost:8080/status.php || exit 1

# Start both services via supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/services.conf"]
