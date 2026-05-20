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
            // FIXED: Pulling 'password' but aliasing it as 'password_hash' for the admins table
            $stmt = $conn->prepare('SELECT id, password AS password_hash FROM admins WHERE email = ? OR phone = ? LIMIT 1');
            $stmt->execute([$loginInput, $loginInput]);
        } elseif ($role === 'worker') {
            $stmt = $conn->prepare('SELECT id, name, password AS password_hash, approved, status FROM workers WHERE (email = ? OR phone = ?) LIMIT 1');
            $stmt->execute([$loginInput, $loginInput]);
        } else {
            // Note: If you ever get this same error for normal users, change this to "password AS password_hash" too!
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
                // Workers must be approved by admin before becoming active
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
    <title>فليكس - تسجيل الدخول</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-indigo-600">فليكس</h1>
                <p class="text-gray-600 mt-2">تسجيل الدخول إلى حسابك</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 text-right bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 text-right bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">البريد الإلكتروني أو الهاتف</label>
                    <input type="text" name="login-input" placeholder="email أو phone" value="<?= htmlspecialchars($_POST['login-input'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">كلمة المرور</label>
                    <input type="password" name="login-password" placeholder="••••••••"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>

                <div class="flex items-center justify-between gap-4 text-gray-700">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="role" value="user" <?= (!isset($_POST['role']) || $_POST['role'] === 'user') ? 'checked' : '' ?> class="w-4 h-4">
                        عميل
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="role" value="worker" <?= (isset($_POST['role']) && $_POST['role'] === 'worker') ? 'checked' : '' ?> class="w-4 h-4">
                        فني
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="role" value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : '' ?> class="w-4 h-4">
                        مدير
                    </label>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition">تسجيل الدخول</button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                ليس لديك حساب؟ <a href="signup.php" class="text-indigo-600 font-semibold hover:underline">إنشاء حساب</a>
            </div>

            <p class="text-center text-gray-500 text-xs mt-6">© فليكس - منصة الخدمات المنزلية</p>
        </div>
    </div>
</body>
</html>
