<?php
/**
 * FLIX Login - Consolidated Version
 * Bilingual support (Arabic/English)
 * Professional UI with error handling & fallback to demo mode
 */

session_start();
include 'lang.php';
include 'db.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = $_POST['email_or_phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';
    
    // DEBUG: Log the attempt
    error_log("LOGIN ATTEMPT: email=$email_or_phone, user_type=$user_type, password_len=" . strlen($password));
    
    // Validate input
    if (empty($email_or_phone) || empty($password)) {
        $error_message = t('required_field');
    } else {
        $user = null;
        
        // Try to find user first
        global $DEMO_USERS, $DEMO_WORKERS;
        
        // For demo mode, always search demo data
        if ($user_type === 'worker') {
            foreach ($DEMO_WORKERS as $demo_user) {
                if ($demo_user['email'] === $email_or_phone || $demo_user['phone'] === $email_or_phone) {
                    $user = $demo_user;
                    break;
                }
            }
        } else {
            foreach ($DEMO_USERS as $demo_user) {
                if ($demo_user['email'] === $email_or_phone) {
                    if ($user_type === 'admin' && $demo_user['user_type'] === 'admin') {
                        $user = $demo_user;
                        break;
                    } else if ($user_type === 'user' && $demo_user['user_type'] === 'user') {
                        $user = $demo_user;
                        break;
                    }
                }
            }
        }
        
        if ($user) {
            // Verify password
            $stored_password = $user['password_hash'] ?? $user['password'] ?? null;
            
            if ($stored_password && password_verify($password, $stored_password)) {
                // Password is correct - check account status and login
                $can_login = true;
                
                if ($user_type === 'worker') {
                    // Check worker status
                    if (($user['approved'] ?? null) !== 'yes') {
                        $error_message = t('account_not_approved');
                        $can_login = false;
                    } else if (($user['status'] ?? 'inactive') === 'inactive') {
                        $error_message = t('account_inactive');
                        $can_login = false;
                    }
                } else {
                    // Check user/admin status
                    if (($user['account_status'] ?? 'active') !== 'active') {
                        $error_message = t('account_inactive');
                        $can_login = false;
                    }
                }
                
                if ($can_login) {
                    // Set session and redirect
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'] ?? '';
                    $_SESSION['user_email'] = $user['email'] ?? '';
                    $_SESSION['user_phone'] = $user['phone'] ?? '';
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['user_city'] = $user['city'] ?? '';
                    
                    if ($user_type === 'worker') {
                        $_SESSION['specialization'] = $user['specialization'] ?? '';
                        header('Location: worker_dashboard.php?lang=' . $lang);
                        exit;
                    } else if ($user_type === 'admin') {
                        header('Location: admin.php?lang=' . $lang);
                        exit;
                    } else {
                        header('Location: user_dashboard.php?lang=' . $lang);
                        exit;
                    }
                }
            } else {
                $error_message = t('invalid_credentials');
            }
        } else {
            $error_message = t('invalid_credentials');
        }
    }
}

if (isset($_GET['registered'])) {
    $success_message = t('account_created_successfully');
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('sign_in'); ?> - FLIX</title>
    <link rel="stylesheet" href="css/premium-ui.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            background: #f5f5f5;
        }
        
        .login-container {
            display: flex;
            min-height: 100vh;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 2rem;
        }
        
        .login-left h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            animation: slideInLeft 0.8s ease-out;
        }
        
        .login-left p {
            font-size: 1.25rem;
            opacity: 0.95;
            max-width: 400px;
            text-align: center;
            font-weight: 300;
            line-height: 1.6;
        }
        
        .login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: white;
        }
        
        .login-form-wrapper {
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            font-size: 1.875rem;
            color: #0f766e;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .lang-switcher {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
            z-index: 10;
        }
        
        .lang-switcher a {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: white;
            color: #0f766e;
            border: 1px solid #e5e5e5;
        }
        
        .lang-switcher a.active {
            background: #0f766e;
            color: white;
            border-color: #0f766e;
        }
        
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e5e5e5;
        }
        
        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 0.875rem;
            font-weight: 600;
            color: #9ca3af;
            transition: all 0.2s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab.active {
            color: #0f766e;
            border-bottom-color: #0f766e;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4b5563;
            font-size: 0.875rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
            background: #fafafa;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .remember-me input[type="checkbox"] {
            width: auto;
        }
        
        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 118, 110, 0.2);
        }
        
        .form-links {
            text-align: center;
            margin-top: 1rem;
        }
        
        .form-links a {
            color: #0f766e;
            text-decoration: none;
            font-size: 0.875rem;
            margin: 0 0.5rem;
            font-weight: 500;
        }
        
        .form-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            animation: slideDown 0.3s ease;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #7f1d1d;
            border-left: 4px solid #dc2626;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #059669;
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 1024px) {
            .login-container { flex-direction: column; }
            .login-left { min-height: 300px; }
            .login-right { padding: 1.5rem; }
            .lang-switcher { position: static; margin-bottom: 1rem; }
        }
    </style>
</head>
<body>
    <div class="lang-switcher">
        <a href="<?php echo getLangLink('ar'); ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        <a href="<?php echo getLangLink('en'); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
    </div>
    
    <div class="login-container">
        <div class="login-left">
            <h1>FLIX</h1>
            <p><?php echo $lang === 'ar' ? 'حلولك الموثوقة لجميع خدماتك المنزلية' : 'Your trusted solution for all home services'; ?></p>
        </div>
        
        <div class="login-right">
            <div class="login-form-wrapper">
                <div class="login-header">
                    <h2><?php echo t('sign_in'); ?></h2>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div style="border-bottom: 2px solid #e5e5e5; margin-bottom: 1.5rem;">
                        <div class="tabs">
                            <button type="button" class="tab active" data-role="user"><?php echo t('i_need_service'); ?></button>
                            <button type="button" class="tab" data-role="worker"><?php echo t('i_provide_service'); ?></button>
                            <button type="button" class="tab" data-role="admin">Admin</button>
                        </div>
                    </div>
                    
                    <input type="hidden" id="user_type" name="user_type" value="user">
                    
                    <div class="form-group">
                        <label><?php echo t('email_or_phone'); ?></label>
                        <input type="text" name="email_or_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo t('password'); ?></label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember" style="margin-bottom: 0;"><?php echo t('remember_me'); ?></label>
                    </div>
                    
                    <button type="submit" class="submit-btn"><?php echo t('sign_in'); ?></button>
                </form>
                
                <div class="form-links">
                    <a href="<?php echo getLangLink($lang); ?>"><?php echo t('forgot_password'); ?></a>
                    <span>|</span>
                    <a href="signup.php?lang=<?php echo $lang; ?>"><?php echo t('sign_up'); ?></a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const tabs = document.querySelectorAll('.tab');
        const userTypeInput = document.getElementById('user_type');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                userTypeInput.value = this.dataset.role;
            });
        });
    </script>
</body>
</html>
