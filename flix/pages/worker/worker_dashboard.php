<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'worker') { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="./worker_dashboard.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="./worker_dashboard.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'مرحبا ' . htmlspecialchars($_SESSION['user_name']) : 'Hi ' . htmlspecialchars($_SESSION['user_name']); ?></h1>
            <p><?php echo $lang === 'ar' ? 'الطلبات المتاحة' : 'Available Opportunities'; ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">12</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'متاح' : 'Available'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">3</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'جاري' : 'Ongoing'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">24</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">4.8</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'التقييم' : 'Rating'; ?></div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الطلبات المتاحة' : 'Available Jobs'; ?></h3>
            <div class="provider-card">
                <div class="provider-avatar" style="background: #E8F5EE;">P1</div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $lang === 'ar' ? 'أنابيب - تسرب مياه' : 'Plumbing - Water Leak'; ?></div>
                    <div class="provider-role"><?php echo $lang === 'ar' ? 'حي النيل • الآن' : 'Nile Area • Now'; ?></div>
                </div>
                <div class="provider-price">300 EGP</div>
            </div>

            <div class="provider-card">
                <div class="provider-avatar" style="background: #FEF3E2; color: #9A6400;">P2</div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $lang === 'ar' ? 'كهرباء - مصباح' : 'Electrical - Lamp'; ?></div>
                    <div class="provider-role"><?php echo $lang === 'ar' ? 'وسط البلد • اليوم' : 'Downtown • Today'; ?></div>
                </div>
                <div class="provider-price">250 EGP</div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الإجراءات' : 'Actions'; ?></h3>
            <a href="./worker_available_requests.php?lang=<?php echo $lang; ?>" class="btn btn-primary btn-block"><?php echo $lang === 'ar' ? 'عرض الفرص' : 'Browse Opportunities'; ?></a>
            <a href="./worker_orders.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'الطلبات المقبولة' : 'My Jobs'; ?></a>
            <a href="./worker_payments.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'الأرباح' : 'Earnings'; ?></a>
            <a href="../user/logout.php" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Sign Out'; ?></a>
        </div>
    </div>
</body>
</html>
