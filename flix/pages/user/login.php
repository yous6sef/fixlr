<?php
session_start();
include('../../core/lang.php');

$lang = $_GET['lang'] ?? 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$error = '';

$accounts = [
    'user@test.com' => ['password' => 'User@123456', 'type' => 'user', 'name' => 'Ahmed Hassan'],
    'worker@test.com' => ['password' => 'Worker@123456', 'type' => 'worker', 'name' => 'Mohamed Ali'],
    'admin@test.com' => ['password' => 'Admin@123456', 'type' => 'admin', 'name' => 'Admin User'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (isset($accounts[$email]) && $accounts[$email]['password'] === $password) {
        $user = $accounts[$email];
        $_SESSION['user_id'] = md5($email);
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $email;
        $_SESSION['user_type'] = $user['type'];
        $_SESSION['lang'] = $lang;
        
        $page = $user['type'] === 'admin' ? '../admin/admin.php' : ($user['type'] === 'worker' ? '../worker/worker_dashboard.php' : './user_dashboard.php');
        header('Location: ' . $page . '?lang=' . $lang);
        exit();
    }
    $error = $lang === 'ar' ? 'بريد إلكتروني أو كلمة مرور غير صحيحة' : 'Invalid email or password';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Login'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { background: white; border-radius: 14px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 2rem; max-width: 400px; width: 100%; }
        .logo { font-size: 2rem; font-weight: 700; color: #1A6B4A; text-align: center; margin-bottom: 0.5rem; }
        .title { font-size: 1.4rem; font-weight: 600; text-align: center; margin-bottom: 0.3rem; }
        .subtitle { font-size: 0.9rem; color: #8A9389; text-align: center; margin-bottom: 1.5rem; }
        .error { background: #FEE2E2; color: #991B1B; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; }
        .form-group { margin-bottom: 1rem; }
        .label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #141714; }
        input { width: 100%; padding: 0.75rem; border: 1.5px solid rgba(20,23,20,0.1); border-radius: 8px; font-size: 0.95rem; transition: all 0.2s; }
        input:focus { outline: none; border-color: #1A6B4A; background: #E8F5EE; }
        button { width: 100%; padding: 0.85rem; background: linear-gradient(135deg, #1A6B4A 0%, #2D9A6C 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 1rem; transition: all 0.2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(26,107,74,0.2); }
        .demo { background: #E8F5EE; border: 1px solid #D1FAE5; border-radius: 8px; padding: 1rem; margin-top: 1.5rem; font-size: 0.85rem; color: #4A5249; }
        .demo-title { font-weight: 600; color: #1A6B4A; margin-bottom: 0.5rem; }
        .demo-item { margin-bottom: 0.4rem; line-height: 1.4; }
        code { background: rgba(255,255,255,0.6); padding: 0.15rem 0.3rem; border-radius: 4px; font-family: monospace; font-size: 0.85rem; }
        .lang-btns { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem; }
        .lang-btns a { padding: 0.5rem 1rem; border: 1px solid rgba(20,23,20,0.1); background: #F7F8F6; border-radius: 8px; text-decoration: none; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .lang-btns a.active { background: #1A6B4A; color: white; border-color: #1A6B4A; }
        @media (max-width: 480px) { .login-box { padding: 1.5rem; } .title { font-size: 1.2rem; } }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">FLIX</div>
        <div class="title"><?php echo $lang === 'ar' ? 'مرحبا بعودتك' : 'Welcome Back'; ?></div>
        <div class="subtitle"><?php echo $lang === 'ar' ? 'تسجيل الدخول إلى حسابك' : 'Sign in to your account'; ?></div>

        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="label"><?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?></label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label class="label"><?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?></label>
                <input type="password" name="password" required>
            </div>
            <button type="submit"><?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Sign In'; ?></button>
        </form>

        <div class="demo">
            <div class="demo-title"><?php echo $lang === 'ar' ? 'حسابات تجريبية' : 'Demo Accounts'; ?></div>
            <div class="demo-item"><strong><?php echo $lang === 'ar' ? 'مستخدم' : 'User'; ?></strong><br><code>user@test.com</code> / <code>User@123456</code></div>
            <div class="demo-item"><strong><?php echo $lang === 'ar' ? 'عامل' : 'Worker'; ?></strong><br><code>worker@test.com</code> / <code>Worker@123456</code></div>
            <div class="demo-item"><strong><?php echo $lang === 'ar' ? 'مسؤول' : 'Admin'; ?></strong><br><code>admin@test.com</code> / <code>Admin@123456</code></div>
        </div>

        <div class="lang-btns">
            <a href="./login.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="./login.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>
    </div>
</body>
</html>
