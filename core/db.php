<?php
$dsn = "pgsql:host=ep-long-pond-agq9ju7z-pooler.c-2.eu-central-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require;channel_binding=require";
$user = "neondb_owner";
$password = "npg_5iRT4jmxCebI";

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
