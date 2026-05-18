<?php
/**
 * User Requests Dashboard - طلبات المستخدم
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get user requests with details
$stmt = $conn->prepare("
    SELECT 
        sr.id, 
        sr.status, 
        sr.created_at,
        sr.problem_description,
        sr.checking_fee,
        sr.fixing_price,
        sr.total_price,
        st.name_ar as service_type,
        c.name_ar as city,
        d.name_ar as device,
        u_worker.name as worker_name,
        u_worker.total_rating
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.id
    JOIN cities c ON sr.city_id = c.id
    LEFT JOIN devices d ON sr.device_id = d.id
    LEFT JOIN users u_worker ON sr.worker_id = u_worker.id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status translations
$statusLabels = [
    'pending' => 'في الانتظار',
    'accepted' => 'تم القبول',
    'worker_arrived' => 'الفني وصل',
    'arrived_confirmed' => 'تأكيد الوصول',
    'checking_completed' => 'الكشف مكتمل',
    'checking_paid' => 'تم الدفع',
    'negotiating_price' => 'التفاوض على السعر',
    'fixing_started' => 'بدء الإصلاح',
    'fixing_completed' => 'الإصلاح مكتمل',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغى',
    'rejected' => 'مرفوض'
];

$statusColors = [
    'pending' => '#ff9800',
    'accepted' => '#2196f3',
    'worker_arrived' => '#9c27b0',
    'arrived_confirmed' => '#673ab7',
    'checking_completed' => '#3f51b5',
    'checking_paid' => '#1976d2',
    'negotiating_price' => '#00bcd4',
    'fixing_started' => '#009688',
    'fixing_completed' => '#4caf50',
    'completed' => '#8bc34a',
    'cancelled' => '#9e9e9e',
    'rejected' => '#f44336'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلباتي</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .btn-new {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-new:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .request-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-right: 5px solid;
            cursor: pointer;
            transition: all 0.3s;
        }

        .request-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .request-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
        }

        .request-description {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #555;
            margin: 12px 0;
            line-height: 1.5;
        }

        .request-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }

        .request-date {
            font-size: 12px;
            color: #999;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .btn-cancel {
            background: #f44336;
            color: white;
        }

        .btn-cancel:hover {
            background: #d32f2f;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state h2 {
            color: #999;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #bbb;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 8px 16px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            color: #666;
        }

        .tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .price-info {
            background: #e8f5e9;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            color: #2e7d32;
            font-weight: 600;
        }

        .worker-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 طلباتي</h1>
            <a href="user_new_request.php" class="btn-new">+ طلب جديد</a>
        </div>

        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <h2>لا توجد طلبات</h2>
                <p>لم تقم بإرسال أي طلب خدمة حتى الآن</p>
                <a href="user_new_request.php" class="btn-new" style="display: inline-block;">إرسال طلب الآن</a>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card" style="border-color: <?php echo $statusColors[$request['status']]; ?>">
                    <div class="request-header">
                        <div>
                            <div class="request-title"><?php echo htmlspecialchars($request['service_type']); ?></div>
                            <div style="font-size: 12px; color: #999; margin-top: 4px;">
                                #<?php echo $request['id']; ?>
                            </div>
                        </div>
                        <div class="status-badge" style="background: <?php echo $statusColors[$request['status']]; ?>">
                            <?php echo $statusLabels[$request['status']]; ?>
                        </div>
                    </div>

                    <div class="request-details">
                        <div class="detail-item">
                            <span class="detail-label">📍 المدينة:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($request['city']); ?></span>
                        </div>
                        <?php if ($request['device']): ?>
                        <div class="detail-item">
                            <span class="detail-label">🔧 الجهاز:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($request['device']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <span class="detail-label">💰 رسم الكشف:</span>
                            <span class="price-info"><?php echo $request['checking_fee']; ?> ج.م</span>
                        </div>
                        <?php if ($request['fixing_price']): ?>
                        <div class="detail-item">
                            <span class="detail-label">🔩 سعر الإصلاح:</span>
                            <span class="detail-value"><?php echo $request['fixing_price']; ?> ج.م</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="request-description">
                        <strong>المشكلة:</strong><br>
                        <?php echo htmlspecialchars(substr($request['problem_description'], 0, 150)); ?>...
                    </div>

                    <?php if ($request['worker_name']): ?>
                    <div class="worker-info">
                        👨‍🔧 <strong>الفني:</strong> <?php echo htmlspecialchars($request['worker_name']); ?>
                        ⭐ <?php echo round($request['total_rating'], 1); ?>/5
                    </div>
                    <?php endif; ?>

                    <div class="request-footer">
                        <div class="request-date">
                            📅 <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                        </div>
                        <div class="action-buttons">
                            <a href="request_detail.php?id=<?php echo $request['id']; ?>" class="btn-action btn-view">
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
