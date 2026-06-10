<?php
// Centralized SEO include: outputs title, meta tags, hreflang, OG, Twitter, and JSON-LD
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure $lang is available
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
}

// Include unique title generator
if (!function_exists('seoGenerateUniqueTitle')) {
    include_once(__DIR__ . '/seo-unique-titles.php');
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'example.com';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
$canonical = $protocol . '://' . $host . $requestPath . ($requestQuery ? '?' . $requestQuery : '');
$baseNoQuery = $protocol . '://' . $host . $requestPath;

// Page-specific fallbacks
$pageTitle = $pageTitle ?? $siteTitle ?? ($lang === 'ar' ? 'فليكس - سوق الخدمات المنزلية في مصر' : 'FLIX | Home Services Marketplace in Egypt');

// Use unique title generator if title is still generic or not explicitly set
if (empty($pageTitle) || $pageTitle === $siteTitle || (strpos($pageTitle, 'Dashboard') !== false && empty($forcedPageTitle))) {
    $requestPath = $_SERVER['REQUEST_URI'] ?? '/';
    $currentQuery = parse_url($requestPath, PHP_URL_QUERY);
    $queryParams = [];
    if (!empty($currentQuery)) {
        parse_str($currentQuery, $queryParams);
    }
    $pageTitle = seoGenerateUniqueTitle($lang, $GLOBALS['conn'] ?? null, $requestPath, $queryParams);
}
$pageDescription = $pageDescription ?? $siteDescription ?? ($lang === 'ar'
    ? 'فليكس هو السوق الرائد للخدمات المنزلية في مصر. احجز سباكين، كهربائيين، نجارين، عمال نظافة، وفنيين موثوقين لصيانة وإصلاح المنزل بسرعة وأمان.'
    : 'FLIX is Egypt’s leading home services marketplace. Book trusted plumbers, electricians, carpenters, cleaners, and repair professionals fast with secure service and support.');
$pageKeywords = $pageKeywords ?? $siteKeywords ?? ($lang === 'ar'
    ? 'فليكس, خدمات منزلية, صيانة المنزل, سباكين, كهربائيين, نجارين, عمال نظافة, إصلاحات المنزل, فنيين محليين, سوق الخدمات'
    : 'FLIX, home services, home repair, plumbers, electricians, carpenters, cleaners, handyman, maintenance, service marketplace, local technicians, Egypt home services');
$previewImage = $previewImage ?? ($protocol . '://' . $host . '/logoc.jpeg');

function seoBuildAlternateUrl($protocol, $host, $path, $currentQuery, $langCode) {
    $queryParams = [];
    if (!empty($currentQuery)) {
        parse_str($currentQuery, $queryParams);
    }
    $queryParams['lang'] = $langCode;
    $queryString = http_build_query($queryParams);
    return $protocol . '://' . $host . $path . ($queryString ? '?' . $queryString : '');
}

$alternateEn = seoBuildAlternateUrl($protocol, $host, $requestPath, $requestQuery, 'en');
$alternateAr = seoBuildAlternateUrl($protocol, $host, $requestPath, $requestQuery, 'ar');
$xDefaultUrl = $protocol . '://' . $host . '/';

// Ensure the page title is always SEO-friendly
if (empty($pageTitle)) {
    $pageTitle = ($lang === 'ar') ? 'فليكس - سوق الخدمات المنزلية في مصر' : 'FLIX | Trusted Home Services in Egypt';
}
if (mb_strlen($pageTitle) < 35) {
    $pageTitle .= ($lang === 'ar') ? ' | فليكس' : ' | FLIX';
}

// Output SEO tags
echo "    <meta name=\"description\" content=\"" . htmlspecialchars($pageDescription) . "\">\n";
echo "    <meta name=\"keywords\" content=\"" . htmlspecialchars($pageKeywords) . "\">\n";
echo "    <meta name=\"author\" content=\"FLIX\">\n";
echo "    <meta name=\"application-name\" content=\"FLIX\">\n";
echo "    <meta name=\"apple-mobile-web-app-title\" content=\"FLIX\">\n";
echo "    <meta name=\"robots\" content=\"index,follow\">\n";
echo "    <meta name=\"googlebot\" content=\"index,follow\">\n";
echo "    <link rel=\"canonical\" href=\"" . htmlspecialchars($canonical) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"en-US\" href=\"" . htmlspecialchars($alternateEn) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"ar-EG\" href=\"" . htmlspecialchars($alternateAr) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($xDefaultUrl) . "\">\n";
echo "    <meta property=\"og:locale\" content=\"" . ($lang === 'ar' ? 'ar_EG' : 'en_US') . "\">\n";
echo "    <meta property=\"og:locale:alternate\" content=\"" . ($lang === 'ar' ? 'en_US' : 'ar_EG') . "\">\n";
echo "    <meta property=\"og:title\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
echo "    <meta property=\"og:description\" content=\"" . htmlspecialchars($pageDescription) . "\">\n";
echo "    <meta property=\"og:image\" content=\"" . htmlspecialchars($previewImage) . "\">\n";
echo "    <meta property=\"og:image:type\" content=\"image/jpeg\">\n";
echo "    <meta property=\"og:image:width\" content=\"1200\">\n";
echo "    <meta property=\"og:image:height\" content=\"630\">\n";
echo "    <meta property=\"og:image:alt\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
echo "    <meta property=\"og:type\" content=\"website\">\n";
echo "    <meta property=\"og:url\" content=\"" . htmlspecialchars($canonical) . "\">\n";
echo "    <meta property=\"og:site_name\" content=\"FLIX\">\n";
echo "    <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
echo "    <meta name=\"twitter:title\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
echo "    <meta name=\"twitter:description\" content=\"" . htmlspecialchars($pageDescription) . "\">\n";
echo "    <meta name=\"twitter:image\" content=\"" . htmlspecialchars($previewImage) . "\">\n";
echo "    <meta name=\"twitter:image:alt\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
echo "    <meta name=\"theme-color\" content=\"#1A6B4A\">\n";
echo "    <link rel=\"icon\" type=\"image/png\" href=\"/favicon-96x96.png\" sizes=\"96x96\">\n";
echo "    <link rel=\"icon\" type=\"image/svg+xml\" href=\"/favicon.svg\">\n";
echo "    <link rel=\"shortcut icon\" href=\"/favicon.ico\">\n";
echo "    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/apple-touch-icon.png\">\n";
echo "    <link rel=\"manifest\" href=\"/site.webmanifest\">\n";

// JSON-LD
$org = [
    "@context" => "https://schema.org",
    "@type" => "Organization",
    "name" => "FLIX",
    "url" => $protocol . '://' . $host,
    "logo" => $previewImage,
    "description" => $pageDescription
];
$webpage = [
    "@context" => "https://schema.org",
    "@type" => "WebPage",
    "name" => $pageTitle,
    "description" => $pageDescription,
    "url" => $canonical,
    "inLanguage" => $lang
];

echo "    <script type=\"application/ld+json\">\n" . json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n";
echo "    <script type=\"application/ld+json\">\n" . json_encode($webpage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n";

// Output <title>
echo "    <title>" . htmlspecialchars($pageTitle) . "</title>\n";

?>
