<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

$errors = [];
$success = '';

function normalizeText($value) {
    return trim($value ?? '');
}

function saveUploadFile($fieldName, $subDir, $required = false, $allowedExtensions = []) {
    // Fallback local storage (kept for compatibility)
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $required ? null : null;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $originalName = basename($_FILES[$fieldName]['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
        return null;
    }

    $uploadDir = UPLOAD_DIR . '/' . trim($subDir, '/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFileName = time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $newFileName;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return str_replace('\\', '/', str_replace(__DIR__ . '/', '', $targetPath));
    }

    return null;
}

/**
 * Upload file to Cloudinary using server-side signature
 * Returns secure_url on success or null on failure
 */
function uploadToCloudinary($file, $folder = 'flix_workers') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $cloudName = CLOUDINARY_CLOUD_NAME;
    $apiKey = CLOUDINARY_API_KEY;
    $apiSecret = CLOUDINARY_API_SECRET;

    $timestamp = time();
    $params = ['timestamp' => $timestamp, 'folder' => $folder];
    ksort($params);

    $toSignParts = [];
    foreach ($params as $k => $v) {
        $toSignParts[] = "$k=$v";
    }
    $toSign = implode('&', $toSignParts);
    $signature = sha1($toSign . $apiSecret);

    $url = "https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload";

    $cfile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);

    $post = [
        'file' => $cfile,
        'api_key' => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder' => $folder
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log('Cloudinary upload error: ' . $err);
        return null;
    }

    $json = json_decode($result, true);
    if (!empty($json['secure_url'])) {
        return $json['secure_url'];
    }

    error_log('Cloudinary upload failed: ' . $result);
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'user';
    $name = normalizeText($_POST['name'] ?? '');
    $email = normalizeText($_POST['email'] ?? '');
    $phone = normalizeText($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $location = normalizeText($_POST['location'] ?? '');

    if ($name === '' || $phone === '' || $password === '' || $location === '') {
        $errors[] = 'يرجى ملء جميع الحقول الأساسية.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !filter_var($phone, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^[0-9+\- ]{6,20}$/']])) {
        $errors[] = 'يرجى إدخال بريد إلكتروني صالح أو رقم هاتف صالح.';
    }

    // Worker registration is stored in the users table with user_type = 'worker'.
    // No separate worker table is used in this simplified workflow.

    if (empty($errors)) {
        // Check for existing email/phone in both users and workers tables
        $checkExisting = $conn->prepare("SELECT 1 FROM users WHERE email = :email OR phone = :phone UNION SELECT 1 FROM workers WHERE email = :email OR phone = :phone LIMIT 1");
        $checkExisting->execute([':email' => $email, ':phone' => $phone]);
        if ($checkExisting->fetch()) {
            $errors[] = 'البريد الإلكتروني أو رقم الهاتف موجود بالفعل.';
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userType = $role === 'worker' ? 'worker' : 'user';

        if ($userType === 'worker') {
            // Upload documents to Cloudinary
            $id_front_path = uploadToCloudinary($_FILES['id_front']);
            $id_back_path = uploadToCloudinary($_FILES['id_back']);
            $certificate_path = uploadToCloudinary($_FILES['certificate']);
            $cv_path = uploadToCloudinary($_FILES['cv']);

            $national_id = normalizeText($_POST['national_id'] ?? '');
            $special = normalizeText($_POST['special'] ?? '');

            $stmt = $conn->prepare('INSERT INTO workers (name, email, phone, password_hash, specialization, national_id, id_front_path, id_back_path, certificate_path, cv_path, city, location, approved, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $success = $stmt->execute([$name, $email, $phone, $hashedPassword, $special, $national_id, $id_front_path, $id_back_path, $certificate_path, $cv_path, $location, $location, 'pending', 'inactive']);

            if ($success) {
                header('Location: login.php?registered=1');
                exit();
            }

            $errors[] = 'حدث خطأ أثناء إنشاء حساب الفني. حاول مرة أخرى.';
        } else {
            // Regular user
            $accountStatus = 'active';
            $stmt = $conn->prepare('INSERT INTO users (name, email, phone, password_hash, city, address, user_type, account_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $success = $stmt->execute([$name, $email, $phone, $hashedPassword, $location, '', 'user', $accountStatus]);

            if ($success) {
                header('Location: login.php?registered=1');
                exit();
            }

            $errors[] = 'حدث خطأ أثناء إنشاء الحساب. حاول مرة أخرى.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس - تسجيل جديد</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        #worker-fields {
            display: <?= (isset($_POST['role']) && $_POST['role'] === 'worker') ? 'block' : 'none' ?>;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-indigo-600">فليكس</h1>
                <p class="text-gray-600 mt-2">إنشاء حساب جديد للمستخدم أو الفني</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="mb-6 text-right bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $errorItem): ?>
                            <li><?= htmlspecialchars($errorItem) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="register-form" name="sign" method="post" enctype="multipart/form-data" class="space-y-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">الاسم الكامل</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="أحمد محمد"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">البريد الإلكتروني</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="example@domain.com"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">رقم الهاتف</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="01012345678"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">كلمة المرور</label>
                        <input type="password" name="password" placeholder="••••••••"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">الموقع</label>
                        <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" placeholder="العنوان كما في بطاقة الهوية أو المدينة"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">نوع الحساب</label>
                    <select id="register-role" name="role" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="user" <?= (!isset($_POST['role']) || $_POST['role'] === 'user') ? 'selected' : '' ?>>عميل</option>
                        <option value="worker" <?= (isset($_POST['role']) && $_POST['role'] === 'worker') ? 'selected' : '' ?>>فني</option>
                    </select>
                </div>

                <div id="worker-fields" class="space-y-5">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">رقم الهوية الوطنية</label>
                        <input type="text" name="national_id" value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>" placeholder="***********"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">التخصص</label>
                        <select name="special" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر التخصص</option>
                            <option value="سباك" <?= (isset($_POST['special']) && $_POST['special'] === 'سباك') ? 'selected' : '' ?>>سباك</option>
                            <option value="كهربائي" <?= (isset($_POST['special']) && $_POST['special'] === 'كهربائي') ? 'selected' : '' ?>>كهربائي</option>
                            <option value="نجار" <?= (isset($_POST['special']) && $_POST['special'] === 'نجار') ? 'selected' : '' ?>>نجار</option>
                            <option value="دهان" <?= (isset($_POST['special']) && $_POST['special'] === 'دهان') ? 'selected' : '' ?>>دهان</option>
                            <option value="تنظيف" <?= (isset($_POST['special']) && $_POST['special'] === 'تنظيف') ? 'selected' : '' ?>>تنظيف</option>
                            <option value="صيانة" <?= (isset($_POST['special']) && $_POST['special'] === 'صيانة') ? 'selected' : '' ?>>صيانة عامة</option>
                        </select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">صورة الهوية - أمامية</label>
                            <input type="file" name="id_front" accept="image/png,image/jpeg,application/pdf" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">صورة الهوية - خلفية</label>
                            <input type="file" name="id_back" accept="image/png,image/jpeg,application/pdf" class="w-full" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">شهادة (اختياري)</label>
                            <input type="file" name="certificate" accept="image/png,image/jpeg,application/pdf" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">السيرة الذاتية (اختياري)</label>
                            <input type="file" name="cv" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="w-full" />
                        </div>
                    </div>
                    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
                        يتم إنشاء حساب الفني كـ <strong>قيد المراجعة</strong> حتى يعتمد من قبل الإدارة.
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition">إنشاء الحساب</button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-6">لديك حساب؟ <a href="login.php" class="font-semibold text-indigo-600 hover:underline">تسجيل الدخول</a></p>
        </div>
    </div>

    <script>
        const roleSelect = document.getElementById('register-role');
        const workerFields = document.getElementById('worker-fields');

        roleSelect.addEventListener('change', function () {
            if (this.value === 'worker') {
                workerFields.style.display = 'block';
            } else {
                workerFields.style.display = 'none';
            }
        });
    </script>
</body>
</html>