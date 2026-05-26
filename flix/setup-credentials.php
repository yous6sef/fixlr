<?php
// Setup Test Credentials in Database
include 'db.php';

echo "Setting up test credentials...\n\n";

try {
    // Test User Account
    $user_email = 'user@test.com';
    $user_password = 'User@123456';
    $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);
    
    // Check if user exists
    $check = pg_query_params($db, "SELECT id FROM users WHERE email = $1", [$user_email]);
    if (pg_num_rows($check) == 0) {
        $insert = pg_query_params($db, 
            "INSERT INTO users (name, email, phone, password_hash, city, user_type, account_status, total_rating, total_reviews) 
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)",
            ['Test User', $user_email, '+201001234567', $user_password_hash, 'Cairo', 'user', 'active', 0, 0]
        );
        echo "✓ User Account Created\n";
    } else {
        echo "✓ User Account Already Exists\n";
    }
    
    // Test Worker Account
    $worker_email = 'worker@test.com';
    $worker_password = 'Worker@123456';
    $worker_password_hash = password_hash($worker_password, PASSWORD_DEFAULT);
    
    $check = pg_query_params($db, "SELECT id FROM workers WHERE email = $1", [$worker_email]);
    if (pg_num_rows($check) == 0) {
        $insert = pg_query_params($db, 
            "INSERT INTO workers (name, email, phone, password, city, specialization, approved, status, total_rating, total_reviews) 
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)",
            ['Test Worker', $worker_email, '+201009876543', $worker_password_hash, 'Cairo', 'Plumbing', 'yes', 'active', 0, 0]
        );
        echo "✓ Worker Account Created\n";
    } else {
        echo "✓ Worker Account Already Exists\n";
    }
    
    // Test Admin Account
    $admin_email = 'admin@test.com';
    $admin_password = 'Admin@123456';
    $admin_password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $check = pg_query_params($db, "SELECT id FROM users WHERE email = $1 AND user_type = $2", [$admin_email, 'admin']);
    if (pg_num_rows($check) == 0) {
        $insert = pg_query_params($db, 
            "INSERT INTO users (name, email, phone, password_hash, city, user_type, account_status, total_rating, total_reviews) 
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)",
            ['Admin User', $admin_email, '+201005555555', $admin_password_hash, 'Cairo', 'admin', 'active', 0, 0]
        );
        echo "✓ Admin Account Created\n";
    } else {
        echo "✓ Admin Account Already Exists\n";
    }
    
    echo "\n==========================================\n";
    echo "TEST LOGIN CREDENTIALS\n";
    echo "==========================================\n\n";
    
    echo "USER (Service Requester)\n";
    echo "------------------------\n";
    echo "Email:    user@test.com\n";
    echo "Password: User@123456\n";
    echo "Type:     I need service\n\n";
    
    echo "WORKER (Service Provider)\n";
    echo "-------------------------\n";
    echo "Email:    worker@test.com\n";
    echo "Password: Worker@123456\n";
    echo "Type:     I provide service\n\n";
    
    echo "ADMIN\n";
    echo "-----\n";
    echo "Email:    admin@test.com\n";
    echo "Password: Admin@123456\n";
    echo "Type:     Admin\n\n";
    
    echo "==========================================\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
