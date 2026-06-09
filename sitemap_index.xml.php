<?php
// ============================================================================
// SITEMAP INDEX - REFERENCES ALL SITEMAPS
// ============================================================================
header('Content-Type: application/xml; charset=utf-8');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

$sitemaps = [
    ['url' => '/sitemap.xml', 'lastmod' => date('c')],
    ['url' => '/sitemap-ar.xml', 'lastmod' => date('c')],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($sitemaps as $sitemap) {
    $fullUrl = rtrim($baseUrl, '/') . $sitemap['url'];
    echo "    <sitemap>\n";
    echo "        <loc>" . htmlspecialchars($fullUrl) . "</loc>\n";
    echo "        <lastmod>" . htmlspecialchars($sitemap['lastmod']) . "</lastmod>\n";
    echo "    </sitemap>\n";
}

echo "</sitemapindex>\n";

?>