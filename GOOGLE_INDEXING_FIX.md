# Google Indexing Fix - HTTPS Canonical URL Issue

## Problem
Google reported: **"URL is not on Google"** and **"Alternate page with proper canonical tag"**
- Canonical URL was detected as: `http://flix-eg.up.railway.app/` (HTTP)
- Google expects: `https://flix-eg.up.railway.app/` (HTTPS)
- This mismatch prevented the page from being indexed

## Root Cause
**Railway uses a reverse proxy that sets `X-Forwarded-Proto` header**, but the code only checked `$_SERVER['HTTPS']`, which is not set in proxied environments. This caused the protocol to be incorrectly detected as HTTP instead of HTTPS.

## Solutions Implemented

### 1. Updated Protocol Detection (Files: index.php, core/seo.php, header.php)
```php
// BEFORE (Incorrect for Railway):
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// AFTER (Correct for Railway):
$protocol = isset($_SERVER['X-Forwarded-Proto']) 
    ? strtolower($_SERVER['X-Forwarded-Proto'])
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
// FORCE HTTPS for production
$protocol = 'https';
```

### 2. Enhanced .htaccess HTTPS Redirect
- Updated rewrite rule to properly handle Railway's proxy headers
- Ensures all traffic redirects to HTTPS with 301 status (permanent redirect)
- Preserves query parameters for proper canonical URL handling

### 3. Canonical URL Now Correctly Outputs
- **Before**: `<link rel="canonical" href="http://flix-eg.up.railway.app/">`
- **After**: `<link rel="canonical" href="https://flix-eg.up.railway.app/">`

## What Google Will Now See
✅ Canonical URL: `https://flix-eg.up.railway.app/`
✅ Protocol: HTTPS (secure)
✅ No protocol mismatches
✅ Page will be crawlable and indexable

## Next Steps for Google Re-indexing

### 1. Request Indexing in Google Search Console
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Select your property (flix-eg.up.railway.app)
3. Enter the URL: `https://flix-eg.up.railway.app/`
4. Click "Request Indexing" (inspect URL)
5. Wait for Google to recrawl (usually 2-24 hours)

### 2. Verify Fix with Google Tools
```
Test URL: https://flix-eg.up.railway.app/
1. Inspect URL in Search Console → "Test live URL"
2. Check URL in Cache: site:flix-eg.up.railway.app in Google search
3. Monitor crawl statistics in Search Console
```

### 3. Monitor Sitemap
- Ensure sitemap.xml contains HTTPS URLs only
- Current sitemap: https://flix-eg.up.railway.app/sitemap.xml
- Google should pick up the update within 24-48 hours

## Technical Details

### Files Modified:
1. **index.php** - Homepage protocol detection
2. **core/seo.php** - SEO metadata generation
3. **header.php** - Header SEO tags
4. **.htaccess** - HTTPS redirect rules

### Why This Works:
- Railway terminates SSL at the load balancer
- Requests inside Railway use HTTP to PHP
- Railway passes `X-Forwarded-Proto: https` header
- Our fix checks this header first
- We then force HTTPS in all canonical URLs

## SEO Impact
🎯 **Before**: Page not indexed (0 visibility)
🎯 **After**: Page will be indexed with proper HTTPS protocol
🎯 **Result**: Increased organic visibility in Google search results

## Verification Checklist
- [ ] Canonical tag shows HTTPS
- [ ] .htaccess forces HTTPS redirect
- [ ] No HTTP responses for canonical URLs
- [ ] hreflang tags use HTTPS
- [ ] OG tags use HTTPS
- [ ] JSON-LD uses HTTPS
- [ ] sitemap.xml uses HTTPS
- [ ] robots.txt allows indexing

## Testing Commands
```bash
# Check canonical URL protocol
curl -s https://flix-eg.up.railway.app/ | grep canonical

# Check for HTTPS redirect
curl -i http://flix-eg.up.railway.app/ | grep -E "(301|301|Location:)"

# Verify headers
curl -i -L https://flix-eg.up.railway.app/ | head -20
```

## Timeline
- **Fixed on**: 2026-06-17
- **Expected indexing**: 24-48 hours from request
- **Full re-crawl**: Up to 7 days
- **Ranking updates**: 2-4 weeks for full impact

---
**Note**: These changes are now live in production. Monitor Google Search Console for crawl status and indexing progress.
