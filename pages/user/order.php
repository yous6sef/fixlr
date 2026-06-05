<?php
session_start();
include('../../core/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php'); 
    exit();
}
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
$success = false;
$taskId = null;
$errors = [];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user_name = $stmt->fetch(PDO::FETCH_ASSOC);
$name = $user_name['name'] ?? 'User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialization = trim($_POST['specialization']);
    $address = trim($_POST['address'] ?? '');
    $google_maps_link = trim($_POST['google_maps_link'] ?? '');
    $address_description = trim($_POST['address_description']);
    $problem_description = trim($_POST['problem_description']);
    $city = trim($_POST['city']);
    $budget = trim($_POST['budget']);

    // Validate Google Maps link if provided
    if (!empty($google_maps_link)) {
        if (!filter_var($google_maps_link, FILTER_VALIDATE_URL)) {
            $errors[] = $lang === 'ar' ? 'رابط خرائط غير صالح' : 'Invalid Google Maps link';
        } else {
            $host = parse_url($google_maps_link, PHP_URL_HOST) ?: '';
            if (!preg_match('/(google\.|maps\.app\.goo\.gl|goo\.gl)/i', $host)) {
                $errors[] = $lang === 'ar' ? 'رابط خرائط غير معتمد' : 'Unsupported map link host';
            }
        }
    }

    if (empty($errors)) {
        try {
            $query = "INSERT INTO service_requests (
                user_id, city, specialization, problem_description,
                status, address, google_maps_link,
                address_description, budget, username, created_at, updated_at
            ) VALUES (
                :user_id, :city, :specialization, :problem_description,
                'REQUESTED', :address, :googleMapsLink,
                :addressDescription, :budget, :username, NOW(), NOW()
            ) RETURNING id";

            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':city' => $city,
                ':specialization' => $specialization,
                ':problem_description' => $problem_description,
                ':address' => $address,
                ':googleMapsLink' => $google_maps_link,
                ':addressDescription' => $address_description,
                ':budget' => $budget,
                ':username' => $name
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $taskId = $row['id'];
            $success = true;

            $_SESSION['last_task'] = [
                'id' => $taskId,
                'specialization' => $specialization,
                'city' => $city,
                'status' => 'REQUESTED'
            ];
        } catch (PDOException $e) {
            error_log('Task creation error: ' . $e->getMessage());
            $errors[] = $lang === 'ar' ? 'خطأ في حفظ الطلب' : 'Error saving task';
        }
    }
}

$stmt = $conn->query("SELECT id, name_ar, name_en FROM service_types");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$services_ar = [];
$services_en = [];

foreach ($services as $service) {
    $services_ar[] = $service['name_ar'];
    $services_en[] = $service['name_en'];
}
?>
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'طلب جديد' : 'New Request'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
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
                        <li><strong><?php echo $lang === 'ar' ? 'التخصص:' : 'specialization:'; ?></strong> <?php echo htmlspecialchars($t['specialization']); ?></li>
                        <li><strong><?php echo $lang === 'ar' ? 'المدينة:' : 'City:'; ?></strong> <?php echo htmlspecialchars($t['city']); ?></li>
                        <li><strong><?php echo $lang === 'ar' ? 'الحالة:' : 'Status:'; ?></strong> <?php echo htmlspecialchars($t['status']); ?></li>
                    </ul>
                    <a href="user_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-primary" style="display: inline-block; padding: 0.75rem 1.5rem;"><?php echo $lang === 'ar' ? 'العودة إلى اللوحة' : 'Back to Dashboard'; ?></a>
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
                    <select name="specialization" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;" required>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo htmlspecialchars($service['name_en']); ?>" <?php echo (isset($service_type) && $service_type === $service['name_en']) ? 'selected' : ''; ?> style="font-size: medium;">
                                <?php echo htmlspecialchars($lang === 'ar' ? $service['name_ar'] : $service['name_en']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?>
                    </label>
                    <textarea name="problem_description" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'العنوان' : 'Address'; ?>
                    </label>
                    <input type="text" name="address" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? ' (اختياري) رابط خرائط جوجل' : 'Google Maps Link (optional)'; ?>
                    </label>
                    <input type="url" name="google_maps_link" placeholder="https://maps.google.com/... " style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'وصف العنوان (تفاصيل الوصول)' : 'Address Description (access details)'; ?>
                    </label>
                    <textarea name="address_description" style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 80px;"></textarea>
                </div>

                <div>
                    <label style="display: block; color: #141714; font-weight: 500; margin-bottom: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'المدينة' : 'City'; ?>
                    </label>
                    <select name="city" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                        <option value="6th of October City"><?php echo $lang === 'ar' ? 'السادس من اكتوبر' : '6th of October City'; ?></option>
                        <option value="Sheikh Zayed"><?php echo $lang === 'ar' ? 'الشيخ زايد' : 'Sheikh Zayed'; ?></option>
                    </select>
                </div><br>

                <div>
                    <label><?php echo $lang === 'ar' ? 'ادخل السعر الذي ستدفعه' : 'make a price you want to pay'; ?></label><br><br>
                    <input type="number" name="budget" placeholder="<?php echo $lang === 'ar' ? 'السعر بالجنيه المصري' : 'Price in EGP'; ?>" min="200" required style="width: 100%; padding: 0.75rem; border: 1px solid #D4D3D0; border-radius: 8px;">
                </div><br>

                <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'إنشاء الطلب' : 'Create Request'; ?></button>
                <a href="pages/user/user_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-secondary" style="text-align: center;"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
            </form>
        <?php endif; ?>
        </div>
    </div>
</body>
</html>
