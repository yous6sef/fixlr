<?php
// ============================================================================
// ARABIC SITEMAP FOR BILINGUAL SEO
// ============================================================================
header('Content-Type: application/xml; charset=utf-8');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

$arPages = [
    ['url' => '/?lang=ar', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => '/pages/user/login.php?lang=ar', 'priority' => '0.9', 'changefreq' => 'weekly'],
    ['url' => '/pages/user/signup.php?lang=ar', 'priority' => '0.95', 'changefreq' => 'weekly'],
    ['url' => '/pages/user/user_dashboard.php?lang=ar', 'priority' => '0.85', 'changefreq' => 'daily'],
    ['url' => '/pages/user/order.php?lang=ar', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/pages/user/user_requests.php?lang=ar', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['url' => '/pages/user/track.php?lang=ar', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['url' => '/pages/worker/worker_dashboard.php?lang=ar', 'priority' => '0.85', 'changefreq' => 'daily'],
    ['url' => '/pages/worker/worker_orders.php?lang=ar', 'priority' => '0.8', 'changefreq' => 'daily'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '         xmlns:mobile="https://www.google.com/schemas/sitemap-mobile/1.0"' . "\n";
echo '         xmlns:xhtml="https://www.w3.org/1999/xhtml">' . "\n";

foreach ($arPages as $page) {
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
    echo "        <mobile:mobile/>\n";
    
    $enUrl = preg_replace('/\?lang=ar/', '', $fullUrl);
    $arUrl = $fullUrl;
    
    echo "        <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"" . htmlspecialchars($enUrl) . "\"/>\n";
    echo "        <xhtml:link rel=\"alternate\" hreflang=\"ar\" href=\"" . htmlspecialchars($arUrl) . "\"/>\n";
    echo "        <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($enUrl) . "\"/>\n";
    
    echo "    </url>\n";
}

echo "</urlset>\n";

?>