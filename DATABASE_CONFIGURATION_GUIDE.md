# 📚 Flix Platform - Database Configuration Guide

## Quick Start

### For Local Development (Default)
```bash
# No setup needed!
# System automatically uses SQLite at flix/flix.db
# Database creates tables automatically on first run

# Just start the server:
php -S localhost:8000
```

### For Production (PostgreSQL)
```bash
# Set environment variable:
export DATABASE_URL="postgresql://user:password@host:port/database"

# Or on Windows (PowerShell):
$env:DATABASE_URL="postgresql://user:password@host:port/database"

# Then start:
php -S localhost:8000
```

---

## Database Architecture

### Development: SQLite
```
flix/flix.db (auto-created file-based database)
├─ users (customer accounts)
├─ workers (technician accounts)
├─ admins
├─ service_types
├─ service_requests
├─ payments
├─ ratings
└─ [other tables]
```

### Production: PostgreSQL  
```
Neon / Railway PostgreSQL
├─ Same schema as SQLite
├─ Better performance
├─ SSL/TLS encryption
└─ Automatic backups
```

---

## How the Connection Works

### 1. db.php Logic
```php
if (getenv('DATABASE_URL')) {
    // Production: Parse PostgreSQL connection string
    // Connect to PostgreSQL with SSL
} else {
    // Development: Use SQLite
    // Create database file if needed
    // Initialize schema automatically
}
```

### 2. Schema Initialization (SQLite only)
- When first connection made, checks if tables exist
- If not found, runs `initializeSqliteSchema()` function
- Creates all necessary tables with same structure as PostgreSQL
- Inserts default admin account

### 3. PDO Abstraction
- Both databases use PHP's PDO (PHP Data Objects)
- Same prepared statements work for both
- No code changes needed when switching databases

---

## Column Mapping

### Users Table
| Column | Type | Notes |
|--------|------|-------|
| id | INT | Auto-increment primary key |
| email | TEXT/VARCHAR | Unique, required |
| phone | TEXT/VARCHAR | Can be used for login |
| password_hash | TEXT/VARCHAR | bcrypt hashed |
| name | TEXT/VARCHAR | User's full name |
| city | TEXT/VARCHAR | User's location |
| user_type | TEXT/VARCHAR | 'user' only for customers |
| account_status | TEXT/VARCHAR | 'active' or 'inactive' |
| created_at | TIMESTAMP | Auto-set on insert |

### Workers Table
| Column | Type | Notes |
|--------|------|-------|
| id | INT | Auto-increment primary key |
| email | TEXT/VARCHAR | Unique, required |
| phone | TEXT/VARCHAR | Can be used for login |
| password_hash | TEXT/VARCHAR | bcrypt hashed |
| name | TEXT/VARCHAR | Technician's name |
| specialization | TEXT/VARCHAR | e.g., 'سباكة' |
| approved | TEXT/VARCHAR | 'pending', 'yes', 'rejected' |
| status | TEXT/VARCHAR | 'active' or 'inactive' |
| created_at | TIMESTAMP | Auto-set on insert |

### Admins Table
| Column | Type | Notes |
|--------|------|-------|
| id | INT | Auto-increment primary key |
| email | TEXT/VARCHAR | Unique, required |
| password_hash | TEXT/VARCHAR | bcrypt hashed |
| name | TEXT/VARCHAR | Admin name |
| role | TEXT/VARCHAR | Always 'admin' |
| created_at | TIMESTAMP | Auto-set on insert |

---

## Authentication Flow

### Login Process (login.php)
```
1. User submits form with:
   - Email or Phone
   - Password
   - Role (user/worker/admin)

2. Query appropriate table:
   SELECT id, name, password_hash FROM [users|workers|admins]
   WHERE (email = ? OR phone = ?)

3. Verify password:
   password_verify($input_password, $database_hash)

4. Set session variables:
   $_SESSION['user_id'] = database id
   $_SESSION['user_role'] = role type
   $_SESSION['user_name'] = user name

5. Redirect to dashboard:
   user → user_dashboard.php
   worker → worker_dashboard.php
   admin → admin.php
```

### Password Hashing (Security)
```php
// Creating password:
$hashed = password_hash('Test@1234', PASSWORD_BCRYPT);

// Verifying password:
if (password_verify('Test@1234', $hashed)) {
    // Correct password ✅
}
```

---

## Test Data

### Pre-loaded Test Accounts
Run `http://localhost:8000/setup-test-users.php` once to create:

```
👤 Customer: user@flix.com / Test@1234
👷 Technician: worker@flix.com / Test@1234
⚙️ Admin: admin@flix.com / Test@1234
```

### Pre-loaded Services
```
1. 🚰 سباكة (Plumbing)
2. ⚡ كهرباء (Electrical)
3. 🧹 تنظيف (Cleaning)
4. 🔨 نجارة (Carpentry)
5. 🎨 دهان (Painting)
6. 📦 نقل (Moving)
```

### Pre-loaded Cities
```
1. القاهرة (Cairo)
2. الجيزة (Giza)
3. الإسكندرية (Alexandria)
4. المنصورة (Mansoura)
```

---

## Connection Strings

### SQLite (Development)
```
sqlite:flix/flix.db
```

### PostgreSQL (Production - Neon)
```
postgresql://user:password@host:5432/dbname?sslmode=require
```

### PostgreSQL (Production - Railway)
```
postgresql://user:password@host:3306/railway
```

### MySQL (Alternative)
```
mysql://user:password@localhost:3306/dbname
```

---

## Error Handling

### Database Connection Errors
```php
// In db.php catch block:
try {
    $conn = new PDO(...);
} catch (PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}
```

### Query Preparation Errors
```php
// In login.php:
$stmt = $conn->prepare('SELECT ... FROM users WHERE ...');
// If column doesn't exist → PDOException thrown
// Error message shows column name
```

### Common Errors & Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| "no such column: password" | Wrong column name | Use `password_hash` |
| "UNIQUE constraint failed" | Duplicate email/phone | Check before insert |
| "DATABASE_URL missing" | Production config not set | Set env variable or use SQLite |
| "Connection refused" | PostgreSQL not running | Start PostgreSQL or use SQLite |

---

## Performance Tips

### SQLite
- ✅ Great for development/testing
- ✅ No external server needed
- ✅ File-based storage (flix/flix.db)
- ⚠️ Limited concurrent users (file locking)
- ⚠️ Not suitable for high traffic

### PostgreSQL
- ✅ Great for production
- ✅ Concurrent user support
- ✅ Advanced query optimization
- ✅ Replication & backups
- ⚠️ Requires external server
- ⚠️ More complex setup

---

## Migration from SQLite to PostgreSQL

When ready to deploy to production:

### Step 1: Export SQLite Data
```bash
# Use migration tools or manual export
sqlite3 flix.db .dump > backup.sql
```

### Step 2: Create PostgreSQL Database
```bash
createdb flix
psql flix < backup.sql
```

### Step 3: Update Connection
```bash
export DATABASE_URL="postgresql://user:pass@host:port/flix"
# or in .env file:
DATABASE_URL=postgresql://...
```

### Step 4: Test
```bash
php -S localhost:8000
# Test login with existing accounts
```

---

## Debugging

### Enable Error Logging
```php
// In db.php:
error_log("Database connection made");

// View logs:
tail -f /var/log/php.log
```

### Check Database Status
```php
// Add ?debug_db=1 to any URL
// Shows: Database Type: SQLite/PostgreSQL
```

### Verify Accounts
```php
// Test if user exists:
$stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute(['user@flix.com']);
$user = $stmt->fetch();
var_dump($user); // Shows all columns
```

---

## References

- [SQLite Documentation](https://www.sqlite.org/docs.html)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [PHP PDO Tutorial](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [bcrypt Password Hashing](https://www.php.net/manual/en/function.password-hash.php)
- [Neon PostgreSQL Hosting](https://neon.tech/)
- [Railway PostgreSQL Hosting](https://railway.app/)

---

## Support

For issues:
1. Check error messages in browser
2. Review PHP error logs
3. Verify DATABASE_URL is set (for production)
4. Ensure flix/ directory is writable (for SQLite)
5. Check database user permissions (for PostgreSQL)

The system is production-ready! 🚀
