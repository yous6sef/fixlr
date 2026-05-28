<?php
session_start();
include('../../core/lang.php');
include('../../lib/CloudinaryUploadHandler.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;
$type = $_GET['type'] ?? 'user'; // 'user' or 'worker'

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($type === 'worker' ? '../worker/worker_dashboard.php' : './user_dashboard.php') . '?lang=' . $lang);
    exit;
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['type'] ?? 'user';

    // Common validation
    $fullName = trim($_POST['fullName'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (strlen($fullName) < 3) $errors[] = $lang === 'ar' ? 'الاسم يجب أن يكون 3 أحرف على الأقل' : 'Full name must be at least 3 characters';
    if (!preg_match('/^(\+|00)201\d{9}$/', $phoneNumber)) $errors[] = $lang === 'ar' ? 'رقم هاتف مصري غير صحيح' : 'Invalid Egyptian phone number';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $lang === 'ar' ? 'بريد إلكتروني غير صحيح' : 'Invalid email address';
    if (strlen($password) < 8) $errors[] = $lang === 'ar' ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' : 'Password must be at least 8 characters';
    if ($password !== $confirmPassword) $errors[] = $lang === 'ar' ? 'كلمات المرور غير متطابقة' : 'Passwords do not match';

    // Worker-specific validation
    if ($formType === 'worker') {
        $idCardNumber = trim($_POST['idCardNumber'] ?? '');
        $residentialLocation = trim($_POST['residentialLocation'] ?? '');
        $workLocation = $_POST['workLocation'] ?? '6th of October City';
        $specializations = $_POST['specializations'] ?? [];

        if (strlen($idCardNumber) < 10) $errors[] = $lang === 'ar' ? 'رقم بطاقة هوية غير صحيح' : 'Invalid ID card number';
        if (empty($specializations)) $errors[] = $lang === 'ar' ? 'اختر تخصص واحد على الأقل' : 'Select at least one specialization';
        if (empty($_FILES['idCardFront']['tmp_name'])) $errors[] = $lang === 'ar' ? 'صورة وجه البطاقة مطلوبة' : 'ID card front image is required';
        if (empty($_FILES['idCardBack']['tmp_name'])) $errors[] = $lang === 'ar' ? 'صورة ظهر البطاقة مطلوبة' : 'ID card back image is required';
        if (empty($_FILES['criminalRecord']['tmp_name'])) $errors[] = $lang === 'ar' ? 'السجل الجنائي مطلوب' : 'Criminal record document is required';
    }

    // Check email
    if (empty($errors)) {
        $emailCheckQuery = "SELECT id FROM users WHERE email = $1";
        $emailCheckResult = pg_query_params($POSTGRES_CONN, $emailCheckQuery, [$email]);
        if (pg_num_rows($emailCheckResult) > 0) {
            $errors[] = $lang === 'ar' ? 'البريد الإلكتروني مسجل بالفعل' : 'Email already registered';
        }
    }

    // Handle file uploads for worker
    $uploadedFiles = [];
    if ($formType === 'worker' && empty($errors)) {
        try {
            $cloudinary = new CloudinaryUploadHandler();

            // Upload ID card front
            $frontValidation = CloudinaryUploadHandler::validateUpload($_FILES['idCardFront'], 'idCardFront');
            if (!$frontValidation['success']) {
                $errors[] = 'ID card front: ' . $frontValidation['error'];
            } else {
                $frontUpload = $cloudinary->uploadToCloudinary($_FILES['idCardFront'], 'flix/documents/id-cards');
                if (!$frontUpload['success']) {
                    $errors[] = 'Failed to upload ID card front: ' . $frontUpload['error'];
                } else {
                    $uploadedFiles['idCardFront'] = $frontUpload['url'];
                }
            }

            if (empty($errors)) {
                $backValidation = CloudinaryUploadHandler::validateUpload($_FILES['idCardBack'], 'idCardBack');
                if (!$backValidation['success']) {
                    $errors[] = 'ID card back: ' . $backValidation['error'];
                } else {
                    $backUpload = $cloudinary->uploadToCloudinary($_FILES['idCardBack'], 'flix/documents/id-cards');
                    if (!$backUpload['success']) {
                        $errors[] = 'Failed to upload ID card back: ' . $backUpload['error'];
                    } else {
                        $uploadedFiles['idCardBack'] = $backUpload['url'];
                    }
                }
            }

            if (empty($errors)) {
                $crimeValidation = CloudinaryUploadHandler::validateUpload($_FILES['criminalRecord'], 'criminalRecord');
                if (!$crimeValidation['success']) {
                    $errors[] = 'Criminal record: ' . $crimeValidation['error'];
                } else {
                    $crimeUpload = $cloudinary->uploadToCloudinary($_FILES['criminalRecord'], 'flix/documents/criminal-records');
                    if (!$crimeUpload['success']) {
                        $errors[] = 'Failed to upload criminal record: ' . $crimeUpload['error'];
                    } else {
                        $uploadedFiles['criminalRecord'] = $crimeUpload['url'];
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Upload service error: ' . $e->getMessage();
        }
    }

    // Create user account
    if (empty($errors) && ($formType === 'user' || count($uploadedFiles) >= 3)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $userType = $formType === 'worker' ? 'worker' : 'user';
            $city = $formType === 'worker' ? ($_POST['workLocation'] ?? '6th of October City') : '6th of October City';

            $userQuery = "INSERT INTO users (fullName, email, phoneNumber, password_hash, userType, city)
                         VALUES ($1, $2, $3, $4, $5, $6)
                         RETURNING id";

            $userResult = pg_query_params($POSTGRES_CONN, $userQuery, [
                $fullName,
                $email,
                $phoneNumber,
                $passwordHash,
                $userType,
                $city
            ]);

            if ($userResult) {
                $user = pg_fetch_assoc($userResult);
                $userId = $user['id'];

                // If worker, create worker record
                if ($formType === 'worker') {
                    $workerQuery = "INSERT INTO workers (userId, idCardNumber, idCardFrontUrl, idCardBackUrl, criminalRecordUrl, resumeUrl, specializations, residentialLocation, workLocation, status)
                                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, 'PENDING_APPROVAL')";

                    pg_query_params($POSTGRES_CONN, $workerQuery, [
                        $userId,
                        $_POST['idCardNumber'],
                        $uploadedFiles['idCardFront'],
                        $uploadedFiles['idCardBack'],
                        $uploadedFiles['criminalRecord'],
                        $uploadedFiles['resume'] ?? null,
                        json_encode($_POST['specializations']),
                        $_POST['residentialLocation'],
                        $_POST['workLocation']
                    ]);
                }

                $success = true;
            }
        } catch (Exception $e) {
            $errors[] = $lang === 'ar' ? 'خطأ في قاعدة البيانات: ' : 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'إنشاء حساب' : 'Sign Up'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        :root {
            --primary: #1A6B4A;
            --primary-light: #2D9A6C;
            --surface: #FFFFFF;
            --surface-light: #F7F8F6;
        }

        body { background: var(--surface-light); }

        .signup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .signup-card {
            background: var(--surface);
            border-radius: 14px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .signup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .signup-header h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .type-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .type-tab {
            padding: 1rem;
            border: 2px solid #D4D3D0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--surface);
            color: #4A5249;
            font-weight: 600;
        }

        .type-tab.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-color: var(--primary);
        }

        .type-tab:hover {
            border-color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #141714;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #D4D3D0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 107, 74, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        .alert-success {
            background: #E8F5EE;
            color: var(--primary);
            border: 1px solid #D4E8E0;
        }

        .error-list {
            list-style: none;
            padding: 0;
        }

        .error-list li {
            padding: 0.5rem 0;
        }

        .error-list li:before {
            content: "• ";
            margin-right: 0.5rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(26, 107, 74, 0.3);
        }

        .already-registered {
            text-align: center;
            margin-top: 1.5rem;
            color: #4A5249;
        }

        .already-registered a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-item input {
            width: auto;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
        }

        .file-upload {
            border: 2px dashed #D4D3D0;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            background: #F7F8F6;
        }

        .file-upload input {
            display: none;
        }

        .worker-fields {
            display: none;
        }

        .worker-fields.show {
            display: block;
        }

        .success-message h2 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <?php if ($success): ?>
                <div class="success-message">
                    <h2><?php echo $lang === 'ar' ? 'تم التسجيل بنجاح!' : 'Registration Successful!'; ?></h2>
                    <p><?php echo $lang === 'ar' 
                        ? ($type === 'worker' ? 'تم تقديم طلبك للموافقة. ستتلقى بريدًا إلكترونيًا عند الموافقة.' : 'حسابك جاهز للاستخدام')
                        : ($type === 'worker' ? 'Your application has been submitted for approval.' : 'Your account is ready to use'); 
                    ?></p>
                    <a href="login.php?lang=<?php echo $lang; ?>" class="btn btn-primary" style="display: inline-block; margin-top: 1rem;">
                        <?php echo $lang === 'ar' ? 'العودة إلى تسجيل الدخول' : 'Back to Login'; ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="signup-header">
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
                    <div class="type-tab active" onclick="switchType('user', this)">
                        <?php echo $lang === 'ar' ? 'عميل' : 'User'; ?>
                    </div>
                    <div class="type-tab" onclick="switchType('worker', this)">
                        <?php echo $lang === 'ar' ? 'عامل' : 'Worker'; ?>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" id="signupForm">
                    <input type="hidden" name="type" value="user" id="typeInput">

                    <!-- Common Fields -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'الاسم الكامل' : 'Full Name'; ?> *</label>
                            <input type="text" name="fullName" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?> *</label>
                            <input type="email" name="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'رقم الهاتف' : 'Phone Number'; ?> *</label>
                            <input type="tel" name="phoneNumber" placeholder="+201001234567" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?> *</label>
                            <input type="password" name="password" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?> *</label>
                        <input type="password" name="confirmPassword" required>
                    </div>

                    <!-- Worker-Specific Fields -->
                    <div id="workerFields" class="worker-fields">
                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'رقم بطاقة الهوية' : 'ID Card Number'; ?> *</label>
                            <input type="text" name="idCardNumber">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo $lang === 'ar' ? 'العنوان السكني' : 'Residential Address'; ?> *</label>
                                <input type="text" name="residentialLocation">
                            </div>
                            <div class="form-group">
                                <label><?php echo $lang === 'ar' ? 'منطقة العمل' : 'Work Area'; ?> *</label>
                                <select name="workLocation">
                                    <option value="6th of October City">6th of October City</option>
                                    <option value="Sheikh Zayed">Sheikh Zayed</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'التخصصات' : 'Specializations'; ?> *</label>
                            <div class="checkbox-group">
                                <?php 
                                $specs = ['Plumbing', 'Electrical', 'Carpentry', 'Painting'];
                                foreach ($specs as $spec): 
                                ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="specializations[]" value="<?php echo $spec; ?>" id="spec_<?php echo $spec; ?>">
                                        <label for="spec_<?php echo $spec; ?>"><?php echo $spec; ?></label>
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
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchType(type, el) {
            document.getElementById('typeInput').value = type;
            document.querySelectorAll('.type-tab').forEach(tab => tab.classList.remove('active'));
            if (el && el.classList) el.classList.add('active');
            
            const workerFields = document.getElementById('workerFields');
            if (type === 'worker') {
                workerFields.classList.add('show');
            } else {
                workerFields.classList.remove('show');
            }
        }
    </script>
</body>
</html>
