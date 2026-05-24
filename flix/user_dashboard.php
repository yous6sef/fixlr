<?php
/**
 * User Dashboard - لوحة التحكم للعميل
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Verify user is not a worker (or is a regular user)
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found";
    exit();
}

// Get user's requests statistics
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_requests,
        COUNT(CASE WHEN status IN ('accepted', 'worker_arrived', 'arrived_confirmed', 'checking_completed', 'checking_paid', 'negotiating_price', 'fixing_started', 'fixing_completed') THEN 1 END) as active_requests,
        SUM(CASE WHEN status = 'completed' THEN checking_fee + COALESCE(fixing_price, 0) ELSE 0 END) as total_spent
    FROM service_requests
    WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get recent requests
$recentStmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.status,
        sr.created_at,
        sr.checking_fee,
        sr.fixing_price,
        st.name_ar as service_type,
        u_worker.name as worker_name,
        c.name_ar as city
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.id
    JOIN cities c ON sr.city_id = c.id
    LEFT JOIN users u_worker ON sr.worker_id = u_worker.id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$recentStmt->execute([$userId]);
$recentRequests = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'pending' => '⏳ في الانتظار',
    'accepted' => '✅ مقبول',
    'worker_arrived' => '🚗 وصول الفني',
    'arrived_confirmed' => '🤝 تأكيد الوصول',
    'checking_completed' => '🔍 الكشف مكتمل',
    'checking_paid' => '💳 تم الدفع',
    'negotiating_price' => '💬 التفاوض على السعر',
    'fixing_started' => '🔧 بدء الإصلاح',
    'fixing_completed' => '✨ الإصلاح مكتمل',
    'completed' => '🎉 مكتمل',
];

$statusColors = [
    'pending' => '#ff9800',
    'accepted' => '#ff9800',
    'worker_arrived' => '#2196f3',
    'arrived_confirmed' => '#9c27b0',
    'checking_completed' => '#3f51b5',
    'checking_paid' => '#1976d2',
    'negotiating_price' => '#00bcd4',
    'fixing_started' => '#009688',
    'fixing_completed' => '#4caf50',
    'completed' => '#8bc34a',
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - العميل</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #06b6d4;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .welcome {
            font-size: 28px;
            font-weight: 700;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .nav-btn.primary {
            background: var(--success);
            border-color: var(--success);
        }

        .nav-btn.primary:hover {
            background: #059669;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--surface);
            padding: 28px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border-top: 4px solid;
            transition: all 0.3s ease;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card:nth-child(1) { border-color: var(--success); }
        .stat-card:nth-child(1) .stat-number { color: var(--success); }

        .stat-card:nth-child(2) { border-color: var(--warning); }
        .stat-card:nth-child(2) .stat-number { color: var(--warning); }

        .stat-card:nth-child(3) { border-color: var(--primary); }
        .stat-card:nth-child(3) .stat-number { color: var(--primary); }

        .stat-card:nth-child(4) { border-color: var(--danger); }
        .stat-card:nth-child(4) .stat-number { color: var(--danger); }

        .section {
            background: var(--surface);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border);
        }

        .profile-summary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 28px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-lg);
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .profile-rating {
            font-size: 16px;
            margin-bottom: 8px;
            opacity: 0.95;
        }

        .profile-city {
            font-size: 15px;
            opacity: 0.9;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .profile-action {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }

        .profile-action:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .request-item {
            padding: 20px;
            background: var(--bg);
            border-radius: 12px;
            margin-bottom: 12px;
            border-right: 4px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .request-item:hover {
            background: #f1f5f9;
            transform: translateX(-4px);
        }

        .request-info {
            flex: 1;
        }

        .request-title {
            font-weight: 700;
            color: var(--text);
            font-size: 16px;
            margin-bottom: 8px;
        }

        .request-details {
            font-size: 13px;
            color: var(--text-light);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .request-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .request-status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: white;
            background: var(--warning);
            white-space: nowrap;
        }

        .btn-small {
            padding: 10px 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-small:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
        }

        .empty-state p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .btn-new-request {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-new-request:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
        }

        .quick-action-btn {
            padding: 18px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .quick-action-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .profile-summary {
                flex-direction: column;
                gap: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            .request-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .request-actions {
                width: 100%;
                margin-top: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="welcome">🏠 لوحة التحكم</div>
            <div class="nav-buttons">
                <a href="user_new_request.php" class="nav-btn primary">➕ طلب جديد</a>
                <a href="user_requests.php" class="nav-btn">📋 طلباتي</a>
                <a href="user_profile.php" class="nav-btn">👤 الملف الشخصي</a>
                <a href="logout.php" class="nav-btn">🚪 خروج</a>
            </div>
        </div>

        <!-- PROFILE SUMMARY -->
        <div class="profile-summary">
            <div class="profile-info">
                <div class="profile-name">👋 مرحباً، <?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-rating">⭐ التقييم: <?php echo round($user['total_rating'] ?? 0, 1); ?>/5 (<?php echo $user['total_reviews'] ?? 0; ?> تقييم)</div>
                <div class="profile-city">📍 <?php echo htmlspecialchars($user['city'] ?? 'لم يتم تحديد'); ?></div>
            </div>
            <div class="profile-actions">
                <a href="user_profile.php" class="profile-action">📝 عرض الملف</a>
                <a href="logout.php" class="profile-action">🚪 تسجيل الخروج</a>
            </div>
        </div>

        <!-- STATISTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                <div class="stat-label">إجمالي الطلبات</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $stats['completed_requests']; ?></div>
                <div class="stat-label">مكتملة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔄</div>
                <div class="stat-number"><?php echo $stats['active_requests']; ?></div>
                <div class="stat-label">نشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number"><?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                <div class="stat-label">إجمالي الإنفاق</div>
            </div>
        </div>

        <!-- RECENT REQUESTS -->
        <div class="section">
            <div class="section-title">
                📋 الطلبات الأخيرة
            </div>

            <?php if (empty($recentRequests)): ?>
                <div class="empty-state">
                    <p>😊 لم تقم بإرسال أي طلب حتى الآن</p>
                    <a href="user_new_request.php" class="btn-new-request">إنشاء طلب الآن</a>
                </div>
            <?php else: ?>
                <?php foreach ($recentRequests as $request): ?>
                    <div class="request-item">
                        <div class="request-info">
                            <div class="request-title"><?php echo htmlspecialchars($request['service_type']); ?></div>
                            <div class="request-details">
                                <span>📍 <?php echo htmlspecialchars($request['city']); ?></span>
                                <span>💰 <?php echo $request['checking_fee']; ?> ج.م</span>
                                <?php if ($request['worker_name']): ?>
                                    <span>👤 <?php echo htmlspecialchars($request['worker_name']); ?></span>
                                <?php endif; ?>
                                <span>🕐 منذ <?php 
                                    $time = strtotime($request['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 60) echo 'للتو';
                                    elseif ($diff < 3600) echo (int)($diff/60) . ' د';
                                    else echo (int)($diff/3600) . ' ساعة';
                                ?></span>
                            </div>
                        </div>
                        <div class="request-actions">
                            <div class="request-status" style="background: <?php echo $statusColors[$request['status']] ?? '#f59e0b'; ?>;">
                                <?php echo $statusLabels[$request['status']] ?? $request['status']; ?>
                            </div>
                            <a href="request_detail.php?id=<?php echo $request['id']; ?>" class="btn-small">عرض التفاصيل</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--border);">
                    <a href="user_requests.php" style="color: var(--primary); text-decoration: none; font-weight: 700; font-size: 15px;">عرض جميع الطلبات ← </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="section">
            <div class="section-title">
                ⚡ إجراءات سريعة
            </div>
            <div class="quick-actions">
                <a href="user_new_request.php" class="quick-action-btn" style="background: linear-gradient(135deg, var(--success) 0%, #059669 100%); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                    ➕ طلب جديد
                </a>
                <a href="user_requests.php" class="quick-action-btn" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);">
                    📋 طلباتي
                </a>
                <a href="user_profile.php" class="quick-action-btn" style="background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                    👤 الملف الشخصي
                </a>
                <a href="logout.php" class="quick-action-btn" style="background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);">
                    🚪 تسجيل الخروج
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 60 seconds
        setTimeout(() => location.reload(), 60000);
    </script>
</body>
</html>
