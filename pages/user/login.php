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

  if ($role == "user") {
      $stm = $conn->prepare("SELECT id, password, role FROM users WHERE phone = :phone");
      $stm->bindParam(':phone', $phone);
      $stm->execute();
      $user = $stm->fetch(PDO::FETCH_ASSOC);

      if ($user && !empty($user['password'])) {
          if (password_verify($password, $user['password'])) {
              $_SESSION['user_id'] = $user['id'];
              $_SESSION['role'] = $user['role'];
              if ($user['role'] === 'admin') {
                  header("Location: ../admin/admin_dashboard.php?lang=" . $lang);
              } else {
                  header("Location: ../user/user_dashboard.php?lang=" . $lang);
              }
          } else {
              $error = $lang === 'ar' ? 'كلمة المرور غير صحيحة' : 'Incorrect password';
          }
      } else {
          $error = $lang === 'ar' ? 'الأسم غير صحيح أو كلمة المرور غير مسجلة' : 'User not found or password not set';
      }
  } else {
      $stmt = $conn->prepare("SELECT id, password, role FROM workers WHERE phone = :phone");
      $stmt->bindParam(':phone', $phone);
      $stmt->execute();
      $worker = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($worker && !empty($worker['password'])) {
          if (password_verify($password, $worker['password'])) {
              $_SESSION['user_id'] = $worker['id'];
              $_SESSION['role'] = $worker['role'];
              header("Location: ../worker/worker_dashboard.php?lang=" . $lang);
              exit();
          } else {
              $error = $lang === 'ar' ? 'كلمة المرور غير صحيحة' : 'Incorrect password';
          }
      } else {
          $error = $lang === 'ar' ? 'الأسم غير صحيح أو كلمة المرور غير مسجلة' : 'User not found or password not set';
      }
  }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'تسجيل الدخول' : 'Login';
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

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

        .header-nav a:hover {
            opacity: 0.8;
        }

        .lang-switch-header {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            color: white;
            font-weight: 600;
            transition: background 0.2s;
            font-size: 0.9rem;
        }

        .lang-switch-header:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Main content */
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 3rem;
            max-width: 420px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.875rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.92rem;
            border: 1px solid #FECACA;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 107, 74, 0.1);
        }

        .role-group {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--surface-lighter);
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .role-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            display: block;
        }

        .role-options {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .role-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .role-option input[type="radio"] {
            cursor: pointer;
            width: auto;
        }

        .role-option label {
            cursor: pointer;
            margin: 0;
            font-size: 0.95rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(26, 107, 74, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .signup-link a:hover {
            color: var(--primary-light);
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

            .login-wrapper {
                padding: 1rem;
            }

            .login-container {
                padding: 2rem 1.5rem;
            }

            .login-header .logo-text {
                font-size: 2rem;
            }

            .login-header h1 {
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
            <a href="signup.php?lang=<?php echo $lang; ?>">
                <?php echo $lang === 'ar' ? 'إنشاء حساب' : 'Sign Up'; ?>
            </a>
        </nav>
    </header>

    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-text">FLIX</div>
                <h1><?php echo $lang === 'ar' ? 'مرحبا بعودتك' : 'Welcome Back'; ?></h1>
                <p><?php echo $lang === 'ar' ? 'سجل دخولك باستخدام رقم هاتفك' : 'Sign in with your phone'; ?></p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="label"><?php echo $lang === 'ar' ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                    <input type="text" name="phone" placeholder="<?php echo $lang === 'ar' ? '+20 123 456 7890' : '+20 123 456 7890'; ?>" required>
                </div>

                <div class="form-group">
                    <label class="label"><?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?></label>
                    <input type="password" name="password" placeholder="<?php echo $lang === 'ar' ? 'أدخل كلمة المرور' : 'Enter your password'; ?>" required>
                </div>

                <div class="role-group">
                    <label class="role-label"><?php echo $lang === 'ar' ? 'اختر نوع الحساب' : 'Account Type'; ?></label>
                    <div class="role-options">
                        <div class="role-option">
                            <input type="radio" id="user" name="role" value="user" checked>
                            <label for="user"><?php echo $lang === 'ar' ? 'عميل' : 'Customer'; ?></label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="worker" name="role" value="worker">
                            <label for="worker"><?php echo $lang === 'ar' ? 'عامل' : 'Service Provider'; ?></label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Sign In'; ?></button>

                <div class="signup-link">
                    <?php echo $lang === 'ar' ? 'ليس لديك حساب؟' : "Don't have an account?"; ?>
                    <a href="signup.php?lang=<?php echo $lang; ?>">
                        <?php echo $lang === 'ar' ? 'أنشئ واحدًا' : 'Create one'; ?>
                    </a>
                </div>
            </form>

            <div class="lang-btns">
                <a href="./login.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
                <a href="./login.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
            </div>
        </div>
    </div>
</body>
</html>
