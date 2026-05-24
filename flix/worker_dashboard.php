<?php
/**
 * Worker Dashboard - لوحة التحكم للفني
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'worker') {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// 1. Verify user is a worker from the CORRECT table (workers)
$userStmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$userStmt->execute([$userId]);
$worker = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    echo "You are not a worker or account not found.";
    exit();
}

// 2. Get worker's tasks statistics
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN status = 'accepted' THEN 1 END) as active_tasks,
        COUNT(CASE WHEN status IN ('pending') THEN 1 END) as pending_tasks,
        SUM(CASE WHEN status = 'completed' THEN COALESCE(checking_fee, 0) ELSE 0 END) as total_earnings
    FROM service_requests
    WHERE worker_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// 3. Get active requests (Fixed Joins to match actual DB schema)
$activeStmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.status,
        sr.created_at,
        sr.checking_fee,
        sr.fixing_price,
        st.name_ar as service_type,
        u.name AS user_name,
        c.name_ar as city
    FROM service_requests sr
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN service_types st ON sr.service_type_id = st.id
    LEFT JOIN cities c ON sr.city_id = c.id
    WHERE sr.worker_id = ? AND sr.status NOT IN ('completed', 'cancelled', 'rejected')
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$activeStmt->execute([$userId]);
$activeTasks = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Get today's earnings directly from service_requests
$todayEarningsStmt = $conn->prepare("
    SELECT COALESCE(SUM(checking_fee), 0) as today_earnings 
    FROM service_requests
    WHERE worker_id = ? 
    AND DATE(completed_at) = DATE('now')
    AND status = 'completed'
");
$todayEarningsStmt->execute([$userId]);
$todayEarnings = $todayEarningsStmt->fetch(PDO::FETCH_ASSOC);

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
    <title>لوحة التحكم - الفني</title>
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

        .nav-btn.danger {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .nav-btn.danger:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
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

        .profile-details {
            font-size: 15px;
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

        .task-item {
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

        .task-item:hover {
            background: #f1f5f9;
            transform: translateX(-4px);
        }

        .task-info {
            flex: 1;
        }

        .task-title {
            font-weight: 700;
            color: var(--text);
            font-size: 16px;
            margin-bottom: 8px;
        }

        .task-details {
            font-size: 13px;
            color: var(--text-light);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .task-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .task-status {
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

            .task-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .task-actions {
                width: 100%;
                margin-top: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="welcome">🔧 لوحة التحكم</div>
            <div class="nav-buttons">
                <a href="worker_available_requests.php" class="nav-btn">📋 الطلبات المتاحة</a>
                <a href="worker_orders.php" class="nav-btn">✅ طلباتي</a>
                <a href="worker_payments.php" class="nav-btn">💰 الأرباح</a>
                <a href="worker_profile.php" class="nav-btn">👤 الملف الشخصي</a>
                <a href="logout.php" class="nav-btn danger">🚪 خروج</a>
            </div>
        </div>

        <!-- PROFILE SUMMARY -->
        <div class="profile-summary">
            <div class="profile-info">
                <div class="profile-name">👋 مرحباً، <?php echo htmlspecialchars($worker['name'] ?? 'فني'); ?></div>
                <div class="profile-details">⭐ التقييم: 4.5/5</div>
                <div class="profile-city">🏢 التخصص: <?php echo htmlspecialchars($worker['specialization'] ?? 'متعدد المجالات'); ?></div>
                <div class="profile-city">📍 المدينة: <?php echo htmlspecialchars($worker['city'] ?? 'لم يتم تحديد'); ?></div>
            </div>
            <div class="profile-actions">
                <a href="worker_profile.php" class="profile-action">📝 عرض الملف</a>
                <a href="logout.php" class="profile-action">🚪 تسجيل الخروج</a>
            </div>
        </div>

        <!-- STATISTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $stats['completed_tasks'] ?? 0; ?></div>
                <div class="stat-label">مكتملة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔄</div>
                <div class="stat-number"><?php echo $stats['active_tasks'] ?? 0; ?></div>
                <div class="stat-label">نشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-number"><?php echo $stats['pending_tasks'] ?? 0; ?></div>
                <div class="stat-label">قيد الانتظار</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number"><?php echo number_format($stats['total_earnings'] ?? 0, 2); ?> ج.م</div>
                <div class="stat-label">إجمالي الأرباح</div>
            </div>
        </div>

        <!-- ACTIVE TASKS -->
        <div class="section">
            <div class="section-title">
                📋 المهام النشطة
            </div>

            <?php if (empty($activeTasks)): ?>
                <div class="empty-state">
                    <p>😊 لا توجد مهام نشطة حالياً</p>
                    <a href="worker_available_requests.php" style="display: inline-block; background: linear-gradient(135deg, var(--success) 0%, #059669 100%); color: white; padding: 14px 32px; border: none; border-radius: 12px; cursor: pointer; font-size: 16px; text-decoration: none; font-weight: 700; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                        ابحث عن طلبات جديدة ↑
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($activeTasks as $task): ?>
                    <div class="task-item">
                        <div class="task-info">
                            <div class="task-title"><?php echo htmlspecialchars($task['service_type'] ?? 'خدمة'); ?></div>
                            <div class="task-details">
                                <span>📍 <?php echo htmlspecialchars($task['city'] ?? 'موقع'); ?></span>
                                <span>💰 <?php echo $task['checking_fee'] ?? 300; ?> ج.م</span>
                                <?php if ($task['user_name']): ?>
                                    <span>👤 <?php echo htmlspecialchars($task['user_name']); ?></span>
                                <?php endif; ?>
                                <span>🕐 منذ <?php 
                                    $time = strtotime($task['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 60) echo 'للتو';
                                    elseif ($diff < 3600) echo (int)($diff/60) . ' د';
                                    else echo (int)($diff/3600) . ' ساعة';
                                ?></span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <div class="task-status" style="background: <?php echo $statusColors[$task['status']] ?? '#f59e0b'; ?>;">
                                <?php echo $statusLabels[$task['status']] ?? $task['status']; ?>
                            </div>
                            <a href="request_detail.php?id=<?php echo $task['id']; ?>" class="btn-small">عرض التفاصيل</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--border);">
                    <a href="worker_orders.php" style="color: var(--primary); text-decoration: none; font-weight: 700; font-size: 15px;">عرض جميع الطلبات ← </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="section">
            <div class="section-title">
                ⚡ إجراءات سريعة
            </div>
            <div class="quick-actions">
                <a href="worker_available_requests.php" class="quick-action-btn" style="background: linear-gradient(135deg, var(--success) 0%, #059669 100%); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                    📋 طلبات متاحة
                </a>
                <a href="worker_orders.php" class="quick-action-btn" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);">
                    ✅ طلباتي
                </a>
                <a href="worker_payments.php" class="quick-action-btn" style="background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                    💰 الأرباح
                </a>
                <a href="worker_profile.php" class="quick-action-btn" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);">
                    👤 الملف الشخصي
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
