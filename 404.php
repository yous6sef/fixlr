<?php
http_response_code(404);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$pageTitle = ($lang === 'ar') ? 'خطأ 404 - الصفحة غير موجودة | فليكس' : '404 Not Found - FLIX';
$pageDescription = ($lang === 'ar') ? 'عذرًا، الصفحة التي تبحث عنها غير موجودة. الرجاء العودة إلى الصفحة الرئيسية أو استخدام شريط البحث.' : 'Sorry, the page you are looking for cannot be found. Please return to the homepage or use the search bar.';
$pageKeywords = ($lang === 'ar') ? '404, خطأ, الصفحة غير موجودة, فليكس' : '404, not found, page missing, FLIX';
?>
<!doctype html>
<html lang="<?php echo $lang === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include('core/seo.php'); ?>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f5f5; color: #333; }
        .page-404 { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px; }
        .page-404 .card { max-width: 760px; text-align: center; background: #ffffff; padding: 36px; border-radius: 18px; box-shadow: 0 24px 60px rgba(0,0,0,0.08); }
        .page-404 h1 { margin: 0 0 18px; font-size: 3rem; }
        .page-404 p { margin: 0 0 22px; font-size: 1.05rem; line-height: 1.6; }
        .page-404 a { color: #1a6b4a; text-decoration: none; font-weight: 700; }
        .page-404 a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page-404">
        <div class="card">
            <h1><?php echo $lang === 'ar' ? 'خطأ 404' : '404 Not Found'; ?></h1>
            <p><?php echo htmlspecialchars($pageDescription); ?></p>
            <p><a href="/"><?php echo $lang === 'ar' ? 'العودة إلى الصفحة الرئيسية' : 'Return to the homepage'; ?></a></p>
        </div>
    </div>
</body>
</html>
