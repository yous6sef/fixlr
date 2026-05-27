# FLIX Installation & Setup Guide

## Quick Start (5 minutes)

### 1. Prerequisites
- PHP 8.0+ 
- PostgreSQL 10+ (or SQLite)
- Node.js 16+ (for Socket.io)
- Git
- Cloudinary account (for image uploads)

### 2. Clone Repository
```bash
git clone https://github.com/youssef1621111/fixlr.git
cd fixlr
```

### 3. Environment Setup
```bash
# Copy example environment file
cp .env.example .env

# Edit .env with your values
# DATABASE_URL=postgresql://user:pass@localhost/flix_db
# CLOUDINARY_CLOUD_NAME=your_name
# etc.
```

### 4. Database Setup

#### Option A: PostgreSQL
```bash
# Connect to PostgreSQL
psql

# Create database
CREATE DATABASE flix_db;

# Exit psql
\q

# Import schema (create this from schema.sql in database/)
psql flix_db < database/schema.sql
```

#### Option B: SQLite (Development)
```bash
# SQLite will auto-create on first run
# No setup needed - flix.db will be created
```

### 5. Start Services
```bash
# Terminal 1: Start PHP dev server
php -S localhost:8000

# Terminal 2: Start Node.js Socket.io server
cd flix/node && npm install && node server.js

# Terminal 3: Watch CSS changes (optional)
npm run watch-css
```

### 6. Access Application
```
http://localhost:8000/pages/user/signup.php
```

### 7. Demo Credentials
```
User Account:
  Email: user@test.com
  Password: User@123456

Worker Account:
  Email: worker@test.com
  Password: Worker@123456

Admin Account:
  Email: admin@test.com
  Password: Admin@123456
```

---

## Detailed Setup

### Database Configuration

#### PostgreSQL (Recommended for Production)
```bash
# Install PostgreSQL
# macOS
brew install postgresql

# Ubuntu/Debian
sudo apt-get install postgresql postgresql-contrib

# Windows
# Download from https://www.postgresql.org/download/windows/

# Start PostgreSQL
brew services start postgresql  # macOS
sudo service postgresql start   # Linux

# Create user and database
psql postgres
CREATE USER flix_user WITH PASSWORD 'secure_password_here';
CREATE DATABASE flix_db OWNER flix_user;
GRANT ALL PRIVILEGES ON DATABASE flix_db TO flix_user;

# Set DATABASE_URL in .env
DATABASE_URL=postgresql://flix_user:secure_password_here@localhost:5432/flix_db
```

#### SQLite (Development)
```bash
# No installation needed
# SQLite is built into PHP
# Database file: flix/flix.db
```

### Cloudinary Setup

1. **Create Cloudinary Account**
   - Go to https://cloudinary.com
   - Sign up for free account
   - Get Cloud Name, API Key, API Secret

2. **Add to .env**
   ```
   CLOUDINARY_CLOUD_NAME=your_cloud_name
   CLOUDINARY_API_KEY=your_api_key
   CLOUDINARY_API_SECRET=your_api_secret
   ```

3. **Verify Upload Works**
   - Go to http://localhost:8000/pages/user/signup.php
   - Select Worker type
   - Try uploading an image
   - Check Cloudinary dashboard for uploaded file

### Node.js Setup

```bash
cd flix/node

# Install dependencies
npm install

# Start server
node server.js

# Should see:
# Socket.io Server running on port 3000
# Connected to FLIX backend
```

---

## Configuration

### PHP Configuration (php.ini)
```ini
; Required settings for FLIX

; Upload file size
upload_max_filesize = 10M
post_max_size = 10M

; Session configuration  
session.save_path = "/tmp"
session.gc_maxlifetime = 86400
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Lax"

; Error reporting (development only)
error_reporting = E_ALL
display_errors = 0
log_errors = 1
error_log = "logs/php_errors.log"

; Default timezone
date.timezone = "Africa/Cairo"
```

### Web Server Configuration

#### Apache
```apache
<VirtualHost *:80>
    ServerName flix.local
    DocumentRoot /path/to/fixlr
    
    <Directory /path/to/fixlr>
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
    </Directory>
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name flix.local;
    root /path/to/fixlr;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

---

## Troubleshooting Installation

### PHP Dev Server Doesn't Start
```bash
# Check if port 8000 is in use
lsof -i :8000  # macOS/Linux
netstat -ano | findstr :8000  # Windows

# Use different port
php -S localhost:8001
```

### Database Connection Error
```bash
# Check PostgreSQL is running
psql -U postgres

# Verify DATABASE_URL format
# Correct: postgresql://user:pass@localhost:5432/flix_db
# Wrong: postgresql://user:pass@localhost/flix_db (missing port)

# Test connection
php -r "echo pg_connect('postgresql://user:pass@localhost:5432/flix_db') ? 'OK' : 'FAIL';"
```

### Cloudinary Upload Fails
```bash
# Check credentials in .env
echo $CLOUDINARY_CLOUD_NAME

# Verify PHP has curl extension
php -m | grep curl

# Test Cloudinary API
curl -X POST https://api.cloudinary.com/v1_1/YOUR_CLOUD_NAME/image/upload \
  -F "file=@test.jpg" \
  -F "api_key=YOUR_API_KEY"
```

### Node.js Socket.io Error
```bash
# Check Node.js version
node --version  # Should be 16+

# Clear node_modules and reinstall
cd flix/node && rm -rf node_modules && npm install

# Check port 3000 is available
lsof -i :3000

# Run with debugging
DEBUG=* node server.js
```

---

## Next Steps After Installation

1. **Create Test Task**
   - Login as user@test.com
   - Go to pages/user/task_create.php
   - Create a test task
   - Should see task ID on success page

2. **Accept Task as Worker**
   - Login as worker@test.com
   - Go to pages/worker/worker_available_requests.php
   - Accept the task you created
   - Track task status in pages/user/track.php

3. **Approve Worker in Admin**
   - Logout and login as admin@test.com
   - Go to pages/admin/admin_dashboard.php
   - Approve the worker
   - Verify can now create tasks

4. **Test Full Workflow**
   - Create task
   - Accept task
   - Navigate through all 11 states
   - Complete task
   - Submit ratings

---

## Development Tools

### VS Code Extensions (Recommended)
```json
{
  "recommendations": [
    "felixbecker.php-debug",
    "bmewburn.vscode-intelephense-client",
    "kokororin.vscode-phpfmt",
    "github.copilot",
    "ms-dotnettools.vscode-dotnet-runtime"
  ]
}
```

### Debug with Xdebug
```php
// Add to .env
XDEBUG_CONFIG=idekey=vscode

// Enable in PHP script
xdebug_break();  // Sets breakpoint
```

### Testing with Postman
1. Import API endpoints from docs
2. Set Authorization header
3. Test endpoints with sample data
4. Save as collection for later

---

## Production Deployment

See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for:
- Docker setup
- Railway deployment
- Heroku setup
- SSL/HTTPS configuration
- Database backups
- Monitoring setup

---

**Installation Complete!** 🎉

Next: Read [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md) for complete system overview.
