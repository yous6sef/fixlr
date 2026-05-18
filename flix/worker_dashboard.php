<?php
/**
 * Worker Dashboard - لوحة التحكم للفني
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Verify user is a worker
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'worker'");
$userStmt->execute([$userId]);
$worker = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    echo "You are not a worker";
    exit();
}

// Get worker's tasks statistics
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN status = 'accepted' THEN 1 END) as active_tasks,
        COUNT(CASE WHEN status IN ('pending') THEN 1 END) as pending_tasks,
        SUM(CASE WHEN status = 'completed' THEN checking_fee + COALESCE(fixing_price, 0) ELSE 0 END) as total_earnings
    FROM service_requests
    WHERE worker_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get active requests
$activeStmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.status,
        sr.created_at,
        sr.checking_fee,
        sr.fixing_price,
        st.name_ar as service_type,
        u.name as user_name,
        c.name_ar as city
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.id
    JOIN users u ON sr.user_id = u.id
    JOIN cities c ON sr.city_id = c.id
    WHERE sr.worker_id = ? AND sr.status NOT IN ('completed', 'cancelled', 'rejected')
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$activeStmt->execute([$userId]);
$activeTasks = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's earnings
$todayEarningsStmt = $conn->prepare("
    SELECT COALESCE(SUM(total_revenue), 0) as today_earnings 
    FROM worker_daily_revenue
    WHERE worker_id = ? AND date_of_revenue = CURRENT_DATE
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
    <title>لوحة التحكم</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .welcome {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 5px solid;
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
        }

        .stat-card:nth-child(1) {
            border-color: #4caf50;
        }

        .stat-card:nth-child(2) {
            border-color: #ff9800;
        }

        .stat-card:nth-child(3) {
            border-color: #2196f3;
        }

        .stat-card:nth-child(4) {
            border-color: #f44336;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .task-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 10px;
            border-right: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-info {
            flex: 1;
        }

        .task-title {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .task-details {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            display: flex;
            gap: 15px;
        }

        .task-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            background: #ff9800;
        }

        .task-actions {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-small:hover {
            background: #5568d3;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 15px;
        }

        .btn-get-jobs {
            background: #4caf50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-get-jobs:hover {
            background: #388e3c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .profile-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .profile-rating {
            font-size: 16px;
        }

        .profile-city {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 4px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .profile-action {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .profile-action:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="welcome">🏠 لوحة التحكم</div>
            <div class="nav-buttons">
                <a href="worker_available_requests.php" class="nav-btn">🆕 طلبات جديدة</a>
                <a href="worker_payment_submit.php" class="nav-btn">💳 الدفع</a>
                <a href="worker_profile.php" class="nav-btn">👤 الملف الشخصي</a>
            </div>
        </div>

        <!-- PROFILE SUMMARY -->
        <div class="profile-summary">
            <div class="profile-info">
                <div class="profile-name">👋 مرحباً، <?php echo htmlspecialchars($worker['name']); ?></div>
                <div class="profile-rating">⭐ التقييم: <?php echo round($worker['total_rating'], 1); ?>/5 (<?php echo $worker['total_reviews']; ?> تقييم)</div>
                <div class="profile-city">📍 <?php echo htmlspecialchars($worker['city']); ?></div>
            </div>
            <div class="profile-actions">
                <a href="worker_profile.php" class="profile-action">عرض الملف</a>
                <a href="logout.php" class="profile-action">تسجيل الخروج</a>
            </div>
        </div>

        <!-- STATISTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $stats['completed_tasks']; ?></div>
                <div class="stat-label">مهام مكتملة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔄</div>
                <div class="stat-number"><?php echo $stats['active_tasks']; ?></div>
                <div class="stat-label">مهام نشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number"><?php echo number_format($todayEarnings['today_earnings'], 2); ?> ج.م</div>
                <div class="stat-label">أرباح اليوم</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-number"><?php echo number_format($stats['total_earnings'] ?? 0, 2); ?> ج.م</div>
                <div class="stat-label">إجمالي الأرباح</div>
            </div>
        </div>

        <!-- ACTIVE TASKS -->
        <div class="section">
            <div class="section-title">
                🚀 المهام النشطة
            </div>

            <?php if (empty($activeTasks)): ?>
                <div class="empty-state">
                    <p>لا توجد مهام نشطة حالياً</p>
                    <a href="worker_available_requests.php" class="btn-get-jobs">البحث عن طلبات جديدة</a>
                </div>
            <?php else: ?>
                <?php foreach ($activeTasks as $task): ?>
                    <div class="task-item">
                        <div class="task-info">
                            <div class="task-title"><?php echo htmlspecialchars($task['service_type']); ?></div>
                            <div class="task-details">
                                <span>👤 <?php echo htmlspecialchars($task['user_name']); ?></span>
                                <span>📍 <?php echo htmlspecialchars($task['city']); ?></span>
                                <span>💰 <?php echo $task['checking_fee']; ?> ج.م</span>
                                <span>📅 منذ <?php 
                                    $time = strtotime($task['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 60) echo 'للتو';
                                    elseif ($diff < 3600) echo (int)($diff/60) . ' دقيقة';
                                    else echo (int)($diff/3600) . ' ساعة';
                                ?></span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <div class="task-status" style="background: <?php echo $statusColors[$task['status']] ?? '#ff9800'; ?>">
                                <?php echo $statusLabels[$task['status']] ?? $task['status']; ?>
                            </div>
                            <a href="request_detail.php?id=<?php echo $task['id']; ?>" class="btn-small">عرض</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="section">
            <div class="section-title">
                ⚡ إجراءات سريعة
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                <a href="worker_available_requests.php" style="background: #4caf50; color: white; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                    🔍 البحث عن طلبات
                </a>
                <a href="worker_payment_submit.php" style="background: #2196f3; color: white; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                    💳 تقديم الدفع
                </a>
                <a href="worker_profile.php" style="background: #ff9800; color: white; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                    👤 الملف الشخصي
                </a>
                <a href="logout.php" style="background: #f44336; color: white; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 600; transition: all 0.3s;">
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
