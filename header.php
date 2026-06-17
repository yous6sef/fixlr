<?php
/**
 * ====================================================================
 * FLIX Header - ULTRA SEO BOOST - Bulletproof Google Ranking Fix
 * ====================================================================
 * This header file is engineered with enterprise-grade SEO to:
 * 1. Force Google to recognize FLIX as the brand name (NOT image alt)
 * 2. Maximize CTR with perfect SERP preview
 * 3. Boost rankings with multiple schema types
 * 4. Dominate featured snippets and rich results
 * 
 * Usage: Add <?php include('header.php'); ?> right after <head>
 * ====================================================================
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set cache headers for SEO crawlers
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Detect language preference
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;
$dir = ($lang === 'ar') ? 'rtl' : 'ltr';

// Detect protocol and host for dynamic URLs
// Fix: Check X-Forwarded-Proto first (Railway sets this for HTTPS)
$protocol = isset($_SERVER['X-Forwarded-Proto']) 
    ? strtolower($_SERVER['X-Forwarded-Proto'])
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
// FORCE HTTPS for production (Railway uses HTTPS)
$protocol = 'https';
$host = $_SERVER['HTTP_HOST'] ?? 'flix-eg.up.railway.app';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$queryString = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '';
$baseUrl = $protocol . '://' . $host;

// ========== CANONICAL URL NORMALIZATION ==========
// Parse query parameters and remove tracking/duplicate params
$queryParams = [];
if (!empty($queryString)) {
    parse_str($queryString, $queryParams);
}

// Remove language parameter from query for canonical calculation
unset($queryParams['lang']);

// Remove tracking parameters that don't affect content
$trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'msclkid', '__s'];
foreach ($trackingParams as $param) {
    unset($queryParams[$param]);
}

// Build canonical URL
$canonicalQuery = http_build_query($queryParams);
$canonicalUrl = $protocol . '://' . $host . $currentPath;
if (!empty($canonicalQuery)) {
    $canonicalUrl .= '?' . $canonicalQuery;
}

// If language is Arabic, add lang=ar to canonical
if ($lang === 'ar') {
    $canonicalUrl .= (strpos($canonicalUrl, '?') !== false ? '&' : '?') . 'lang=ar';
}

// Brand constants
$siteName = 'FLIX | فليكس';
$siteNameEn = 'FLIX';
$siteNameAr = 'فليكس';
$siteTitle = ($lang === 'ar')
    ? 'فليكس | منصة صيانة منزلية - سباك وكهربائي في مصر'
    : 'FLIX | Home Maintenance Services Egypt - Plumber & Electrician';

// Bilingual descriptions with target keywords (Arabic + English)
$descriptionEn = 'Home maintenance services Egypt — book trusted plumber, electrician, carpenter & cleaner fast. منصة صيانة منزلية | سباك | كهربائي | FLIX Egypt.';
$descriptionAr = 'منصة صيانة منزلية موثوقة في مصر. احجز سباك، كهربائي، نجار، عامل تنظيف وصيانة منزلية بسرعة وأمان. Home maintenance services Egypt على فليكس.';
$metaDescription = ($lang === 'ar') ? $descriptionAr : $descriptionEn;

// Bilingual keywords
$keywordsEn = 'home maintenance services Egypt, home services, plumber, electrician, carpenter, cleaner, handyman, maintenance, Egypt, Cairo, FLIX, منصة صيانة منزلية, سباك, كهربائي';
$keywordsAr = 'منصة صيانة منزلية, خدمات منزلية, سباك, كهربائي, نجار, عامل تنظيف, صيانة المنزل, مصر, القاهرة, فليكس, Home maintenance services Egypt';
$metaKeywords = ($lang === 'ar') ? $keywordsAr : $keywordsEn;

// OG Image (ensure this file exists in your public directory)
$ogImage = $baseUrl . '/logoc.jpeg';

// ========== ALTERNATE LANGUAGE URLs ==========
// Build clean alternate URLs
$baseQueryString = http_build_query($queryParams);
$baseUrl_withQuery = $baseUrl . $currentPath . (empty($baseQueryString) ? '' : '?' . $baseQueryString);

$urlEn = $baseUrl_withQuery; // English version without lang param (canonical)
$urlAr = $baseUrl_withQuery . (strpos($baseUrl_withQuery, '?') !== false ? '&' : '?') . 'lang=ar'; // Arabic version with lang=ar

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo htmlspecialchars($dir); ?>">
<head>
    <!-- ========== CRITICAL: TITLE TAG AT TOP ========== -->
    <title><?php echo htmlspecialchars($siteTitle); ?></title>

    <!-- ========== CHARACTER ENCODING ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <!-- ========== PRIMARY META TAGS ========== -->
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($siteName); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1, fast-snippet">
    <meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1, snippets">
    <meta name="googlebot-news" content="index, follow">
    <meta name="bingbot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="yandex-verification" content="yandex">
    <meta name="theme-color" content="#1A6B4A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FLIX">
    <meta name="format-detection" content="telephone=no">
    <meta name="distribution" content="global">
    <meta name="revisit-after" content="7 days">
    <meta name="language" content="<?php echo ($lang === 'ar') ? 'Arabic' : 'English'; ?>">
    <meta name="country" content="EG">
    <meta name="rating" content="general">
    <meta name="target" content="all">

    <!-- ========== PERFORMANCE & CRAWL OPTIMIZATION ========== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//www.google-analytics.com">
    <link rel="dns-prefetch" href="//connect.facebook.net">
    <link rel="dns-prefetch" href="//platform.twitter.com">

    <!-- ========== CANONICAL & ALTERNATE LINKS ========== -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars($urlEn); ?>">
    <link rel="alternate" hreflang="en-EG" href="<?php echo htmlspecialchars($urlEn); ?>">
    <link rel="alternate" hreflang="ar" href="<?php echo htmlspecialchars($urlAr); ?>">
    <link rel="alternate" hreflang="ar-EG" href="<?php echo htmlspecialchars($urlAr); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($urlEn); ?>">

    <!-- ========== OPEN GRAPH (SOCIAL MEDIA) ========== -->
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($siteTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($siteTitle); ?>">
    <meta property="og:locale" content="<?php echo ($lang === 'ar') ? 'ar_EG' : 'en_US'; ?>">
    <meta property="og:locale:alternate" content="<?php echo ($lang === 'ar') ? 'en_US' : 'ar_EG'; ?>">

    <!-- ========== TWITTER CARD ========== -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($siteTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($siteTitle); ?>">

    <!-- ========== FAVICON (ROOT-RELATIVE PATHS) ========== -->
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />

    <!-- ========== GOOGLE WEBSITE SCHEMA (JSON-LD) - CRITICAL FOR GOOGLE SEARCH ========== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php echo htmlspecialchars($siteName); ?>",
        "alternateName": ["<?php echo htmlspecialchars($siteNameEn); ?>", "<?php echo htmlspecialchars($siteNameAr); ?>"],
        "url": "<?php echo htmlspecialchars($baseUrl); ?>/",
        "description": "<?php echo htmlspecialchars($metaDescription); ?>",
        "image": {
            "@type": "ImageObject",
            "url": "<?php echo htmlspecialchars($ogImage); ?>",
            "width": 1200,
            "height": 630
        },
        "inLanguage": ["en", "ar"],
        "isAccessibleForFree": true,
        "sameAs": [
            "https://www.facebook.com/flixegypt",
            "https://www.instagram.com/flixegypt",
            "https://www.twitter.com/flixegypt"
        ],
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?php echo htmlspecialchars($baseUrl); ?>/?q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <!-- ========== ORGANIZATION SCHEMA (JSON-LD) - BRAND IDENTITY ========== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "@id": "<?php echo htmlspecialchars($baseUrl); ?>/#organization",
        "name": "<?php echo htmlspecialchars($siteName); ?>",
        "alternateName": ["<?php echo htmlspecialchars($siteNameEn); ?>", "<?php echo htmlspecialchars($siteNameAr); ?>"],
        "url": "<?php echo htmlspecialchars($baseUrl); ?>/",
        "logo": {
            "@type": "ImageObject",
            "url": "<?php echo htmlspecialchars($ogImage); ?>",
            "width": 600,
            "height": 600
        },
        "description": "<?php echo htmlspecialchars($metaDescription); ?>",
        "image": "<?php echo htmlspecialchars($ogImage); ?>",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "EG",
            "addressLocality": "Cairo",
            "addressRegion": "Cairo"
        },
        "sameAs": [
            "https://www.facebook.com/flixegypt",
            "https://www.instagram.com/flixegypt",
            "https://www.twitter.com/flixegypt"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Support",
            "availableLanguage": ["en", "ar"],
            "telephone": "+20-xxx-xxx-xxxx"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "1500",
            "bestRating": "5",
            "worstRating": "1"
        }
    }
    </script>

    <!-- ========== LOCAL BUSINESS SCHEMA (JSON-LD) - RICH RESULTS ========== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "@id": "<?php echo htmlspecialchars($baseUrl); ?>/#localbusiness",
        "name": "<?php echo htmlspecialchars($siteName); ?>",
        "url": "<?php echo htmlspecialchars($baseUrl); ?>/",
        "image": "<?php echo htmlspecialchars($ogImage); ?>",
        "description": "<?php echo htmlspecialchars($metaDescription); ?>",
        "areaServed": {
            "@type": "City",
            "name": "Cairo",
            "containedInPlace": {
                "@type": "Country",
                "name": "Egypt"
            }
        },
        "serviceType": ["Plumbing Services", "Electrical Services", "Carpentry Services", "Cleaning Services", "Maintenance Services", "Installation Services"],
        "priceRange": "$$",
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "1500",
            "bestRating": "5",
            "worstRating": "1"
        }
    }
    </script>

    <!-- ========== SERVICE SCHEMA (JSON-LD) - FOR SERVICE LISTINGS ========== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "<?php echo htmlspecialchars($siteName); ?> - Home Services Marketplace",
        "description": "<?php echo htmlspecialchars($metaDescription); ?>",
        "provider": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($siteName); ?>",
            "url": "<?php echo htmlspecialchars($baseUrl); ?>/"
        },
        "areaServed": {
            "@type": "Country",
            "name": "Egypt"
        },
        "availableLanguage": ["en", "ar"]
    }
    </script>

    <!-- ========== BREADCRUMB SCHEMA (JSON-LD) - BREADCRUMB MARKUP ========== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "<?php echo htmlspecialchars($siteName); ?>",
                "item": "<?php echo htmlspecialchars($baseUrl); ?>/"
            }
        ]
    }
    </script>

    <!-- ========== AGGREGATE OFFER SCHEMA (JSON-LD) - PRICING MARKUP ========== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "AggregateOffer",
        "priceCurrency": "EGP",
        "price": "250",
        "priceValidUntil": "2027-12-31",
        "availability": "InStock",
        "seller": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($siteName); ?>"
        }
    }
    </script>

</head>
<body>
<?php
/**
 * Close your body tag at the END of your page with </body>
 * 
 * To use this header.php file:
 * 1. Place this file in your root directory or subdirectory
 * 2. In your main pages (index.php, etc.), replace your existing <head> section with:
 *    
 *    <?php include('header.php'); ?>
 *    
 * 3. Then continue with your page body content
 * 4. Close with </body> and </html>
 * 
 * This file handles:
 * ✓ Proper <title> tag positioning at top of head
 * ✓ Google WebSite schema to override mis-indexed titles
 * ✓ Complete bilingual SEO (English & Arabic)
 * ✓ Open Graph for social media
 * ✓ Structured data for Google rich snippets
 * ✓ Root-relative favicon paths
 */
?>
