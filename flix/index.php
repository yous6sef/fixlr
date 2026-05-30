<?php
session_start();
include('core/lang.php');
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'فليكس - خدمات المنزل' : 'Flix - Home Services Marketplace'; ?></title>
    <link rel="stylesheet" href="public/css/app.css">
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
            background: var(--surface);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER ===== */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
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
        }

        .header-nav a:hover {
            opacity: 0.8;
        }

        .lang-switch {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            color: white;
            font-weight: 600;
            transition: background 0.2s;
        }

        .lang-switch:hover {
            background: rgba(255,255,255,0.3);
        }

        /* ===== HERO SECTION ===== */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
        }

        /* ===== FEATURES SECTION ===== */
        .features {
            padding: 4rem 2rem;
            background: var(--surface-lighter);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 3rem;
            color: var(--primary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-lighter);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* ===== HOW IT WORKS ===== */
        .how-it-works {
            padding: 4rem 2rem;
            background: white;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .step {
            text-align: center;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }

        .step h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .step p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* ===== CTA SECTION ===== */
        .cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .cta h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--text-primary);
            color: white;
            padding: 2rem;
            text-align: center;
            margin-top: auto;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 640px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            header {
                flex-direction: column;
                gap: 1rem;
            }

            .header-nav {
                flex-direction: column;
                gap: 0.75rem;
                width: 100%;
            }

            .header-nav a {
                display: block;
                text-align: center;
            }

            .hero-buttons, .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- ===== HEADER ===== -->
        <header>
            <div class="logo">FLIX</div>
            <nav class="header-nav">
                <a href="pages/user/login.php?lang=<?php echo $lang; ?>">
                    <?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Sign In'; ?>
                </a>
                <button class="lang-switch" onclick="toggleLanguage()">
                    <?php echo $lang === 'ar' ? 'English' : 'العربية'; ?>
                </button>
            </nav>
        </header>

        <!-- ===== HERO SECTION ===== -->
        <section class="hero">
            <h1><?php echo $lang === 'ar' ? 'مرحبا بك في فليكس' : 'Welcome to Flix'; ?></h1>
            <p>
                <?php echo $lang === 'ar' 
                    ? 'منصتك الموثوقة للعثور على أفضل العمال والحرفيين المحترفين لإصلاح واحتياجات منزلك'
                    : 'Your trusted platform to find skilled professionals for all your home repair and service needs'; 
                ?>
            </p>
            <div class="hero-buttons">
                <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=user" class="btn btn-primary">
                    <?php echo $lang === 'ar' ? 'أنا عميل' : 'I\'m a User'; ?>
                </a>
                <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=worker" class="btn btn-secondary">
                    <?php echo $lang === 'ar' ? 'أنا عامل' : 'I\'m a Worker'; ?>
                </a>
            </div>
        </section>

        <!-- ===== FEATURES SECTION ===== -->
        <section class="features">
            <div class="features-container">
                <h2 class="section-title">
                    <?php echo $lang === 'ar' ? 'لماذا فليكس؟' : 'Why Flix?'; ?>
                </h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h3><?php echo $lang === 'ar' ? 'عمال موثوقون' : 'Verified Workers'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'جميع العمال يخضعون للتحقق والموافقة من قبل فريقنا'
                            : 'All workers are verified and approved by our team'; 
                        ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <h3><?php echo $lang === 'ar' ? 'أسعار عادلة' : 'Fair Pricing'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'أسعار شفافة وعادلة بدون رسوم مخفية'
                            : 'Transparent and fair pricing with no hidden fees'; 
                        ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⭐</div>
                        <h3><?php echo $lang === 'ar' ? 'تقييمات وآراء' : 'Ratings & Reviews'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'اختر بناءً على تقييمات المستخدمين الحقيقية'
                            : 'Choose based on real user ratings and reviews'; 
                        ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW IT WORKS ===== -->
        <section class="how-it-works">
            <div class="features-container">
                <h2 class="section-title">
                    <?php echo $lang === 'ar' ? 'كيف تعمل؟' : 'How It Works'; ?>
                </h2>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h4><?php echo $lang === 'ar' ? 'إنشاء طلب' : 'Create Request'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'وصف احتياجك واختر الموقع'
                            : 'Describe your needs and select location'; 
                        ?></p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h4><?php echo $lang === 'ar' ? 'تقبل العامل' : 'Worker Accepts'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'يتقبل عامل مؤهل طلبك'
                            : 'A qualified worker accepts your request'; 
                        ?></p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h4><?php echo $lang === 'ar' ? 'الخدمة' : 'Service'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'يصل العامل وينجز العمل بكفاءة'
                            : 'Worker arrives and completes the job'; 
                        ?></p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h4><?php echo $lang === 'ar' ? 'التقييم' : 'Rate'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'قيم الخدمة والعامل'
                            : 'Rate the service and worker'; 
                        ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CTA SECTION ===== -->
        <section class="cta">
            <h2><?php echo $lang === 'ar' ? 'هل أنت مستعد للبدء؟' : 'Ready to Get Started?'; ?></h2>
            <p>
                <?php echo $lang === 'ar' 
                    ? 'انضم إلى آلاف المستخدمين والعمال الراضين'
                    : 'Join thousands of satisfied users and workers'; 
                ?>
            </p>
            <div class="cta-buttons">
                <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=user" class="btn btn-primary">
                    <?php echo $lang === 'ar' ? 'ابدأ كعميل' : 'Sign Up as User'; ?>
                </a>
                <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=worker" class="btn btn-secondary">
                    <?php echo $lang === 'ar' ? 'ابدأ كعامل' : 'Sign Up as Worker'; ?>
                </a>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer>
            <p>&copy; 2026 FLIX. <?php echo $lang === 'ar' ? 'جميع الحقوق محفوظة' : 'All rights reserved'; ?>.</p>
        </footer>
    </div>

    <script>
        function toggleLanguage() {
            const newLang = <?php echo json_encode($lang); ?> === 'ar' ? 'en' : 'ar';
            window.location.href = `?lang=${newLang}`;
        }
    </script>
</body>
</html>
