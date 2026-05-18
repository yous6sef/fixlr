<?php
/**
 * User Request Submission - جديد طلب خدمة
 */
session_start();
include('db.php');
include('config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user info
$userStmt = $conn->prepare("SELECT name, city, address FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

// Get service types
$servicesStmt = $conn->prepare("SELECT id, name_ar, name_en FROM service_types ORDER BY name_ar");
$servicesStmt->execute();
$services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get cities (only the two active service areas)
$citiesStmt = $conn->prepare("SELECT id, name_ar FROM cities WHERE name_en IN ('6th of October', 'Sheikh Zayed') OR name_ar IN ('اكتوبر السادس', 'الشيخ زايد') ORDER BY name_ar");
$citiesStmt->execute();
$cities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected service type for device loading
$selectedServiceType = $_POST['service_type_id'] ?? null;
$devices = [];

// Load devices for the selected service type automatically
if ($selectedServiceType) {
    $devicesStmt = $conn->prepare("SELECT id, name_ar FROM devices WHERE service_type_id = ? ORDER BY name_ar");
    $devicesStmt->execute([$selectedServiceType]);
    $devices = $devicesStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission (only for final submit, not for service selection)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $city_id = $_POST['city_id'] ?? null;
    $service_type_id = $_POST['service_type_id'] ?? null;
    $device_id = $_POST['device_id'] ?? null;
    $google_maps_link = $_POST['google_maps_link'] ?? '';
    $problem_description = $_POST['problem_description'] ?? '';

    if (!$city_id || !$service_type_id || !$problem_description) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO service_requests 
                (user_id, service_type_id, device_id, city_id, google_maps_link, problem_description, checking_fee, status)
                VALUES (?, ?, ?, ?, ?, ?, 300, 'pending')
            ");
            
            $stmt->execute([$userId, $service_type_id, $device_id, $city_id, $google_maps_link, $problem_description]);
            $requestId = $conn->lastInsertId();
            
            // Create status history entry
            $historyStmt = $conn->prepare("
                INSERT INTO request_status_history 
                (service_request_id, new_status, changed_by)
                VALUES (?, 'pending', ?)
            ");
            $historyStmt->execute([$requestId, $userId]);
            
            $success = 'تم إرسال طلبك بنجاح! انتظر قبول أحد الفنيين';
            
            // Redirect after success
            header('refresh:3;url=user_requests.php');
        } catch (Exception $e) {
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب خدمة جديد</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: right;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="url"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .price-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-right: 4px solid #667eea;
        }

        .price-info p {
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .price-info strong {
            color: #667eea;
            font-size: 18px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        button {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit {
            background: #667eea;
            color: white;
            flex: 1;
        }

        .btn-submit:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .required {
            color: #c62828;
        }

        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 طلب خدمة جديد</h1>
            <p>قم بملء البيانات التالية لطلب الخدمة</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="price-info">
            <p>💰 رسم الكشف:</p>
            <p><strong>300 جنيه مصري</strong> - يتم دفعها بعد تأكيد الفني للوصول</p>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">سيتم إضافتها لإيرادات الفني</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="city">المدينة <span class="required">*</span></label>
                <select id="city" name="city_id" required>
                    <option value="">-- اختر المدينة --</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city['id']; ?>">
                            <?php echo htmlspecialchars($city['name_ar']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="service">نوع الخدمة <span class="required">*</span></label>
                <select id="service" name="service_type_id" required>
                    <option value="">-- اختر نوع الخدمة --</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['id']; ?>" <?php echo ($selectedServiceType == $service['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service['name_ar']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selectedServiceType): ?>
                    <button type="submit" name="load_devices" value="1" class="btn-submit" style="margin-top: 10px;">تحديث الأجهزة</button>
                <?php endif; ?>
            </div>

            <?php if ($selectedServiceType && !empty($devices)): ?>
                <div class="form-group">
                    <label for="device">الجهاز (اختياري)</label>
                    <select id="device" name="device_id">
                        <option value="">-- اختر الجهاز --</option>
                        <?php foreach ($devices as $device): ?>
                            <option value="<?php echo $device['id']; ?>">
                                <?php echo htmlspecialchars($device['name_ar']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="maps">رابط خريطة جوجل (اختياري)</label>
                <input 
                    type="url" 
                    id="maps" 
                    name="google_maps_link" 
                    placeholder="https://maps.google.com/..."
                    pattern="https://.*"
                >
                <div class="note">💡 يمكنك نسخ رابط موقعك من جوجل مابس لتسهيل الوصول</div>
            </div>

            <div class="form-group">
                <label for="description">وصف المشكلة <span class="required">*</span></label>
                <textarea 
                    id="description" 
                    name="problem_description" 
                    placeholder="اشرح المشكلة بالتفصيل..." 
                    required
                ></textarea>
            </div>

            <div class="button-group">
                <a href="user_requests.php" class="btn-cancel" style="text-decoration: none; text-align: center;">إلغاء</a>
                <button type="submit" name="submit_request" value="1" class="btn-submit">إرسال الطلب</button>
            </div>
        </form>
    </div>
</body>
</html>
