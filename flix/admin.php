<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') { header('Location: login.php'); exit(); }
include('lang.php');
$lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'لوحة الإدارة' : 'Admin Panel'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="admin.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="admin.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'لوحة الإدارة' : 'Admin Panel'; ?></h1>
            <p><?php echo $lang === 'ar' ? 'نظرة عامة على النظام' : 'System Overview'; ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">234</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'المستخدمون' : 'Users'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">87</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'العاملون' : 'Workers'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">45</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'قيد الانتظار' : 'Pending'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">23</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'اليوم' : 'Today'; ?></div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الطلبات الأخيرة' : 'Recent Requests'; ?></h3>
            <div class="provider-card">
                <div class="provider-avatar">R1</div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $lang === 'ar' ? 'أنابيب' : 'Plumbing'; ?></div>
                    <div class="provider-role"><?php echo $lang === 'ar' ? 'فاطمة أحمد' : 'Fatima Ahmed'; ?></div>
                </div>
                <div style="text-align: right;">
                    <div class="badge badge-success"><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></div>
                </div>
            </div>

            <div class="provider-card">
                <div class="provider-avatar" style="background: #FEF3E2; color: #9A6400;">R2</div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $lang === 'ar' ? 'كهرباء' : 'Electrical'; ?></div>
                    <div class="provider-role"><?php echo $lang === 'ar' ? 'محمود علي' : 'Mahmoud Ali'; ?></div>
                </div>
                <div style="text-align: right;">
                    <div class="badge badge-active"><?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الإدارة' : 'Management'; ?></h3>
            <a href="logout.php" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Sign Out'; ?></a>
        </div>
    </div>
</body>
</html>
