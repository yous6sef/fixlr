<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
$request_id = $_GET['id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Request Details'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en&id=<?php echo $request_id; ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar&id=<?php echo $request_id; ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Request Details'; ?></h1>
            <p><?php echo $lang === 'ar' ? 'رقم الطلب: #' . $request_id : 'Request ID: #' . $request_id; ?></p>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'معلومات الخدمة' : 'Service Information'; ?></h3>
            <div style="margin-bottom: 2rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'نوع الخدمة' : 'Service Type'; ?>
                    </label>
                    <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'أنابيب' : 'Plumbing'; ?></div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?>
                    </label>
                    <div style="color: #141714;"><?php echo $lang === 'ar' ? 'إصلاح تسرب مياه في المطبخ' : 'Fix water leak in kitchen'; ?></div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'السعر' : 'Price'; ?>
                    </label>
                    <div style="color: #1A6B4A; font-weight: 600; font-size: 1.25rem;">800 EGP</div>
                </div>
            </div>

            <h3><?php echo $lang === 'ar' ? 'العامل' : 'Worker'; ?></h3>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #F7F8F6; border-radius: 8px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #E8F5EE; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1A6B4A;">AM</div>
                <div>
                    <div style="color: #141714; font-weight: 600;">Ahmed Mohamed</div>
                    <div style="color: #8A9389; font-size: 0.875rem;">4.8 Rating • 45 Jobs</div>
                </div>
            </div>
        </div>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
