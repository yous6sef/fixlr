<?php
// ========================================
// FLIX Login - Bilingual
// ========================================

include 'lang.php';
include 'db.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = $_POST['email_or_phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';
    
    // Validate input
    if (empty($email_or_phone) || empty($password)) {
        $error_message = t('required_field');
    } else {
        try {
            // Prepare query based on user type
            if ($user_type === 'admin') {
                $query = "SELECT * FROM users WHERE user_type = 'admin' AND (email = $1 OR phone = $1)";
            } else if ($user_type === 'worker') {
                $query = "SELECT * FROM workers WHERE (email = $1 OR phone = $1)";
            } else {
                $query = "SELECT * FROM users WHERE user_type = 'user' AND (email = $1 OR phone = $1)";
            }
            
            $result = pg_query_params($db, $query, [$email_or_phone]);
            
            if ($result && pg_num_rows($result) > 0) {
                $user = pg_fetch_assoc($result);
                
                // Verify password
                $password_field = ($user_type === 'worker') ? 'password' : 'password_hash';
                
                if (isset($user[$password_field]) && password_verify($password, $user[$password_field])) {
                    // Check account status
                    if ($user_type === 'worker' && isset($user['approved'])) {
                        if ($user['approved'] !== 'yes') {
                            $error_message = t('account_not_approved');
                        } else if (isset($user['status']) && $user['status'] === 'inactive') {
                            $error_message = t('account_inactive');
                        } else {
                            // Login successful
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_phone'] = $user['phone'];
                            $_SESSION['user_type'] = $user_type;
                            $_SESSION['user_city'] = $user['city'] ?? '';
                            
                            if ($user_type === 'worker') {
                                $_SESSION['specialization'] = $user['specialization'] ?? '';
                                header('Location: worker_dashboard.php');
                            } else if ($user_type === 'admin') {
                                header('Location: admin.php');
                            } else {
                                header('Location: usermain.php');
                            }
                            exit;
                        }
                    } else if ($user_type !== 'worker' && isset($user['account_status'])) {
                        if ($user['account_status'] !== 'active') {
                            $error_message = t('account_inactive');
                        } else {
                            // Login successful
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_phone'] = $user['phone'];
                            $_SESSION['user_type'] = $user_type;
                            $_SESSION['user_city'] = $user['city'] ?? '';
                            
                            if ($user_type === 'admin') {
                                header('Location: admin.php');
                            } else {
                                header('Location: usermain.php');
                            }
                            exit;
                        }
                    } else {
                        // Login successful (fallback)
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_phone'] = $user['phone'];
                        $_SESSION['user_type'] = $user_type;
                        $_SESSION['user_city'] = $user['city'] ?? '';
                        
                        if ($user_type === 'worker') {
                            $_SESSION['specialization'] = $user['specialization'] ?? '';
                            header('Location: worker_dashboard.php');
                        } else if ($user_type === 'admin') {
                            header('Location: admin.php');
                        } else {
                            header('Location: usermain.php');
                        }
                        exit;
                    }
                } else {
                    $error_message = t('invalid_credentials');
                }
            } else {
                $error_message = t('invalid_credentials');
            }
        } catch (Exception $e) {
            $error_message = t('error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login'); ?> - FLIX</title>
    <link rel="stylesheet" href="css/premium-ui.css">
    <style>
        .login-container {
            display: flex;
            height: 100vh;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: var(--spacing-lg);
        }
        
        .login-left h1 {
            font-size: 3.5rem;
            margin-bottom: var(--spacing-md);
            font-weight: 800;
            animation: slideInLeft 0.8s ease-out;
        }
        
        .login-left p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 400px;
            text-align: center;
        }
        
        .login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: var(--spacing-lg);
            background: var(--color-neutral-50);
        }
        
        .login-form-wrapper {
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .login-header h2 {
            font-size: 2rem;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-sm);
        }
        
        .lang-switcher {
            position: absolute;
            top: var(--spacing-md);
            right: var(--spacing-md);
            display: flex;
            gap: var(--spacing-sm);
        }
        
        .lang-switcher a {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .lang-switcher a.active {
            background: var(--color-primary);
            color: white;
        }
        
        .lang-switcher a:not(.active) {
            color: var(--color-primary);
            border: 1px solid var(--color-primary);
        }
        
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: var(--spacing-lg);
            border-bottom: 2px solid var(--color-neutral-200);
        }
        
        .tab {
            flex: 1;
            padding: var(--spacing-md);
            text-align: center;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-neutral-600);
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }
        
        .form-group {
            margin-bottom: var(--spacing-md);
        }
        
        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--color-neutral-700);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--color-neutral-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }
        
        .remember-me input[type="checkbox"] {
            width: auto;
        }
        
        .form-group button {
            width: 100%;
            padding: var(--spacing-md);
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: var(--spacing-md);
        }
        
        .form-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(14, 165, 233, 0.3);
        }
        
        .form-links {
            text-align: center;
            margin-top: var(--spacing-md);
        }
        
        .form-links a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 0.9rem;
            margin: 0 var(--spacing-sm);
        }
        
        .form-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            animation: slideDown 0.3s ease;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border-left: 4px solid #22c55e;
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 1024px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                min-height: 300px;
            }
            
            .login-right {
                padding: var(--spacing-md);
            }
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
                    <div style="border-bottom: 2px solid var(--color-neutral-200); margin-bottom: var(--spacing-lg);">
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
                    
                    <div class="form-group">
                        <button type="submit"><?php echo t('sign_in'); ?></button>
                    </div>
                </form>
                
                <div class="form-links">
                    <a href="<?php echo getLangLink($lang); ?>"><?php echo t('forgot_password'); ?></a>
                    <span>|</span>
                    <a href="signup-new.php?lang=<?php echo $lang; ?>"><?php echo t('sign_up'); ?></a>
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
