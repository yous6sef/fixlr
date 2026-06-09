<?php
/**
 * ====================================================================
 * FLIX Dynamic XML Sitemap Generator
 * ====================================================================
 * Generates a dynamic XML sitemap from your database containing:
 * - Homepage
 * - All service categories
 * - All service+city combinations
 * - High-priority craftsman profiles
 * 
 * USAGE:
 * 1. Access via: https://flix-eg.up.railway.app/sitemap.php
 * 2. Cron job (optional): Run daily to regenerate
 * 3. Reference in header: <link rel="sitemap" href="/sitemap.php" />
 * 
 * DATABASE REQUIREMENTS:
 * - services table: id, name_en, name_ar, priority
 * - cities table: id, name_en, name_ar
 * - workers/craftsmen table: id, rating, review_count, created_at (for priority)
 * 
 * CONFIG: Update database connection details in the CONFIGURATION section
 * ====================================================================
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400'); // Cache for 24 hours

// ========== CONFIGURATION ==========
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$siteUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

// Database Configuration - UPDATE THESE WITH YOUR CONNECTION DETAILS
$dbHost = getenv('DB_HOST') ?? 'localhost';
$dbName = getenv('DB_NAME') ?? 'flix_db';
$dbUser = getenv('DB_USER') ?? 'root';
$dbPass = getenv('DB_PASS') ?? '';
$dbPort = getenv('DB_PORT') ?? 3306;

// ========== DATABASE CONNECTION ==========
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // If database fails, output minimal sitemap
    $pdo = null;
    error_log('Sitemap Database Error: ' . $e->getMessage());
}

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '         xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n";
echo '         xmlns:mobile="http://www.mobile.googlebot.com/schema/mobile/1.0">' . "\n";

// ========== PRIORITY CALCULATION ==========
function getPriority($type, $rating = 0, $reviewCount = 0) {
    $basePriority = [
        'homepage' => 1.0,
        'service' => 0.8,
        'service-city' => 0.7,
        'craftsman-featured' => 0.9,
        'craftsman-regular' => 0.5
    ];
    
    $priority = $basePriority[$type] ?? 0.5;
    
    // Boost priority for high-rated craftsmen
    if (strpos($type, 'craftsman') !== false && $rating >= 4.5) {
        $priority = min($priority + 0.15, 1.0);
    }
    
    return number_format($priority, 1);
}

function getCurrentDate() {
    return date('Y-m-d\TH:i:sP', time());
}

// ========== 1. HOMEPAGE ==========
echo '  <url>' . "\n";
echo '    <loc>' . htmlspecialchars($siteUrl . '/') . '</loc>' . "\n";
echo '    <lastmod>' . getCurrentDate() . '</lastmod>' . "\n";
echo '    <changefreq>weekly</changefreq>' . "\n";
echo '    <priority>' . getPriority('homepage') . '</priority>' . "\n";
echo '    <mobile:mobile/>' . "\n";
echo '  </url>' . "\n";

// ========== 2. SERVICE CATEGORIES ==========
if ($pdo) {
    try {
        $servicesQuery = $pdo->query("
            SELECT id, name_en, slug_en, slug_ar, category_order
            FROM services
            WHERE is_active = 1
            ORDER BY category_order ASC
        ");
        
        $services = $servicesQuery->fetchAll();
        
        foreach ($services as $service) {
            // English version
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($siteUrl . '/services/' . $service['id'] . '?lang=en') . '</loc>' . "\n";
            echo '    <lastmod>' . getCurrentDate() . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>' . getPriority('service') . '</priority>' . "\n";
            echo '    <mobile:mobile/>' . "\n";
            echo '  </url>' . "\n";
            
            // Arabic version
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($siteUrl . '/services/' . $service['id'] . '?lang=ar') . '</loc>' . "\n";
            echo '    <lastmod>' . getCurrentDate() . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>' . getPriority('service') . '</priority>' . "\n";
            echo '    <mobile:mobile/>' . "\n";
            echo '  </url>' . "\n";
        }
    } catch (Exception $e) {
        error_log('Services Query Error: ' . $e->getMessage());
    }
}

// ========== 3. SERVICE + CITY COMBINATIONS ==========
if ($pdo) {
    try {
        $combinationsQuery = $pdo->query("
            SELECT DISTINCT s.id as service_id, c.id as city_id, c.name_en
            FROM services s
            CROSS JOIN cities c
            WHERE s.is_active = 1 AND c.is_active = 1
            LIMIT 500
        ");
        
        $combinations = $combinationsQuery->fetchAll();
        
        foreach ($combinations as $combo) {
            // English version
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($siteUrl . '/services/' . $combo['service_id'] . '?city=' . $combo['city_id'] . '&lang=en') . '</loc>' . "\n";
            echo '    <lastmod>' . getCurrentDate() . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>' . getPriority('service-city') . '</priority>' . "\n";
            echo '    <mobile:mobile/>' . "\n";
            echo '  </url>' . "\n";
            
            // Arabic version
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($siteUrl . '/services/' . $combo['service_id'] . '?city=' . $combo['city_id'] . '&lang=ar') . '</loc>' . "\n";
            echo '    <lastmod>' . getCurrentDate() . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>' . getPriority('service-city') . '</priority>' . "\n";
            echo '    <mobile:mobile/>' . "\n";
            echo '  </url>' . "\n";
        }
    } catch (Exception $e) {
        error_log('Service-City Combinations Query Error: ' . $e->getMessage());
    }
}

// ========== 4. TOP CRAFTSMEN PROFILES (High-Rated) ==========
if ($pdo) {
    try {
        $craftsmenQuery = $pdo->query("
            SELECT id, rating, review_count, created_at
            FROM workers
            WHERE is_active = 1 AND is_verified = 1
            ORDER BY rating DESC, review_count DESC
            LIMIT 100
        ");
        
        $craftsmen = $craftsmenQuery->fetchAll();
        
        foreach ($craftsmen as $craftsman) {
            $isFeatured = ($craftsman['rating'] >= 4.7 && $craftsman['review_count'] >= 20);
            $type = $isFeatured ? 'craftsman-featured' : 'craftsman-regular';
            
            // English version
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($siteUrl . '/craftsman/' . $craftsman['id'] . '?lang=en') . '</loc>' . "\n";
            echo '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($craftsman['created_at'])) . '</lastmod>' . "\n";
            echo '    <changefreq>monthly</changefreq>' . "\n";
            echo '    <priority>' . getPriority($type, $craftsman['rating'], $craftsman['review_count']) . '</priority>' . "\n";
            echo '    <mobile:mobile/>' . "\n";
            echo '  </url>' . "\n";
            
            // Arabic version
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($siteUrl . '/craftsman/' . $craftsman['id'] . '?lang=ar') . '</loc>' . "\n";
            echo '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($craftsman['created_at'])) . '</lastmod>' . "\n";
            echo '    <changefreq>monthly</changefreq>' . "\n";
            echo '    <priority>' . getPriority($type, $craftsman['rating'], $craftsman['review_count']) . '</priority>' . "\n";
            echo '    <mobile:mobile/>' . "\n";
            echo '  </url>' . "\n";
        }
    } catch (Exception $e) {
        error_log('Craftsmen Query Error: ' . $e->getMessage());
    }
}

// ========== 5. STATIC PAGES ==========
$staticPages = [
    ['path' => '/about', 'freq' => 'yearly', 'priority' => 0.6],
    ['path' => '/contact', 'freq' => 'yearly', 'priority' => 0.6],
    ['path' => '/terms', 'freq' => 'yearly', 'priority' => 0.5],
    ['path' => '/privacy', 'freq' => 'yearly', 'priority' => 0.5],
    ['path' => '/faq', 'freq' => 'monthly', 'priority' => 0.6],
];

foreach ($staticPages as $page) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($siteUrl . $page['path']) . '</loc>' . "\n";
    echo '    <lastmod>' . getCurrentDate() . '</lastmod>' . "\n";
    echo '    <changefreq>' . $page['freq'] . '</changefreq>' . "\n";
    echo '    <priority>' . $page['priority'] . '</priority>' . "\n";
    echo '    <mobile:mobile/>' . "\n";
    echo '  </url>' . "\n";
}

// Close XML
echo '</urlset>';

// Close database connection
if ($pdo) {
    $pdo = null;
}
?>
