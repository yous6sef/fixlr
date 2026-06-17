<?php
/**
 * FLIX Dynamic XML Sitemap Generator
 * Outputs all public landing pages and service paths with bilingual hreflang alternates.
 */
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$protocol = 'https';
$host = $_SERVER['HTTP_HOST'] ?? 'flix-eg.up.railway.app';
$baseUrl = rtrim($protocol . '://' . $host, '/');

function sitemapLastmod(): string
{
    return date('c');
}

function sitemapPriority(string $type): string
{
    $priorities = [
        'homepage' => '1.0',
        'auth' => '0.9',
        'signup' => '0.95',
        'service' => '0.85',
        'city' => '0.75',
    ];
    return $priorities[$type] ?? '0.6';
}

function sitemapBuildUrl(string $path, ?string $lang = null, array $extraParams = []): string
{
    global $baseUrl;

    $params = $extraParams;
    if ($lang === 'ar') {
        $params['lang'] = 'ar';
    }

    $url = $baseUrl . $path;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function sitemapEmitUrl(string $path, string $priority, string $changefreq, array $extraParams = []): void
{
    $enUrl = sitemapBuildUrl($path, 'en', $extraParams);
    $arUrl = sitemapBuildUrl($path, 'ar', $extraParams);

    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($enUrl, ENT_XML1, 'UTF-8') . "</loc>\n";
    echo '    <lastmod>' . sitemapLastmod() . "</lastmod>\n";
    echo '    <changefreq>' . htmlspecialchars($changefreq, ENT_XML1, 'UTF-8') . "</changefreq>\n";
    echo '    <priority>' . htmlspecialchars($priority, ENT_XML1, 'UTF-8') . "</priority>\n";
    echo '    <xhtml:link rel="alternate" hreflang="en" href="' . htmlspecialchars($enUrl, ENT_XML1, 'UTF-8') . "\"/>\n";
    echo '    <xhtml:link rel="alternate" hreflang="ar" href="' . htmlspecialchars($arUrl, ENT_XML1, 'UTF-8') . "\"/>\n";
    echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($enUrl, ENT_XML1, 'UTF-8') . "\"/>\n";
    echo "  </url>\n";
}

$publicPages = [
    ['path' => '/', 'type' => 'homepage', 'changefreq' => 'daily'],
    ['path' => '/pages/user/login.php', 'type' => 'auth', 'changefreq' => 'weekly'],
    ['path' => '/pages/user/signup.php', 'type' => 'signup', 'changefreq' => 'weekly', 'params' => ['type' => 'user']],
    ['path' => '/pages/user/signup.php', 'type' => 'signup', 'changefreq' => 'weekly', 'params' => ['type' => 'worker']],
];

$serviceSlugs = [
    ['slug' => 'plumbing', 'en' => 'Plumbing', 'ar' => 'سباكة'],
    ['slug' => 'electrical', 'en' => 'Electrical', 'ar' => 'كهرباء'],
    ['slug' => 'ac-service', 'en' => 'AC Service', 'ar' => 'تكييف'],
    ['slug' => 'cleaning', 'en' => 'Cleaning', 'ar' => 'تنظيف'],
    ['slug' => 'carpentry', 'en' => 'Carpentry', 'ar' => 'نجارة'],
    ['slug' => 'painting', 'en' => 'Painting', 'ar' => 'دهان'],
];

$citySlugs = [
    ['slug' => 'cairo', 'en' => 'Cairo', 'ar' => 'القاهرة'],
    ['slug' => 'giza', 'en' => 'Giza', 'ar' => 'الجيزة'],
    ['slug' => 'alexandria', 'en' => 'Alexandria', 'ar' => 'الإسكندرية'],
    ['slug' => 'mansoura', 'en' => 'Mansoura', 'ar' => 'المنصورة'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

foreach ($publicPages as $page) {
    sitemapEmitUrl(
        $page['path'],
        sitemapPriority($page['type']),
        $page['changefreq'],
        $page['params'] ?? []
    );
}

foreach ($serviceSlugs as $service) {
    sitemapEmitUrl(
        '/',
        sitemapPriority('service'),
        'weekly',
        ['service' => $service['slug']]
    );
}

foreach ($serviceSlugs as $service) {
    foreach ($citySlugs as $city) {
        sitemapEmitUrl(
            '/',
            sitemapPriority('city'),
            'weekly',
            ['service' => $service['slug'], 'city' => $city['slug']]
        );
    }
}

echo '</urlset>';
