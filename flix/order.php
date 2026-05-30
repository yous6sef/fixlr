<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') { header('Location: pages/user/login.php'); exit(); }
include('core/lang.php');
$lang = $_GET['lang'] ?? 'en';
$success = false;
$taskId = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type = trim($_POST['service_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $urgency = trim($_POST['urgency'] ?? 'Normal');
    $address = trim($_POST['address'] ?? '');
    $google_maps_link = trim($_POST['google_maps_link'] ?? '');
    $address_description = trim($_POST['address_description'] ?? '');
    $problem_description = trim($_POST['problem_description'] ?? '');
    $speciality = trim($_POST['speciality'] ?? '');
    $city = trim($_POST['city'] ?? '');

    // Validate required fields
    if (empty($service_type)) $errors[] = $lang === 'ar' ? 'نوع الخدمة مطلوب' : 'Service type is required';
    if (empty($description)) $errors[] = $lang === 'ar' ? 'الوصف مطلوب' : 'Description is required';
    if (empty($address)) $errors[] = $lang === 'ar' ? 'العنوان مطلوب' : 'Address is required';
    if (empty($problem_description)) $errors[] = $lang === 'ar' ? 'وصف المشكلة مطلوب' : 'Problem description is required';
    if (empty($speciality)) $errors[] = $lang === 'ar' ? 'التخصص مطلوب' : 'Speciality is required';
    if (empty($city)) $errors[] = $lang === 'ar' ? 'المدينة مطلوبة' : 'City is required';

    // Validate Google Maps link if provided
    if (!empty($google_maps_link)) {
        if (!filter_var($google_maps_link, FILTER_VALIDATE_URL)) {
            $errors[] = $lang === 'ar' ? 'رابط خرائط غير صالح' : 'Invalid Google Maps link';
        } else {
            $host = parse_url($google_maps_link, PHP_URL_HOST) ?: '';
            // allow google domains and goo.gl short links
            if (!preg_match('/(google\.|maps\.app\.goo\.gl|goo\.gl)/i', $host)) {
                $errors[] = $lang === 'ar' ? 'رابط خرائط غير معتمد' : 'Unsupported map link host';
            }
        }
    }

    if (empty($errors)) {
        try {
            require_once('core/db.php');

            $statusHistory = json_encode([['status' => 'REQUESTED', 'timestamp' => date('c')]]);
            $query = "INSERT INTO tasks (
                userId, city, service_type, specialization, description,
                currentStatus, urgency, address, googleMapsLink,
                addressDescription, problemDescription, statusHistory, createdAt, updatedAt
            ) VALUES (
                :userId, :city, :service_type, :specialization, :description,
                'REQUESTED', :urgency, :address, :googleMapsLink,
                :addressDescription, :problemDescription, :statusHistory, NOW(), NOW()
            )";

            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':userId' => $_SESSION['user_id'],
                ':city' => $city,
                ':service_type' => $service_type,
                ':specialization' => $speciality,
                ':description' => $description,
                ':urgency' => $urgency,
                ':address' => $address,
                ':googleMapsLink' => $google_maps_link,
                ':addressDescription' => $address_description,
                ':problemDescription' => $problem_description,
                ':statusHistory' => $statusHistory
            ]);

            if ($result) {
                $taskId = $conn->lastInsertId('tasks_id_seq') ?: $conn->lastInsertId();
                $success = true;
                $_SESSION['last_task'] = [
                    'id' => $taskId,
                    'service_type' => $service_type,
                    'speciality' => $speciality,
                    'city' => $city,
                    'status' => 'REQUESTED'
                ];
            }
        } catch (PDOException $e) {
            error_log('Task creation error: ' . $e->getMessage());
            $errors[] = $lang === 'ar' ? 'خطأ في حفظ الطلب' : 'Error saving task';
        }
    }
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
                $t = $_SESSION['last_task']; ?>
                <div class="alert alert-success" style="background: #E8F5EE; border: 1px solid #D1FAE5; border-radius: 8px; padding: 1.5rem; text-align: center;">
                    <h3 style="color: #1A6B4A; margin-bottom: 1rem;"><?php echo $lang === 'ar' ? '✓ تم إنشاء الطلب بنجاح' : '✓ Request Created Successfully'; ?></h3>
                    <p style="color: #4A5249; margin-bottom: 1rem;"><?php echo $lang === 'ar' ? 'سيتم البحث عن عمال مناسبين وسيتواصل معك قريبًا.' : 'Suitable workers will be searched and you will be contacted soon.'; ?></p>
                    <ul style="text-align: left; color: #4A5249; margin-bottom: 1rem;">
                        <li><strong><?php echo $lang === 'ar' ? 'رقم الطلب:' : 'Task ID:'; ?></strong> #<?php echo htmlspecialchars($t['id']); ?></li>
                        <li><strong><?php echo $lang === 'ar' ? 'التخصص:' : 'Speciality:'; ?></strong> <?php echo htmlspecialchars($t['speciality']); ?></li>
                        <li><strong><?php echo $lang === 'ar' ? 'المدينة:' : 'City:'; ?></strong> <?php echo htmlspecialchars($t['city']); ?></li>
                        <li><strong><?php echo $lang === 'ar' ? 'الحالة:' : 'Status:'; ?></strong> <?php echo htmlspecialchars($t['status']); ?></li>
                    </ul>
                    <a href="pages/user/user_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-primary" style="display: inline-block; padding: 0.75rem 1.5rem;"><?php echo $lang === 'ar' ? 'العودة إلى اللوحة' : 'Back to Dashboard'; ?></a>
                </div>
            <?php elseif (!empty($errors)): ?>
                <div class="alert alert-error" style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                    <ul style="color: #DC2626; list-style: none; padding: 0;">
                        <?php foreach ($errors as $err): ?>
                            <li style="padding: 0.25rem 0;">• <?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'نوع الخدمة' : 'Service Type'; ?>
                    </label>
                    <select name="service_type" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                        <option value=""><?php echo $lang === 'ar' ? 'اختر خدمة' : 'Select service'; ?></option>
                        <option value="plumbing" <?php echo $service_type === 'plumbing' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'أنابيب' : 'Plumbing'; ?></option>
                        <option value="electrical" <?php echo $service_type === 'electrical' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'كهرباء' : 'Electrical'; ?></option>
                        <option value="cleaning" <?php echo $service_type === 'cleaning' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'تنظيف' : 'Cleaning'; ?></option>
                        <option value="maintenance" <?php echo $service_type === 'maintenance' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'صيانة' : 'Maintenance'; ?></option>
                    </select>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?>
                    </label>
                    <textarea name="description" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 120px;"><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'الاستعجالية' : 'Urgency'; ?>
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="urgency" value="Normal" <?php echo $urgency === 'Normal' ? 'checked' : ''; ?>> 
                            <span><?php echo $lang === 'ar' ? 'عادي' : 'Normal'; ?></span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="urgency" value="Urgent" <?php echo $urgency === 'Urgent' ? 'checked' : ''; ?>> 
                            <span><?php echo $lang === 'ar' ? 'عاجل' : 'Urgent'; ?></span>
                        </label>
                    </div>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'العنوان' : 'Address'; ?>
                    </label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'رابط خرائط جوجل' : 'Google Maps Link'; ?>
                    </label>
                    <input type="url" name="google_maps_link" value="<?php echo htmlspecialchars($google_maps_link); ?>" placeholder="https://maps.google.com/..." style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'وصف العنوان (تفاصيل الوصول)' : 'Address Description (access details)'; ?>
                    </label>
                    <textarea name="address_description" style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 80px;"><?php echo htmlspecialchars($address_description); ?></textarea>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'وصف المشكلة' : 'Problem Description'; ?>
                    </label>
                    <textarea name="problem_description" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 120px;"><?php echo htmlspecialchars($problem_description); ?></textarea>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'التخصص' : 'Speciality'; ?>
                    </label>
                    <select name="speciality" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                        <option value=""><?php echo $lang === 'ar' ? 'اختر تخصص' : 'Select speciality'; ?></option>
                        <option value="Plumbing" <?php echo $speciality === 'Plumbing' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'أنابيب' : 'Plumbing'; ?></option>
                        <option value="Electrical" <?php echo $speciality === 'Electrical' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'كهرباء' : 'Electrical'; ?></option>
                        <option value="Carpentry" <?php echo $speciality === 'Carpentry' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'نجارة' : 'Carpentry'; ?></option>
                        <option value="Painting" <?php echo $speciality === 'Painting' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'دهان' : 'Painting'; ?></option>
                    </select>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'المدينة' : 'City'; ?>
                    </label>
                    <select name="city" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                        <option value="6th of October City" <?php echo $city === '6th of October City' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? '6th of October City' : '6th of October City'; ?></option>
                        <option value="Sheikh Zayed" <?php echo $city === 'Sheikh Zayed' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'Sheikh Zayed' : 'Sheikh Zayed'; ?></option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'إنشاء الطلب' : 'Create Request'; ?></button>
                <a href="pages/user/user_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-secondary" style="text-align: center;"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
