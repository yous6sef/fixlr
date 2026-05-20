<?php
/**
 * Worker Available Requests - طلبات الفنيين المتاحة
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'worker') {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// 1. Verify user is a worker from the CORRECT table (workers) and use ::text for UUIDs
$userStmt = $conn->prepare("SELECT city FROM workers WHERE id::text = ?");
$userStmt->execute([$userId]);
$worker = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    echo "You are not a worker or account not found.";
    exit();
}

// 2. Get available requests in worker's city (Fixed Joins to match actual DB schema)
$stmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.status,
        sr.created_at,
        sr.description AS problem_description,
        sr.budget AS checking_fee,
        sr.address AS google_maps_link,
        sr.specialization AS service_type,
        sr.city AS city,
        u.name AS user_name,
        5 AS total_rating, -- Mocked rating until reviews table is connected
        1 AS total_reviews -- Mocked reviews count
    FROM service_requests sr
    LEFT JOIN users u ON sr.user_id = u.id
    WHERE sr.status = 'pending' 
    AND sr.city = ?
    ORDER BY sr.created_at DESC
");

$stmt->execute([$worker['city']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Handle accept request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept') {
    $requestId = $_POST['request_id'] ?? null;
    
    if ($requestId) {
        try {
            // Update request status directly
            $updateStmt = $conn->prepare("
                UPDATE service_requests 
                SET status = 'accepted', worker_id = ?
                WHERE id = ? AND status = 'pending'
            ");
            $updateStmt->execute([$userId, $requestId]);
            
            // Refresh page
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $error = "حدث خطأ أثناء قبول الطلب: " . $e->getMessage();
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
                    <a href="worker_dashboard.php" class="nav-btn" style="color: #667eea; border-color: #667eea;">لوحتي</a>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

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
                            </div>
                            <div class="request-id">#<?php echo $request['id']; ?></div>
                        </div>
                        <div class="price-badge">
                            💰 الميزانية: <?php echo htmlspecialchars($request['checking_fee']); ?> ج.م
                        </div>
                    </div>

                    <div class="user-card">
                        👤 <span class="user-name"><?php echo htmlspecialchars($request['user_name'] ?? 'عميل'); ?></span>
                        <span class="user-rating">⭐ <?php echo round($request['total_rating'] ?? 0, 1); ?>/5 (<?php echo $request['total_reviews'] ?? 0; ?> تقييم)</span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">📍 العنوان:</span>
                        <span class="info-value"><?php echo htmlspecialchars($request['google_maps_link'] ?? $request['city']); ?></span>
                    </div>

                    <div class="description-box">
                        <strong>📋 وصف المشكلة:</strong><br>
                        <?php echo nl2br(htmlspecialchars($request['problem_description'])); ?>
                    </div>

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
