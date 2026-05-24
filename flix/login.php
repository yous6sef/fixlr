<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login-input'] ?? '');
    $password = $_POST['login-password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($loginInput === '' || $password === '') {
        $error = 'يرجى إدخال البريد الإلكتروني أو رقم الهاتف وكلمة المرور.';
    } else {
        if ($role === 'admin') {
            $stmt = $conn->prepare('SELECT id, password_hash FROM admins WHERE email = ? OR phone = ? LIMIT 1');
            $stmt->execute([$loginInput, $loginInput]);
        } elseif ($role === 'worker') {
            $stmt = $conn->prepare('SELECT id, name, password_hash, approved, status FROM workers WHERE (email = ? OR phone = ?) LIMIT 1');
            $stmt->execute([$loginInput, $loginInput]);
        } else {
            $stmt = $conn->prepare('SELECT id, name, password_hash, account_status FROM users WHERE (email = ? OR phone = ?) AND user_type = ? LIMIT 1');
            $stmt->execute([$loginInput, $loginInput, $role]);
        }
        
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($account && password_verify($password, $account['password_hash'] ?? '')) {
            if ($role === 'admin') {
                $_SESSION['admin_id'] = $account['id'];
                $_SESSION['user_role'] = 'admin';
                header('Location: admin.php');
                exit();
            }

            if ($role === 'user') {
                if (($account['account_status'] ?? 'active') !== 'active') {
                    $error = 'حسابك غير مفعل حالياً. تواصل مع الدعم.';
                } else {
                    $_SESSION['user_id'] = $account['id'];
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['user_name'] = $account['name'] ?? '';
                    header('Location: user_dashboard.php');
                    exit();
                }
            }

            if ($role === 'worker') {
                $approved = $account['approved'] ?? 'pending';
                $status = $account['status'] ?? 'inactive';
                
                if ($approved !== 'yes') {
                    $error = 'حسابك قيد المراجعة من قبل الإدارة. سيتم إعلامك عند الموافقة.';
                } elseif ($status !== 'active') {
                    $error = 'حسابك غير مفعل. تواصل مع الدعم.';
                } else {
                    $_SESSION['user_id'] = $account['id'];
                    $_SESSION['user_role'] = 'worker';
                    $_SESSION['user_name'] = $account['name'] ?? '';
                    header('Location: worker_dashboard.php');
                    exit();
                }
            }
        } else {
            $error = 'بيانات الدخول غير صحيحة، يرجى المراجعة.';
        }
    }
}

if (isset($_GET['registered'])) {
    $success = 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول.';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - Flix | منصة الخدمات المنزلية</title>
    <link rel="stylesheet" href="/css/premium-ui.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
            padding: var(--spacing-2xl);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }
        
        .login-header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: var(--spacing-md);
        }
        
        .login-header p {
            color: var(--neutral-600);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            color: var(--neutral-700);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            font-family: inherit;
            transition: border-color var(--transition-base);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input {
            display: none;
        }
        
        .role-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
            border: 2px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            cursor: pointer;
            font-weight: 600;
            transition: all var(--transition-base);
            text-align: center;
        }
        
        .role-option input:checked + .role-label {
            border-color: var(--primary);
            background-color: rgba(14, 165, 233, 0.1);
            color: var(--primary);
        }
        
        .form-group.error input {
            border-color: var(--error);
        }
        
        .error-message {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: var(--spacing-sm);
            padding: var(--spacing-md);
            background-color: rgba(239, 68, 68, 0.1);
            border-radius: var(--radius-lg);
            border-right: 3px solid var(--error);
        }
        
        .success-message {
            color: var(--success);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-md);
            background-color: rgba(16, 185, 129, 0.1);
            border-radius: var(--radius-lg);
            border-right: 3px solid var(--success);
        }
        
        .submit-btn {
            width: 100%;
            padding: var(--spacing-md);
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-base);
            box-shadow: var(--shadow-lg);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .divider {
            text-align: center;
            margin: var(--spacing-xl) 0;
            color: var(--neutral-400);
            font-size: 0.875rem;
        }
        
        .signup-link {
            text-align: center;
            color: var(--neutral-600);
            font-size: 0.875rem;
            margin-top: var(--spacing-lg);
        }
        
        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition-base);
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: var(--spacing-lg);
        }
        
        .back-home a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color var(--transition-base);
        }
        
        .back-home a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Flix</h1>
            <p>تسجيل الدخول إلى حسابك</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>نوع الحساب</label>
                <div class="role-selector">
                    <div class="role-option">
                        <input type="radio" name="role" value="user" id="role-user" <?= (!isset($_POST['role']) || $_POST['role'] === 'user') ? 'checked' : '' ?>>
                        <label for="role-user" class="role-label">👤 عميل</label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" value="worker" id="role-worker" <?= (isset($_POST['role']) && $_POST['role'] === 'worker') ? 'checked' : '' ?>>
                        <label for="role-worker" class="role-label">👷 فني</label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" value="admin" id="role-admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : '' ?>>
                        <label for="role-admin" class="role-label">⚙️ مدير</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="login-input">البريد الإلكتروني أو رقم الهاتف</label>
                <input 
                    type="text" 
                    id="login-input"
                    name="login-input" 
                    placeholder="أدخل بريدك أو رقمك" 
                    value="<?= htmlspecialchars($_POST['login-input'] ?? '') ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="login-password">كلمة المرور</label>
                <input 
                    type="password" 
                    id="login-password"
                    name="login-password" 
                    placeholder="••••••••"
                    required>
            </div>

            <button type="submit" class="submit-btn">🔓 دخول الآن</button>
        </form>

        <div class="divider">────────────────</div>

        <div class="signup-link">
            ليس لديك حساب؟ 
            <a href="signup.php">إنشاء حساب جديد</a>
        </div>

        <div class="back-home">
            <a href="landing.html">← العودة للصفحة الرئيسية</a>
        </div>

        <p style="text-align: center; color: var(--neutral-400); font-size: 0.75rem; margin-top: var(--spacing-xl);">
            © 2024-2026 Flix. جميع الحقوق محفوظة 🇪🇬
        </p>
    </div>
</body>
</html>
    </div>
</body>
</html>
