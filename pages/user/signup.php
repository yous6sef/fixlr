<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;

$type = $_GET['type'] ?? 'user';

$connection = $conn;

if (isset($_SESSION['user_id'])) {
    header(
        'Location: ' .
        ($type === 'worker'
            ? 'pages/worker/worker_dashboard.php'
            : 'user_dashboard.php')
        . '?lang=' . $lang
    );
    exit;
}

$errors = [];
$success = false;

/*
|--------------------------------------------------------------------------
| Upload Function (BY WORKER ID)
|--------------------------------------------------------------------------
*/
function uploadFile($file, $workerId)
{
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;

    $uploadDir = "../../uploads/workers/$workerId/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $path = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => true, 'path' => "uploads/workers/$workerId/$fileName"];
    }

    return ['success' => false, 'error' => 'Upload failed'];
}

/*
|--------------------------------------------------------------------------
| Handle Request
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formType = $_POST['type'] ?? 'user';

    $fullName = trim($_POST['fullName'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $workLocation = trim($_POST['workLocation'] ?? '6th of October City');
    $national_id = trim($_POST['national_id'] ?? '');
    $residentialLocation = trim($_POST['residentialLocation'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (strlen($fullName) < 3) {
        $errors[] = 'Name too short';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    // Check if phone number already exists in the database
    if (!empty($phoneNumber)) {
        $checkPhoneQuery = "
            SELECT id FROM users WHERE phone = ?
            UNION
            SELECT id FROM workers WHERE phone = ?
        ";
        $checkPhoneStmt = $connection->prepare($checkPhoneQuery);
        $checkPhoneStmt->execute([$phoneNumber, $phoneNumber]);
        $existingPhone = $checkPhoneStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingPhone) {
            $errors[] = 'This phone number is already registered in the system';
        }
    }

    if (empty($errors)) {
        try {
            $connection->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Create User
            $userQuery = "
                INSERT INTO users (name, phone, password, city)
                VALUES (?, ?, ?, ?)
            ";
            $userStmt = $connection->prepare($userQuery);
            $userStmt->execute([$fullName, $phoneNumber, $passwordHash, $workLocation]);

            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            // Commit user creation immediately
            $connection->commit();
            $success = true;

            // If worker, handle separately
            if ($formType === 'worker') {
                try {
                    $connection->beginTransaction();

                    $specializations = $_POST['specializations'] ?? [];

                    $workerQuery = "
                        INSERT INTO workers
                        (name, national_id, specialization, Location, city, phone, password, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING_APPROVAL')
                        RETURNING id
                    ";
                    $workerStmt = $connection->prepare($workerQuery);
                    $workerStmt->execute([
                        $fullName,
                        $national_id,
                        $specializations[0] ?? '', // For simplicity, only storing the first specialization
                        $residentialLocation,
                        $workLocation,
                        $phoneNumber,
                        $passwordHash
                    ]);

                    $worker = $workerStmt->fetch(PDO::FETCH_ASSOC);
                    $workerId = $worker['id'];

                    // Upload files
                    $uploadedFiles = [];

                    $front = uploadFile($_FILES['idCardFront'], $workerId);
                    if ($front['success']) $uploadedFiles['front'] = $front['path'];

                    $back = uploadFile($_FILES['idCardBack'], $workerId);
                    if ($back['success']) $uploadedFiles['back'] = $back['path'];

                    $crime = uploadFile($_FILES['criminalRecord'], $workerId);
                    if ($crime['success']) $uploadedFiles['crime'] = $crime['path'];

                    if (!empty($_FILES['resume']['tmp_name'])) {
                        $resume = uploadFile($_FILES['resume'], $workerId);
                        if ($resume['success']) $uploadedFiles['resume'] = $resume['path'];
                    }

                    // Update worker with files
                    $update = "
                        UPDATE workers
                        SET id_front_path = ?,
                            id_back_path = ?,
                            certificate_path = ?,
                            cv_path = ?
                        WHERE id = ?
                    ";
                    $stmt = $connection->prepare($update);
                    $stmt->execute([
                        $uploadedFiles['front'] ?? null,
                        $uploadedFiles['back'] ?? null,
                        $uploadedFiles['crime'] ?? null,
                        $uploadedFiles['resume'] ?? null,
                        $workerId
                    ]);

                    $connection->commit();
                } catch (Exception $e) {
                    $connection->rollBack();
                    $errors[] = "Worker creation failed: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $connection->rollBack();
            $errors[] = $e->getMessage();
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
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'إنشاء حساب عميل لربط مع فنيين موثوقين' : 'Sign Up - Create Your Account';
        $pageDescription = $lang === 'ar' ? 'أنشئ حسابا مجانا من فليكس وابدأ بطلب خدمات منزلية وصلات' : 'Create a FLIX account and book trusted home services';
        include('../../core/seo.php');
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        :root {
            --primary: #1A6B4A;
            --primary-light: #2D9A6C;
            --primary-lighter: #E8F5EE;
            --text-primary: #141714;
            --text-secondary: #4A5249;
            --text-tertiary: #8A9389;
            --border: #D4D3D0;
            --surface: #FFFFFF;
            --surface-light: #F7F8F6;
            --surface-lighter: #F0F2EE;
            --radius: 14px;
            --shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--surface-light);
            color: var(--text-primary);
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-brand { display: flex; align-items: center; }

        .logo {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .header-nav {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
            font-size: 0.95rem;
        }

        .header-nav a:hover { opacity: 0.8; }

        .signup-wrapper {
            min-height: calc(100vh - 70px);
            padding: 2rem 1rem;
        }

        .signup-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2.5rem;
        }

        .signup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .signup-header .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .signup-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .signup-header p {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .type-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .type-tab {
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--surface-light);
            color: var(--text-secondary);
            font-weight: 600;
        }

        .type-tab.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-color: var(--primary);
        }

        .type-tab:hover {
            border-color: var(--primary);
            background: var(--primary-lighter);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 107, 74, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-color: #FECACA;
        }

        .error-list {
            list-style: none;
            padding: 0;
        }

        .error-list li {
            padding: 0.35rem 0;
            font-size: 0.95rem;
        }

        .error-list li:before {
            content: "• ";
            margin-right: 0.5rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(26, 107, 74, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .success-message {
            text-align: center;
            padding: 2rem;
        }

        .success-message h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .success-message p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .btn-back {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(26, 107, 74, 0.3);
        }

        .already-registered {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .already-registered a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .already-registered a:hover {
            color: var(--primary-light);
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .checkbox-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .checkbox-item input {
            width: auto;
            margin-top: 0.25rem;
            cursor: pointer;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .file-upload {
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            background: var(--surface-lighter);
            transition: all 0.2s;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: var(--primary-lighter);
        }

        .file-upload input {
            display: none;
        }

        .file-upload p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin: 0;
        }

        .worker-fields {
            display: none;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .worker-fields.show {
            display: block;
        }

        .password-helper {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-tertiary);
        }

        .password-helper.error {
            color: #DC2626;
        }

        .password-helper.success {
            color: #059669;
        }

        .lang-btns {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .lang-btns a {
            padding: 0.55rem 1rem;
            border: 1px solid var(--border);
            background: var(--surface-light);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .lang-btns a:hover {
            border-color: var(--primary);
            background: var(--primary-lighter);
        }

        .lang-btns a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        @media (max-width: 640px) {
            header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-nav {
                width: 100%;
                justify-content: center;
            }

            .signup-wrapper {
                padding: 1rem;
            }

            .signup-container {
                padding: 1.5rem;
            }

            .signup-header .logo-text {
                font-size: 2rem;
            }

            .signup-header h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-brand">
            <div class="logo">FLIX</div>
        </div>
        <nav class="header-nav">
            <a href="../../index.php?lang=<?php echo $lang; ?>">
                <?php echo $lang === 'ar' ? 'الرئيسية' : 'Home'; ?>
            </a>
            <a href="login.php?lang=<?php echo $lang; ?>">
                <?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Sign In'; ?>
            </a>
        </nav>
    </header>

    <div class="signup-wrapper">
        <div class="signup-container">
            <?php if ($success): ?>
                <div class="success-message">
                    <h2><?php echo $lang === 'ar' ? 'تم التسجيل بنجاح!' : 'Registration Successful!'; ?></h2>
                    <p><?php echo $lang === 'ar' 
                        ? ($type === 'worker' ? 'تم تقديم طلبك للموافقة. ستتلقى بريدًا إلكترونيًا عند الموافقة.' : 'حسابك جاهز للاستخدام')
                        : ($type === 'worker' ? 'Your application has been submitted for approval.' : 'Your account is ready to use'); 
                    ?></p>
                    <a href="login.php?lang=<?php echo $lang; ?>" class="btn-back">
                        <?php echo $lang === 'ar' ? 'العودة إلى تسجيل الدخول' : 'Back to Login'; ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="signup-header">
                    <div class="logo-text">FLIX</div>
                    <h1><?php echo $lang === 'ar' ? 'إنشاء حساب جديد' : 'Create New Account'; ?></h1>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="type-tabs">
                    <button class="type-tab active" onclick="switchType('user', this)">
                        <?php echo $lang === 'ar' ? 'عميل' : 'Customer'; ?>
                    </button>
                    <button class="type-tab" onclick="switchType('worker', this)">
                        <?php echo $lang === 'ar' ? 'عامل' : 'Service Provider'; ?>
                    </button>
                </div>

                <form method="POST" enctype="multipart/form-data" id="signupForm">
                    <input type="hidden" name="type" value="user" id="typeInput">

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'الاسم الكامل' : 'Full Name'; ?> *</label>
                        <input type="text" name="fullName" placeholder="<?php echo $lang === 'ar' ? 'أحمد محمد' : 'John Doe'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'رقم الهاتف' : 'Phone Number'; ?> *</label>
                        <input type="tel" name="phoneNumber" placeholder="+20 1001234567" id="phone" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?> *</label>
                        <input type="password" name="password" id="password" placeholder="<?php echo $lang === 'ar' ? 'أدخل كلمة مرور قوية' : 'Enter a strong password'; ?>" required>
                        <div id="passwordHint" class="password-helper"><?php echo $lang === 'ar' ? 'الحد الأدنى 8 أحرف' : 'Minimum 8 characters'; ?></div>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?> *</label>
                        <input type="password" name="confirmPassword" id="confirmPassword" placeholder="<?php echo $lang === 'ar' ? 'أعد إدخال كلمة المرور' : 'Re-enter your password'; ?>" required>
                    </div>

                    <!-- Worker-Specific Fields -->
                    <div id="workerFields" class="worker-fields">
                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'رقم بطاقة الهوية' : 'ID Card Number'; ?> *</label>
                            <input type="text" name="national_id" placeholder="<?php echo $lang === 'ar' ? '123456789012345' : '12345678901234'; ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo $lang === 'ar' ? 'العنوان السكني' : 'Residential Address'; ?> *</label>
                                <input type="text" name="residentialLocation" placeholder="<?php echo $lang === 'ar' ? 'العنوان' : 'Address'; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'منطقة العمل' : 'Work Area'; ?> *</label>
                            <select name="workLocation">
                                <option value="6th of October City">6th of October City</option>
                                <option value="Sheikh Zayed">Sheikh Zayed</option>
                                <option value="Cairo">Cairo</option>
                                <option value="Giza">Giza</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'التخصصات' : 'Specializations'; ?> *</label>
                            <div class="checkbox-group">
                                <?php foreach ($services as $service): ?>
                                    <div class="checkbox-item">
                                        <input type="radio" name="specializations[]" value="<?php echo htmlspecialchars($service['name_en']); ?>" id="spec_<?php echo $service['id']; ?>">
                                        <label for="spec_<?php echo $service['id']; ?>">
                                            <?php echo $lang === 'ar' ? htmlspecialchars($service['name_ar']) : htmlspecialchars($service['name_en']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'وجه بطاقة الهوية' : 'ID Card Front'; ?> * (JPG/PNG)</label>
                            <div class="file-upload" onclick="document.getElementById('idCardFront').click()">
                                <p><?php echo $lang === 'ar' ? 'اضغط لتحميل الصورة' : 'Click to upload'; ?></p>
                            </div>
                            <input type="file" id="idCardFront" name="idCardFront" accept="image/jpeg,image/png">
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'ظهر بطاقة الهوية' : 'ID Card Back'; ?> * (JPG/PNG)</label>
                            <div class="file-upload" onclick="document.getElementById('idCardBack').click()">
                                <p><?php echo $lang === 'ar' ? 'اضغط لتحميل الصورة' : 'Click to upload'; ?></p>
                            </div>
                            <input type="file" id="idCardBack" name="idCardBack" accept="image/jpeg,image/png">
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'السجل الجنائي' : 'Criminal Record'; ?> * (JPG/PNG/PDF)</label>
                            <div class="file-upload" onclick="document.getElementById('criminalRecord').click()">
                                <p><?php echo $lang === 'ar' ? 'اضغط لتحميل الملف' : 'Click to upload'; ?></p>
                            </div>
                            <input type="file" id="criminalRecord" name="criminalRecord" accept="image/jpeg,image/png,application/pdf">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <?php echo $lang === 'ar' ? 'إنشاء الحساب' : 'Create Account'; ?>
                    </button>

                    <div class="already-registered">
                        <?php echo $lang === 'ar' ? 'لديك حساب بالفعل؟' : 'Already have an account?'; ?> 
                        <a href="./login.php?lang=<?php echo $lang; ?>">
                            <?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Sign In'; ?>
                        </a>
                    </div>
                </form>

                <div class="lang-btns">
                    <a href="./signup.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
                    <a href="./signup.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
function switchType(type, btn) {
    document.getElementById('typeInput').value = type;
    document.querySelectorAll('.type-tab').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');

    const workerFields = document.getElementById('workerFields');
    const requiredFiles = [
        document.getElementById('idCardFront'),
        document.getElementById('idCardBack'),
        document.getElementById('criminalRecord')
    ];

    if (type === 'worker') {
        workerFields.classList.add('show');
        requiredFiles.forEach(file => file.required = true);
    } else {
        workerFields.classList.remove('show');
        requiredFiles.forEach(file => file.required = false);
    }
}

const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirmPassword');
const passwordHint = document.getElementById('passwordHint');
const lang = "<?php echo $lang; ?>";

function validatePassword() {
    const pass = password.value;
    const confirm = confirmPassword.value;

    if (pass.length < 8) {
        passwordHint.textContent = lang === 'ar' ? 'يجب أن تحتوي على 8 أحرف على الأقل' : 'Must be at least 8 characters';
        passwordHint.classList.add('error');
        passwordHint.classList.remove('success');
    } else if (confirm && pass !== confirm) {
        passwordHint.textContent = lang === 'ar' ? 'كلمات المرور غير متطابقة' : 'Passwords do not match';
        passwordHint.classList.add('error');
        passwordHint.classList.remove('success');
    } else if (confirm && pass === confirm && pass.length >= 8) {
        passwordHint.textContent = lang === 'ar' ? '✓ كلمات المرور متطابقة' : '✓ Passwords match';
        passwordHint.classList.add('success');
        passwordHint.classList.remove('error');
    }
}

password.addEventListener('input', validatePassword);
confirmPassword.addEventListener('input', validatePassword);
    </script>
</body>
</html>
