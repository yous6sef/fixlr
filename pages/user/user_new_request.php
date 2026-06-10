<?php
/**
 * User Request Submission - جديد طلب خدمة
 */
session_start();
include('../../core/db.php');
include('../../core/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
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
            
            // Get city info for worker matching
            $cityStmt = $conn->prepare("SELECT name_ar FROM cities WHERE id = ?");
            $cityStmt->execute([$city_id]);
            $cityData = $cityStmt->fetch();
            $cityName = $cityData['name_ar'] ?? '';
            
            // Get service type info
            $serviceStmt = $conn->prepare("SELECT name_ar FROM service_types WHERE id = ?");
            $serviceStmt->execute([$service_type_id]);
            $serviceData = $serviceStmt->fetch();
            $serviceName = $serviceData['name_ar'] ?? '';
            
            $success = "تم إرسال طلبك بنجاح! 🎉 سيتم إرسال طلبك إلى الفنيين في {$cityName} المتخصصين في {$serviceName}";
            
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
    <?php
        $lang = 'ar';
        $pageTitle = 'طلب خدمة منزلية جديدة - احجز فنياً موثوقاً | فليكس';
        $pageDescription = 'قدم طلب خدمة منزلية الآن واحصل على فني متخصص موثوق خلال 30-45 دقيقة فقط. خدمات سباكة وكهرباء ونجارة وتنظيف وصيانة بأسعار شفافة وآمنة.';
        include('../../core/seo.php');
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #06b6d4;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 40px;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            color: var(--text);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            color: var(--text-light);
            font-size: 16px;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-right: 4px solid;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-right-color: #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-right-color: #22c55e;
        }

        .form-group {
            margin-bottom: 28px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: var(--text);
            font-weight: 600;
            font-size: 15px;
            transition: color 0.3s;
        }

        .label-required {
            color: var(--danger);
        }

        .help-text {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            color: var(--text-light);
        }

        input[type="text"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
            background: var(--surface);
            color: var(--text);
        }

        input[type="text"]:focus,
        input[type="url"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            background: var(--surface);
        }

        input[type="text"]::placeholder,
        input[type="url"]::placeholder,
        textarea::placeholder {
            color: var(--text-light);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M7 8L10 11L13 8' stroke='%230ea5e9' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }

        option {
            background: var(--surface);
            color: var(--text);
            padding: 10px;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
            font-size: 14px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-box {
            background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-right: 4px solid var(--primary);
        }

        .info-box h3 {
            color: var(--primary-dark);
            font-size: 15px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-box p {
            color: var(--text);
            font-size: 14px;
            margin-bottom: 4px;
        }

        .info-box .price {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-top: 12px;
        }

        .maps-helper {
            background: #f0fdf4;
            padding: 16px;
            border-radius: 12px;
            margin-top: 10px;
            border-left: 4px solid var(--success);
        }

        .maps-helper p {
            color: var(--text-light);
            font-size: 13px;
            margin-bottom: 10px;
        }

        .maps-link {
            display: inline-block;
            background: var(--success);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .maps-link:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 40px;
            border-top: 2px solid var(--border);
            padding-top: 30px;
        }

        button, .btn-cancel {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cairo', sans-serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(14, 165, 233, 0.4);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: var(--border);
            color: var(--text);
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #cbd5e1;
            transform: translateY(-3px);
        }

        .loading-indicator {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 600px) {
            .container {
                padding: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column-reverse;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 طلب خدمة جديد</h1>
            <p>أخبرنا عن احتياجاتك لنوصلك بأفضل فني متخصص</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>خطأ:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>نجاح:</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>💰 رسم الكشف الأولي</h3>
            <p>يقوم الفني الخاص بك بالفحص والتشخيص</p>
            <div class="price">300 جنيه مصري</div>
            <p style="font-size: 12px; margin-top: 8px; color: var(--text-light);">يُخصم من سعر الإصلاح النهائي بعد الاتفاق على السعر</p>
        </div>

        <form method="POST" action="" id="requestForm">
            <div class="form-group">
                <label for="city">
                    🏙️ اختر المدينة <span class="label-required">*</span>
                </label>
                <select id="city" name="city_id" required onchange="this.form.style.borderColor='var(--primary)';">
                    <option value="">-- اختر من قائمة المدن --</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city['id']); ?>">
                            📍 <?php echo htmlspecialchars($city['name_ar']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="help-text">اخترنا هاتين المدينتين لتقديم أفضل خدمة</span>
            </div>

            <div class="form-group">
                <label for="service">
                    🔧 نوع الخدمة المطلوبة <span class="label-required">*</span>
                </label>
                <select id="service" name="service_type_id" required>
                    <option value="">-- اختر نوع الخدمة --</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo htmlspecialchars($service['id']); ?>" <?php echo ($selectedServiceType == $service['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service['name_ar']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="help-text">سنبحث عن فنيين متخصصين في هذا المجال</span>
            </div>

            <?php if ($selectedServiceType && !empty($devices)): ?>
                <div class="form-group">
                    <label for="device">
                        📱 نوع الجهاز (اختياري)
                    </label>
                    <select id="device" name="device_id">
                        <option value="">-- لا أعرف نوع الجهاز --</option>
                        <?php foreach ($devices as $device): ?>
                            <option value="<?php echo htmlspecialchars($device['id']); ?>">
                                <?php echo htmlspecialchars($device['name_ar']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="maps">
                    🗺️ رابط موقعك من جوجل مابس (اختياري)
                </label>
                <input 
                    type="url" 
                    id="maps" 
                    name="google_maps_link" 
                    placeholder="https://maps.google.com/..."
                    pattern="https://.*"
                >
                <div class="maps-helper">
                    <p>📍 كيفية الحصول على رابط موقعك:</p>
                    <ol style="margin-right: 20px; font-size: 13px;">
                        <li>افتح جوجل مابس</li>
                        <li>ابحث عن موقعك أو انقر على المكان</li>
                        <li>اضغط على الثلاث نقاط واختر "نسخ الرابط"</li>
                        <li>الصقه هنا لتسهيل وصول الفني</li>
                    </ol>
                    <a href="https://maps.google.com" target="_blank" class="maps-link">
                        فتح جوجل مابس 🌐
                    </a>
                </div>
            </div>

            <div class="form-group">
                <label for="description">
                    📝 وصف المشكلة بالتفصيل <span class="label-required">*</span>
                </label>
                <textarea 
                    id="description" 
                    name="problem_description" 
                    placeholder="اشرح المشكلة بوضوح... مثلاً: المصرف يسرب ماء من الأسفل، أو المكيف لا يبرد..."
                    required
                ></textarea>
                <span class="help-text">شرح دقيق يساعد الفني على التحضير الجيد</span>
            </div>

            <div class="button-group">
                <a href="./user_dashboard.php" class="btn-cancel">❌ إلغاء</a>
                <button type="submit" name="submit_request" value="1" class="btn-submit">
                    ✅ إرسال الطلب
                </button>
            </div>
        </form>
    </div>

    <script>
        // Ensure dropdown works properly
        document.getElementById('city').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        document.getElementById('service').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Form validation
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const city = document.getElementById('city').value;
            const service = document.getElementById('service').value;
            const description = document.getElementById('description').value;

            if (!city || !service || !description) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة');
                return false;
            }
        });
    </script>

    <?php
        // Include FAQ generator and display FAQ for thin content fix
        include('../../core/seo-dynamic-faq.php');
        
        // Determine selected service name for FAQ context
        $selectedServiceName = '';
        if ($selectedServiceType) {
            foreach ($services as $service) {
                if ($service['id'] == $selectedServiceType) {
                    $selectedServiceName = $service['name_ar'];
                    break;
                }
            }
        }
        
        // Generate and display FAQ
        echo seoGenerateDynamicFaq('ar', $conn, $selectedServiceType, $selectedServiceName);
    ?>
</body>
</html>
