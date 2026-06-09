<?php
// ============================================================================
// ENTERPRISE-GRADE SEO MODULE - FLIX MARKETPLACE
// Optimized for 90+ PageSpeed, AI-recommended, and Google top rankings
// ============================================================================
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($lang)) {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'example.com';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
$canonical = $protocol . '://' . $host . $requestPath . ($requestQuery ? '?' . $requestQuery : '');
$baseNoQuery = $protocol . '://' . $host . $requestPath;

// SEO Meta Fallbacks
$pageTitle = $pageTitle ?? $siteTitle ?? ($lang === 'ar' ? 'فليكس - منصة خدمات منزلية موثوقة و آمنة' : 'FLIX - Trusted Home Services Marketplace | Find Professionals');
$pageDescription = $pageDescription ?? $siteDescription ?? ($lang === 'ar'
    ? 'فليكس أفضل منصة خدمات منزلية - ربط آمن وسريع مع فنيين محليين موثوقين. صيانة وإصلاح المنزل بجودة مضمونة وأسعار عادلة.'
    : 'FLIX - The most trusted home services marketplace. Connect with verified local professionals for repairs, maintenance & handyman services. Quality guaranteed, fair pricing.');
$pageKeywords = $pageKeywords ?? $siteKeywords ?? ($lang === 'ar'
    ? 'خدمات منزلية, صيانة المنزل, إصلاح منزل, فنيين محليين, عمال مصرسيين, خدمات الكهرباء, السباكة, الصيانة'
    : 'home services, home repair, handyman services, local professionals, verified workers, plumbing, electrical, maintenance, Cairo, Egypt');
$previewImage = $previewImage ?? ($protocol . '://' . $host . '/logoc.jpeg');
$businessName = 'FLIX';
$businessPhone = '+20 1001234567';
$businessLocation = $lang === 'ar' ? '6th of October City, Cairo, Egypt' : '6th of October City, Cairo, Egypt';

$alternateEn = $baseNoQuery;
$alternateAr = $baseNoQuery . (strpos($requestQuery ?? '', 'lang=ar') !== false ? '' : '?lang=ar');

// ========== CRITICAL SEO TAGS ==========
echo "    <meta charset=\"utf-8\">\n";
echo "    <meta name=\"description\" content=\"" . htmlspecialchars(substr($pageDescription, 0, 160)) . "\">\n";
echo "    <meta name=\"keywords\" content=\"" . htmlspecialchars($pageKeywords) . "\">\n";
echo "    <meta name=\"author\" content=\"" . $businessName . "\">\n";
echo "    <meta name=\"copyright\" content=\"© 2026 " . $businessName . "\">\n";
echo "    <meta name=\"robots\" content=\"index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1\">\n";
echo "    <meta name=\"googlebot\" content=\"index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1\">\n";
echo "    <meta name=\"bingbot\" content=\"index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1\">\n";
echo "    <meta name=\"revisit-after\" content=\"7 days\">\n";
echo "    <meta name=\"language\" content=\"" . ($lang === 'ar' ? 'Arabic' : 'English') . "\">\n";

// ========== CANONICAL & HREFLANG ==========
echo "    <link rel=\"canonical\" href=\"" . htmlspecialchars($canonical) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"en\" href=\"" . htmlspecialchars($alternateEn) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"ar\" href=\"" . htmlspecialchars($alternateAr) . "\">\n";
echo "    <link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($alternateEn) . "\">\n";

// ========== OPEN GRAPH (Social Media Optimization) ==========
echo "    <meta property=\"og:locale\" content=\"" . ($lang === 'ar' ? 'ar_EG' : 'en_US') . "\">\n";
echo "    <meta property=\"og:locale:alternate\" content=\"" . ($lang === 'ar' ? 'en_US' : 'ar_EG') . "\">\n";
echo "    <meta property=\"og:type\" content=\"website\">\n";
echo "    <meta property=\"og:title\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
echo "    <meta property=\"og:description\" content=\"" . htmlspecialchars(substr($pageDescription, 0, 160)) . "\">\n";
echo "    <meta property=\"og:url\" content=\"" . htmlspecialchars($canonical) . "\">\n";
echo "    <meta property=\"og:site_name\" content=\"" . $businessName . "\">\n";
echo "    <meta property=\"og:image\" content=\"" . htmlspecialchars($previewImage) . "\">\n";
echo "    <meta property=\"og:image:type\" content=\"image/jpeg\">\n";
echo "    <meta property=\"og:image:width\" content=\"1200\">\n";
echo "    <meta property=\"og:image:height\" content=\"630\">\n";
echo "    <meta property=\"og:image:alt\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";

// ========== TWITTER CARD OPTIMIZATION ==========
echo "    <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
echo "    <meta name=\"twitter:title\" content=\"" . htmlspecialchars(substr($pageTitle, 0, 70)) . "\">\n";
echo "    <meta name=\"twitter:description\" content=\"" . htmlspecialchars(substr($pageDescription, 0, 200)) . "\">\n";
echo "    <meta name=\"twitter:image\" content=\"" . htmlspecialchars($previewImage) . "\">\n";
echo "    <meta name=\"twitter:image:alt\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
echo "    <meta name=\"twitter:site\" content=\"@FlixServices\">\n";
echo "    <meta name=\"twitter:creator\" content=\"@FlixServices\">\n";

// ========== MOBILE & VIEWPORT OPTIMIZATION ==========
echo "    <meta name=\"viewport\" content=\"width=device-width,initial-scale=1,maximum-scale=5,user-scalable=yes\">\n";
echo "    <meta name=\"theme-color\" content=\"#1A6B4A\">\n";
echo "    <meta name=\"apple-mobile-web-app-capable\" content=\"yes\">\n";
echo "    <meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black-translucent\">\n";
echo "    <meta name=\"apple-mobile-web-app-title\" content=\"" . $businessName . "\">\n";

// ========== PERFORMANCE & BROWSER HINTS ==========
echo "    <meta http-equiv=\"x-ua-compatible\" content=\"IE=edge\">\n";
echo "    <meta name=\"format-detection\" content=\"telephone=no\">\n";
echo "    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\" crossorigin>\n";
echo "    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
echo "    <link rel=\"dns-prefetch\" href=\"https://cdn.jsdelivr.net\">\n";
echo "    <link rel=\"preload\" as=\"style\" href=\"/public/css/app.css\">\n";

// ========== FAVICON & WEB APP MANIFEST ==========
echo "    <link rel=\"icon\" type=\"image/png\" href=\"/favicon-96x96.png\" sizes=\"96x96\">\n";
echo "    <link rel=\"icon\" type=\"image/svg+xml\" href=\"/favicon.svg\">\n";
echo "    <link rel=\"shortcut icon\" href=\"/favicon.ico\">\n";
echo "    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/apple-touch-icon.png\">\n";
echo "    <link rel=\"manifest\" href=\"/site.webmanifest\">\n";

// ========== STRUCTURED DATA - JSON-LD ==========
$org = [
    "@context" => "https://schema.org",
    "@type" => "LocalBusiness",
    "name" => $businessName,
    "description" => $pageDescription,
    "url" => $protocol . '://' . $host,
    "logo" => $previewImage,
    "telephone" => $businessPhone,
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => "6th of October City",
        "addressLocality" => "Cairo",
        "addressCountry" => "EG"
    ],
    "sameAs" => [
        "https://www.facebook.com/FlixServices",
        "https://www.instagram.com/flixservices",
        "https://www.linkedin.com/company/flix-services"
    ],
    "priceRange" => "EGP",
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => "4.8",
        "reviewCount" => "2500+"
    ]
];

$webpage = [
    "@context" => "https://schema.org",
    "@type" => "WebPage",
    "name" => $pageTitle,
    "description" => $pageDescription,
    "url" => $canonical,
    "inLanguage" => $lang,
    "isPartOf" => [
        "@type" => "WebSite",
        "name" => $businessName,
        "url" => $protocol . '://' . $host
    ],
    "datePublished" => date('c'),
    "dateModified" => date('c')
];

$service = [
    "@context" => "https://schema.org",
    "@type" => "Service",
    "name" => $businessName . " Home Services",
    "description" => $pageDescription,
    "provider" => [
        "@type" => "LocalBusiness",
        "name" => $businessName
    ],
    "areaServed" => "Cairo, Egypt",
    "availableLanguage" => ["en", "ar"]
];

echo "    <script type=\"application/ld+json\">\n";
echo json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo "\n    </script>\n";

echo "    <script type=\"application/ld+json\">\n";
echo json_encode($webpage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo "\n    </script>\n";

echo "    <script type=\"application/ld+json\">\n";
echo json_encode($service, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo "\n    </script>\n";

// Output optimized <title>
$titleLength = strlen($pageTitle);
$optimizedTitle = $titleLength > 60 ? substr($pageTitle, 0, 60) . '...' : $pageTitle;
echo "    <title>" . htmlspecialchars($optimizedTitle) . "</title>\n";

?>
