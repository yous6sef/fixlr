<?php
// ============================================================================
// ENHANCED SITEMAP WITH PRIORITY SCORING & LANGUAGE ALTERNATES
// ============================================================================
header('Content-Type: application/xml; charset=utf-8');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

// Priority based on importance: Home > Sign Up/Login > Dashboard > Tracking > Profile
$pages = [
    ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    
    // Authentication Pages
    ['url' => '/pages/user/login.php', 'priority' => '0.9', 'changefreq' => 'weekly'],
    ['url' => '/pages/user/signup.php', 'priority' => '0.95', 'changefreq' => 'weekly'],
    
    // User Dashboard & Core Features
    ['url' => '/pages/user/user_dashboard.php', 'priority' => '0.85', 'changefreq' => 'daily'],
    ['url' => '/pages/user/order.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/pages/user/user_requests.php', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['url' => '/pages/user/track.php', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['url' => '/pages/user/request_detail.php', 'priority' => '0.75', 'changefreq' => 'weekly'],
    
    // User Account Pages
    ['url' => '/pages/user/profile.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => '/pages/user/payments.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => '/pages/user/receipt.php', 'priority' => '0.65', 'changefreq' => 'monthly'],
    
    // Worker Pages
    ['url' => '/pages/worker/worker_dashboard.php', 'priority' => '0.85', 'changefreq' => 'daily'],
    ['url' => '/pages/worker/worker_orders.php', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['url' => '/pages/worker/worker_profile.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => '/pages/worker/worker_payments.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => '/pages/worker/worker_receipt.php', 'priority' => '0.65', 'changefreq' => 'monthly'],
    ['url' => '/pages/worker/worker_track.php', 'priority' => '0.75', 'changefreq' => 'daily'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '         xmlns:mobile="https://www.google.com/schemas/sitemap-mobile/1.0"' . "\n";
echo '         xmlns:xhtml="https://www.w3.org/1999/xhtml">' . "\n";

foreach ($pages as $page) {
    $url = $page['url'];
    $priority = $page['priority'];
    $changefreq = $page['changefreq'];
    $fullUrl = rtrim($baseUrl, '/') . $url;
    $lastmod = date('c');
    
    echo "    <url>\n";
    echo "        <loc>" . htmlspecialchars($fullUrl) . "</loc>\n";
    echo "        <lastmod>" . $lastmod . "</lastmod>\n";
    echo "        <changefreq>" . htmlspecialchars($changefreq) . "</changefreq>\n";
    echo "        <priority>" . htmlspecialchars($priority) . "</priority>\n";
    
    // Mobile tag for responsive design
    echo "        <mobile:mobile/>\n";
    
    // Language alternates for bilingual support
    $enUrl = $fullUrl . (strpos($url, '?') !== false ? '&lang=en' : '?lang=en');
    $arUrl = $fullUrl . (strpos($url, '?') !== false ? '&lang=ar' : '?lang=ar');
    
    echo "        <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"" . htmlspecialchars($enUrl) . "\"/>\n";
    echo "        <xhtml:link rel=\"alternate\" hreflang=\"ar\" href=\"" . htmlspecialchars($arUrl) . "\"/>\n";
    echo "        <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($enUrl) . "\"/>\n";
    
    echo "    </url>\n";
}

echo "</urlset>\n";

?>
