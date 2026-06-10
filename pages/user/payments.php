<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'إيصالات الدفع وسجل العمليات' : 'Payment Receipts & Transaction History';
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'السجل المالي والفواتير' : 'Payment History & Invoices'; ?></h1>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الملخص' : 'Summary'; ?></h3>
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-value">5,600</div>
                    <div class="stat-label"><?php echo $lang === 'ar' ? 'إجمالي' : 'Total'; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">4,200</div>
                    <div class="stat-label"><?php echo $lang === 'ar' ? 'مدفوع' : 'Paid'; ?></div>
                </div>
            </div>

            <h3><?php echo $lang === 'ar' ? 'المعاملات' : 'Transactions'; ?></h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px;">
                    <div>
                        <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'الدفع #1' : 'Payment #1'; ?></div>
                        <div style="color: #8A9389; font-size: 0.875rem;">2024-01-15</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #1A6B4A; font-weight: 600;">+ 800 EGP</div>
                        <span class="badge badge-success"><?php echo $lang === 'ar' ? 'نجح' : 'Success'; ?></span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px;">
                    <div>
                        <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'الدفع #2' : 'Payment #2'; ?></div>
                        <div style="color: #8A9389; font-size: 0.875rem;">2024-01-14</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #1A6B4A; font-weight: 600;">+ 400 EGP</div>
                        <span class="badge badge-success"><?php echo $lang === 'ar' ? 'نجح' : 'Success'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
