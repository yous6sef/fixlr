<?php
/**
 * ====================================================================
 * FLIX Semantic HTML5 & AI Bot Optimization Utilities
 * ====================================================================
 * Provides helper functions to ensure proper semantic HTML structure
 * for Google, Bing, and AI search engines (ChatGPT, Perplexity).
 * 
 * USAGE:
 * Include this file early in your PHP templates:
 * <?php include('core/semantic-seo.php'); ?>
 * 
 * Then use the helper functions throughout your page markup.
 * ====================================================================
 */

/**
 * Output a proper page title (H1) with semantic markup
 * Should be used ONCE per page at the top of <main>
 * 
 * @param string $title The page title
 * @param string $lang 'en' or 'ar' for language context
 * @return void
 */
function seoPageTitle($title, $lang = 'en') {
    $dir = ($lang === 'ar') ? 'rtl' : 'ltr';
    echo '<h1 class="page-title" lang="' . htmlspecialchars($lang) . '" dir="' . htmlspecialchars($dir) . '">';
    echo htmlspecialchars($title);
    echo '</h1>' . "\n";
}

/**
 * Output a section heading (H2) for logical page sections
 * Use these for service categories, worker sections, etc.
 * 
 * @param string $title The section heading
 * @param string $id Optional: CSS/semantic ID for the section
 * @param string $lang 'en' or 'ar'
 * @return void
 */
function seoSectionHeading($title, $id = '', $lang = 'en') {
    $idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
    $dir = ($lang === 'ar') ? 'rtl' : 'ltr';
    echo '<h2' . $idAttr . ' class="section-heading" lang="' . htmlspecialchars($lang) . '" dir="' . htmlspecialchars($dir) . '">';
    echo htmlspecialchars($title);
    echo '</h2>' . "\n";
}

/**
 * Output a service card with proper semantic markup
 * Marks up individual services for rich snippets
 * 
 * @param array $serviceData: [
 *     'name' => 'Service Name',
 *     'description' => 'Service description',
 *     'icon' => 'icon-url.png',
 *     'url' => 'https://...',
 *     'rating' => 4.8,
 *     'reviews' => 150,
 *     'price_min' => 250,
 *     'price_max' => 1000,
 *     'currency' => 'EGP',
 *     'availability' => true
 * ]
 * @return void
 */
function seoServiceCard($serviceData) {
    $name = htmlspecialchars($serviceData['name'] ?? 'Service');
    $desc = htmlspecialchars($serviceData['description'] ?? '');
    $url = htmlspecialchars($serviceData['url'] ?? '#');
    $rating = floatval($serviceData['rating'] ?? 0);
    $reviews = intval($serviceData['reviews'] ?? 0);
    $priceMin = floatval($serviceData['price_min'] ?? 0);
    $priceMax = floatval($serviceData['price_max'] ?? 0);
    $currency = htmlspecialchars($serviceData['currency'] ?? 'EGP');
    $available = $serviceData['availability'] ?? true;
    
    ?>
    <article class="service-card" itemscope itemtype="https://schema.org/Service">
        <meta itemprop="name" content="<?php echo $name; ?>">
        <meta itemprop="description" content="<?php echo $desc; ?>">
        <meta itemprop="url" content="<?php echo $url; ?>">
        
        <?php if ($rating > 0): ?>
        <div class="service-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
            <meta itemprop="ratingValue" content="<?php echo $rating; ?>">
            <meta itemprop="ratingCount" content="<?php echo $reviews; ?>">
            <meta itemprop="bestRating" content="5">
            <meta itemprop="worstRating" content="1">
            <span class="stars">⭐ <?php echo $rating; ?></span>
            <span class="review-count"><?php echo $reviews; ?> reviews</span>
        </div>
        <?php endif; ?>
        
        <?php if ($priceMin > 0): ?>
        <div class="service-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <meta itemprop="priceCurrency" content="<?php echo $currency; ?>">
            <meta itemprop="price" content="<?php echo round(($priceMin + $priceMax) / 2); ?>">
            <meta itemprop="availability" content="<?php echo $available ? 'InStock' : 'OutOfStock'; ?>">
            <span class="price-range">
                <?php echo $currency; ?> <?php echo $priceMin; ?> - <?php echo $priceMax; ?>
            </span>
        </div>
        <?php endif; ?>
        
        <h3 class="service-name"><?php echo $name; ?></h3>
        <p class="service-description"><?php echo $desc; ?></p>
        <a href="<?php echo $url; ?>" class="service-link">View Details →</a>
    </article>
    <?php
}

/**
 * Output a craftsman profile card with proper LocalBusiness schema
 * Displays worker information with ratings and contact
 * 
 * @param array $craftsmanData: [
 *     'id' => 123,
 *     'name' => 'Ahmed Hassan',
 *     'specialty' => 'Plumbing',
 *     'image' => 'profile-image.jpg',
 *     'rating' => 4.8,
 *     'reviews' => 152,
 *     'phone' => '+20-xxx-xxx-xxxx',
 *     'city' => 'Cairo',
 *     'verified' => true,
 *     'description' => 'Professional plumber with 10+ years experience'
 * ]
 * @return void
 */
function seoWorkerCard($craftsmanData) {
    $id = intval($craftsmanData['id'] ?? 0);
    $name = htmlspecialchars($craftsmanData['name'] ?? 'Professional');
    $specialty = htmlspecialchars($craftsmanData['specialty'] ?? 'Service');
    $image = htmlspecialchars($craftsmanData['image'] ?? '/default-profile.jpg');
    $rating = floatval($craftsmanData['rating'] ?? 0);
    $reviews = intval($craftsmanData['reviews'] ?? 0);
    $phone = htmlspecialchars($craftsmanData['phone'] ?? '');
    $city = htmlspecialchars($craftsmanData['city'] ?? 'Cairo');
    $verified = $craftsmanData['verified'] ?? false;
    $description = htmlspecialchars($craftsmanData['description'] ?? '');
    $profileUrl = '/craftsman/' . $id;
    
    ?>
    <article class="worker-card" itemscope itemtype="https://schema.org/LocalBusiness">
        <meta itemprop="name" content="<?php echo $name; ?>">
        <meta itemprop="url" content="<?php echo htmlspecialchars($profileUrl); ?>">
        <meta itemprop="image" content="<?php echo $image; ?>">
        <meta itemprop="description" content="<?php echo $description; ?>">
        
        <div class="worker-header">
            <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="worker-image" itemprop="image">
            <div class="worker-info">
                <h3 class="worker-name" itemprop="name"><?php echo $name; ?></h3>
                <p class="worker-specialty" itemprop="knowsAbout"><?php echo $specialty; ?></p>
                
                <?php if ($verified): ?>
                <span class="verified-badge" title="Verified Professional">✓ Verified</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($rating > 0): ?>
        <div class="worker-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
            <meta itemprop="ratingValue" content="<?php echo $rating; ?>">
            <meta itemprop="ratingCount" content="<?php echo $reviews; ?>">
            <meta itemprop="bestRating" content="5">
            <meta itemprop="worstRating" content="1">
            <div class="rating-display">
                <span class="rating-stars">⭐ <?php echo $rating; ?>/5</span>
                <span class="rating-count">(<?php echo $reviews; ?> reviews)</span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="worker-location" itemprop="areaServed">
            <span class="location-icon">📍</span>
            <span itemprop="addressLocality"><?php echo $city; ?></span>
        </div>
        
        <?php if ($phone): ?>
        <div class="worker-contact" itemprop="contactPoint" itemscope itemtype="https://schema.org/ContactPoint">
            <meta itemprop="contactType" content="Customer Service">
            <meta itemprop="telephone" content="<?php echo $phone; ?>">
            <a href="tel:<?php echo str_replace(' ', '', $phone); ?>" class="contact-link">📞 Call</a>
        </div>
        <?php endif; ?>
        
        <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="worker-view-link">View Profile →</a>
    </article>
    <?php
}

/**
 * Output breadcrumb navigation with Schema.org markup
 * Critical for Google search breadcrumb display
 * 
 * @param array $breadcrumbs: [
 *     ['name' => 'Home', 'url' => '/'],
 *     ['name' => 'Services', 'url' => '/services'],
 *     ['name' => 'Plumbing', 'url' => '/services/plumbing']
 * ]
 * @return void
 */
function seoBreadcrumbs($breadcrumbs, $lang = 'en') {
    $dir = ($lang === 'ar') ? 'rtl' : 'ltr';
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => []
    ];
    
    echo '<nav class="breadcrumb" aria-label="Breadcrumb" dir="' . htmlspecialchars($dir) . '">' . "\n";
    echo '<ol itemscope itemtype="https://schema.org/BreadcrumbList">' . "\n";
    
    foreach ($breadcrumbs as $index => $crumb) {
        $position = $index + 1;
        $name = htmlspecialchars($crumb['name'] ?? '');
        $url = htmlspecialchars($crumb['url'] ?? '#');
        
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $name,
            'item' => $url
        ];
        
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">' . "\n";
        echo '<a itemprop="item" href="' . $url . '"><span itemprop="name">' . $name . '</span></a>' . "\n";
        echo '<meta itemprop="position" content="' . $position . '">' . "\n";
        echo '</li>' . "\n";
        
        if ($index < count($breadcrumbs) - 1) {
            echo '<li class="separator">/</li>' . "\n";
        }
    }
    
    echo '</ol>' . "\n";
    echo '</nav>' . "\n";
    
    // Output JSON-LD breadcrumb schema
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}

/**
 * Output proper main content wrapper with semantic HTML5
 * Start your main content with this
 * 
 * @param string $lang 'en' or 'ar'
 * @param string $id Optional: ID for the main element
 * @return void
 */
function seoMainStart($lang = 'en', $id = '') {
    $idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
    $dir = ($lang === 'ar') ? 'rtl' : 'ltr';
    echo '<main' . $idAttr . ' lang="' . htmlspecialchars($lang) . '" dir="' . htmlspecialchars($dir) . '">' . "\n";
}

function seoMainEnd() {
    echo '</main>' . "\n";
}

/**
 * Output FAQ Schema for better SERP appearance
 * 
 * @param array $faqs: [
 *     [
 *         'question' => 'How to book?',
 *         'answer' => 'Click on a service...'
 *     ],
 *     ...
 * ]
 * @return void
 */
function seoFAQSchema($faqs) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];
    
    foreach ($faqs as $faq) {
        $schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['question'] ?? '',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer'] ?? ''
            ]
        ];
    }
    
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}

/**
 * Output article/blog post metadata
 * Use for blog articles and detailed guides
 * 
 * @param array $articleData: [
 *     'headline' => 'Article Title',
 *     'description' => 'Article summary',
 *     'image' => 'article-image.jpg',
 *     'datePublished' => '2024-01-15',
 *     'author' => 'Author Name',
 *     'wordCount' => 1500
 * ]
 * @return void
 */
function seoArticleSchema($articleData) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $articleData['headline'] ?? 'Article',
        'description' => $articleData['description'] ?? '',
        'image' => $articleData['image'] ?? '',
        'datePublished' => $articleData['datePublished'] ?? date('Y-m-d'),
        'author' => [
            '@type' => 'Organization',
            'name' => $articleData['author'] ?? 'FLIX'
        ],
        'wordCount' => intval($articleData['wordCount'] ?? 0)
    ];
    
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}

?>
