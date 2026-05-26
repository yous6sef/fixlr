<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') { header('Location: login.php'); exit(); }
include('lang.php');
$lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'طلب جديد' : 'New Request'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'طلب خدمة جديد' : 'New Service Request'; ?></h1>
        </div>

        <div class="card">
            <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'نوع الخدمة' : 'Service Type'; ?>
                    </label>
                    <select name="service_type" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                        <option value=""><?php echo $lang === 'ar' ? 'اختر خدمة' : 'Select service'; ?></option>
                        <option value="plumbing"><?php echo $lang === 'ar' ? 'أنابيب' : 'Plumbing'; ?></option>
                        <option value="electrical"><?php echo $lang === 'ar' ? 'كهرباء' : 'Electrical'; ?></option>
                        <option value="cleaning"><?php echo $lang === 'ar' ? 'تنظيف' : 'Cleaning'; ?></option>
                        <option value="maintenance"><?php echo $lang === 'ar' ? 'صيانة' : 'Maintenance'; ?></option>
                    </select>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?>
                    </label>
                    <textarea name="description" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'الاستعجالية' : 'Urgency'; ?>
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="urgency" value="normal" checked> 
                            <span><?php echo $lang === 'ar' ? 'عادي' : 'Normal'; ?></span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="urgency" value="urgent"> 
                            <span><?php echo $lang === 'ar' ? 'عاجل' : 'Urgent'; ?></span>
                        </label>
                    </div>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'العنوان' : 'Address'; ?>
                    </label>
                    <input type="text" name="address" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'إنشاء الطلب' : 'Create Request'; ?></button>
                <a href="user_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-secondary" style="text-align: center;"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
            </form>
        </div>
    </div>
</body>
</html>
