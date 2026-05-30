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
                        json_encode($specializations),
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
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <input type="radio" id="user" name="role" value="user" checked class="w-4 h-4" onclick="switchType('user')">
                        <label for="user" class="text-gray-700">عميل</label>
                        <input type="radio" id="worker" name="role" value="worker" class="w-4 h-4" onclick="switchType('worker')">
                        <label for="worker" class="text-gray-700">عامل</label>
                    </div><br>
                </div>

                <form method="POST" enctype="multipart/form-data" id="signupForm">
                    <input type="hidden" name="type" value="user" id="typeInput">

                    <!-- Common Fields -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'الاسم الكامل' : 'Full Name'; ?> *</label>
                            <input type="text" name="fullName" required>
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
                            <input type="text" name="national_id">
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
                                        <input type="checkbox" name="specializations[]" value="<?php echo $spec; ?>" id="spec_<?php echo $spec; ?>" >
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
                            <input type="file" id="idCardFront" name="idCardFront" accept="image/jpeg,image/png" >
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'ظهر بطاقة الهوية' : 'ID Card Back'; ?> * (JPG/PNG)</label>
                            <div class="file-upload" onclick="document.getElementById('idCardBack').click()">
                                <p><?php echo $lang === 'ar' ? 'اضغط لتحميل الصورة' : 'Click to upload'; ?></p>
                            </div>
                            <input type="file" id="idCardBack" name="idCardBack" accept="image/jpeg,image/png" >
                        </div>

                        <div class="form-group">
                            <label><?php echo $lang === 'ar' ? 'السجل الجنائي' : 'Criminal Record'; ?> * (JPG/PNG/PDF)</label>
                            <div class="file-upload" onclick="document.getElementById('criminalRecord').click()">
                                <p><?php echo $lang === 'ar' ? 'اضغط لتحميل الملف' : 'Click to upload'; ?></p>
                            </div>
                            <input type="file" id="criminalRecord" name="criminalRecord" accept="image/jpeg,image/png,application/pdf" >
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
        function switchType(type) {
    document.getElementById('typeInput').value = type;

    const workerFields = document.getElementById('workerFields');

    const requiredFiles = [
        document.getElementById('idCardFront'),
        document.getElementById('idCardBack'),
        document.getElementById('criminalRecord')
    ];

    if (type === 'worker') {
        workerFields.classList.add('show');

        requiredFiles.forEach(file => {
            file.required = true;
        });

    } else {
        workerFields.classList.remove('show');

        requiredFiles.forEach(file => {
            file.required = false;
        });
    }
}
    </script>
</body>
</html>
