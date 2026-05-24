<?php
/**
 * Database Connection Handler
 * Supports PostgreSQL (production) and SQLite (local development)
 */

$databaseUrl = getenv('DATABASE_URL');

try {
    if ($databaseUrl) {
        // Production: Use PostgreSQL via DATABASE_URL
        $dbopts = parse_url($databaseUrl);
        
        $host = $dbopts["host"] ?? 'localhost';
        $port = $dbopts["port"] ?? 5432;
        $user = $dbopts["user"] ?? 'postgres';
        $pass = $dbopts["pass"] ?? '';
        $dbname = ltrim($dbopts["path"] ?? '/flix', '/');
        
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
        
        $conn = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $conn->exec("SET NAMES 'utf8'");
        
    } else {
        // Development: Use SQLite as fallback
        $dbPath = __DIR__ . '/flix.db';
        $dsn = "sqlite:{$dbPath}";
        
        $conn = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $conn->exec("PRAGMA foreign_keys = ON");
        $conn->exec("PRAGMA journal_mode = WAL");
        
        // Auto-initialize SQLite schema if tables don't exist
        $checkTable = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (!$checkTable->fetch()) {
            initializeSqliteSchema($conn);
        }
        
        error_log("✅ SQLite database ready at: {$dbPath}");
    }
    
} catch (PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

/**
 * Initialize SQLite schema for development
 */
function initializeSqliteSchema($conn) {
    $schema = <<<SQL
    
    -- Users Table
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT NOT NULL,
        password_hash TEXT NOT NULL,
        city TEXT,
        address TEXT,
        google_maps_link TEXT,
        profile_picture TEXT,
        total_rating REAL DEFAULT 0,
        total_reviews INTEGER DEFAULT 0,
        total_tasks INTEGER DEFAULT 0,
        user_type TEXT DEFAULT 'user',
        account_status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Workers Table
    CREATE TABLE IF NOT EXISTS workers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT NOT NULL,
        password_hash TEXT NOT NULL,
        specialization TEXT,
        national_id TEXT,
        id_front_path TEXT,
        id_back_path TEXT,
        certificate_path TEXT,
        cv_path TEXT,
        city TEXT,
        location TEXT,
        approved TEXT DEFAULT 'pending',
        status TEXT DEFAULT 'inactive',
        paused TEXT DEFAULT 'no',
        unpaid_streak_days INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Admin Table
    CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT DEFAULT 'admin',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Service Types Table
    CREATE TABLE IF NOT EXISTS service_types (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name_ar TEXT NOT NULL UNIQUE,
        name_en TEXT NOT NULL UNIQUE,
        icon TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Devices Table
    CREATE TABLE IF NOT EXISTS devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_type_id INTEGER NOT NULL,
        name_ar TEXT NOT NULL,
        name_en TEXT NOT NULL,
        icon TEXT,
        FOREIGN KEY (service_type_id) REFERENCES service_types(id),
        UNIQUE(service_type_id, name_ar)
    );
    
    -- Cities Table
    CREATE TABLE IF NOT EXISTS cities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name_ar TEXT NOT NULL UNIQUE,
        name_en TEXT NOT NULL UNIQUE,
        coordinates TEXT
    );
    
    -- Service Requests Table
    CREATE TABLE IF NOT EXISTS service_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        worker_id INTEGER,
        service_type_id INTEGER NOT NULL,
        device_id INTEGER,
        city_id INTEGER NOT NULL,
        google_maps_link TEXT,
        problem_description TEXT NOT NULL,
        checking_fee REAL DEFAULT 300,
        fixing_price REAL,
        total_price REAL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        accepted_at DATETIME,
        worker_arrival_timestamp DATETIME,
        user_arrival_timestamp DATETIME,
        checking_completed_timestamp DATETIME,
        fixing_started_timestamp DATETIME,
        fixing_completed_timestamp DATETIME,
        completed_at DATETIME,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (worker_id) REFERENCES workers(id),
        FOREIGN KEY (service_type_id) REFERENCES service_types(id),
        FOREIGN KEY (city_id) REFERENCES cities(id)
    );
    
    -- Payments Table
    CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_request_id INTEGER NOT NULL,
        worker_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        payment_type TEXT DEFAULT 'checking_fee',
        payment_status TEXT DEFAULT 'pending',
        payment_method TEXT,
        transaction_id TEXT,
        receipt_image TEXT,
        paid_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
        FOREIGN KEY (worker_id) REFERENCES workers(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    
    -- Ratings Table
    CREATE TABLE IF NOT EXISTS ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_request_id INTEGER NOT NULL,
        rater_id INTEGER NOT NULL,
        rated_user_id INTEGER NOT NULL,
        rating INTEGER NOT NULL,
        review_text TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
        FOREIGN KEY (rater_id) REFERENCES users(id),
        FOREIGN KEY (rated_user_id) REFERENCES users(id)
    );
    
    -- Worker Reviews Table
    CREATE TABLE IF NOT EXISTS reviews_worker (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        request_id INTEGER NOT NULL,
        worker_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        rating INTEGER NOT NULL,
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (request_id) REFERENCES service_requests(id),
        FOREIGN KEY (worker_id) REFERENCES workers(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    
    -- Insert test data
    INSERT OR IGNORE INTO admins (name, email, password_hash, role) 
    VALUES ('مدير النظام', 'admin@flix.com', '\$2y\$10\$YourHashedPasswordHere', 'admin');
    
    -- Insert test cities
    INSERT OR IGNORE INTO cities (name_ar, name_en) VALUES ('السادس من أكتوبر', '6th of October');
    INSERT OR IGNORE INTO cities (name_ar, name_en) VALUES ('الشيخ زايد', 'Sheikh Zayed');
    
    -- Insert test service types
    INSERT OR IGNORE INTO service_types (name_ar, name_en) VALUES ('سباكة', 'Plumbing');
    INSERT OR IGNORE INTO service_types (name_ar, name_en) VALUES ('كهرباء', 'Electrical');
    INSERT OR IGNORE INTO service_types (name_ar, name_en) VALUES ('تكييف', 'Air Conditioning');
    
    -- Insert test users
    INSERT OR IGNORE INTO users (name, email, phone, password_hash, city, total_rating, total_reviews) 
    VALUES ('أحمد محمد', 'user@flix.com', '01001234567', '\$2y\$10\$eTn9psHONCTI.loeg5SkAuqPp.d4jI1YsUkr0pxykhQR8ZP6/wSSi', 'السادس من أكتوبر', 4.5, 2);
    
    -- Insert test workers
    INSERT OR IGNORE INTO workers (name, email, phone, password_hash, specialization, city, approved, status) 
    VALUES ('فني محترف', 'worker@flix.com', '01001234568', '\$2y\$10\$eTn9psHONCTI.loeg5SkAuqPp.d4jI1YsUkr0pxykhQR8ZP6/wSSi', 'سباكة', 'السادس من أكتوبر', 'yes', 'active');
    
    SQL;
    
    // Execute schema
    $conn->exec($schema);
}
?>
