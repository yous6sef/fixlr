<?php
/**
 * Database Schema Setup for Service Platform
 * Creates all necessary tables for the home services marketplace
 */

session_start();
include('db.php');

try {
    // Drop existing tables first (if any)
    $conn->exec("DROP TABLE IF EXISTS request_status_history CASCADE");
    $conn->exec("DROP TABLE IF EXISTS ratings CASCADE");
    $conn->exec("DROP TABLE IF EXISTS worker_daily_revenue CASCADE");
    $conn->exec("DROP TABLE IF EXISTS payments CASCADE");
    $conn->exec("DROP TABLE IF EXISTS service_requests CASCADE");
    $conn->exec("DROP TABLE IF EXISTS devices CASCADE");
    $conn->exec("DROP TABLE IF EXISTS service_types CASCADE");
    $conn->exec("DROP TABLE IF EXISTS cities CASCADE");
    $conn->exec("DROP TABLE IF EXISTS users CASCADE");
    
    // =============== USERS TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            address TEXT,
            google_maps_link TEXT,
            profile_picture VARCHAR(255),
            total_rating DECIMAL(3,2) DEFAULT 0,
            total_reviews INT DEFAULT 0,
            total_tasks INT DEFAULT 0,
            user_type VARCHAR(20) DEFAULT 'user',
            account_status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // =============== WORKERS TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS workers (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            specialization VARCHAR(100),
            national_id VARCHAR(100),
            id_front_path VARCHAR(255),
            id_back_path VARCHAR(255),
            certificate_path VARCHAR(255),
            cv_path VARCHAR(255),
            city VARCHAR(100),
            location TEXT,
            approved VARCHAR(20) DEFAULT 'pending',
            status VARCHAR(20) DEFAULT 'inactive',
            paused VARCHAR(5) DEFAULT 'no',
            unpaid_streak_days INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // =============== SERVICE TYPES TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS service_types (
            id SERIAL PRIMARY KEY,
            name_ar VARCHAR(255) NOT NULL,
            name_en VARCHAR(255) NOT NULL,
            icon VARCHAR(50),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (name_ar),
            UNIQUE (name_en)
        )
    ");

    // =============== DEVICES TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS devices (
            id SERIAL PRIMARY KEY,
            service_type_id INT NOT NULL,
            name_ar VARCHAR(255) NOT NULL,
            name_en VARCHAR(255) NOT NULL,
            icon VARCHAR(50),
            FOREIGN KEY (service_type_id) REFERENCES service_types(id),
            UNIQUE (service_type_id, name_ar),
            UNIQUE (service_type_id, name_en)
        )
    ");

    // =============== CITIES TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS cities (
            id SERIAL PRIMARY KEY,
            name_ar VARCHAR(100) NOT NULL,
            name_en VARCHAR(100) NOT NULL,
            coordinates VARCHAR(50),
            UNIQUE (name_ar),
            UNIQUE (name_en)
        )
    ");

    // =============== SERVICE REQUESTS TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS service_requests (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL,
            worker_id INT,
            service_type_id INT NOT NULL,
            device_id INT,
            city_id INT NOT NULL,
            google_maps_link TEXT,
            problem_description TEXT NOT NULL,
            
            -- Pricing
            checking_fee DECIMAL(10,2) DEFAULT 300,
            fixing_price DECIMAL(10,2),
            total_price DECIMAL(10,2),
            
            -- Status tracking
            status VARCHAR(50) DEFAULT 'pending',
            
            -- Timestamps for each stage
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            accepted_at TIMESTAMP,
            worker_arrival_timestamp TIMESTAMP,
            user_arrival_timestamp TIMESTAMP,
            checking_completed_timestamp TIMESTAMP,
            fixing_started_timestamp TIMESTAMP,
            fixing_completed_timestamp TIMESTAMP,
            completed_at TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (worker_id) REFERENCES users(id),
            FOREIGN KEY (service_type_id) REFERENCES service_types(id),
            FOREIGN KEY (device_id) REFERENCES devices(id),
            FOREIGN KEY (city_id) REFERENCES cities(id)
        )
    ");

    // =============== PAYMENT RECORDS TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id SERIAL PRIMARY KEY,
            service_request_id INT NOT NULL,
            worker_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_type VARCHAR(50) DEFAULT 'checking_fee',
            payment_status VARCHAR(50) DEFAULT 'pending',
            payment_method VARCHAR(50),
            transaction_id VARCHAR(255),
            receipt_image VARCHAR(255),
            paid_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (worker_id) REFERENCES users(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // =============== WORKER DAILY REVENUE TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS worker_daily_revenue (
            id SERIAL PRIMARY KEY,
            worker_id INT NOT NULL,
            date_of_revenue DATE NOT NULL,
            total_revenue DECIMAL(10,2) DEFAULT 0,
            commission_percentage DECIMAL(5,2) DEFAULT 20,
            commission_amount DECIMAL(10,2) DEFAULT 0,
            remaining_amount DECIMAL(10,2) DEFAULT 0,
            payment_status VARCHAR(50) DEFAULT 'pending',
            instapay_transaction_id VARCHAR(255),
            receipt_image VARCHAR(255),
            submitted_at TIMESTAMP,
            confirmed_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (worker_id) REFERENCES users(id),
            UNIQUE (worker_id, date_of_revenue)
        )
    ");

    // =============== RATINGS TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS ratings (
            id SERIAL PRIMARY KEY,
            service_request_id INT NOT NULL,
            rater_id INT NOT NULL,
            rated_user_id INT NOT NULL,
            rating INT NOT NULL,
            review_text TEXT,
            rater_type VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (rater_id) REFERENCES users(id),
            FOREIGN KEY (rated_user_id) REFERENCES users(id),
            UNIQUE (service_request_id, rater_id)
        )
    ");

    // =============== REQUEST STATUS HISTORY TABLE ===============
    $conn->exec("
        CREATE TABLE IF NOT EXISTS request_status_history (
            id SERIAL PRIMARY KEY,
            service_request_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50) NOT NULL,
            changed_by INT,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (changed_by) REFERENCES users(id)
        )
    ");

    // =============== INSERT INITIAL DATA ===============
    
    // Insert Service Types
    $conn->exec("
        INSERT INTO service_types (name_ar, name_en, icon) VALUES
        ('السباكة', 'Plumbing', '🔧'),
        ('الكهرباء', 'Electricity', '⚡'),
        ('الأجهزة المنزلية', 'Home Appliances', '🏠'),
        ('تكييف الهواء', 'Air Conditioning', '❄️'),
        ('الدهان', 'Painting', '🎨'),
        ('التنظيف', 'Cleaning', '🧼'),
        ('النجارة', 'Carpentry', '🪚'),
        ('أمن الأقفال', 'Locksmith', '🔐'),
        ('العزل المائي', 'Waterproofing', '💧'),
        ('الإنترنت والشبكات', 'Network & IT', '🌐')
        ON CONFLICT DO NOTHING
    ");

    // Insert Devices for AC
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'مكيف نافذة', 'Window AC', '❄️' FROM service_types WHERE name_en = 'Air Conditioning'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'مكيف سبليت', 'Split AC', '❄️' FROM service_types WHERE name_en = 'Air Conditioning'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'تكييف مركزي', 'Central AC', '❄️' FROM service_types WHERE name_en = 'Air Conditioning'
        ON CONFLICT DO NOTHING
    ");

    // Insert Devices for Home Appliances
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'ثلاجة', 'Refrigerator', '🧊' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'فرن', 'Oven', '🔥' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'سخان المياه', 'Water Heater', '🚿' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'غسالة صحون', 'Dishwasher', '🍽️' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'مجفف ملابس', 'Dryer', '🧺' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'مكنسة كهربائية', 'Vacuum Cleaner', '🧹' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'تلفزيون', 'Television', '📺' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'موقد', 'Stove', '🍳' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");
    $conn->exec("
        INSERT INTO devices (service_type_id, name_ar, name_en, icon) 
        SELECT id, 'خلاط', 'Blender', '🥤' FROM service_types WHERE name_en = 'Home Appliances'
        ON CONFLICT DO NOTHING
    ");

    // Insert Cities
    $conn->exec("
        INSERT INTO cities (name_ar, name_en) VALUES
        ('اكتوبر السادس', '6th of October'),
        ('الشيخ زايد', 'Sheikh Zayed')
        ON CONFLICT DO NOTHING
    ");

    // Insert Test Users
    $testUserPassword = password_hash('123456', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, phone, password_hash, city, address, user_type, account_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT DO NOTHING
    ");
    $stmt->execute(['عميل تجربة', 'user@test.com', '01001234567', $testUserPassword, '6th of October', 'Test Address', 'user', 'active']);
    $stmt->execute(['فني تجربة', 'worker@test.com', '01111234567', $testUserPassword, '6th of October', 'Worker Address', 'worker', 'active']);

    echo "✅ Database schema created successfully!";

} catch (PDOException $e) {
    echo "❌ Error creating schema: " . $e->getMessage();
}
?>
