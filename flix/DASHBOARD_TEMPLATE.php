<?php
// ========================================
// FLIX Dashboard Template - Premium UI
// Suitable for: User, Worker, Admin Dashboards
// ========================================

session_start();
require_once __DIR__ . '/db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$user_name = $_SESSION['user_name'] ?? 'مستخدم';

// Fetch user data based on role
if ($user_role === 'user') {
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
} elseif ($user_role === 'worker') {
    $stmt = $conn->prepare('SELECT * FROM workers WHERE id = ?');
    $stmt->execute([$user_id]);
} else {
    $stmt = $conn->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$user_id]);
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: landing.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - Flix</title>
    
    <link rel="stylesheet" href="/css/premium-ui.css">
    <link rel="stylesheet" href="/css/animations-enhanced.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--neutral-50);
        }
        
        .dashboard-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            gap: 0;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--neutral-900) 0%, var(--neutral-800) 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            z-index: 100;
        }
        
        .sidebar-logo {
            padding: 0 var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            font-size: 1.75rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar-nav {
            list-style: none;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.25rem;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-xl);
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all var(--transition-base);
            border-right: 3px solid transparent;
            font-weight: 500;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(14, 165, 233, 0.15);
            border-right-color: var(--primary);
        }
        
        .sidebar-nav-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        /* Main Content */
        .main-content {
            grid-column: 2;
            display: flex;
            flex-direction: column;
            margin-left: 280px;
        }
        
        /* Header */
        .dashboard-header {
            background: white;
            border-bottom: 1px solid var(--neutral-200);
            padding: var(--spacing-lg) var(--spacing-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .header-title h1 {
            font-size: 1.75rem;
            color: var(--neutral-900);
            margin-bottom: 0.25rem;
        }
        
        .header-title p {
            color: var(--neutral-600);
            font-size: 0.875rem;
        }
        
        .header-actions {
            display: flex;
            gap: var(--spacing-md);
            align-items: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--neutral-100);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition-base);
        }
        
        .user-profile:hover {
            background: var(--neutral-200);
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .content {
            flex: 1;
            padding: var(--spacing-xl);
            overflow-y: auto;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: var(--spacing-2xl);
        }
        
        .page-header h2 {
            font-size: 2rem;
            color: var(--neutral-900);
            margin-bottom: var(--spacing-sm);
        }
        
        .page-header p {
            color: var(--neutral-600);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-2xl);
        }
        
        .stat-card {
            background: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-xl);
            border: 1px solid var(--neutral-200);
            transition: all var(--transition-base);
        }
        
        .stat-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: var(--primary);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-label {
            color: var(--neutral-600);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--neutral-900);
            margin-bottom: var(--spacing-sm);
        }
        
        .stat-change {
            font-size: 0.875rem;
            color: var(--success);
            font-weight: 600;
        }
        
        /* Content Cards */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--spacing-xl);
        }
        
        .content-card {
            background: white;
            border-radius: var(--radius-xl);
            overflow: hidden;
            border: 1px solid var(--neutral-200);
            transition: all var(--transition-base);
        }
        
        .content-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }
        
        .card-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--neutral-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: var(--spacing-lg);
        }
        
        .card-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--neutral-200);
            background: var(--neutral-50);
            display: flex;
            gap: var(--spacing-md);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: fixed;
                left: -280px;
                transition: left var(--transition-base);
                z-index: 200;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .dashboard-header {
                padding: var(--spacing-md);
            }
            
            .header-title h1 {
                font-size: 1.25rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: var(--spacing-md);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">Flix</div>
            
            <nav class="sidebar-nav">
                <?php if ($user_role === 'user'): ?>
                    <li><a href="?page=dashboard" class="active"><span class="sidebar-nav-icon">📊</span> لوحة التحكم</a></li>
                    <li><a href="?page=requests"><span class="sidebar-nav-icon">📋</span> طلباتي</a></li>
                    <li><a href="?page=payments"><span class="sidebar-nav-icon">💳</span> الدفع</a></li>
                    <li><a href="?page=profile"><span class="sidebar-nav-icon">👤</span> ملفي الشخصي</a></li>
                    <li><a href="?page=support"><span class="sidebar-nav-icon">💬</span> الدعم</a></li>
                <?php elseif ($user_role === 'worker'): ?>
                    <li><a href="?page=dashboard" class="active"><span class="sidebar-nav-icon">📊</span> لوحة التحكم</a></li>
                    <li><a href="?page=requests"><span class="sidebar-nav-icon">🆕</span> طلبات جديدة</a></li>
                    <li><a href="?page=active"><span class="sidebar-nav-icon">⚡</span> طلبي الحالي</a></li>
                    <li><a href="?page=earnings"><span class="sidebar-nav-icon">💰</span> أرباحي</a></li>
                    <li><a href="?page=profile"><span class="sidebar-nav-icon">👤</span> ملفي الشخصي</a></li>
                <?php else: ?>
                    <li><a href="?page=dashboard" class="active"><span class="sidebar-nav-icon">📊</span> لوحة التحكم</a></li>
                    <li><a href="?page=users"><span class="sidebar-nav-icon">👥</span> المستخدمون</a></li>
                    <li><a href="?page=workers"><span class="sidebar-nav-icon">👷</span> الفنيون</a></li>
                    <li><a href="?page=orders"><span class="sidebar-nav-icon">📦</span> الطلبات</a></li>
                    <li><a href="?page=payments"><span class="sidebar-nav-icon">💳</span> المدفوعات</a></li>
                <?php endif; ?>
                
                <li style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: var(--spacing-md);">
                    <a href="?logout=1" style="color: #ef4444;">
                        <span class="sidebar-nav-icon">🚪</span> تسجيل الخروج
                    </a>
                </li>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-title">
                    <h1>مرحباً بك</h1>
                    <p><?= htmlspecialchars($user_name) ?></p>
                </div>
                
                <div class="header-actions">
                    <div class="user-profile">
                        <div class="avatar">
                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--neutral-900);"><?= htmlspecialchars($user_name) ?></div>
                            <div style="font-size: 0.75rem; color: var(--neutral-600);">
                                <?php 
                                if ($user_role === 'user') echo 'عميل';
                                elseif ($user_role === 'worker') echo 'فني';
                                else echo 'مدير';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="page-header">
                    <h2>لوحة التحكم</h2>
                    <p>مرحباً بك في Flix - منصة الخدمات المنزلية</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">إجمالي الطلبات</div>
                                <div class="stat-value">12</div>
                            </div>
                            <div class="stat-icon">📊</div>
                        </div>
                        <div class="stat-change">+2 هذا الشهر</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">الرصيد الحالي</div>
                                <div class="stat-value">٥٠٠ ر.س</div>
                            </div>
                            <div class="stat-icon">💰</div>
                        </div>
                        <div class="stat-change">متوازن</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">التقييم</div>
                                <div class="stat-value">4.8⭐</div>
                            </div>
                            <div class="stat-icon">⭐</div>
                        </div>
                        <div class="stat-change">ممتاز</div>
                    </div>
                </div>

                <!-- Content Cards Grid -->
                <div class="content-grid">
                    <div class="content-card">
                        <div class="card-header">
                            <h3 style="font-size: 1.125rem; font-weight: 700;">الطلبات الحديثة</h3>
                        </div>
                        <div class="card-body">
                            <p style="color: var(--neutral-600); font-size: 0.875rem;">لا توجد طلبات جديدة في الوقت الحالي</p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary" style="flex: 1;">عرض الكل</button>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-header">
                            <h3 style="font-size: 1.125rem; font-weight: 700;">آخر الأنشطة</h3>
                        </div>
                        <div class="card-body">
                            <p style="color: var(--neutral-600); font-size: 0.875rem;">لا توجد أنشطة حديثة</p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary" style="flex: 1;">عرض السجل</button>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-header">
                            <h3 style="font-size: 1.125rem; font-weight: 700;">المساعدة والدعم</h3>
                        </div>
                        <div class="card-body">
                            <p style="color: var(--neutral-600); font-size: 0.875rem;">هل تحتاج إلى مساعدة؟ تواصل معنا الآن</p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary" style="flex: 1;">اتصل بالدعم</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add active class to current navigation item
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            if (link.href.includes(new URLSearchParams(window.location.search).get('page'))) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>