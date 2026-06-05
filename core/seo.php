<?php
// Centralized SEO include: outputs title, meta tags, hreflang, OG, Twitter, and JSON-LD
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure $lang is available
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'example.com';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
$canonical = $protocol . '://' . $host . $requestPath . ($requestQuery ? '?' . $requestQuery : '');
$baseNoQuery = $protocol . '://' . $host . $requestPath;

// Page-specific fallbacks
$pageTitle = $pageTitle ?? $siteTitle ?? ($lang === 'ar' ? 'فليكس - خدمات المنزل' : 'Flix - Home Services Marketplace');
$pageDescription = $pageDescription ?? $siteDescription ?? ($lang === 'ar'
    ? 'فليكس يربط المستخدمين بالفنيين المحليين لصيانة وإصلاح المنزل بسرعة وسهولة.'
    : 'FLIX connects users with trusted local repair and maintenance professionals for homes.');
$pageKeywords = $pageKeywords ?? $siteKeywords ?? ($lang === 'ar'
    ? 'خدمات منزلية, صيانة المنزل, فنيين محليين, إصلاحات, منصة فليكس'
    : 'home services, home repair, handyman services, local professionals, home maintenance, FLIX marketplace');
$previewImage = $previewImage ?? ($protocol . '://' . $host . '/logoc.jpeg');

$alternateEn = $baseNoQuery;
$alternateAr = $baseNoQuery . (strpos($requestQuery ?? '', 'lang=ar') !== false ? '' : '?lang=ar');

// Output SEO tags
echo "    <meta name=\"description\" content=\"" . htmlspecialchars($pageDescription) . "\">\n";
echo "    <meta name=\"keywords\" content=\"" . htmlspecialchars($pageKeywords) . "\">\n";
echo "    <meta name=\"author\" content=\"FLIX\">\n";
echo "    <meta name=\"robots\" content=\"index,follow\">\n";
echo "    <meta name=\"googlebot\" content=\"index,follow\">\n";
echo "    <link rel=\"canonical\" href=\"" . htmlspecialchars($canonical) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"en\" href=\"" . htmlspecialchars($alternateEn) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"ar\" href=\"" . htmlspecialchars($alternateAr) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($alternateEn) . "\">\n";
echo "    <meta property=\"og:locale\" content=\"" . ($lang === 'ar' ? 'ar_AR' : 'en_US') . "\">\n";
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
echo "    <link rel=\"shortcut icon\" href=\"" . htmlspecialchars($protocol . '://' . $host . '/logoc.jpeg') . "\" type=\"image/jpeg\">\n";
echo "    <link rel=\"icon\" href=\"" . htmlspecialchars($protocol . '://' . $host . '/logoc.jpeg') . "\" type=\"image/jpeg\">\n";
echo "    <link rel=\"apple-touch-icon\" href=\"" . htmlspecialchars($protocol . '://' . $host . '/logoc.jpeg') . "\">\n";
echo "    <link rel=\"mask-icon\" href=\"" . htmlspecialchars($protocol . '://' . $host . '/logoc.jpeg') . "\" color=\"#1A6B4A\">\n";

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
