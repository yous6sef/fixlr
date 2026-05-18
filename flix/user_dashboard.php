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

        .nav-btn.primary {
            background: #4caf50;
        }

        .nav-btn.primary:hover {
            background: #388e3c;
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

        .request-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 10px;
            border-right: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .request-info {
            flex: 1;
        }

        .request-title {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .request-details {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            display: flex;
            gap: 15px;
        }

        .request-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            background: #ff9800;
        }

        .request-actions {
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

        .btn-new-request {
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

        .btn-new-request:hover {
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
                <a href="user_new_request.php" class="nav-btn primary">+ طلب جديد</a>
                <a href="user_requests.php" class="nav-btn">📋 طلباتي</a>
                <a href="user_profile.php" class="nav-btn">👤 الملف الشخصي</a>
            </div>
        </div>

        <!-- PROFILE SUMMARY -->
        <div class="profile-summary">
            <div class="profile-info">
                <div class="profile-name">👋 مرحباً، <?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-rating">⭐ التقييم: <?php echo round($user['total_rating'], 1); ?>/5 (<?php echo $user['total_reviews']; ?> تقييم)</div>
                <div class="profile-city">📍 <?php echo htmlspecialchars($user['city']); ?></div>
            </div>
            <div class="profile-actions">
                <a href="user_profile.php" class="profile-action">عرض الملف</a>
                <a href="logout.php" class="profile-action">تسجيل الخروج</a>
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
                <div class="stat-label">طلبات مكتملة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔄</div>
                <div class="stat-number"><?php echo $stats['active_requests']; ?></div>
                <div class="stat-label">طلبات نشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number"><?php echo number_format($stats['total_spent'] ?? 0, 2); ?> ج.م</div>
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
                    <p>لم تقم بإرسال أي طلب حتى الآن</p>
                    <a href="user_new_request.php" class="btn-new-request">إرسال طلب الآن</a>
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
                                <span>📅 منذ <?php 
                                    $time = strtotime($request['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 60) echo 'للتو';
                                    elseif ($diff < 3600) echo (int)($diff/60) . ' دقيقة';
                                    else echo (int)($diff/3600) . ' ساعة';
                                ?></span>
                            </div>
                        </div>
                        <div class="request-actions">
                            <div class="request-status" style="background: <?php echo $statusColors[$request['status']] ?? '#ff9800'; ?>">
                                <?php echo $statusLabels[$request['status']] ?? $request['status']; ?>
                            </div>
                            <a href="request_detail.php?id=<?php echo $request['id']; ?>" class="btn-small">عرض</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="user_requests.php" style="color: #667eea; text-decoration: none; font-weight: 600;">عرض جميع الطلبات →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="section">
            <div class="section-title">
                ⚡ إجراءات سريعة
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                <a href="user_new_request.php" style="background: #4caf50; color: white; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                    ➕ طلب جديد
                </a>
                <a href="user_requests.php" style="background: #2196f3; color: white; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                    📋 طلباتي
                </a>
                <a href="user_profile.php" style="background: #ff9800; color: white; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 600; transition: all 0.3s;">
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
