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
    <title><?php echo $lang === 'ar' ? 'الملف الشخصي' : 'Profile'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'الملف الشخصي' : 'My Profile'; ?></h1>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'معلومات الحساب' : 'Account Information'; ?></h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?>
                    </label>
                    <div style="color: #141714; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                </div>
                <div>
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'البريد' : 'Email'; ?>
                    </label>
                    <div style="color: #141714; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                </div>
                <div>
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'النوع' : 'Type'; ?>
                    </label>
                    <div style="color: #141714; font-weight: 600; text-transform: capitalize;"><?php echo ucfirst($_SESSION['user_type']); ?></div>
                </div>
                <div>
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'تاريخ التسجيل' : 'Joined'; ?>
                    </label>
                    <div style="color: #141714; font-weight: 600;">2024-01-01</div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-secondary"><?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Sign Out'; ?></a>
        </div>
    </div>
</body>
</html>
