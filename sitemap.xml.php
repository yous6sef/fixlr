<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$pages = [
    '/',
    '/pages/user/login.php',
    '/pages/user/signup.php',
    '/pages/user/order.php',
    '/pages/user/user_dashboard.php',
    '/pages/user/track.php',
    '/pages/user/profile.php',
    '/pages/user/user_requests.php',
    '/pages/user/receipt.php',
    '/pages/user/payments.php',
    '/pages/user/request_detail.php',
    '/pages/user/new_request.php',
    '/pages/worker/worker_dashboard.php',
    '/pages/worker/worker_available_requests.php',
    '/pages/worker/worker_orders.php',
    '/pages/worker/worker_profile.php',
    '/pages/worker/worker_payments.php',
    '/pages/worker/worker_receipt.php',
    '/pages/worker/worker_track.php',
    '/pages/user/login.php?lang=en',
    '/pages/user/login.php?lang=ar',
];

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>\n';
?>
<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $page): ?>
    <url>
        <loc><?php echo htmlspecialchars(rtrim($baseUrl, '/') . $page); ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>
</urlset>
