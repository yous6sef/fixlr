<?php
/**
 * ====================================================================
 * FLIX Advanced SEO Configuration
 * ====================================================================
 * This file contains advanced SEO optimizations including:
 * - Meta tag enhancements
 * - Structured data for rich results
 * - Core Web Vitals optimization hints
 * - Link preloading for performance
 */

// Function to generate FAQs schema (improves SERP appearance)
function generateFAQSchema($lang = 'en') {
    $faqs = $lang === 'ar' ? [
        [
            'question' => 'ما هو فليكس؟',
            'answer' => 'فليكس هو منصة خدمات منزلية موثوقة في مصر تربط العملاء بفنيين محترفين للصيانة والإصلاح.'
        ],
        [
            'question' => 'كيف أحجز خدمة؟',
            'answer' => 'يمكنك تصفح الفنيين المتاحين واختيار من تفضل، ثم حجز الخدمة مباشرة من التطبيق.'
        ],
        [
            'question' => 'هل الخدمة آمنة وموثوقة؟',
            'answer' => 'نعم، جميع الفنيين لدينا معتمدين ومدققين ولديهم تقييمات عالية من العملاء السابقين.'
        ]
    ] : [
        [
            'question' => 'What is FLIX?',
            'answer' => 'FLIX is a trusted home services marketplace in Egypt connecting customers with professional technicians for repairs and maintenance.'
        ],
        [
            'question' => 'How do I book a service?',
            'answer' => 'Browse available technicians, choose your preferred professional, and book the service directly from the app.'
        ],
        [
            'question' => 'Are the services safe and reliable?',
            'answer' => 'Yes, all our technicians are verified, vetted, and have high ratings from previous customers.'
        ]
    ];
    
    $faqArray = [];
    foreach ($faqs as $index => $faq) {
        $faqArray[] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }
    
    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqArray
    ];
}

// Function to generate review schema
function generateReviewSchema($lang = 'en') {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'AggregateRating',
        'ratingValue' => '4.8',
        'ratingCount' => '1500',
        'reviewCount' => '1500',
        'bestRating' => '5',
        'worstRating' => '1'
    ];
}

// Function to add performance hints
function getPerformanceHints() {
    return '
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//www.google-analytics.com">
    <link rel="dns-prefetch" href="//connect.facebook.net">
    <link rel="prefetch" href="/public/images/">
    <link rel="prefetch" href="/public/js/">
    ';
}

// Function to generate breadcrumb schema with multiple levels
function generateAdvancedBreadcrumb($currentPage = 'home', $lang = 'en') {
    $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    
    $pages = [
        'home' => ['label' => $lang === 'ar' ? 'الرئيسية' : 'Home', 'url' => $baseUrl . '/'],
        'services' => ['label' => $lang === 'ar' ? 'الخدمات' : 'Services', 'url' => $baseUrl . '/services/'],
        'about' => ['label' => $lang === 'ar' ? 'حول' : 'About', 'url' => $baseUrl . '/about/'],
        'contact' => ['label' => $lang === 'ar' ? 'تواصل' : 'Contact', 'url' => $baseUrl . '/contact/'],
    ];
    
    $items = [];
    $position = 1;
    
    $items[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => 'FLIX',
        'item' => $baseUrl . '/'
    ];
    
    if ($currentPage !== 'home' && isset($pages[$currentPage])) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $pages[$currentPage]['label'],
            'item' => $pages[$currentPage]['url']
        ];
    }
    
    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items
    ];
}

// Function to add social proof schema
function generateSocialProofSchema() {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => 'FLIX - Home Services',
        'author' => [
            '@type' => 'Organization',
            'name' => 'FLIX Egypt',
            'url' => 'https://flix-eg.up.railway.app'
        ],
        'interactionStatistic' => [
            [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/ReviewAction',
                'userInteractionCount' => '1500'
            ],
            [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/BookmarkAction',
                'userInteractionCount' => '5000'
            ]
        ]
    ];
}

?>
