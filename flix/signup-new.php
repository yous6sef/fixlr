<?php
// ========================================
// FLIX Signup - Bilingual
// ========================================

include 'lang.php';
include 'db.php';

$error_message = '';
$success_message = '';
$user_type = isset($_GET['type']) ? $_GET['type'] : 'user';

if (!in_array($user_type, ['user', 'worker'])) {
    $user_type = 'user';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $city = $_POST['city'] ?? '';
    $signup_type = $_POST['user_type'] ?? 'user';
    
    // Validate input
    $errors = [];
    
    if (empty($name)) $errors[] = t('required_field');
    if (empty($email)) $errors[] = t('required_field');
    if (empty($phone)) $errors[] = t('required_field');
    if (empty($password)) $errors[] = t('required_field');
    if (empty($city)) $errors[] = t('required_field');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('invalid_email');
    }
    
    if ($password !== $password_confirm) {
        $errors[] = t('password_mismatch');
    }
    
    if (strlen($password) < 6) {
        $errors[] = t('weak_password');
    }
    
    if (count($errors) > 0) {
        $error_message = implode(', ', $errors);
    } else {
        try {
            if ($signup_type === 'worker') {
                // Check if worker already exists
                $check = pg_query_params($db, "SELECT id FROM workers WHERE email = $1 OR phone = $1", [$email, $phone]);
                
                if (pg_num_rows($check) > 0) {
                    $error_message = t('account_exists');
                } else {
                    $specialization = $_POST['specialization'] ?? '';
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert = pg_query_params($db, 
                        "INSERT INTO workers (name, email, phone, password, city, specialization, approved, status, created_at) 
                         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW())",
                        [$name, $email, $phone, $password_hash, $city, $specialization, 'pending', 'active']
                    );
                    
                    if ($insert) {
                        $_SESSION['user_id'] = pg_last_oid($insert);
                        $_SESSION['user_type'] = 'worker';
                        $success_message = t('success_signup');
                        header("Refresh: 2; url=login-new.php?lang=$lang");
                    } else {
                        $error_message = t('error_creating_account');
                    }
                }
            } else {
                // Regular user signup
                $check = pg_query_params($db, "SELECT id FROM users WHERE email = $1 OR phone = $1", [$email, $phone]);
                
                if (pg_num_rows($check) > 0) {
                    $error_message = t('account_exists');
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert = pg_query_params($db, 
                        "INSERT INTO users (name, email, phone, password_hash, city, user_type, account_status, created_at) 
                         VALUES ($1, $2, $3, $4, $5, $6, $7, NOW())",
                        [$name, $email, $phone, $password_hash, $city, 'user', 'active']
                    );
                    
                    if ($insert) {
                        $_SESSION['user_id'] = pg_last_oid($insert);
                        $_SESSION['user_type'] = 'user';
                        $success_message = t('success_signup');
                        header("Refresh: 2; url=login-new.php?lang=$lang");
                    } else {
                        $error_message = t('error_creating_account');
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = t('error');
        }
    }
}

global $services, $cities;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('signup'); ?> - FLIX</title>
    <link rel="stylesheet" href="css/premium-ui.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            min-height: 100vh;
            padding: var(--spacing-lg);
        }
        
        .signup-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: var(--spacing-lg);
            animation: fadeIn 0.5s ease;
        }
        
        .signup-header {
            text-align: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .signup-header h1 {
            font-size: 2rem;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-sm);
        }
        
        .signup-header p {
            color: var(--color-neutral-600);
        }
        
        .type-selector {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            border-bottom: 2px solid var(--color-neutral-200);
            padding-bottom: var(--spacing-md);
        }
        
        .type-btn {
            flex: 1;
            padding: var(--spacing-md);
            background: var(--color-neutral-100);
            border: 2px solid transparent;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .type-btn.active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }
        
        .type-btn:hover {
            border-color: var(--color-primary);
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
            background: rgba(255, 255, 255, 0.9);
        }
        
        .lang-switcher a.active {
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .form-section {
            margin-bottom: var(--spacing-lg);
        }
        
        .form-section.hidden {
            display: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
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
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .form-group textarea {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--color-neutral-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
            resize: vertical;
            min-height: 80px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .terms {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }
        
        .terms input[type="checkbox"] {
            width: auto;
            margin-top: 4px;
        }
        
        .terms label {
            margin-bottom: 0;
            font-weight: 400;
            font-size: 0.9rem;
        }
        
        .submit-btn {
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
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(14, 165, 233, 0.3);
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
        
        .login-link {
            text-align: center;
            margin-top: var(--spacing-md);
            color: var(--color-neutral-600);
        }
        
        .login-link a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
        
        @media (max-width: 768px) {
            .signup-container {
                padding: var(--spacing-md);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="lang-switcher">
        <a href="<?php echo getLangLink('ar'); ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        <a href="<?php echo getLangLink('en'); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
    </div>
    
    <div class="signup-container">
        <div class="signup-header">
            <h1><?php echo t('sign_up'); ?></h1>
            <p><?php echo t('i_have_account'); ?> <a href="login-new.php?lang=<?php echo $lang; ?>" style="color: var(--color-primary); text-decoration: none;"><?php echo t('sign_in'); ?></a></p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="type-selector">
                <button type="button" class="type-btn active" data-type="user">
                    <?php echo t('i_need_service'); ?>
                </button>
                <button type="button" class="type-btn" data-type="worker">
                    <?php echo t('i_provide_service'); ?>
                </button>
            </div>
            
            <input type="hidden" name="user_type" id="user_type" value="user">
            
            <!-- User Registration Form -->
            <div id="user-form" class="form-section">
                <div class="form-row full">
                    <div class="form-group">
                        <label><?php echo t('full_name'); ?> *</label>
                        <input type="text" name="full_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo t('email'); ?> *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('phone'); ?> *</label>
                        <input type="tel" name="phone" required>
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label><?php echo t('city'); ?> *</label>
                        <select name="city" required>
                            <option value="">-- <?php echo t('select_city'); ?> --</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['name_en']); ?>">
                                    <?php echo htmlspecialchars(getName($c)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo t('password'); ?> *</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('confirm_password'); ?> *</label>
                        <input type="password" name="password_confirm" required>
                    </div>
                </div>
            </div>
            
            <!-- Worker Registration Form -->
            <div id="worker-form" class="form-section hidden">
                <div class="form-row full">
                    <div class="form-group">
                        <label><?php echo t('full_name'); ?> *</label>
                        <input type="text" name="full_name" value="">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo t('email'); ?> *</label>
                        <input type="email" name="email" value="">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('phone'); ?> *</label>
                        <input type="tel" name="phone" value="">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo t('specialization'); ?> *</label>
                        <select name="specialization">
                            <option value="">-- <?php echo t('select_specialization'); ?> --</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['name_en']); ?>">
                                    <?php echo htmlspecialchars(getName($s)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('city'); ?> *</label>
                        <select name="city" required>
                            <option value="">-- <?php echo t('select_city'); ?> --</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['name_en']); ?>">
                                    <?php echo htmlspecialchars(getName($c)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo t('password'); ?> *</label>
                        <input type="password" name="password" value="">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('confirm_password'); ?> *</label>
                        <input type="password" name="password_confirm" value="">
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label><?php echo t('documents'); ?></label>
                        <input type="file" name="documents" accept=".pdf,.jpg,.jpeg,.png">
                        <small style="color: var(--color-neutral-600); margin-top: var(--spacing-sm);">
                            <?php echo t('id_front'); ?>, <?php echo t('id_back'); ?>, <?php echo t('certificate'); ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms"><?php echo t('i_agree'); ?> <a href="#" style="color: var(--color-primary);"><?php echo t('terms_agree'); ?></a></label>
            </div>
            
            <button type="submit" class="submit-btn"><?php echo t('sign_up'); ?></button>
            
            <div class="login-link">
                <?php echo $lang === 'ar' ? 'لديك حساب بالفعل؟' : 'Already have an account?'; ?>
                <a href="login-new.php?lang=<?php echo $lang; ?>"><?php echo t('sign_in'); ?></a>
            </div>
        </form>
    </div>
    
    <script>
        const typeBtns = document.querySelectorAll('.type-btn');
        const userForm = document.getElementById('user-form');
        const workerForm = document.getElementById('worker-form');
        const userTypeInput = document.getElementById('user_type');
        
        typeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                typeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const type = this.dataset.type;
                userTypeInput.value = type;
                
                if (type === 'worker') {
                    userForm.classList.add('hidden');
                    workerForm.classList.remove('hidden');
                } else {
                    userForm.classList.remove('hidden');
                    workerForm.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
