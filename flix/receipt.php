<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
include('lang.php');
$lang = $_GET['lang'] ?? 'en';
$request_id = $_GET['id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'الإيصال' : 'Receipt'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en&id=<?php echo $request_id; ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar&id=<?php echo $request_id; ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #1A6B4A; margin-bottom: 0.5rem;">FLIX</h2>
                <p style="color: #8A9389; font-size: 0.875rem;"><?php echo $lang === 'ar' ? 'إيصال الخدمة' : 'Service Receipt'; ?></p>
            </div>

            <div style="background: #F7F8F6; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="color: #8A9389;"><?php echo $lang === 'ar' ? 'معرف الطلب' : 'Request ID'; ?>:</span>
                    <span style="color: #141714; font-weight: 600;">#<?php echo $request_id; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="color: #8A9389;"><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?>:</span>
                    <span style="color: #141714; font-weight: 600;">2024-01-15</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #8A9389;"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?>:</span>
                    <span class="badge badge-success"><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></span>
                </div>
            </div>

            <h3 style="color: #141714; margin-bottom: 1rem;"><?php echo $lang === 'ar' ? 'التفاصيل' : 'Details'; ?></h3>
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #E5E4E1;">
                    <span style="color: #4A5249;"><?php echo $lang === 'ar' ? 'أنابيب - إصلاح تسرب' : 'Plumbing - Leak Fix'; ?></span>
                    <span style="color: #141714; font-weight: 600;">800 EGP</span>
                </div>
            </div>

            <div style="background: #E8F5EE; padding: 1rem; border-radius: 8px; border: 1px solid #D4E8E0;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #1A6B4A; font-weight: 600;"><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?>:</span>
                    <span style="color: #1A6B4A; font-size: 1.25rem; font-weight: 700;">800 EGP</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
