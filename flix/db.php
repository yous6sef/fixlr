<?php
// Railway-compatible database connection
// Reads DATABASE_URL from Railway environment or falls back to .env values

$database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

if ($database_url) {
    // Parse DATABASE_URL (Railway format: postgresql://user:password@host:port/dbname)
    $url = parse_url($database_url);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 5432;
    $dbname = ltrim($url['path'] ?? '/', '/');
    $user = $url['user'] ?? 'postgres';
    $password = $url['pass'] ?? '';
    $ssl = true; // Railway uses SSL
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
} else {
    // Fallback to individual environment variables (for local development)
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '5432';
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'flix';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'postgres';
    $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=disable";
}

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Connection successful
    // Uncomment for debugging:
    // echo "✅ Database connection successful!";
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "❌ Connection failed: " . $e->getMessage();
    exit;
}
?>
