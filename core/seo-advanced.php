<?php
/**
 * ====================================================================
 * FLIX Advanced SEO & GEO Engine - Production Grade
 * ====================================================================
 * Dynamically generates bilingual meta tags, structured data schemas,
 * and social media cards based on page type and database variables.
 * 
 * INTEGRATION:
 * Include this file early in your page's <head> section:
 * <?php include('core/seo-advanced.php'); ?>
 * 
 * REQUIRED VARIABLES (set BEFORE including this file):
 * - $lang: 'ar' or 'en' (language)
 * - $pageType: 'home', 'service', 'craftsman', 'search'
 * - $serviceId: (optional) Database ID of service category
 * - $craftsmanId: (optional) Database ID of craftsman/worker
 * - $cityId: (optional) Database ID of city
 * 
 * DATABASE MAPPINGS (update these arrays with your DB queries):
 * - $serviceName, $serviceDescription, $serviceKeywords
 * - $craftsmanName, $craftsmanRating, $craftsmanReviewCount, $craftsmanSpecialty
 * - $cityName, $cityNameAr
 * ====================================================================
 */

// Ensure language is set
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
}

// Set page type if not already set
if (!isset($pageType)) {
    $pageType = $_GET['type'] ?? 'home';
}

// Protocol and URL setup
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'flix-eg.up.railway.app';
$baseUrl = $protocol . '://' . $host;
$currentUrl = $baseUrl . ($_SERVER['REQUEST_URI'] ?? '/');
$canonicalUrl = $currentUrl; // Remove query params if needed

// ========== STEP 1: DYNAMIC BILINGUAL META TAGS ==========

// Default site values
$siteName = 'FLIX | فليكس';
$brandName = 'FLIX';
$brandNameAr = 'فليكس';

// Initialize page-specific variables
$pageTitle = '';
$pageDescription = '';
$pageKeywords = '';
$ogImage = $baseUrl . '/logoc.jpeg';
$ogTitle = '';
$ogDescription = '';
$structuredSchema = null;

// PAGE TYPE: HOME
if ($pageType === 'home') {
    if ($lang === 'ar') {
        $pageTitle = 'فليكس | منصة الخدمات المنزلية الموثوقة | حجز الفنيين والحرفيين في مصر';
        $pageDescription = 'منصة فليكس تربطك بأفضل الفنيين والحرفيين الموثوقين لجميع خدمات المنزل. سباكة، كهرباء، نجارة، دهان، تنظيف، صيانة. احجز الآن بسهولة وأمان.';
        $pageKeywords = 'خدمات منزلية, سباك, كهربائي, نجار, دهان, عامل نظافة, فني صيانة, مصر, القاهرة, الإسكندرية, فليكس, حجز خدمات, فنيين محليين';
        $ogTitle = 'فليكس - أفضل منصة للخدمات المنزلية في مصر';
        $ogDescription = 'احجز أفضل الفنيين والحرفيين الموثوقين لجميع خدمات منزلك. سباكة، كهرباء، نجارة، دهان، تنظيف وأكثر.';
    } else {
        $pageTitle = 'FLIX | Trusted Home Services Marketplace | Book Professional Craftsmen in Egypt';
        $pageDescription = 'FLIX connects you with verified local plumbers, electricians, carpenters, painters, cleaners, and handymen. Quality guaranteed, fair pricing, instant booking. Available in Cairo, Alexandria & more.';
        $pageKeywords = 'home services, plumber, electrician, carpenter, painter, cleaner, handyman, maintenance, repair, Egypt, Cairo, Alexandria, FLIX, professional services';
        $ogTitle = 'FLIX - Egypt\'s Most Trusted Home Services Marketplace';
        $ogDescription = 'Book verified local professionals for all your home service needs. Plumbing, electrical, carpentry, painting, cleaning, and more.';
    }
}

// PAGE TYPE: SERVICE (e.g., Plumbing in Cairo)
elseif ($pageType === 'service') {
    // IMPORTANT: Map these variables from your database query
    // $serviceName = $row['service_name']; // e.g., "Plumbing" or "السباكة"
    // $serviceDescription = $row['service_description'];
    // $cityName = $row['city_name']; // e.g., "Cairo" or "القاهرة"
    // $cityNameAr = $row['city_name_ar'];
    
    if (!isset($serviceName)) $serviceName = ($lang === 'ar') ? 'السباكة' : 'Plumbing';
    if (!isset($cityName)) $cityName = ($lang === 'ar') ? 'القاهرة' : 'Cairo';
    if (!isset($cityNameAr)) $cityNameAr = 'القاهرة';
    
    if ($lang === 'ar') {
        $pageTitle = $serviceName . ' في ' . $cityNameAr . ' | احجز أفضل فني ' . $serviceName . ' مع فليكس';
        $pageDescription = 'ابحث عن أفضل فنيين ' . $serviceName . ' الموثوقين في ' . $cityNameAr . ' على منصة فليكس. خدمات محترفة بأسعار عادلة. احجز الآن.';
        $pageKeywords = 'فني ' . $serviceName . ', ' . $serviceName . ' في ' . $cityNameAr . ', حجز ' . strtolower($serviceName) . ', ' . $cityNameAr . ', فليكس, خدمات ' . $serviceName;
        $ogTitle = $serviceName . ' في ' . $cityNameAr . ' - فليكس';
        $ogDescription = 'اعثر على أفضل فني ' . $serviceName . ' الموثوق في ' . $cityNameAr . '. احجز الآن مع ضمان الجودة والسعر العادل.';
    } else {
        $pageTitle = 'Professional ' . ucfirst($serviceName) . ' Services in ' . $cityName . ' | Book with FLIX';
        $pageDescription = 'Find and book the best verified ' . strtolower($serviceName) . ' professionals in ' . $cityName . ' on FLIX. Quality guaranteed, fair pricing, fast booking.';
        $pageKeywords = strtolower($serviceName) . ', ' . strtolower($serviceName) . ' in ' . $cityName . ', book ' . strtolower($serviceName) . ', ' . $cityName . ', FLIX, professional ' . strtolower($serviceName);
        $ogTitle = 'Professional ' . $serviceName . ' in ' . $cityName . ' | FLIX';
        $ogDescription = 'Book trusted ' . strtolower($serviceName) . ' professionals in ' . $cityName . '. Quality service, fair prices, instant booking.';
    }
    
    // Set canonical URL with service parameters
    $canonicalUrl = $baseUrl . '/services/' . (isset($serviceId) ? $serviceId : '0') . '?city=' . (isset($cityId) ? $cityId : '0') . '&lang=' . $lang;
}

// PAGE TYPE: CRAFTSMAN PROFILE
elseif ($pageType === 'craftsman') {
    // IMPORTANT: Map these variables from your database query
    // $craftsmanName = $row['worker_name'];
    // $craftsmanSpecialty = $row['specialty']; // e.g., "Plumbing", "السباكة"
    // $craftsmanRating = $row['rating']; // e.g., 4.8
    // $craftsmanReviewCount = $row['review_count']; // e.g., 152
    // $cityName = $row['city_name'];
    // $craftsmanImage = $row['profile_image'];
    
    if (!isset($craftsmanName)) $craftsmanName = 'Ahmed';
    if (!isset($craftsmanSpecialty)) $craftsmanSpecialty = ($lang === 'ar') ? 'سباك' : 'Plumber';
    if (!isset($craftsmanRating)) $craftsmanRating = 4.8;
    if (!isset($craftsmanReviewCount)) $craftsmanReviewCount = 150;
    if (!isset($cityName)) $cityName = ($lang === 'ar') ? 'القاهرة' : 'Cairo';
    if (!isset($craftsmanImage)) $craftsmanImage = $baseUrl . '/default-profile.jpg';
    
    if ($lang === 'ar') {
        $pageTitle = $craftsmanName . ' - ' . $craftsmanSpecialty . ' محترف في ' . $cityName . ' | فليكس';
        $pageDescription = $craftsmanName . ' متخصص في ' . $craftsmanSpecialty . ' مع تقييم ' . $craftsmanRating . '/5 من ' . $craftsmanReviewCount . ' عميل. احجز خدماته الآن على فليكس.';
        $pageKeywords = $craftsmanName . ', ' . $craftsmanSpecialty . ', ' . $cityName . ', فنيين محليين, فليكس, حجز خدمات';
        $ogTitle = $craftsmanName . ' - ' . $craftsmanSpecialty . ' ⭐' . $craftsmanRating;
        $ogDescription = $craftsmanName . ' - ' . $craftsmanSpecialty . ' محترف | ' . $craftsmanReviewCount . ' عميل راضي | احجز الآن';
    } else {
        $pageTitle = $craftsmanName . ' - Professional ' . $craftsmanSpecialty . ' in ' . $cityName . ' | FLIX';
        $pageDescription = $craftsmanName . ' is a verified ' . $craftsmanSpecialty . ' professional rated ' . $craftsmanRating . '/5 by ' . $craftsmanReviewCount . ' satisfied customers. Book on FLIX now.';
        $pageKeywords = $craftsmanName . ', ' . $craftsmanSpecialty . ', ' . $cityName . ', professional craftsman, FLIX, book service';
        $ogTitle = $craftsmanName . ' - ' . $craftsmanSpecialty . ' ⭐' . $craftsmanRating;
        $ogDescription = $craftsmanName . ' - Verified ' . $craftsmanSpecialty . ' | ' . $craftsmanReviewCount . ' Happy Customers | Book Now';
    }
    
    $ogImage = $craftsmanImage;
    $canonicalUrl = $baseUrl . '/craftsman/' . (isset($craftsmanId) ? $craftsmanId : '0') . '?lang=' . $lang;
}

// PAGE TYPE: SEARCH RESULTS
elseif ($pageType === 'search') {
    $searchQuery = $_GET['q'] ?? 'services';
    
    if ($lang === 'ar') {
        $pageTitle = 'نتائج البحث عن "' . htmlspecialchars($searchQuery) . '" | فليكس';
        $pageDescription = 'ابحث عن ' . htmlspecialchars($searchQuery) . ' على منصة فليكس. اعثر على أفضل الفنيين والحرفيين الموثوقين.';
        $pageKeywords = htmlspecialchars($searchQuery) . ', فليكس, خدمات منزلية';
        $ogTitle = 'نتائج البحث: ' . htmlspecialchars($searchQuery);
        $ogDescription = 'ابحث عن ' . htmlspecialchars($searchQuery) . ' مع أفضل الفنيين المحليين';
    } else {
        $pageTitle = 'Search Results for "' . htmlspecialchars($searchQuery) . '" | FLIX';
        $pageDescription = 'Search for ' . htmlspecialchars($searchQuery) . ' on FLIX. Find verified local professionals and craftsmen.';
        $pageKeywords = htmlspecialchars($searchQuery) . ', FLIX, home services, professionals';
        $ogTitle = 'Search: ' . htmlspecialchars($searchQuery);
        $ogDescription = 'Find ' . htmlspecialchars($searchQuery) . ' with top-rated local professionals';
    }
}

// ========== STEP 2: SERVICE & LOCALBUSINESS SCHEMA MARKUP ==========

if ($pageType === 'service' && isset($serviceId)) {
    // Service + LocalBusiness Schema for service pages
    $structuredSchema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            '@id' => $baseUrl . '/services/' . $serviceId,
            'name' => $pageTitle,
            'description' => $pageDescription,
            'url' => $canonicalUrl,
            'areaServed' => [
                '@type' => 'City',
                'name' => isset($cityName) ? $cityName : 'Cairo',
                'containedInPlace' => [
                    '@type' => 'Country',
                    'name' => 'Egypt'
                ]
            ],
            'serviceType' => isset($serviceName) ? $serviceName : 'Home Service',
            'provider' => [
                '@type' => 'LocalBusiness',
                'name' => 'FLIX',
                'url' => $baseUrl,
                'image' => $ogImage,
                'aggregateRating' => [
                    '@type' => 'AggregateRating',
                    'ratingValue' => '4.8',
                    'ratingCount' => '2500',
                    'bestRating' => '5',
                    'worstRating' => '1'
                ]
            ]
        ]
    ];
}

elseif ($pageType === 'craftsman' && isset($craftsmanId)) {
    // LocalBusiness + AggregateRating Schema for craftsman profiles
    $structuredSchema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => $baseUrl . '/craftsman/' . $craftsmanId,
            'name' => isset($craftsmanName) ? $craftsmanName : 'Professional',
            'image' => isset($craftsmanImage) ? $craftsmanImage : $ogImage,
            'description' => isset($craftsmanSpecialty) ? $craftsmanSpecialty . ' Professional' : 'Service Professional',
            'url' => $canonicalUrl,
            'telephone' => isset($craftsmanPhone) ? $craftsmanPhone : '+20-xxx-xxx-xxxx',
            'address' => [
                '@type' => 'PostalAddress',
                '@language' => $lang,
                'streetAddress' => isset($craftsmanAddress) ? $craftsmanAddress : 'Cairo',
                'addressLocality' => isset($cityName) ? $cityName : 'Cairo',
                'addressCountry' => 'EG'
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => isset($craftsmanRating) ? $craftsmanRating : '4.5',
                'ratingCount' => isset($craftsmanReviewCount) ? $craftsmanReviewCount : '50',
                'bestRating' => '5',
                'worstRating' => '1'
            ],
            'priceRange' => 'EGP',
            'availability' => 'Available'
        ]
    ];
}

elseif ($pageType === 'home') {
    // Organization + WebSite Schema for homepage
    $structuredSchema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'FLIX | فليكس',
            'url' => $baseUrl,
            'description' => $pageDescription,
            'inLanguage' => ['en', 'ar'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $baseUrl . '/?q={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'FLIX',
            'alternateName' => ['فليكس', 'FLIX Egypt'],
            'url' => $baseUrl,
            'logo' => $ogImage,
            'description' => $pageDescription,
            'sameAs' => [
                'https://www.facebook.com/flixegypt',
                'https://www.instagram.com/flixegypt',
                'https://www.twitter.com/flixegypt'
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'ratingCount' => '2500'
            ]
        ]
    ];
}

// ========== OUTPUT: META TAGS ==========
?>
<!-- FLIX Advanced SEO - Dynamic Meta Tags -->
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

<!-- Alternate Language Links (hreflang) -->
<?php if ($pageType === 'home'): ?>
<link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars($baseUrl . '/?lang=en'); ?>">
<link rel="alternate" hreflang="ar" href="<?php echo htmlspecialchars($baseUrl . '/?lang=ar'); ?>">
<link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($baseUrl); ?>">
<?php elseif ($pageType === 'service' && isset($serviceId, $cityId)): ?>
<link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars($baseUrl . '/services/' . $serviceId . '?city=' . $cityId . '&lang=en'); ?>">
<link rel="alternate" hreflang="ar" href="<?php echo htmlspecialchars($baseUrl . '/services/' . $serviceId . '?city=' . $cityId . '&lang=ar'); ?>">
<?php endif; ?>

<!-- Open Graph Tags (Facebook, WhatsApp, LinkedIn) -->
<meta property="og:title" content="<?php echo htmlspecialchars($ogTitle); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($ogDescription); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="FLIX | فليكس">
<meta property="og:locale" content="<?php echo ($lang === 'ar') ? 'ar_EG' : 'en_US'; ?>">
<meta property="og:locale:alternate" content="<?php echo ($lang === 'ar') ? 'en_US' : 'ar_EG'; ?>">

<!-- Twitter Card Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($ogTitle); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($ogDescription); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
<meta name="twitter:site" content="@flixegypt">

<!-- Additional SEO Meta Tags -->
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="language" content="<?php echo ($lang === 'ar') ? 'Arabic' : 'English'; ?>">
<meta name="revisit-after" content="7 days">

<?php
// ========== STEP 3: OUTPUT STRUCTURED DATA SCHEMAS ==========
if ($structuredSchema && is_array($structuredSchema)) {
    foreach ($structuredSchema as $schema) {
        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
    }
}
?>
