<?php
$dsn = "pgsql:host=ep-long-pond-agq9ju7z-pooler.c-2.eu-central-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require;channel_binding=require";
$user = "neondb_owner";
$password = "npg_5iRT4jmxCebI";

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    try {
        $colCheck = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_name = 'service_requests' AND column_name = 'request_id'");
        $colCheck->execute();
        if (!$colCheck->fetch()) {
            $conn->exec("ALTER TABLE service_requests ADD COLUMN request_id INTEGER");
        }
        // Ensure chat storage exists for assigned worker conversations
        $conn->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id SERIAL PRIMARY KEY,
            request_id INTEGER NOT NULL,
            sender_type TEXT NOT NULL,
            sender_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
        )");
    } catch (PDOException $innerException) {
        // Ignore if schema access is restricted or column already exists.
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
