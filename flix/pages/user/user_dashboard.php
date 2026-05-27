<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
include('lang.php');
$lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
    <style>
        body { padding-bottom: 100px; }
        .header-content { margin-bottom: 1.5rem; }
        .greeting { color: #8A9389; margin-bottom: 0.3rem; }
        .user-name { font-size: 1.5rem; font-weight: 600; }
        @media (max-width: 480px) { .page-container { padding-bottom: 0.5rem; } }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="user_dashboard.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="user_dashboard.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <div class="header-content">
                <div class="greeting"><?php echo $lang === 'ar' ? 'صباح الخير' : 'Good morning'; ?></div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">5</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'الطلبات' : 'Requests'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">3</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'مكتملة' : 'Completed'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">2</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'نشطة' : 'Active'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">1500 EGP</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'المنفق' : 'Spent'; ?></div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الطلبات الأخيرة' : 'Recent Requests'; ?></h3>
            <div class="provider-card">
                <div class="provider-avatar">SM</div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $lang === 'ar' ? 'أنابيب - إصلاح الأنابيب' : 'Plumbing - Pipe Fix'; ?></div>
                    <div class="provider-role"><?php echo $lang === 'ar' ? 'أحمد محمد • 2024-01-15' : 'Ahmed Mohamed • 2024-01-15'; ?></div>
                </div>
                <div class="provider-price">800 EGP</div>
            </div>

            <div class="provider-card">
                <div class="provider-avatar" style="background: #FEF3E2; color: #9A6400;">EM</div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $lang === 'ar' ? 'كهرباء - إصلاح' : 'Electrical - Repair'; ?></div>
                    <div class="provider-role"><?php echo $lang === 'ar' ? 'محمود علي • 2024-01-18' : 'Mahmoud Ali • 2024-01-18'; ?></div>
                </div>
                <div class="provider-price">400 EGP</div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الإجراءات السريعة' : 'Quick Actions'; ?></h3>
            <a href="order.php?lang=<?php echo $lang; ?>" class="btn btn-primary btn-block"><?php echo $lang === 'ar' ? 'طلب جديد' : 'New Request'; ?></a>
            <a href="user_requests.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'عرض الطلبات' : 'View Requests'; ?></a>
            <a href="profile.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'الملف الشخصي' : 'Profile'; ?></a>
            <a href="logout.php" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Sign Out'; ?></a>
        </div>
    </div>
</body>
</html>
