<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') { header('Location: pages/user/login.php'); exit(); }
include('core/lang.php');
$lang = $_GET['lang'] ?? 'en';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type = $_POST['service_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $urgency = $_POST['urgency'] ?? 'normal';
    $address = $_POST['address'] ?? '';
    $google_maps_link = $_POST['google_maps_link'] ?? '';
    $address_description = $_POST['address_description'] ?? '';
    $problem_description = $_POST['problem_description'] ?? '';
    $speciality = $_POST['speciality'] ?? '';
    $city = $_POST['city'] ?? '';

    // For now, store the request in session for demo purposes
    $_SESSION['last_request'] = [
        'service_type' => $service_type,
        'description' => $description,
        'urgency' => $urgency,
        'address' => $address,
        'google_maps_link' => $google_maps_link,
        'address_description' => $address_description,
        'problem_description' => $problem_description,
        'speciality' => $speciality,
        'city' => $city,
        'created_at' => date('c')
    ];

    $success = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'طلب جديد' : 'New Request'; ?> - FLIX</title>
    <link rel="stylesheet" href="public/css/app.css">
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
            <?php if ($success):
                $r = $_SESSION['last_request']; ?>
                <div class="alert alert-success">
                    <h3><?php echo $lang === 'ar' ? 'تم إنشاء الطلب' : 'Request Created'; ?></h3>
                    <p><?php echo $lang === 'ar' ? 'تم تسجيل طلبك بنجاح. سيتواصل معك مزود الخدمة قريبًا.' : 'Your request has been recorded. A provider will contact you shortly.'; ?></p>
                    <ul>
                        <li><?php echo $lang === 'ar' ? 'الخدمة:' : 'Service:'; ?> <?php echo htmlspecialchars($r['service_type']); ?></li>
                        <li><?php echo $lang === 'ar' ? 'التخصص:' : 'Speciality:'; ?> <?php echo htmlspecialchars($r['speciality']); ?></li>
                        <li><?php echo $lang === 'ar' ? 'المدينة:' : 'City:'; ?> <?php echo htmlspecialchars($r['city']); ?></li>
                    </ul>
                    <a href="pages/user/user_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-primary"><?php echo $lang === 'ar' ? 'العودة إلى اللوحة' : 'Back to Dashboard'; ?></a>
                </div>
            <?php else: ?>
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

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'رابط خرائط جوجل' : 'Google Maps Link'; ?>
                    </label>
                    <input type="url" name="google_maps_link" placeholder="https://maps.google.com/..." style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'وصف العنوان (تفاصيل الوصول)' : 'Address Description (access details)'; ?>
                    </label>
                    <textarea name="address_description" style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 80px;"></textarea>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'وصف المشكلة' : 'Problem Description'; ?>
                    </label>
                    <textarea name="problem_description" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'التخصص' : 'Speciality'; ?>
                    </label>
                    <select name="speciality" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                        <option value=""><?php echo $lang === 'ar' ? 'اختر تخصص' : 'Select speciality'; ?></option>
                        <option value="Plumbing"><?php echo $lang === 'ar' ? 'أنابيب' : 'Plumbing'; ?></option>
                        <option value="Electrical"><?php echo $lang === 'ar' ? 'كهرباء' : 'Electrical'; ?></option>
                        <option value="Carpentry"><?php echo $lang === 'ar' ? 'نجارة' : 'Carpentry'; ?></option>
                        <option value="Painting"><?php echo $lang === 'ar' ? 'دهان' : 'Painting'; ?></option>
                    </select>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'المدينة' : 'City'; ?>
                    </label>
                    <select name="city" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                        <option value="6th of October City"><?php echo $lang === 'ar' ? '6th of October City' : '6th of October City'; ?></option>
                        <option value="Sheikh Zayed"><?php echo $lang === 'ar' ? 'Sheikh Zayed' : 'Sheikh Zayed'; ?></option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'إنشاء الطلب' : 'Create Request'; ?></button>
                <a href="pages/user/user_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-secondary" style="text-align: center;"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
            </form>
        </div>
    </div>
</body>
</html>
