<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'worker') { header('Location: login.php'); exit(); }
include('lang.php');
$lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'طلباتي' : 'My Jobs'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'طلباتي' : 'My Jobs'; ?></h1>
        </div>

        <div class="card">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="provider-card">
                    <div class="provider-avatar">J1</div>
                    <div class="provider-info">
                        <div class="provider-name"><?php echo $lang === 'ar' ? 'أنابيب - تسرب' : 'Plumbing - Leak'; ?></div>
                        <div class="provider-role"><?php echo $lang === 'ar' ? 'فاطمة أحمد' : 'Fatima Ahmed'; ?></div>
                    </div>
                    <span class="badge badge-active"><?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></span>
                </div>

                <div class="provider-card">
                    <div class="provider-avatar" style="background: #FEF3E2; color: #9A6400;">J2</div>
                    <div class="provider-info">
                        <div class="provider-name"><?php echo $lang === 'ar' ? 'كهرباء - تبديل' : 'Electrical - Change'; ?></div>
                        <div class="provider-role"><?php echo $lang === 'ar' ? 'محمود علي' : 'Mahmoud Ali'; ?></div>
                    </div>
                    <span class="badge badge-success"><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></span>
                </div>
            </div>
        </div>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
