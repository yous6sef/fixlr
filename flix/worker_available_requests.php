<?php
/**
 * Worker Available Requests - طلبات الفنيين المتاحة
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Verify user is a worker
$userStmt = $conn->prepare("SELECT user_type, city FROM users WHERE id = ? AND user_type = 'worker'");
$userStmt->execute([$userId]);
$worker = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    echo "You are not a worker";
    exit();
}

// Get available requests in worker's city
$stmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.status,
        sr.created_at,
        sr.problem_description,
        sr.checking_fee,
        sr.google_maps_link,
        st.name_ar as service_type,
        c.name_ar as city,
        d.name_ar as device,
        u.name as user_name,
        u.total_rating,
        u.total_reviews
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.id
    JOIN cities c ON sr.city_id = c.id
    LEFT JOIN devices d ON sr.device_id = d.id
    JOIN users u ON sr.user_id = u.id
    WHERE sr.status = 'pending' 
    AND sr.city_id = (SELECT id FROM cities WHERE name_ar = ? OR name_en = ? LIMIT 1)
    AND sr.id NOT IN (SELECT service_request_id FROM service_requests sr2 WHERE sr2.worker_id = ? AND sr2.user_id = sr.user_id)
    ORDER BY sr.created_at DESC
");

$stmt->execute([$worker['city'], $worker['city'], $userId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle accept request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept') {
    $requestId = $_POST['request_id'] ?? null;
    
    if ($requestId) {
        try {
            $conn->beginTransaction();
            
            // Update request status
            $updateStmt = $conn->prepare("
                UPDATE service_requests 
                SET status = 'accepted', worker_id = ?, accepted_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $updateStmt->execute([$userId, $requestId]);
            
            // Create status history
            $historyStmt = $conn->prepare("
                INSERT INTO request_status_history 
                (service_request_id, old_status, new_status, changed_by)
                VALUES (?, 'pending', 'accepted', ?)
            ");
            $historyStmt->execute([$requestId, $userId]);
            
            $conn->commit();
            
            // Refresh page
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الطلبات المتاحة</title>
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
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .header-stats {
            display: flex;
            gap: 20px;
        }

        .stat {
            text-align: center;
            padding: 10px 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .request-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-right: 5px solid #667eea;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .request-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .request-id {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }

        .request-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
        }

        .price-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 16px;
        }

        .description-box {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 8px;
            color: #555;
            font-size: 13px;
            line-height: 1.6;
            margin: 12px 0;
            border-right: 3px solid #667eea;
        }

        .user-card {
            background: #f0f7ff;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .user-name {
            font-weight: 600;
            color: #1565c0;
        }

        .user-rating {
            color: #ff6f00;
            margin-left: 5px;
        }

        .maps-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            padding: 6px 12px;
            background: #f5f5f5;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .maps-link:hover {
            background: #e0e0e0;
        }

        .request-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #eee;
            margin-top: 12px;
        }

        .time-ago {
            font-size: 12px;
            color: #999;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-accept {
            background: #4caf50;
            color: white;
        }

        .btn-accept:hover {
            background: #388e3c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .btn-maps {
            background: #ff6f00;
            color: white;
        }

        .btn-maps:hover {
            background: #e65100;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state h2 {
            color: #999;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #bbb;
            font-size: 16px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .filter-info {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🔧 الطلبات المتاحة</h1>
                <div class="filter-info">
                    📍 الطلبات في مدينتك: <?php echo htmlspecialchars($worker['city']); ?>
                </div>
            </div>
            <div class="header-stats">
                <div class="stat">
                    <div class="stat-number"><?php echo count($requests); ?></div>
                    <div class="stat-label">طلب متاح</div>
                </div>
                <div class="nav-buttons">
                    <a href="worker_dashboard.php" class="nav-btn">لوحتي</a>
                    <a href="worker_payment_submit.php" class="nav-btn">الدفع</a>
                </div>
            </div>
        </div>

        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <h2>لا توجد طلبات متاحة حالياً</h2>
                <p>سيظهر هنا الطلبات الجديدة القادمة من العملاء في مدينتك</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card">
                    <div class="request-header">
                        <div>
                            <div class="request-title">
                                🔨 <?php echo htmlspecialchars($request['service_type']); ?>
                                <?php if ($request['device']): ?>
                                    <small>(<?php echo htmlspecialchars($request['device']); ?>)</small>
                                <?php endif; ?>
                            </div>
                            <div class="request-id">#<?php echo $request['id']; ?></div>
                        </div>
                        <div class="price-badge">
                            💰 <?php echo $request['checking_fee']; ?> ج.م
                        </div>
                    </div>

                    <div class="user-card">
                        👤 <span class="user-name"><?php echo htmlspecialchars($request['user_name']); ?></span>
                        <span class="user-rating">⭐ <?php echo round($request['total_rating'], 1); ?>/5 (<?php echo $request['total_reviews']; ?> تقييم)</span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">📍 المدينة:</span>
                        <span class="info-value"><?php echo htmlspecialchars($request['city']); ?></span>
                    </div>

                    <div class="description-box">
                        <strong>📋 المشكلة:</strong><br>
                        <?php echo htmlspecialchars($request['problem_description']); ?>
                    </div>

                    <?php if ($request['google_maps_link']): ?>
                        <a href="<?php echo htmlspecialchars($request['google_maps_link']); ?>" target="_blank" class="maps-link">
                            📍 فتح الموقع في جوجل مابس
                        </a>
                    <?php endif; ?>

                    <div class="request-footer">
                        <div class="time-ago">
                            ⏱️ قبل <?php 
                            $time = strtotime($request['created_at']);
                            $diff = time() - $time;
                            if ($diff < 60) echo 'للتو';
                            elseif ($diff < 3600) echo (int)($diff/60) . ' دقيقة';
                            elseif ($diff < 86400) echo (int)($diff/3600) . ' ساعة';
                            else echo (int)($diff/86400) . ' يوم';
                            ?>
                        </div>
                        <div class="action-buttons">
                            <a href="request_detail.php?id=<?php echo $request['id']; ?>" class="btn btn-view">
                                👀 عرض التفاصيل
                            </a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" class="btn btn-accept">✅ قبول الطلب</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
