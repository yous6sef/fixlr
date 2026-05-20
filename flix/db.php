<?php
// 1. Get the Neon URL from Railway's environment variables
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    die("❌ Connection failed: DATABASE_URL environment variable is missing.");
}

// 2. Break the URL down into pieces PHP can use
$dbopts = parse_url($databaseUrl);

$host = $dbopts["host"];
$port = $dbopts["port"] ?? 5432; // Default to 5432 if no port is specified
$user = $dbopts["user"];
$pass = $dbopts["pass"];
$dbname = ltrim($dbopts["path"], '/'); // Removes the starting slash from the DB name

// 3. Build the specific DSN string that PHP's PDO requires (Neon requires sslmode)
$dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

// 4. Connect
try {
    $conn = new PDO($dsn, $user, $pass);
    
    // Set PDO to throw exceptions on errors
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}
?>
