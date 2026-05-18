<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $queries = [
        // Users table columns
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email TEXT",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS location TEXT",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS role TEXT DEFAULT 'user'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()",
        
        // Workers table
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS email TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS national_id TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS location TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS city TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS id_front_path TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS id_back_path TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS certificate_path TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS cv_path TEXT",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS approved TEXT DEFAULT 'pending'",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS status TEXT DEFAULT 'no'",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS total_earnings NUMERIC DEFAULT 0",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS paused TEXT DEFAULT 'no'",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS unpaid_streak_days INTEGER DEFAULT 0",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS last_payment_confirmation_at TIMESTAMP WITHOUT TIME ZONE",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS pending_commission NUMERIC DEFAULT 0",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS payment_due NUMERIC DEFAULT 0",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()",
        "ALTER TABLE workers ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()",

        // Service requests
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS status TEXT DEFAULT 'pending'",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS worker_price NUMERIC",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS worker_id UUID",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS refusers INTEGER DEFAULT 0",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS username TEXT",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP WITHOUT TIME ZONE",
        "ALTER TABLE service_requests ALTER COLUMN worker_price TYPE NUMERIC USING worker_price::numeric",
        "ALTER TABLE service_requests ALTER COLUMN final_price TYPE NUMERIC USING final_price::numeric",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS payment_status TEXT DEFAULT 'unpaid'",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS commission_amount NUMERIC DEFAULT 0",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS commission_percentage NUMERIC DEFAULT 0.2",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS platform_fee NUMERIC DEFAULT 0",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS payment_reference TEXT",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS payment_confirmed_at TIMESTAMP WITHOUT TIME ZONE",

        // Tasks table
        "CREATE TABLE IF NOT EXISTS tasks (
            id SERIAL PRIMARY KEY,
            worker_id UUID,
            us_id UUID,
            request_id INTEGER,
            start_time TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",

        // Admins table
        "CREATE TABLE IF NOT EXISTS admins (
            id SERIAL PRIMARY KEY,
            email TEXT UNIQUE,
            phone TEXT UNIQUE,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'admin',
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",

        // Reviews from users to workers
        "DROP TABLE IF EXISTS reviews_worker",
        "CREATE TABLE reviews_worker (
            id SERIAL PRIMARY KEY,
            user_id UUID,
            worker_id UUID,
            request_id INTEGER,
            rating INTEGER,
            comment TEXT,
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",

        // Reviews from workers to users
        "DROP TABLE IF EXISTS reviews_user",
        "CREATE TABLE reviews_user (
            id SERIAL PRIMARY KEY,
            worker_id UUID,
            user_id UUID,
            request_id INTEGER,
            rating INTEGER,
            comment TEXT,
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",

        // Payment history and admin confirmations
        "DROP TABLE IF EXISTS worker_payments",
        "CREATE TABLE worker_payments (
            id SERIAL PRIMARY KEY,
            worker_id UUID,
            request_id INTEGER,
            amount_paid NUMERIC,
            commission_amount NUMERIC,
            status TEXT DEFAULT 'pending',
            transaction_ref TEXT,
            admin_notes TEXT,
            confirmed_at TIMESTAMP WITHOUT TIME ZONE,
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",

        // Chat messages between users, workers, and admin
        "CREATE TABLE IF NOT EXISTS chat_messages (
            id SERIAL PRIMARY KEY,
            request_id INTEGER,
            sender_type TEXT,
            sender_id INTEGER,
            message TEXT,
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",

        // Negotiation tracking columns
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS negotiation_state TEXT DEFAULT 'none'",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS negotiation_started_at TIMESTAMP WITHOUT TIME ZONE",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS negotiation_ended_at TIMESTAMP WITHOUT TIME ZONE",

        // Prevent duplicate pending orders with same details
        "CREATE UNIQUE INDEX IF NOT EXISTS uniq_pending_service_request ON service_requests (us_id, specialization, city, address, description, budget) WHERE status = 'pending'",

        // Negotiation history
        "CREATE TABLE IF NOT EXISTS negotiation_history (
            id SERIAL PRIMARY KEY,
            request_id INTEGER,
            initiator_type TEXT,
            initiator_id INTEGER,
            initial_price NUMERIC,
            proposed_price NUMERIC,
            final_price NUMERIC,
            status TEXT DEFAULT 'ongoing',
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",

        // Real-time events
        "CREATE TABLE IF NOT EXISTS real_time_events (
            id SERIAL PRIMARY KEY,
            request_id INTEGER,
            event_type TEXT,
            event_data TEXT,
            triggered_by_type TEXT,
            triggered_by_id INTEGER,
            created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
        )",
    ];

    foreach ($queries as $query) {
        $conn->exec($query);
    }

    // Default admin user
    $check = $conn->prepare("SELECT COUNT(*) as count FROM admins");
    $check->execute();
    $result = $check->fetch(PDO::FETCH_ASSOC);

    if ($result && (int)$result['count'] === 0) {
        $passwordHash = password_hash(ADMIN_DEFAULT_PASSWORD, PASSWORD_DEFAULT);
        $insertAdmin = $conn->prepare("INSERT INTO admins (email, phone, password, role, created_at, updated_at) VALUES (?, ?, ?, 'admin', NOW(), NOW())");
        $insertAdmin->execute([ADMIN_DEFAULT_EMAIL, ADMIN_DEFAULT_PHONE, $passwordHash]);
    }

    echo "تم تحديث قاعدة البيانات بنجاح.\n";
    echo "Admin default login: " . ADMIN_DEFAULT_EMAIL . " / " . ADMIN_DEFAULT_PASSWORD . "\n";
} catch (PDOException $ex) {
    echo "خطأ في ترحيل قاعدة البيانات: " . $ex->getMessage();
}
