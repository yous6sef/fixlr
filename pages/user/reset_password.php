<?php
session_start();
include("../../core/lang.php");

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$_SESSION['lang'] = $lang;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'إعادة تعيين كلمة المرور - فليكس' : 'Reset Password - FLIX';
        $pageDescription = $lang === 'ar' ? 'أعد تعيين كلمة المرور الخاصة بك' : 'Reset your account password';
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        /* keep same variables and styles as login for consistent UI */
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
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto, Oxygen, Ubuntu, Cantarell,sans-serif;background:var(--surface-light);color:var(--text-primary);min-height:100vh;display:flex;flex-direction:column}
        header{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 100%);color:#fff;padding:1.5rem 2rem;display:flex;justify-content:space-between;align-items:center;box-shadow:var(--shadow)}
        .logo{font-size:1.75rem;font-weight:700}
        .reset-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem}
        .reset-container{background:var(--surface);border-radius:var(--radius);box-shadow:var(--shadow);padding:2.5rem;max-width:520px;width:100%}
        .reset-header{text-align:center;margin-bottom:1.5rem}
        .reset-header .logo-text{font-size:2.25rem;font-weight:700;color:var(--primary);margin-bottom:0.25rem}
        .h1{font-size:1.35rem;font-weight:600;margin-bottom:0.25rem}
        .p{color:var(--text-secondary);font-size:0.95rem}
        .form-group{margin-bottom:1rem}
        .label{display:block;font-weight:500;margin-bottom:0.5rem;color:var(--text-primary);font-size:0.95rem}
        input[type=text], input[type=password], input[type=email]{width:100%;padding:0.9rem;border:1px solid var(--border);border-radius:8px;font-size:0.95rem}
        input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(26,107,74,0.08)}
        .btn-primary{width:100%;padding:0.9rem;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 100%);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer}
        .helper{font-size:0.92rem;color:var(--text-secondary);margin-top:0.5rem}
        .error{background:#FEE2E2;color:#991B1B;padding:0.8rem;border-radius:8px;margin-bottom:1rem;border:1px solid #FECACA}
        .success{background:#E6F6ED;color:#14532D;padding:0.8rem;border-radius:8px;margin-bottom:1rem;border:1px solid #BBF7D0}
        @media(max-width:640px){.reset-container{padding:1.5rem}}
    </style>
</head>
<body>
    <header>
        <div class="logo">FLIX</div>
        <nav class="header-nav">
            <a href="../../index.php?lang=<?php echo $lang; ?>" style="color:white;text-decoration:none;margin-right:1rem"><?php echo $lang === 'ar' ? 'الرئيسية' : 'Home'; ?></a>
        </nav>
    </header>

    <div class="reset-wrapper">
        <div class="reset-container">
            <div class="reset-header">
                <div class="logo-text">FLIX</div>
                <div class="h1"><?php echo $lang === 'ar' ? 'إعادة تعيين كلمة المرور' : 'Reset Password'; ?></div>
                <div class="p"><?php echo $lang === 'ar' ? 'أدخل رقم هاتفك وبريدك الإلكتروني (اختياري) لإعادة تعيين كلمة المرور' : 'Enter your phone and optional email to reset your password'; ?></div>
            </div>

            <div id="message"></div>

            <form id="resetForm" novalidate>
                <div class="form-group">
                    <label class="label"><?php echo $lang === 'ar' ? 'البريد الإلكتروني (اختياري)' : 'Email (optional)'; ?></label>
                    <input type="email" name="email" id="email" placeholder="<?php echo $lang === 'ar' ? 'example@mail.com' : 'example@mail.com'; ?>">
                </div>

                <div class="form-group">
                    <label class="label"><?php echo $lang === 'ar' ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                    <input type="text" name="phone" id="phone" placeholder="<?php echo $lang === 'ar' ? '+20 123 456 7890' : '+20 123 456 7890'; ?>" required>
                </div>

                <div class="form-group">
                    <label class="label"><?php echo $lang === 'ar' ? 'كلمة المرور الجديدة' : 'New Password'; ?></label>
                    <input type="password" name="new_password" id="new_password" placeholder="<?php echo $lang === 'ar' ? 'كلمة مرور جديدة' : 'New password'; ?>" required>
                </div>

                <div class="form-group">
                    <label class="label"><?php echo $lang === 'ar' ? 'تأكيد كلمة المرور الجديدة' : 'Confirm New Password'; ?></label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="<?php echo $lang === 'ar' ? 'أعد إدخال كلمة المرور' : 'Re-enter password'; ?>" required>
                </div>

                <button type="submit" class="btn-primary"><?php echo $lang === 'ar' ? 'إعادة تعيين كلمة المرور' : 'Reset Password'; ?></button>

                <div class="helper" style="text-align:<?php echo $lang === 'ar' ? 'right' : 'left'; ?>;">
                    <a href="login.php?lang=<?php echo $lang; ?>" style="color:var(--primary);font-weight:600;text-decoration:none;">
                        <?php echo $lang === 'ar' ? 'العودة لتسجيل الدخول' : 'Back to Sign In'; ?></a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function(){
            const form = document.getElementById('resetForm');
            const msg = document.getElementById('message');
            function setMessage(html, type){
                msg.innerHTML = '<div class="' + (type==='error'?'error':'success') + '">' + html + '</div>';
            }

            form.addEventListener('submit', function(e){
                e.preventDefault();
                msg.innerHTML = '';
                const phone = document.getElementById('phone').value.trim();
                const email = document.getElementById('email').value.trim();
                const np = document.getElementById('new_password').value;
                const cp = document.getElementById('confirm_password').value;

                // basic front-end validation
                if(!phone){
                    setMessage('<?php echo $lang === 'ar' ? 'يرجى إدخال رقم الهاتف.' : 'Please enter your phone number.'; ?>','error');
                    return;
                }

                if(np.length < 6){
                    setMessage('<?php echo $lang === 'ar' ? 'يجب أن تكون كلمة المرور 6 أحرف على الأقل.' : 'Password must be at least 6 characters.'; ?>','error');
                    return;
                }

                if(np !== cp){
                    setMessage('<?php echo $lang === 'ar' ? 'كلمتا المرور غير متطابقتين.' : 'Passwords do not match.'; ?>','error');
                    return;
                }

                // Success (front-end only)
                setMessage('<?php echo $lang === 'ar' ? 'تم التحقق — هذه صفحة تجريبية للواجهة فقط.' : 'Checked — this is front-end only demo page.'; ?>','success');
                // Clear passwords for privacy
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
            });
        })();
    </script>
</body>
</html>
