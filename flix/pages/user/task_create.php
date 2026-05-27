<?php
session_start();
include('lang.php');
include('db.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php?lang=' . $lang);
    exit;
}

// Get user information
$DEMO_MODE = true;
$connection = null;

if ($DEMO_MODE) {
    // Use mock user data
    $user = [
        'fullName' => 'Ahmed Hassan',
        'city' => 'Cairo'
    ];
} else {
    $userQuery = "SELECT fullName, city FROM users WHERE id = $1";
    $userResult = pg_query_params($connection, $userQuery, [$_SESSION['user_id']]);
    $user = pg_fetch_assoc($userResult);
}

$errors = [];
$success = false;
$taskId = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialization = trim($_POST['specialization'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $urgency = $_POST['urgency'] ?? 'Normal';
    $address = trim($_POST['address'] ?? '');

    // Validate inputs
    if (empty($specialization)) {
        $errors[] = $lang === 'ar' ? 'اختر التخصص' : 'Please select a specialization';
    }
    if (strlen($description) < 10) {
        $errors[] = $lang === 'ar' ? 'الوصف يجب أن يكون 10 أحرف على الأقل' : 'Description must be at least 10 characters';
    }
    if (empty($address)) {
        $errors[] = $lang === 'ar' ? 'أدخل العنوان' : 'Please enter address';
    }

    // Create task if no errors
    if (empty($errors)) {
        try {
            if ($DEMO_MODE) {
                // In demo mode, create a mock task
                $taskId = rand(1000, 9999);
                $success = true;
            } else {
                $taskQuery = "INSERT INTO tasks (userId, city, specialization, description, currentStatus, urgency, address)
                             VALUES ($1, $2, $3, $4, 'REQUESTED', $5, $6)
                             RETURNING id";

                $taskResult = pg_query_params($connection, $taskQuery, [
                    $_SESSION['user_id'],
                    $user['city'],
                    $specialization,
                    $description,
                    $urgency,
                    $address
                ]);

                if ($taskResult) {
                    $taskRow = pg_fetch_assoc($taskResult);
                    $taskId = $taskRow['id'];
                    $success = true;
                }
            }
        } catch (Exception $e) {
            $errors[] = $lang === 'ar' ? 'خطأ في إنشاء الطلب' : 'Error creating request: ' . $e->getMessage();
        }
    }
}

// Get list of specializations
$specializations = ['Plumbing', 'Electrical', 'Carpentry', 'Painting', 'Cleaning', 'HVAC'];
$specializationsAr = ['أنابيب', 'كهرباء', 'نجارة', 'طلاء', 'تنظيف', 'تكييف'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'إنشاء طلب جديد' : 'Create New Request'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
    <style>
        :root {
            --primary: #1A6B4A;
            --primary-light: #2D9A6C;
            --surface: #FFFFFF;
            --surface-light: #F7F8F6;
        }

        body {
            background: var(--surface-light);
        }

        .task-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 2rem 1rem;
        }

        .task-card {
            background: var(--surface);
            border-radius: 14px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .task-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .task-header h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .task-header p {
            color: #8A9389;
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
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 107, 74, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .radio-group {
            display: flex;
            gap: 2rem;
            margin-top: 0.5rem;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .radio-item input {
            width: auto;
            cursor: pointer;
        }

        .radio-item label {
            margin: 0;
            cursor: pointer;
            color: #4A5249;
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
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(26, 107, 74, 0.3);
        }

        .success-box {
            text-align: center;
            padding: 2rem;
        }

        .success-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .success-box h2 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .success-box p {
            color: #4A5249;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .task-details {
            background: var(--surface-light);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: left;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #E5E4E1;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #8A9389;
            font-weight: 500;
        }

        .detail-value {
            color: #141714;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-track {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-dashboard {
            background: #E5E4E1;
            color: #141714;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .info-box {
            background: #E8F5EE;
            border-left: 4px solid var(--primary);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }

        .info-box p {
            color: var(--primary);
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 640px) {
            .task-container {
                padding: 1rem;
            }

            .task-card {
                padding: 1.5rem;
            }

            .radio-group {
                gap: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="task-container">
        <div class="task-card">
            <?php if ($success): ?>
                <div class="success-box">
                    <div class="success-icon">✓</div>
                    <h2><?php echo $lang === 'ar' ? 'تم إنشاء الطلب بنجاح!' : 'Request Created Successfully!'; ?></h2>
                    <p><?php echo $lang === 'ar' 
                        ? 'تم نشر طلبك. سيبدأ العمال المؤهلون في قبول طلبك قريبًا. يمكنك تتبع حالة طلبك في الوقت الفعلي.'
                        : 'Your request has been posted. Qualified workers will start accepting it shortly. You can track your request status in real-time.'; 
                    ?></p>

                    <div class="task-details">
                        <div class="detail-item">
                            <span class="detail-label"><?php echo $lang === 'ar' ? 'رقم الطلب' : 'Request ID'; ?></span>
                            <span class="detail-value">#<?php echo $taskId; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label"><?php echo $lang === 'ar' ? 'التخصص' : 'Specialization'; ?></span>
                            <span class="detail-value"><?php echo htmlspecialchars($_POST['specialization']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label"><?php echo $lang === 'ar' ? 'الأولوية' : 'Urgency'; ?></span>
                            <span class="detail-value"><?php echo htmlspecialchars($_POST['urgency']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></span>
                            <span class="detail-value">
                                <span style="background: #FEF3E2; color: #9A6400; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem;">
                                    <?php echo $lang === 'ar' ? 'جاري البحث' : 'Looking for workers'; ?>
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="track.php?taskId=<?php echo $taskId; ?>&lang=<?php echo $lang; ?>" class="btn-action btn-track">
                            <?php echo $lang === 'ar' ? 'تتبع الطلب' : 'Track Request'; ?>
                        </a>
                        <a href="user_dashboard.php?lang=<?php echo $lang; ?>" class="btn-action btn-dashboard">
                            <?php echo $lang === 'ar' ? 'الرجوع' : 'Back'; ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="task-header">
                    <h1><?php echo $lang === 'ar' ? 'إنشاء طلب جديد' : 'Create New Request'; ?></h1>
                    <p><?php echo $lang === 'ar' 
                        ? 'اطلب الخدمة والبحث عن عامل موثوق'
                        : 'Request a service and find a trusted professional'; 
                    ?></p>
                </div>

                <div class="info-box">
                    <p><?php echo $lang === 'ar' 
                        ? 'رسم التفتيش الأولي (300 جنيه) سيتم تطبيقه بعد قبول العامل والوصول إلى الموقع.'
                        : 'Initial inspection fee (300 EGP) will be applied after worker acceptance and arrival.'; 
                    ?></p>
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

                <form method="POST">
                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'التخصص المطلوب' : 'Required Specialization'; ?> *</label>
                        <select name="specialization" required>
                            <option value="">-- <?php echo $lang === 'ar' ? 'اختر التخصص' : 'Select specialization'; ?> --</option>
                            <?php foreach ($specializations as $i => $spec): ?>
                                <option value="<?php echo $spec; ?>">
                                    <?php echo $lang === 'ar' ? $specializationsAr[$i] : $spec; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'وصف المشكلة' : 'Problem Description'; ?> *</label>
                        <textarea name="description" placeholder="<?php echo $lang === 'ar' 
                            ? 'اشرح المشكلة بالتفصيل...' 
                            : 'Describe the problem in detail...'; ?>" required></textarea>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'العنوان' : 'Address'; ?> *</label>
                        <input type="text" name="address" placeholder="<?php echo $lang === 'ar' 
                            ? 'ادخل عنوانك الكامل' 
                            : 'Enter your full address'; ?>" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang === 'ar' ? 'الأولوية' : 'Urgency'; ?> *</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="urgent" name="urgency" value="Urgent" <?php echo ($_POST['urgency'] ?? 'Normal') === 'Urgent' ? 'checked' : ''; ?>>
                                <label for="urgent"><?php echo $lang === 'ar' ? 'عاجل' : 'Urgent'; ?></label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="normal" name="urgency" value="Normal" <?php echo ($_POST['urgency'] ?? 'Normal') === 'Normal' ? 'checked' : ''; ?>>
                                <label for="normal"><?php echo $lang === 'ar' ? 'عادي' : 'Normal'; ?></label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <?php echo $lang === 'ar' ? 'إنشاء الطلب' : 'Create Request'; ?>
                    </button>
                </form>

                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="user_dashboard.php?lang=<?php echo $lang; ?>" style="color: #4A5249; text-decoration: none;">
                        ← <?php echo $lang === 'ar' ? 'العودة' : 'Back'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
