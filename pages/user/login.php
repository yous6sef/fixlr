<?php
session_start();
include("../../core/db.php");
include('../../core/lang.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$_SESSION['lang'] = $lang;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $phone = $_POST['phone'];
  $password = $_POST['password'];
  $role = $_POST['role'];
  if ($role == "user"){
  $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE phone = :phone");
  $stmt->bindParam(':phone', $phone);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  else{
    $stmt = $conn->prepare("SELECT id, password, role FROM workers WHERE phone = :phone");
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
  }
if ($user && !empty($user['password'])) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'worker') {
            header("Location: ../../worker/worker_dashboard.php?lang=" . $lang);
            exit();
        } else {
            header("Location: user_dashboard.php?lang=" . $lang);
            exit();
        }
    } else {
        $error = $lang === 'ar' ? 'كلمة المرور غير صحيحة' : 'Incorrect password';
    }
} else {
    $error = $lang === 'ar' ? 'الأسم غير صحيح أو كلمة المرور غير مسجلة' : 'User not found or password not set';
}

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
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f7f8f6; }
        .login-box { background: white; border-radius: 14px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); padding: 2rem; max-width: 420px; width: 100%; }
        .logo { font-size: 2rem; font-weight: 700; color: #1A6B4A; text-align: center; margin-bottom: 0.5rem; }
        .title { font-size: 1.4rem; font-weight: 600; text-align: center; margin-bottom: 0.3rem; }
        .subtitle { font-size: 0.95rem; color: #6B7280; text-align: center; margin-bottom: 1.5rem; }
        .error { background: #FEE2E2; color: #991B1B; padding: 0.85rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.92rem; }
        .form-group { margin-bottom: 1rem; }
        .label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        input { width: 100%; padding: 0.85rem; border: 1.5px solid rgba(31,41,55,0.12); border-radius: 10px; font-size: 0.95rem; transition: all 0.2s; }
        input:focus { outline: none; border-color: #1A6B4A; background: #F0FDF4; }
        button { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #1A6B4A 0%, #2D9A6C 100%); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 1rem; transition: all 0.2s; }
        button:hover { transform: translateY(-1px); box-shadow: 0 12px 22px rgba(26,107,74,0.18); }
        .lang-btns { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem; }
        .lang-btns a { padding: 0.55rem 0.95rem; border: 1px solid rgba(31,41,55,0.12); background: #F8FAFC; border-radius: 10px; text-decoration: none; color: #111827; font-weight: 600; transition: all 0.2s; }
        .lang-btns a.active { background: #1A6B4A; color: white; border-color: #1A6B4A; }
        @media (max-width: 520px) { .login-box { padding: 1.5rem; } .title { font-size: 1.2rem; } }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">FLIX</div>
        <div class="title"><?php echo $lang === 'ar' ? 'مرحبا بعودتك' : 'Welcome Back'; ?></div>
        <div class="subtitle"><?php echo $lang === 'ar' ? 'سجل دخولك باستخدام رقم هاتفك' : 'Sign in with your phone'; ?></div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="label"><?php echo $lang === 'ar' ? ' رقم الهاتف' : 'Phone'; ?></label>
                <input type="text" name="phone" required>
            </div>
            <div class="form-group">
                <label class="label"><?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?></label>
                <input type="password" name="password" required>
            </div>
            <div class="flex items-center space-x-2 space-x-reverse">
                <input type="radio" id="user-role" name="role" value="user" checked class="w-4 h-4">
                <label for="user-role" class="text-gray-700">عميل</label>
                <input type="radio" id="worker-role" name="role" value="worker" class="w-4 h-4">
                <label for="worker-role" class="text-gray-700">عامل</label>
            </div><br>
            <button type="submit"><?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Sign In'; ?></button>
        </form>

        <div class="lang-btns">
            <a href="./login.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="./login.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>
    </div>
</body>
</html>
