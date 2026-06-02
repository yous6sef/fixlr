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
            --primary-extra-light: #F3FAF7;
            --text-primary: #141714;
            --text-secondary: #4A5249;
            --text-tertiary: #8A9389;
            --border: #D4D3D0;
            --surface: #FFFFFF;
            --surface-light: #F7F8F6;
            --surface-lighter: #F0F2EE;
            --radius: 14px;
            --shadow: 0 2px 12px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 12px 32px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--surface);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
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
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .logo-badge {
            font-size: 0.75rem;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            backdrop-filter: blur(10px);
        }

        .logo-badge {
            font-size: 0.75rem;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            backdrop-filter: blur(10px);
        }

        .header-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 0;
        }

        .header-nav a:hover {
            opacity: 0.9;
        }

        .header-nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: white;
            transition: width 0.3s ease;
        }

        .header-nav a:hover::after {
            width: 100%;
        }

        .lang-switch {
            background: rgba(255,255,255,0.15);
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .lang-switch:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }

        /* ===== HERO SECTION ===== */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 5rem 2rem;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 700px;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .hero h1 {
            font-size: clamp(2rem, 8vw, 3.5rem);
            margin-bottom: 0.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .hero-slogan {
            font-size: clamp(1.25rem, 3vw, 1.75rem);
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: rgba(255, 255, 255, 0.95);
            animation: pulse 3s ease-in-out infinite;
        }

        .slogan-main {
            display: inline-block;
            position: relative;
            padding: 0.5rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease;
        }

        .hero p {
            font-size: clamp(1rem, 2vw, 1.25rem);
            margin-bottom: 2.5rem;
            opacity: 0.95;
            line-height: 1.7;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.05);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .btn:hover::before {
            left: 0;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        /* ===== FEATURES SECTION ===== */
        .features {
            padding: 5rem 2rem;
            background: linear-gradient(180deg, var(--surface-lighter) 0%, var(--surface) 100%);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: clamp(1.75rem, 5vw, 2.5rem);
            margin-bottom: 1rem;
            color: var(--primary);
            font-weight: 800;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(26, 107, 74, 0.1);
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-lighter) 0%, var(--primary-extra-light) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(26, 107, 74, 0.1);
        }

        .feature-card h3 {
            color: var(--primary);
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* ===== HOW IT WORKS ===== */
        .how-it-works {
            padding: 5rem 2rem;
            background: linear-gradient(180deg, var(--surface) 0%, var(--surface-lighter) 100%);
            position: relative;
        }

        .how-it-works::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(45, 154, 108, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(26, 107, 74, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .how-it-works .features-container {
            position: relative;
            z-index: 1;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
            position: relative;
        }

        @media (min-width: 768px) {
            .steps::before {
                content: '';
                position: absolute;
                top: 80px;
                left: 5%;
                right: 5%;
                height: 3px;
                background: linear-gradient(90deg, transparent 0%, var(--primary-light) 25%, var(--primary-light) 75%, transparent 100%);
                z-index: 0;
            }
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 15px rgba(26, 107, 74, 0.3);
            position: relative;
            transition: all 0.3s ease;
        }

        .step:hover .step-number {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 8px 25px rgba(26, 107, 74, 0.4);
        }

        .step h4 {
            color: var(--primary);
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .step p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* ===== CTA SECTION ===== */
        .cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-content {
            position: relative;
            z-index: 2;
            max-width: 700px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: clamp(1.75rem, 5vw, 2.5rem);
            margin-bottom: 1rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .cta p {
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            line-height: 1.7;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ===== FOOTER ===== */
        footer {
            background: linear-gradient(180deg, var(--text-primary) 0%, #0a0a0a 100%);
            color: white;
            padding: 3rem 2rem 1.5rem;
            text-align: center;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: left;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .footer-section h4 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: white;
            font-weight: 700;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.75rem;
            opacity: 0.8;
            font-size: 0.95rem;
        }

        .footer-section ul li a {
            color: white;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .footer-section ul li a:hover {
            opacity: 1;
        }

        .footer-bottom {
            text-align: center;
            opacity: 0.8;
            font-size: 0.9rem;
            padding-top: 1rem;
        }

        .footer-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            header {
                padding: 1rem;
            }

            .logo {
                font-size: 1.5rem;
            }

            .header-nav {
                gap: 1rem;
            }

            .hero {
                padding: 3rem 1.5rem;
            }

            .features, .how-it-works, .cta {
                padding: 3rem 1.5rem;
            }

            .section-subtitle {
                font-size: 1rem;
            }

            .features-grid, .steps {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .feature-card {
                padding: 1.75rem 1.5rem;
            }

            .steps::before {
                display: none;
            }

            .step-number {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .hero-buttons, .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-bottom: 1rem;
                padding-bottom: 1rem;
            }
        }

        @media (max-width: 640px) {
            header {
                padding: 0.75rem;
                gap: 0.5rem;
            }

            .logo {
                font-size: 1.25rem;
            }

            .header-nav {
                width: 100%;
                justify-content: space-around;
                gap: 0.5rem;
            }

            .header-nav a {
                font-size: 0.9rem;
            }

            .lang-switch {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }

            .hero {
                padding: 2rem 1rem;
            }

            .features, .how-it-works, .cta {
                padding: 2rem 1rem;
            }

            .section-title {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .section-subtitle {
                margin-bottom: 2rem;
            }

            .features-grid, .steps {
                gap: 1rem;
            }

            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.75rem;
            }

            .btn {
                padding: 0.875rem 1.75rem;
                font-size: 0.95rem;
            }

            .feature-card h3, .step h4 {
                font-size: 1.1rem;
            }

            .cta-content {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- ===== HEADER ===== -->
        <header>
            <div class="logo-section">
                <img src="public/images/logoflix.png" alt="FLIX Logo" class="logo">
                <span class="logo-badge">PRO</span>
            </div>
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
            <div class="hero-content">
                <img src="public/images/logoflix.png" alt="FLIX Logo" class="hero-logo">
                <h1><?php echo $lang === 'ar' ? 'خدماتك المنزلية، بسهولة وثقة' : 'Your Home Services, Made Simple'; ?></h1>
                <div class="hero-slogan">
                    <span class="slogan-main">
                        <?php echo $lang === 'ar' ? 'فليكس وبس 🚀' : 'Just Flix it 🚀'; ?>
                    </span>
                </div>
                <p>
                    <?php echo $lang === 'ar' 
                        ? 'منصة موثوقة تربطك بأفضل الحرفيين المحترفين لكل احتياجات منزلك. جودة، أمان، وأسعار عادلة'
                        : 'Connect with trusted professionals for all your home repair and service needs. Quality, safety, and fair pricing guaranteed'; 
                    ?>
                </p>
                <div style="font-size: 0.95rem; opacity: 0.9; margin-bottom: 2rem; font-style: italic;">
                    <?php echo $lang === 'ar' 
                        ? '✨ فليكسها و انسىها - اترك الباقي علينا'
                        : '✨ Flix it & Forget it - We handle the rest'; 
                    ?>
                </div>
                <div class="hero-buttons">
                    <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=user" class="btn btn-primary">
                        <?php echo $lang === 'ar' ? 'ابدأ كعميل' : 'Get Started'; ?>
                    </a>
                    <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=worker" class="btn btn-secondary">
                        <?php echo $lang === 'ar' ? 'انضم كعامل' : 'Become a Pro'; ?>
                    </a>
                </div>
            </div>
        </section>

        <!-- ===== FEATURES SECTION ===== -->
        <section class="features">
            <div class="features-container">
                <h2 class="section-title">
                    <?php echo $lang === 'ar' ? 'لماذا اختيار فليكس؟' : 'Why Choose Flix?'; ?>
                </h2>
                <p class="section-subtitle">
                    <?php echo $lang === 'ar' 
                        ? 'نحن نوفر لك أفضل تجربة في البحث عن الخدمات بمختلف أنواعها'
                        : 'We provide the best experience for finding home services you need'; 
                    ?>
                </p>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">✓</div>
                        <h3><?php echo $lang === 'ar' ? 'عمال موثوقون' : 'Verified Professionals'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'جميع العمال يخضعون للتحقق والفحص الشامل من قبل فريقنا المختص'
                            : 'All professionals are thoroughly verified and background checked'; 
                        ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <h3><?php echo $lang === 'ar' ? 'أسعار شفافة' : 'Transparent Pricing'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'لا توجد رسوم مخفية أو تكاليف إضافية غير متوقعة'
                            : 'Clear pricing with no hidden fees or surprise charges'; 
                        ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⭐</div>
                        <h3><?php echo $lang === 'ar' ? 'تقييمات حقيقية' : 'Real Reviews'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'اختر بناءً على تقييمات وآراء العملاء الحقيقية والموثوقة'
                            : 'Choose based on genuine reviews from real customers'; 
                        ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🛡️</div>
                        <h3><?php echo $lang === 'ar' ? 'أمان مضمون' : 'Total Security'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'حماية كاملة لبيانتك الشخصية والمالية مع ضمان الخدمة'
                            : 'Complete protection for your data and secure transactions'; 
                        ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3><?php echo $lang === 'ar' ? 'استجابة سريعة' : 'Quick Response'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'احصل على عروض من محترفين بسرعة وتواصل مباشر سهل'
                            : 'Get quick responses from professionals in your area'; 
                        ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3><?php echo $lang === 'ar' ? 'تطبيق سهل الاستخدام' : 'Easy App'; ?></h3>
                        <p><?php echo $lang === 'ar' 
                            ? 'واجهة بسيطة وسهلة الاستخدام تجعل كل شيء بضغطة زر'
                            : 'Simple and intuitive interface for seamless experience'; 
                        ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW IT WORKS ===== -->
        <section class="how-it-works">
            <div class="features-container">
                <h2 class="section-title">
                    <?php echo $lang === 'ar' ? 'كيف تعمل منصتنا؟' : 'How It Works'; ?>
                </h2>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h4><?php echo $lang === 'ar' ? 'أنشئ طلبك' : 'Create Request'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'صف احتياجك واختر الخدمة التي تريدها والموقع'
                            : 'Tell us what you need and where you are'; 
                        ?></p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h4><?php echo $lang === 'ar' ? 'تلقي عروضاً' : 'Get Offers'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'استقبل عروض من محترفين مؤهلين بسرعة'
                            : 'Receive offers from qualified professionals'; 
                        ?></p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h4><?php echo $lang === 'ar' ? 'اختر وتواصل' : 'Connect'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'اختر من يناسبك والتواصل المباشر معه بسهولة'
                            : 'Pick your pro and chat directly'; 
                        ?></p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h4><?php echo $lang === 'ar' ? 'استمتع بالخدمة' : 'Service Done'; ?></h4>
                        <p><?php echo $lang === 'ar' 
                            ? 'احصل على الخدمة واستمتع بالنتيجة وقيّم التجربة'
                            : 'Get your work done and rate your experience'; 
                        ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CTA SECTION ===== -->
        <section class="cta">
            <div class="cta-content">
                <h2><?php echo $lang === 'ar' ? 'هل أنت مستعد للبدء؟' : 'Ready to Get Started?'; ?></h2>
                <p>
                    <?php echo $lang === 'ar' 
                        ? 'انضم إلى آلاف العملاء والمحترفين الذين يثقون في فليكس يومياً'
                        : 'Join thousands of customers and professionals who trust Flix daily'; 
                    ?>
                </p>
                <div class="cta-buttons">
                    <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=user" class="btn btn-primary">
                        <?php echo $lang === 'ar' ? 'أنا عميل - ابدأ الآن' : 'I\'m a Customer'; ?>
                    </a>
                    <a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=worker" class="btn btn-secondary">
                        <?php echo $lang === 'ar' ? 'أنا مختص - انضم الآن' : 'I\'m a Professional'; ?>
                    </a>
                </div>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer>
            <div class="footer-content">
                <div class="footer-section">
                    <img src="public/images/logoflix.png" alt="FLIX Logo" class="footer-logo">
                    <ul style="margin-top: 1rem;">
                        <li><?php echo $lang === 'ar' ? 'منصة خدمات منزلية موثوقة' : 'Trusted home services platform'; ?></li>
                        <li><?php echo $lang === 'ar' ? 'نخدم آلاف العملاء' : 'Serving thousands daily'; ?></li>
                        <li><?php echo $lang === 'ar' ? 'جودة مضمونة' : 'Quality assured'; ?></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><?php echo $lang === 'ar' ? 'سريع التصفح' : 'Quick Links'; ?></h4>
                    <ul>
                        <li><a href="pages/user/login.php?lang=<?php echo $lang; ?>"><?php echo $lang === 'ar' ? 'تسجيل دخول' : 'Sign In'; ?></a></li>
                        <li><a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=user"><?php echo $lang === 'ar' ? 'اشترك عميل' : 'Sign Up'; ?></a></li>
                        <li><a href="pages/user/signup.php?lang=<?php echo $lang; ?>&type=worker"><?php echo $lang === 'ar' ? 'اشترك عامل' : 'Become Pro'; ?></a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><?php echo $lang === 'ar' ? 'التواصل' : 'Contact'; ?></h4>
                    <ul>
                        <li><?php echo $lang === 'ar' ? 'البريد: info@flix.com' : 'Email: info@flix.com'; ?></li>
                        <li><?php echo $lang === 'ar' ? 'الدعم متاح 24/7' : 'Support: 24/7'; ?></li>
                        <li><?php echo $lang === 'ar' ? 'رد سريع مضمون' : 'Quick response'; ?></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 FLIX. <?php echo $lang === 'ar' ? 'جميع الحقوق محفوظة' : 'All rights reserved'; ?>. | <?php echo $lang === 'ar' ? 'منصة خدماتك الموثوقة' : 'Your Trusted Services Platform'; ?></p>
            </div>
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
