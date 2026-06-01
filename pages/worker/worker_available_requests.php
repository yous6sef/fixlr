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
    <title><?php echo $lang === 'ar' ? 'فرص متاحة' : 'Available Opportunities'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'الفرص المتاحة' : 'Available Opportunities'; ?></h1>
        </div>

        <div class="card">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="provider-card">
                    <div class="provider-avatar">R1</div>
                    <div class="provider-info">
                        <div class="provider-name"><?php echo $lang === 'ar' ? 'أنابيب - تسرب مياه' : 'Plumbing - Water Leak'; ?></div>
                        <div class="provider-role"><?php echo $lang === 'ar' ? 'حي النيل • الآن' : 'Nile Area • Now'; ?></div>
                    </div>
                    <div class="provider-price">300 EGP</div>
                </div>

                <button style="background: #1A6B4A; color: white; padding: 0.75rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%;">
                    <?php echo $lang === 'ar' ? 'قبول' : 'Accept'; ?>
                </button>

                <div class="provider-card" style="margin-top: 1rem;">
                    <div class="provider-avatar" style="background: #FEF3E2; color: #9A6400;">R2</div>
                    <div class="provider-info">
                        <div class="provider-name"><?php echo $lang === 'ar' ? 'كهرباء - تبديل مصابيح' : 'Electrical - Lamp Change'; ?></div>
                        <div class="provider-role"><?php echo $lang === 'ar' ? 'وسط البلد • اليوم' : 'Downtown • Today'; ?></div>
                    </div>
                    <div class="provider-price">250 EGP</div>
                </div>

                <button style="background: #1A6B4A; color: white; padding: 0.75rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%;">
                    <?php echo $lang === 'ar' ? 'قبول' : 'Accept'; ?>
                </button>
            </div>
        </div>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
