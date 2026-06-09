# FLIX SEO BEST PRACTICES GUIDE
## On-Page, Technical, and Content Optimization Standards

---

## 📝 CONTENT GUIDELINES

### Title Tags (H1)
**Format:** Primary Keyword + Brand + Value Proposition  
**Length:** 50-60 characters  
**Examples:**
- EN: "FLIX - Trusted Home Services Platform | Find Professionals"
- AR: "فليكس - منصة خدمات منزلية موثوقة | ابحث عن العمال"

**Best Practices:**
- Include primary keyword in first 10 characters
- Use power words (Best, Top, Easy, Fast)
- Avoid keyword stuffing
- Make it descriptive and clickable

---

### Meta Descriptions
**Format:** Keyword + Benefit + CTA  
**Length:** 50-160 characters (optimally 155)  
**Examples:**
- EN: "Connect with verified local professionals for home repair, maintenance & services. Fair pricing, quality guaranteed."
- AR: "تواصل مع عمال موثوقين لخدمات المنزل. أسعار عادلة وجودة مضمونة."

**Best Practices:**
- Include target keyword naturally (once)
- Include secondary keyword if possible
- Add a CTA (Learn more, Discover, Join)
- Make it unique for each page
- Avoid duplicate descriptions

---

### Heading Structure (H1, H2, H3)

**Hierarchy Rules:**
```
Page = 1 H1
├── Section = 1 H2
│   ├── Subsection = H3
│   └── Subsection = H3
├── Section = 1 H2
│   ├── Subsection = H3
│   └── Subsection = H3
└── Section = 1 H2
    ├── Subsection = H3
    └── Subsection = H3
```

**Example:**
```html
<h1>Home Services Marketplace | Find Local Professionals</h1>

<h2>Why Choose Our Platform?</h2>
  <h3>Verified Professionals</h3>
  <h3>Transparent Pricing</h3>
  <h3>Quick Response</h3>

<h2>How It Works</h2>
  <h3>Step 1: Create Request</h3>
  <h3>Step 2: Get Offers</h3>
  <h3>Step 3: Connect & Service</h3>
```

---

## 🔗 INTERNAL LINKING STRATEGY

### Link Placement
1. **Header/Navigation** - Links to main sections
2. **Body Content** - Contextual links to related pages
3. **Footer** - Sitewide important links
4. **Sidebar** - Related pages and CTA

### Anchor Text Best Practices
✅ **Good:**
- "service request form"
- "find professionals near you"
- "how our platform works"

❌ **Avoid:**
- "click here"
- "read more"
- Over-optimization with exact keyword

### Linking Targets
- Link to 2-5 relevant pages per page
- Prioritize pages with ranking potential
- Avoid linking to low-quality pages
- Use descriptive anchor text

---

## 📸 IMAGE OPTIMIZATION

### File Naming
```
❌ Bad: image1.jpg, photo.png
✅ Good: professional-plumber-cairo.jpg, electrical-service-repair.png
```

### Alt Text (Always Include)
```
❌ Bad: "image", "photo", "pic"
✅ Good: "Professional electrician fixing wiring in Cairo home"
✅ Good: "Trusted plumber with tools ready for service"
```

### File Size
- **Target:** < 100KB per image
- **Format:** JPG (photos), PNG (graphics), WebP (modern)
- **Dimensions:** 1200x630 for OG image

### Alt Text + Keyword Inclusion
- Primary image: Include target keyword
- Other images: Descriptive, natural language
- Always describe what user sees
- Include context, not just keyword

---

## 📊 KEYWORD IMPLEMENTATION

### Keyword Distribution (Per 1000 words)
- **Target Keyword:** 2-3 mentions (0.2-0.3%)
- **Secondary Keywords:** 1-2 mentions each
- **Related Terms:** 3-5 mentions

### Keyword Placement (Priority Order)
1. Title tag (most important)
2. H1 heading
3. First 100 words
4. Meta description
5. H2 subheadings
6. Scattered naturally throughout
7. Image alt text
8. URL slug (if applicable)

### Avoid:
- Keyword stuffing (> 3% density)
- Exact match keyword in all headers
- Unnatural keyword integration
- Targeting too many keywords (focus on 1-2 primary)

---

## 🎯 SEO-OPTIMIZED PAGE STRUCTURE

### Ideal Page Layout
```
1. Header with navigation
2. Hero section with H1 & call-to-action
3. Brief intro paragraph (100-150 words)
4. Feature section (H2 headers with 3-5 features)
5. How it works section (H2 with H3 steps)
6. Benefits/advantages (H2 with bullets)
7. Case study or testimonials (builds trust)
8. FAQ section (improves engagement, keywords)
9. Clear CTA button
10. Footer with links & contact
```

### Content Length Guidelines
- **Homepage:** 1,500-3,000 words
- **Service Pages:** 1,000-2,000 words
- **Landing Pages:** 800-1,500 words
- **Blog Articles:** 1,500-2,500 words

---

## 📱 MOBILE OPTIMIZATION

### Mobile Checklist
- [ ] Responsive design (mobile-first)
- [ ] Touch-friendly buttons (48x48px minimum)
- [ ] Fast load time (< 3 seconds)
- [ ] Readable font size (14px minimum)
- [ ] Proper spacing between elements
- [ ] No interstitials blocking content
- [ ] Clickable elements properly sized

### Mobile Testing Tools
- Google Mobile-Friendly Test
- Google PageSpeed Insights
- Chrome DevTools (device emulation)
- Responsive Design Checker

---

## 🔍 SCHEMA MARKUP

### Required Schema Types

#### 1. Organization Schema
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "FLIX",
  "url": "https://flix.com",
  "logo": "https://flix.com/logo.png",
  "description": "Trusted home services marketplace"
}
```

#### 2. LocalBusiness Schema
```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "FLIX",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "6th of October City",
    "addressLocality": "Cairo",
    "addressCountry": "EG"
  },
  "telephone": "+20-1001234567",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "2500"
  }
}
```

#### 3. Service Schema
```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Home Services",
  "description": "Professional home repair & maintenance services",
  "areaServed": "Cairo, Egypt",
  "provider": {
    "@type": "LocalBusiness",
    "name": "FLIX"
  }
}
```

#### 4. BreadcrumbList Schema
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://flix.com"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Services",
      "item": "https://flix.com/services"
    }
  ]
}
```

---

## ⚡ PERFORMANCE OPTIMIZATION

### Speed Targets
- **LCP:** < 2.5s
- **FID:** < 100ms
- **CLS:** < 0.1
- **Total Load Time:** < 3s

### Optimization Checklist
- [ ] Enable GZIP compression
- [ ] Minify CSS, JS, HTML
- [ ] Optimize images (WebP format)
- [ ] Lazy load images/videos
- [ ] Use CDN for assets
- [ ] Remove render-blocking scripts
- [ ] Defer non-critical JavaScript
- [ ] Preload critical fonts
- [ ] Cache static assets
- [ ] Reduce server response time

### Tools
- Google PageSpeed Insights
- Google Lighthouse
- WebPageTest
- GTmetrix

---

## 🌍 MULTILINGUAL SEO (EN + AR)

### hreflang Implementation
```html
<link rel="alternate" hreflang="en" href="https://flix.com/page">
<link rel="alternate" hreflang="ar" href="https://flix.com/page?lang=ar">
<link rel="alternate" hreflang="x-default" href="https://flix.com/page">
```

### Best Practices
- Unique meta descriptions for each language
- Translate, don't use machine translation
- Use proper language codes (en, ar)
- Maintain consistent URL structure
- Include hreflang on all pages

---

## ✅ QUALITY CHECKLIST (Before Publishing)

### Technical
- [ ] Mobile-friendly (test with Google)
- [ ] Page speed > 85 (PageSpeed Insights)
- [ ] All links working (no 404s)
- [ ] Schema validation (structured-data.org)
- [ ] Meta tags present and unique
- [ ] Canonical tag set
- [ ] Hreflang tags for multilingual
- [ ] Open Graph tags included

### Content
- [ ] Original content (no plagiarism)
- [ ] Proper heading hierarchy
- [ ] Internal links (3-5 relevant)
- [ ] External links to authorities
- [ ] Images optimized with alt text
- [ ] Content length 800+ words
- [ ] Mobile-friendly formatting
- [ ] CTA clear and visible

### SEO
- [ ] Target keyword in title
- [ ] Target keyword in H1
- [ ] Keyword in first 100 words
- [ ] Keyword naturally distributed
- [ ] Secondary keywords included
- [ ] Descriptive meta description
- [ ] User intent addressed
- [ ] Content is unique & valuable

### User Experience
- [ ] Easy to read (short paragraphs)
- [ ] Bullet points for readability
- [ ] Proper formatting (bold, italic)
- [ ] Clear navigation
- [ ] Mobile-responsive
- [ ] No intrusive ads/pop-ups
- [ ] Fast loading
- [ ] Professional design

---

## 📈 MONITORING AFTER PUBLICATION

### First Week
- Monitor for crawl errors
- Check keyword rankings
- Monitor organic traffic
- Check click-through rate
- Monitor bounce rate

### First Month
- Monitor ranking improvements
- Check for backlinks
- Analyze user behavior
- Check engagement metrics
- Update internal links if needed

### Ongoing
- Monthly ranking checks
- Traffic trend monitoring
- Update content if ranking drops
- Add internal links from new pages
- Monitor for algorithm changes

---

## 🎓 RESOURCES

- [Google Search Central](https://developers.google.com/search)
- [SEO Fundamentals](https://moz.com/beginners-guide-to-seo)
- [Schema.org Documentation](https://schema.org)
- [PageSpeed Insights](https://pagespeed.web.dev)
- [Search Console Help](https://support.google.com/webmasters)

---

**Document Version:** 1.0  
**Last Updated:** June 9, 2026  
**Maintained By:** SEO Team
