<?php
// Centralized SEO include: outputs title, meta tags, hreflang, OG, Twitter, and JSON-LD
if (empty($seoSkipSession) && session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header_remove('X-Powered-By');
    if (!empty($seoPublicCache)) {
        header('Cache-Control: public, max-age=3600, s-maxage=86400');
        header('Vary: Accept-Language');
    }
}

// Initialize SEO variables (prevent undefined variable warnings)
if (!isset($siteTitle)) $siteTitle = '';
if (!isset($siteDescription)) $siteDescription = '';
if (!isset($siteKeywords)) $siteKeywords = '';
if (!isset($pageTitle)) $pageTitle = '';
if (!isset($pageDescription)) $pageDescription = '';
if (!isset($pageKeywords)) $pageKeywords = '';
if (!isset($previewImage)) $previewImage = '';
if (!isset($forcedPageTitle)) $forcedPageTitle = '';

// Ensure $lang is available
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
}

// Include unique title generator
if (!function_exists('seoGenerateUniqueTitle')) {
    include_once(__DIR__ . '/seo-unique-titles.php');
}

// Fix: Check X-Forwarded-Proto first (Railway sets this for HTTPS)
$protocol = isset($_SERVER['X-Forwarded-Proto']) 
    ? strtolower($_SERVER['X-Forwarded-Proto'])
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
// FORCE HTTPS for production (Railway uses HTTPS)
$protocol = 'https';
$host = $_SERVER['HTTP_HOST'] ?? 'example.com';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);

// Parse query params to handle lang parameter correctly
$queryParams = [];
if (!empty($requestQuery)) {
    parse_str($requestQuery, $queryParams);
}

// Build URLs: canonical URL respects current language context and removes tracking parameters
// If lang=ar is in URL, canonical should include it
// If lang=en or no lang param, canonical is without lang (English default)

// Parse all query parameters
$allQueryParams = [];
if (!empty($requestQuery)) {
    parse_str($requestQuery, $allQueryParams);
}

// Remove tracking and thin-content parameters that should not appear in canonical URLs
$trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'msclkid', '__s', 'service', 'city'];
foreach ($trackingParams as $param) {
    unset($allQueryParams[$param]);
}

// Get language status
$hasLangInQuery = isset($allQueryParams['lang']);
$requestedLang = $allQueryParams['lang'] ?? 'en';

// Remove lang parameter for canonical base calculation
$baseQueryParams = $allQueryParams;
unset($baseQueryParams['lang']);
$baseQueryString = http_build_query($baseQueryParams);

// Build canonical URL
if ($hasLangInQuery && $requestedLang === 'ar') {
    // Serving Arabic: canonical includes ?lang=ar
    $canonical = $protocol . '://' . $host . $requestPath . 
        ($baseQueryString ? '?' . $baseQueryString . '&lang=ar' : '?lang=ar');
} else {
    // Serving English (default): canonical is without lang param (base query only)
    $canonical = $protocol . '://' . $host . $requestPath . 
        ($baseQueryString ? '?' . $baseQueryString : '');
}

// Build hreflang alternate URLs (symmetric - each points to the other)
$alternateEnUrl = $protocol . '://' . $host . $requestPath . 
    ($baseQueryString ? '?' . $baseQueryString : ''); // English without lang param
$alternateArUrl = $protocol . '://' . $host . $requestPath . 
    ($baseQueryString ? '?' . $baseQueryString . '&lang=ar' : '?lang=ar'); // Arabic with lang=ar
$xDefaultUrl = $alternateEnUrl; // Default to English version

// Bilingual SEO fallbacks with Egypt home-services keywords (AR + EN)
$defaultTitleAr = 'فليكس | منصة صيانة منزلية - سباك وكهربائي في مصر';
$defaultTitleEn = 'FLIX | Home Maintenance Services Egypt - Plumber & Electrician';
$defaultDescAr = 'منصة صيانة منزلية موثوقة في مصر. احجز سباك، كهربائي، نجار، عامل تنظيف وصيانة منزلية بسرعة وأمان. Home maintenance services Egypt على فليكس.';
$defaultDescEn = 'Home maintenance services Egypt — book trusted plumber, electrician, carpenter & cleaner fast. منصة صيانة منزلية | سباك | كهربائي | فليكس.';
$defaultKeywordsAr = 'فليكس, منصة صيانة منزلية, خدمات منزلية, سباك, كهربائي, نجار, عامل تنظيف, صيانة المنزل, إصلاحات, مصر, القاهرة, فنيين محليين';
$defaultKeywordsEn = 'FLIX, home maintenance services Egypt, home services, plumber, electrician, carpenter, cleaner, handyman, maintenance, service marketplace, Egypt, Cairo';

// Page-specific fallbacks
$pageTitle = $pageTitle ?? $siteTitle ?? ($lang === 'ar' ? $defaultTitleAr : $defaultTitleEn);

// Respect explicitly set page titles; only auto-generate when needed
if (empty($forcedPageTitle) && (empty($pageTitle) || strpos($pageTitle, 'Dashboard') !== false)) {
    $requestPath = $_SERVER['REQUEST_URI'] ?? '/';
    $currentQuery = parse_url($requestPath, PHP_URL_QUERY);
    $queryParams = [];
    if (!empty($currentQuery)) {
        parse_str($currentQuery, $queryParams);
    }
    $pageTitle = seoGenerateUniqueTitle($lang, $GLOBALS['conn'] ?? null, $requestPath, $queryParams);
}
$pageDescription = $pageDescription ?? $siteDescription ?? ($lang === 'ar' ? $defaultDescAr : $defaultDescEn);
$pageKeywords = $pageKeywords ?? $siteKeywords ?? ($lang === 'ar' ? $defaultKeywordsAr : $defaultKeywordsEn);
$previewImage = $previewImage ?? ($protocol . '://' . $host . '/logoc.jpeg');

// Ensure the page title is always SEO-friendly
if (empty($pageTitle)) {
    $pageTitle = ($lang === 'ar') ? 'فليكس - سوق الخدمات المنزلية في مصر' : 'FLIX | Trusted Home Services in Egypt';
}
if (strlen($pageTitle) < 35) {
    $pageTitle .= ($lang === 'ar') ? ' | فليكس' : ' | FLIX';
}

// Output SEO tags
echo "    <meta name=\"description\" content=\"" . htmlspecialchars($pageDescription) . "\">\n";
echo "    <meta name=\"keywords\" content=\"" . htmlspecialchars($pageKeywords) . "\">\n";
echo "    <meta name=\"author\" content=\"FLIX\">\n";
echo "    <meta name=\"application-name\" content=\"FLIX\">\n";
echo "    <meta name=\"apple-mobile-web-app-title\" content=\"FLIX\">\n";
echo "    <meta name=\"robots\" content=\"index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1\">\n";
echo "    <meta name=\"googlebot\" content=\"index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1\">\n";
echo "    <meta name=\"geo.region\" content=\"EG\">\n";
echo "    <meta name=\"geo.placename\" content=\"Egypt\">\n";
echo "    <meta name=\"content-language\" content=\"" . ($lang === 'ar' ? 'ar-EG' : 'en-EG') . "\">\n";
echo "    <link rel=\"canonical\" href=\"" . htmlspecialchars($canonical) . "\">\n";
echo "    <!-- Hreflang tags for bilingual support (symmetric links) -->\n";
echo "    <link rel=\"alternate\" hreflang=\"en\" href=\"" . htmlspecialchars($alternateEnUrl) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"en-EG\" href=\"" . htmlspecialchars($alternateEnUrl) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"ar\" href=\"" . htmlspecialchars($alternateArUrl) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"ar-EG\" href=\"" . htmlspecialchars($alternateArUrl) . "\">\n";
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
    "alternateName" => ["فليكس", "FLIX Egypt"],
    "url" => $protocol . '://' . $host,
    "logo" => $previewImage,
    "description" => $pageDescription,
    "areaServed" => [
        "@type" => "Country",
        "name" => "Egypt"
    ]
];
$webpage = [
    "@context" => "https://schema.org",
    "@type" => "WebPage",
    "name" => $pageTitle,
    "description" => $pageDescription,
    "url" => $canonical,
    "inLanguage" => $lang === 'ar' ? 'ar-EG' : 'en-EG',
    "isPartOf" => [
        "@type" => "WebSite",
        "name" => "FLIX",
        "url" => $protocol . '://' . $host . '/'
    ]
];
$website = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => "FLIX",
    "alternateName" => ["فليكس", "FLIX Egypt Home Services"],
    "url" => $protocol . '://' . $host . '/',
    "description" => $pageDescription,
    "inLanguage" => ["en-EG", "ar-EG"],
    "potentialAction" => [
        "@type" => "SearchAction",
        "target" => $protocol . '://' . $host . '/?q={search_term_string}',
        "query-input" => "required name=search_term_string"
    ]
];
$localBusiness = [
    "@context" => "https://schema.org",
    "@type" => "LocalBusiness",
    "name" => "FLIX",
    "description" => $pageDescription,
    "url" => $protocol . '://' . $host . '/',
    "image" => $previewImage,
    "address" => [
        "@type" => "PostalAddress",
        "addressCountry" => "EG",
        "addressLocality" => "Cairo"
    ],
    "areaServed" => ["Cairo", "Giza", "Alexandria", "Mansoura"],
    "priceRange" => "$$",
    "contactPoint" => [
        "@type" => "ContactPoint",
        "contactType" => "customer service",
        "availableLanguage" => ["English", "Arabic"],
        "areaServed" => "EG"
    ]
];

echo "    <script type=\"application/ld+json\">\n" . json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n";
echo "    <script type=\"application/ld+json\">\n" . json_encode($webpage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n";
echo "    <script type=\"application/ld+json\">\n" . json_encode($website, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n";
echo "    <script type=\"application/ld+json\">\n" . json_encode($localBusiness, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n    </script>\n";

// Output <title>
echo "    <title>" . htmlspecialchars($pageTitle) . "</title>\n";

?>
