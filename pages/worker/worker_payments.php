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
    <title><?php echo $lang === 'ar' ? 'الأرباح' : 'Earnings'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'الأرباح' : 'My Earnings'; ?></h1>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'ملخص الأرباح' : 'Earnings Summary'; ?></h3>
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-value">12,400</div>
                    <div class="stat-label"><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">8,600</div>
                    <div class="stat-label"><?php echo $lang === 'ar' ? 'المسحوب' : 'Withdrawn'; ?></div>
                </div>
            </div>

            <h3><?php echo $lang === 'ar' ? 'السحب الأخير' : 'Recent Withdrawals'; ?></h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px;">
                    <div>
                        <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'السحب #1' : 'Withdrawal #1'; ?></div>
                        <div style="color: #8A9389; font-size: 0.875rem;">2024-01-15</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #1A6B4A; font-weight: 600;">2,400 EGP</div>
                        <span class="badge badge-success"><?php echo $lang === 'ar' ? 'نجح' : 'Success'; ?></span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px;">
                    <div>
                        <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'السحب #2' : 'Withdrawal #2'; ?></div>
                        <div style="color: #8A9389; font-size: 0.875rem;">2024-01-10</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #1A6B4A; font-weight: 600;">2,000 EGP</div>
                        <span class="badge badge-success"><?php echo $lang === 'ar' ? 'نجح' : 'Success'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
