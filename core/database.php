<?php
/**
 * Database Connection Handler
 * Supports PostgreSQL (production) and SQLite (local development)
 * Production-ready: No demo mode
 */

// Demo mode flag - set to false for production
$DEMO_MODE = false;

// Mock data for demo testing
$DEMO_USERS = [
    [
        'id' => 1,
        'name' => 'Test User',
        'email' => 'user@test.com',
        'phone' => '+201001234567',
        'password_hash' => '$2y$10$It1pFHiEr56asrkQFnDz5uh1imY34ryX2HiMtJULWfw40k/mKm1xK',
        'city' => 'Cairo',
        'user_type' => 'user',
        'account_status' => 'active',
        'total_rating' => 0,
        'total_reviews' => 0
    ],
    [
        'id' => 999,
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'phone' => '+201005555555',
        'password_hash' => '$2y$10$oCUyMJD6un/W9w4jsyGCq.GE9j9EPIbJGtRDZxgYl.X0BqKYqhg2O',
        'city' => 'Cairo',
        'user_type' => 'admin',
        'account_status' => 'active',
        'total_rating' => 0,
        'total_reviews' => 0
    ]
];

$DEMO_WORKERS = [
    [
        'id' => 2,
        'name' => 'Test Worker',
        'email' => 'worker@test.com',
        'phone' => '+201009876543',
        'password_hash' => '$2y$10$6rFmgC3gbg5YDLvUn7U5aOIF7AwlQlAauIB7BvKTk/8wonrE0/sdO',
        'city' => 'Cairo',
        'specialization' => 'Plumbing',
        'approved' => 'yes',
        'status' => 'active',
        'total_rating' => 4.5,
        'total_reviews' => 127
    ]
];

// If demo mode is enabled, create mock functions or override real ones
if ($DEMO_MODE) {
    // Create wrapper function that always uses demo data when in DEMO_MODE
    function _demo_pg_query_params($connection, $query, $params = []) {
        global $DEMO_USERS, $DEMO_WORKERS;
        
        // Simple mock query handler for common queries
        if (strpos($query, 'SELECT * FROM users WHERE') !== false) {
            foreach ($DEMO_USERS as $user) {
                // Check email or phone match
                if (isset($params[0]) && ($user['email'] === $params[0] || $user['phone'] === $params[0])) {
                    return (object)['result' => [$user], 'count' => 1];
                }
            }
            return (object)['result' => [], 'count' => 0];
        }
        
        if (strpos($query, 'SELECT * FROM workers WHERE') !== false) {
            foreach ($DEMO_WORKERS as $worker) {
                if (isset($params[0]) && ($worker['email'] === $params[0] || $worker['phone'] === $params[0])) {
                    return (object)['result' => [$worker], 'count' => 1];
                }
            }
            return (object)['result' => [], 'count' => 0];
        }
        
        // Default: return empty result
        return (object)['result' => [], 'count' => 0];
    }
    
    function _demo_pg_num_rows($result) {
        return isset($result->count) ? $result->count : 0;
    }
    
    function _demo_pg_fetch_assoc($result) {
        if (!isset($result->result) || empty($result->result)) {
            return false;
        }
        return array_shift($result->result);
    }
    
    // Create a fake $db connection for compatibility
    $db = (object)['connected' => true];
    
    // Also define the standard PostgreSQL function names if they don't exist
    if (!function_exists('pg_query_params')) {
        function pg_query_params($connection, $query, $params = []) {
            return _demo_pg_query_params($connection, $query, $params);
        }
    }
    
    if (!function_exists('pg_num_rows')) {
        function pg_num_rows($result) {
            return _demo_pg_num_rows($result);
        }
    }
    
    if (!function_exists('pg_fetch_assoc')) {
        function pg_fetch_assoc($result) {
            return _demo_pg_fetch_assoc($result);
        }
    }
    
    if (!function_exists('pg_last_oid')) {
        function pg_last_oid($result) {
            return 1;
        }
    }
} else {
    // Production: Attempt real database connection
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
}

// Expose the active PDO connection for compatibility with legacy pg_* usage and wrappers.
if (!empty($conn) && !isset($POSTGRES_CONN)) {
    $POSTGRES_CONN = $conn;
}

if (!function_exists('pgQueryPlaceholderize')) {
    function pgQueryPlaceholderize(string $query): string {
        return preg_replace_callback('/\$([0-9]+)/', function ($matches) {
            return '?';
        }, $query);
    }
}

if (!function_exists('pg_query_params')) {
    function pg_query_params($connection, $query, $params = []) {
        global $conn;
        $pdo = $connection instanceof PDO ? $connection : ($conn instanceof PDO ? $conn : null);
        if (!$pdo) {
            throw new Exception('Database connection unavailable');
        }

        $convertedQuery = pgQueryPlaceholderize($query);
        $stmt = $pdo->prepare($convertedQuery);
        $stmt->execute(array_values($params));

        return (object)[
            'stmt' => $stmt,
            'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'position' => 0
        ];
    }
}

if (!function_exists('pg_query')) {
    function pg_query($connection, $query) {
        return pg_query_params($connection, $query, []);
    }
}

if (!function_exists('pg_fetch_assoc')) {
    function pg_fetch_assoc($result) {
        if (!$result || !isset($result->rows) || $result->position >= count($result->rows)) {
            return false;
        }
        return $result->rows[$result->position++];
    }
}

if (!function_exists('pg_fetch_all')) {
    function pg_fetch_all($result) {
        if (!$result || !isset($result->rows)) {
            return [];
        }
        return $result->rows;
    }
}

if (!function_exists('pg_num_rows')) {
    function pg_num_rows($result) {
        if (!$result || !isset($result->rows)) {
            return 0;
        }
        return count($result->rows);
    }
}

if (!function_exists('pg_last_oid')) {
    function pg_last_oid($result) {
        global $conn;
        try {
            return $conn instanceof PDO ? $conn->lastInsertId() : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('pg_free_result')) {
    function pg_free_result($result) {
        if ($result && isset($result->stmt)) {
            $result->stmt->closeCursor();
        }
    }
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
        request_id INTEGER,
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
