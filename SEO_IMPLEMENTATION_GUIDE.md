# FLIX SEO & GEO Implementation Guide
## Production-Ready Technical SEO Setup for Local Service Marketplace

---

## 📋 Overview of New SEO Files

### 1. **core/seo-advanced.php** - Dynamic Bilingual Meta Tags & Structured Data
- Generates page-specific titles, descriptions, and keywords
- Outputs Open Graph and Twitter Card tags
- Generates Service/LocalBusiness/Organization JSON-LD schemas
- Supports 4 page types: `home`, `service`, `craftsman`, `search`
- **Multi-language support:** English & Arabic with `hreflang` tags

### 2. **core/semantic-seo.php** - HTML5 Semantic Utilities
- Helper functions for proper heading structure
- Service card output with embedded schema
- Worker/craftsman profile cards with ratings
- Breadcrumb navigation with Schema.org markup
- FAQ and Article schemas

### 3. **sitemap.php** - Automated XML Sitemap Generator
- Queries your database for services, cities, and craftsmen
- Generates dynamic XML sitemap with priorities
- Caches for 24 hours
- Includes mobile markup

---

## 🚀 Implementation Steps

### Step 1: Update Your Header Files

**In your `header.php` or at the top of `<head>` in every page template:**

```php
<?php
// Set these BEFORE including seo-advanced.php
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$pageType = 'home'; // or 'service', 'craftsman', 'search'

// For service pages, add:
// $serviceId = $_GET['service_id'] ?? 1;
// $cityId = $_GET['city_id'] ?? 1;
// Query your database:
// $service = $pdo->query("SELECT * FROM services WHERE id = ?");
// $serviceName = $service['name_' . $lang];

// For craftsman pages, add:
// $craftsmanId = $_GET['worker_id'] ?? 1;
// Query your database:
// $craftsman = $pdo->query("SELECT * FROM workers WHERE id = ?");
// $craftsmanName = $craftsman['name'];
// $craftsmanRating = $craftsman['rating'];
// $craftsmanReviewCount = $craftsman['reviews'];

include('core/seo-advanced.php');
?>
```

### Step 2: Update Your Sitemap Reference

**In your `header.php` or `<head>`:**

```html
<!-- Add this line alongside your existing sitemap references -->
<link rel="sitemap" type="application/xml" href="/sitemap.php" />
```

### Step 3: Use Semantic HTML in Your Templates

**For Service Listing Pages:**

```php
<?php include('core/semantic-seo.php'); ?>

<?php seoMainStart($lang); ?>

    <?php seoPageTitle('Available Services', $lang); ?>
    
    <section class="services-grid">
        <?php seoSectionHeading('Plumbing Services', 'plumbing-section', $lang); ?>
        
        <?php
        $services = $pdo->query("SELECT * FROM services WHERE is_active = 1");
        foreach ($services as $service):
            seoServiceCard([
                'name' => $service['name_' . $lang],
                'description' => $service['description_' . $lang],
                'icon' => $service['icon_url'],
                'url' => '/services/' . $service['id'] . '?lang=' . $lang,
                'rating' => $service['avg_rating'],
                'reviews' => $service['total_reviews'],
                'price_min' => $service['price_min'],
                'price_max' => $service['price_max'],
                'currency' => 'EGP',
                'availability' => $service['is_available']
            ]);
        endforeach;
        ?>
    </section>

<?php seoMainEnd(); ?>
```

**For Worker/Craftsman Profile Pages:**

```php
<?php
$workerId = $_GET['id'] ?? 1;
$worker = $pdo->query("SELECT * FROM workers WHERE id = ?", [$workerId])->fetch();
$pageType = 'craftsman';
$craftsmanName = $worker['name'];
$craftsmanRating = $worker['rating'];
$craftsmanReviewCount = $worker['reviews'];
$craftsmanSpecialty = $worker['specialty_' . $lang];

include('core/seo-advanced.php');
include('core/semantic-seo.php');
?>

<?php seoMainStart($lang); ?>

    <?php
    seoBreadcrumbs([
        ['name' => $lang === 'ar' ? 'الرئيسية' : 'Home', 'url' => '/'],
        ['name' => $lang === 'ar' ? 'الفنيين' : 'Professionals', 'url' => '/professionals'],
        ['name' => $worker['name'], 'url' => '#']
    ], $lang);
    ?>
    
    <?php seoWorkerCard([
        'id' => $worker['id'],
        'name' => $worker['name'],
        'specialty' => $worker['specialty_' . $lang],
        'image' => $worker['profile_image'],
        'rating' => $worker['rating'],
        'reviews' => $worker['reviews'],
        'phone' => $worker['phone'],
        'city' => $worker['city_' . $lang],
        'verified' => $worker['is_verified'],
        'description' => $worker['bio_' . $lang]
    ]); ?>

<?php seoMainEnd(); ?>
```

### Step 4: Configure Database Connection for Sitemap

**In `.env` or your configuration file:**

```env
DB_HOST=localhost
DB_NAME=flix_db
DB_USER=root
DB_PASS=password
DB_PORT=3306
```

**Or update the constants in `sitemap.php` directly.**

---

## 📊 Database Schema Requirements

### Services Table
```sql
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_en VARCHAR(255),
    name_ar VARCHAR(255),
    description_en TEXT,
    description_ar TEXT,
    slug_en VARCHAR(255),
    slug_ar VARCHAR(255),
    icon_url VARCHAR(255),
    price_min INT,
    price_max INT,
    avg_rating DECIMAL(2,1),
    total_reviews INT,
    category_order INT,
    is_active BOOLEAN,
    is_available BOOLEAN
);
```

### Cities Table
```sql
CREATE TABLE cities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_en VARCHAR(255),
    name_ar VARCHAR(255),
    slug_en VARCHAR(255),
    slug_ar VARCHAR(255),
    is_active BOOLEAN
);
```

### Workers Table
```sql
CREATE TABLE workers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    bio_en TEXT,
    bio_ar TEXT,
    specialty_en VARCHAR(255),
    specialty_ar VARCHAR(255),
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    city_en VARCHAR(100),
    city_ar VARCHAR(100),
    rating DECIMAL(2,1),
    reviews INT,
    is_verified BOOLEAN,
    is_active BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔑 Key Page Type Configuration

### Homepage (`$pageType = 'home'`)
- Targets general marketplace terms
- No specific service/city targeting
- Generates Organization + WebSite schema
- Example title: "FLIX | منصة الخدمات المنزلية الموثوقة"

### Service Pages (`$pageType = 'service'`)
- Requires: `$serviceId`, `$cityId`, `$serviceName`, `$cityName`, `$cityNameAr`
- Generates Service + LocalBusiness schema
- Example title: "سباك في القاهرة | احجز أفضل فني السباكة"
- Used by Google for local service snippets

### Craftsman Profiles (`$pageType = 'craftsman'`)
- Requires: `$craftsmanId`, `$craftsmanName`, `$craftsmanRating`, `$craftsmanReviewCount`
- Generates LocalBusiness with AggregateRating
- Shows ⭐ rating in Google results
- Example title: "أحمد - فني سباكة محترف في القاهرة"

### Search Results (`$pageType = 'search'`)
- Automatically pulls `$_GET['q']` as search query
- Used for search results page SEO

---

## 🎯 Expected Google Rich Results

### Service Page Example
```
🔗 Plumbing Services in Cairo | FLIX
⭐ 4.8 (2500 reviews)
📍 Cairo, Egypt
EGP 250 - EGP 1,000
Find trusted plumbers in Cairo on FLIX. Quality service, fair pricing.
```

### Worker Profile Example
```
🔗 Ahmed - Professional Plumber | FLIX
⭐ 4.8 (152 reviews)
📍 Cairo, Egypt
📞 Call Now
Verified professional plumber with 10+ years experience
```

---

## 📱 AI Search Engine Optimization

### ChatGPT/Perplexity Optimization
These AI engines prefer:
1. **Clear semantic HTML structure** ✅ (covered by semantic-seo.php)
2. **Structured data (Schema.org)** ✅ (JSON-LD in seo-advanced.php)
3. **Bilingual content** ✅ (Arabic + English support)
4. **Proper heading hierarchy** ✅ (H1, H2, H3 functions)

### Example AI Response
When a user asks "Find me a plumber in Cairo", ChatGPT can now:
- Parse your structured data for service details
- Pull rating information from AggregateRating schema
- Understand service availability
- Reference your phone number from ContactPoint schema

---

## 🔗 Sitemap Usage

### Access the Sitemap
```
https://flix-eg.up.railway.app/sitemap.php
```

### Register in Google Search Console
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Select your property
3. Navigate to **Sitemaps** → **New Sitemap**
4. Enter: `https://flix-eg.up.railway.app/sitemap.php`
5. Click **Submit**

### Sitemap Auto-Caching
- Cached for 24 hours (set in header)
- Add to cron job for daily regeneration (optional):
```bash
0 2 * * * curl https://flix-eg.up.railway.app/sitemap.php > /dev/null 2>&1
```

---

## 📈 SEO Ranking Factors This Improves

| Factor | Improvement |
|--------|-------------|
| **Title Tag Optimization** | 🟢 Service + Location in title |
| **Structured Data** | 🟢 6+ schema types |
| **Mobile Optimization** | 🟢 Marked with `<mobile:mobile/>` |
| **Hreflang Tags** | 🟢 Bilingual SEO |
| **Star Ratings** | 🟢 AggregateRating schema |
| **Rich Snippets** | 🟢 Service details in SERP |
| **Crawlability** | 🟢 Semantic HTML + Sitemap |
| **AI Indexing** | 🟢 ChatGPT-friendly markup |

---

## 🚨 Common Implementation Issues

### Issue: Titles Still Generic After Update
**Solution:** Ensure `$pageType` is set BEFORE including `seo-advanced.php`

### Issue: Schema Not Showing in Google
**Solution:** 
1. Wait 24-48 hours for re-crawl
2. Test in [Google's Rich Results Test](https://search.google.com/test/rich-results)
3. Request indexing in Search Console

### Issue: Sitemap Returns Blank
**Solution:**
1. Check database connection in `sitemap.php`
2. Verify `services` and `cities` tables exist
3. Ensure database queries return results

### Issue: Bilingual Content Duplicated in SERP
**Solution:**
- Ensure `hreflang` tags are present (auto-generated by seo-advanced.php)
- Verify language parameter is correct: `?lang=en` or `?lang=ar`

---

## ✅ Verification Checklist

- [ ] `seo-advanced.php` included in all page `<head>`
- [ ] `$pageType` and `$lang` set before SEO include
- [ ] Database variables mapped for services/craftsmen
- [ ] `sitemap.php` accessible and returning XML
- [ ] Breadcrumbs using `seoBreadcrumbs()` function
- [ ] Worker cards using `seoWorkerCard()` markup
- [ ] Main content wrapped with `seoMainStart()` / `seoMainEnd()`
- [ ] Headers using proper H1/H2/H3 hierarchy
- [ ] Open Graph tags rendering in social shares
- [ ] Schema.org JSON-LD validated in Rich Results Test

---

## 📞 Support Hints

**For database queries, use this pattern:**

```php
$serviceName = $service['name_' . $lang]; // Dynamically use English/Arabic column
$cityName = $city['name_' . $lang];
$craftsmanName = $worker['name']; // Same in both languages
```

**To test JSON-LD schemas:**
- Google: https://search.google.com/test/rich-results
- Schema.org: https://validator.schema.org/

---

Generated for FLIX | فليكس - Production Grade SEO System
