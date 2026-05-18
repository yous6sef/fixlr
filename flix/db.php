<?php
$host = "ep-long-pond-agq9ju7z-pooler.c-2.eu-central-1.aws.neon.tech";
$port = "5432";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_5iRT4jmxCebI";

// Only the endpoint ID (first part of hostname) goes in options
$endpoint_id = "ep-long-pond-agq9ju7z";

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options=endpoint=$endpoint_id";

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>