<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'طلباتي' : 'My Requests'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'طلباتي' : 'My Requests'; ?></h1>
        </div>

        <div class="card">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="./request_detail.php?lang=<?php echo $lang; ?>&id=1" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px; text-decoration: none; color: inherit;">
                    <div>
                        <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'أنابيب - إصلاح تسرب' : 'Plumbing - Leak Fix'; ?></div>
                        <div style="color: #8A9389; font-size: 0.875rem;">2024-01-15 • Ahmed Mohamed</div>
                    </div>
                    <span class="badge badge-success"><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></span>
                </a>

                <a href="./request_detail.php?lang=<?php echo $lang; ?>&id=2" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px; text-decoration: none; color: inherit;">
                    <div>
                        <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'كهرباء - تبديل مصابيح' : 'Electrical - Lamp Change'; ?></div>
                        <div style="color: #8A9389; font-size: 0.875rem;">2024-01-14 • Mahmoud Ali</div>
                    </div>
                    <span class="badge badge-active"><?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></span>
                </a>
            </div>
        </div>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
