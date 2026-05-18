<?php
/**
 * Request Detail Page - تفاصيل الطلب
 * Handles all stages: acceptance, arrival, checking, pricing negotiation, completion, rating
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$requestId = $_GET['id'] ?? null;

if (!$requestId) {
    die('Invalid request ID');
}

// Get request details
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        st.name_ar as service_type,
        c.name_ar as city,
        d.name_ar as device,
        u_user.name as user_name,
        u_user.id as user_id_owner,
        u_user.total_rating as user_rating,
        u_user.phone as user_phone,
        u_worker.name as worker_name,
        u_worker.id as worker_id_owner,
        u_worker.total_rating as worker_rating,
        u_worker.phone as worker_phone
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.id
    JOIN cities c ON sr.city_id = c.id
    LEFT JOIN devices d ON sr.device_id = d.id
    JOIN users u_user ON sr.user_id = u_user.id
    LEFT JOIN users u_worker ON sr.worker_id = u_worker.id
    WHERE sr.id = ? AND (sr.user_id = ? OR sr.worker_id = ?)
");
$stmt->execute([$requestId, $userId, $userId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die('Request not found');
}

$isWorker = $request['worker_id'] == $userId;
$isUser = $request['user_id'] == $userId;

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    try {
        $conn->beginTransaction();
        
        switch ($action) {
            // ============ WORKER ARRIVES ============
            case 'worker_arrived':
                if ($isWorker) {
                    $updateStmt = $conn->prepare("
                        UPDATE service_requests 
                        SET worker_arrival_timestamp = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$requestId]);
                    
                    // If both have arrived, mark as confirmed
                    if ($request['user_arrival_timestamp']) {
                        $confirmStmt = $conn->prepare("
                            UPDATE service_requests 
                            SET status = 'arrived_confirmed'
                            WHERE id = ?
                        ");
                        $confirmStmt->execute([$requestId]);
                    } else {
                        $statusStmt = $conn->prepare("
                            UPDATE service_requests 
                            SET status = 'worker_arrived'
                            WHERE id = ?
                        ");
                        $statusStmt->execute([$requestId]);
                    }
                }
                break;

            // ============ USER CONFIRMS ARRIVAL ============
            case 'user_arrived':
                if ($isUser) {
                    $updateStmt = $conn->prepare("
                        UPDATE service_requests 
                        SET user_arrival_timestamp = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$requestId]);
                    
                    // If both have arrived, mark as confirmed
                    if ($request['worker_arrival_timestamp']) {
                        $confirmStmt = $conn->prepare("
                            UPDATE service_requests 
                            SET status = 'arrived_confirmed'
                            WHERE id = ?
                        ");
                        $confirmStmt->execute([$requestId]);
                    } else {
                        $statusStmt = $conn->prepare("
                            UPDATE service_requests 
                            SET status = 'worker_arrived'
                            WHERE id = ?
                        ");
                        $statusStmt->execute([$requestId]);
                    }
                }
                break;

            // ============ CHECKING COMPLETED ============
            case 'checking_completed':
                if ($isWorker) {
                    $updateStmt = $conn->prepare("
                        UPDATE service_requests 
                        SET status = 'checking_completed', checking_completed_timestamp = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$requestId]);
                }
                break;

            // ============ USER CONFIRMS CHECKING & PAYS ============
            case 'confirm_payment':
                if ($isUser) {
                    $conn->beginTransaction();
                    
                    // Update status
                    $updateStmt = $conn->prepare("
                        UPDATE service_requests 
                        SET status = 'checking_paid'
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$requestId]);
                    
                    // Record payment
                    $paymentStmt = $conn->prepare("
                        INSERT INTO payments 
                        (service_request_id, worker_id, user_id, amount, payment_type, payment_status)
                        VALUES (?, ?, ?, 300, 'checking_fee', 'confirmed')
                    ");
                    $paymentStmt->execute([$requestId, $request['worker_id'], $userId]);
                    
                    // Add to worker daily revenue
                    $dateToday = date('Y-m-d');
                    $revenueStmt = $conn->prepare("
                        INSERT INTO worker_daily_revenue 
                        (worker_id, date_of_revenue, total_revenue)
                        VALUES (?, ?, 300)
                        ON CONFLICT (worker_id, date_of_revenue)
                        DO UPDATE SET total_revenue = worker_daily_revenue.total_revenue + EXCLUDED.total_revenue
                    ");
                    $revenueStmt->execute([$request['worker_id'], $dateToday]);
                    
                    $conn->commit();
                    $success = 'تم تأكيد الدفع بنجاح';
                }
                break;

            // ============ SUBMIT FIXING PRICE ============
            case 'submit_price':
                if ($isWorker) {
                    $fixing_price = $_POST['fixing_price'] ?? null;
                    if ($fixing_price) {
                        $updateStmt = $conn->prepare("
                            UPDATE service_requests 
                            SET fixing_price = ?, status = 'negotiating_price'
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$fixing_price, $requestId]);
                    }
                }
                break;

            // ============ AGREE ON PRICE ============
            case 'agree_price':
                if ($isUser) {
                    $updateStmt = $conn->prepare("
                        UPDATE service_requests 
                        SET status = 'fixing_started'
                        WHERE id = ? AND status = 'negotiating_price'
                    ");
                    $updateStmt->execute([$requestId]);
                }
                break;

            // ============ EDIT PRICE (NEGOTIATION) ============
            case 'edit_price':
                if ($isUser) {
                    $new_price = $_POST['fixing_price'] ?? null;
                    if ($new_price) {
                        $updateStmt = $conn->prepare("
                            UPDATE service_requests 
                            SET fixing_price = ?
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$new_price, $requestId]);
                    }
                }
                break;

            // ============ WORKER FINISHES ============
            case 'worker_finished':
                if ($isWorker) {
                    $updateStmt = $conn->prepare("
                        UPDATE service_requests 
                        SET status = 'fixing_completed', fixing_completed_timestamp = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$requestId]);
                }
                break;

            // ============ COMPLETE TASK ============
            case 'complete_task':
                if ($isUser) {
                    $conn->beginTransaction();
                    
                    // Update status
                    $updateStmt = $conn->prepare("
                        UPDATE service_requests 
                        SET status = 'completed', completed_at = NOW(), total_price = checking_fee + COALESCE(fixing_price, 0)
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$requestId]);
                    
                    // Record fixing fee payment
                    if ($request['fixing_price']) {
                        $paymentStmt = $conn->prepare("
                            INSERT INTO payments 
                            (service_request_id, worker_id, user_id, amount, payment_type, payment_status)
                            VALUES (?, ?, ?, ?, 'fixing_fee', 'confirmed')
                        ");
                        $paymentStmt->execute([$requestId, $request['worker_id'], $userId, $request['fixing_price']]);
                        
                        // Add to worker daily revenue
                        $dateToday = date('Y-m-d');
                        $revenueStmt = $conn->prepare("
                            INSERT INTO worker_daily_revenue 
                            (worker_id, date_of_revenue, total_revenue)
                            VALUES (?, ?, ?)
                            ON CONFLICT (worker_id, date_of_revenue)
                            DO UPDATE SET total_revenue = worker_daily_revenue.total_revenue + EXCLUDED.total_revenue
                        ");
                        $revenueStmt->execute([$request['worker_id'], $dateToday, $request['fixing_price']]);
                    }
                    
                    // Increment user task count
                    $updateUserStmt = $conn->prepare("
                        UPDATE users 
                        SET total_tasks = total_tasks + 1
                        WHERE id = ?
                    ");
                    $updateUserStmt->execute([$userId]);
                    
                    // Increment worker task count
                    $updateWorkerStmt = $conn->prepare("
                        UPDATE users 
                        SET total_tasks = total_tasks + 1
                        WHERE id = ?
                    ");
                    $updateWorkerStmt->execute([$request['worker_id']]);
                    
                    $conn->commit();
                    $success = 'تم إكمال المهمة';
                }
                break;

            // ============ SUBMIT RATING ============
            case 'submit_rating':
                $rating = $_POST['rating'] ?? null;
                $review_text = $_POST['review_text'] ?? '';
                $serviceRequestId = $_POST['service_request_id'] ?? null;

                if ($rating && $serviceRequestId) {
                    $ratedUserId = $isWorker ? $request['user_id_owner'] : $request['worker_id_owner'];
                    $raterType = $isWorker ? 'worker' : 'user';

                    $ratingStmt = $conn->prepare("
                        INSERT INTO ratings 
                        (service_request_id, rater_id, rated_user_id, rating, review_text, rater_type)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON CONFLICT (service_request_id, rater_id)
                        DO UPDATE SET rating = EXCLUDED.rating,
                                      review_text = EXCLUDED.review_text,
                                      rated_user_id = EXCLUDED.rated_user_id,
                                      rater_type = EXCLUDED.rater_type
                    ");
                    $ratingStmt->execute([$serviceRequestId, $userId, $ratedUserId, $rating, $review_text, $raterType]);

                    $updateRatingStmt = $conn->prepare("
                        UPDATE users 
                        SET total_rating = COALESCE((SELECT AVG(rating) FROM ratings WHERE rated_user_id = ?), 0),
                            total_reviews = (SELECT COUNT(*) FROM ratings WHERE rated_user_id = ?)
                        WHERE id = ?
                    ");
                    $updateRatingStmt->execute([$ratedUserId, $ratedUserId, $ratedUserId]);

                    $success = 'شكراً على التقييم';
                }
                break;
        }
        
        $conn->commit();
        header('refresh:1;url=' . $_SERVER['PHP_SELF'] . '?id=' . $requestId);
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Reload request data
$stmt->execute([$requestId, $userId, $userId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user has rated
$ratingStmt = $conn->prepare("
    SELECT * FROM ratings 
    WHERE service_request_id = ? AND rater_id = ?
");
$ratingStmt->execute([$requestId, $userId]);
$userHasRated = $ratingStmt->fetch(PDO::FETCH_ASSOC);

$statusLabels = [
    'pending' => '⏳ في الانتظار',
    'accepted' => '✅ تم القبول',
    'worker_arrived' => '🚗 الفني وصل',
    'arrived_confirmed' => '🤝 تم تأكيد الوصول',
    'checking_completed' => '🔍 الكشف مكتمل',
    'checking_paid' => '💳 تم الدفع',
    'negotiating_price' => '💬 التفاوض على السعر',
    'fixing_started' => '🔧 بدء الإصلاح',
    'fixing_completed' => '✨ الإصلاح مكتمل',
    'completed' => '🎉 مكتمل',
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الطلب</title>
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
            max-width: 900px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .back-link:hover {
            color: #5568d3;
        }

        .request-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .status-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-current {
            font-size: 24px;
            font-weight: bold;
        }

        .status-id {
            font-size: 14px;
            opacity: 0.8;
        }

        .request-content {
            padding: 25px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-right: 3px solid #667eea;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 18px;
            color: #333;
            margin-top: 8px;
            font-weight: 500;
        }

        .person-card {
            padding: 15px;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .person-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .person-phone {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }

        .person-rating {
            color: #ff9800;
            font-size: 14px;
            margin-top: 6px;
        }

        .timeline {
            margin: 25px 0;
            padding: 20px;
            background: #f0f4f8;
            border-radius: 8px;
        }

        .timeline-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .timeline-icon {
            width: 30px;
            height: 30px;
            background: white;
            border: 2px solid #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #667eea;
            font-weight: bold;
        }

        .timeline-content {
            padding-top: 3px;
        }

        .timeline-label {
            font-weight: 600;
            color: #333;
        }

        .timeline-time {
            color: #999;
            font-size: 12px;
        }

        .action-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 25px;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
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

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #4caf50;
            color: white;
        }

        .btn-success:hover {
            background: #388e3c;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background: #e68900;
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .price-form {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .rating-section {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-top: 15px;
        }

        .rating-stars {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        .star {
            font-size: 28px;
            cursor: pointer;
            opacity: 0.5;
            transition: all 0.2s;
        }

        .star:hover,
        .star.active {
            opacity: 1;
            transform: scale(1.2);
        }

        .description-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            line-height: 1.6;
            color: #555;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-link">← العودة</a>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="request-card">
            <div class="status-bar">
                <div>
                    <div class="status-current"><?php echo $statusLabels[$request['status']] ?? $request['status']; ?></div>
                    <div class="status-id">طلب رقم #<?php echo $request['id']; ?></div>
                </div>
                <div style="font-size: 48px;">
                    <?php 
                    $icons = [
                        'pending' => '⏳',
                        'accepted' => '✅',
                        'worker_arrived' => '🚗',
                        'arrived_confirmed' => '🤝',
                        'checking_completed' => '🔍',
                        'checking_paid' => '💳',
                        'negotiating_price' => '💬',
                        'fixing_started' => '🔧',
                        'fixing_completed' => '✨',
                        'completed' => '🎉'
                    ];
                    echo $icons[$request['status']] ?? '📋';
                    ?>
                </div>
            </div>

            <div class="request-content">
                <!-- SERVICE INFO -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">🔨 نوع الخدمة</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['service_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">📍 المدينة</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['city']); ?></div>
                    </div>
                    <?php if ($request['device']): ?>
                    <div class="info-item">
                        <div class="info-label">🔧 الجهاز</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['device']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="info-label">💰 رسم الكشف</div>
                        <div class="info-value"><?php echo $request['checking_fee']; ?> ج.م</div>
                    </div>
                    <?php if ($request['fixing_price']): ?>
                    <div class="info-item">
                        <div class="info-label">🔩 سعر الإصلاح</div>
                        <div class="info-value"><?php echo $request['fixing_price']; ?> ج.م</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['total_price']): ?>
                    <div class="info-item">
                        <div class="info-label">💵 الإجمالي</div>
                        <div class="info-value"><?php echo $request['total_price']; ?> ج.م</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- PROBLEM DESCRIPTION -->
                <div class="description-box">
                    <strong>📋 وصف المشكلة:</strong><br><br>
                    <?php echo htmlspecialchars($request['problem_description']); ?>
                </div>

                <!-- USER & WORKER INFO -->
                <div class="info-grid">
                    <div class="person-card">
                        <div class="person-name">👤 <?php echo htmlspecialchars($request['user_name']); ?></div>
                        <div class="person-phone">📞 <?php echo htmlspecialchars($request['user_phone']); ?></div>
                        <div class="person-rating">⭐ <?php echo round($request['user_rating'], 1); ?>/5</div>
                    </div>
                    <?php if ($request['worker_name']): ?>
                    <div class="person-card">
                        <div class="person-name">🔧 <?php echo htmlspecialchars($request['worker_name']); ?></div>
                        <div class="person-phone">📞 <?php echo htmlspecialchars($request['worker_phone']); ?></div>
                        <div class="person-rating">⭐ <?php echo round($request['worker_rating'], 1); ?>/5</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- TIMELINE -->
                <div class="timeline">
                    <div class="timeline-title">📅 مسار الطلب</div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">1</div>
                        <div class="timeline-content">
                            <div class="timeline-label">تم الإنشاء</div>
                            <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></div>
                        </div>
                    </div>

                    <?php if ($request['accepted_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">2</div>
                        <div class="timeline-content">
                            <div class="timeline-label">تم القبول</div>
                            <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($request['accepted_at'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($request['worker_arrival_timestamp']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">3</div>
                        <div class="timeline-content">
                            <div class="timeline-label">وصول الفني</div>
                            <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($request['worker_arrival_timestamp'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($request['checking_completed_timestamp']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">4</div>
                        <div class="timeline-content">
                            <div class="timeline-label">اكتمل الكشف</div>
                            <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($request['checking_completed_timestamp'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($request['fixing_started_timestamp']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">5</div>
                        <div class="timeline-content">
                            <div class="timeline-label">بدء الإصلاح</div>
                            <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($request['fixing_started_timestamp'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($request['completed_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">✓</div>
                        <div class="timeline-content">
                            <div class="timeline-label">مكتمل</div>
                            <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($request['completed_at'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ACTIONS BASED ON STATUS -->
                <div class="action-section">
                    <div class="action-title">🎯 الإجراءات المتاحة</div>
                    
                    <div class="action-buttons">
                        <?php if ($request['status'] === 'accepted' && $isWorker): ?>
                            <!-- Worker: Confirm Arrival -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="worker_arrived">
                                <button type="submit" class="btn btn-primary">✅ وصلت للموقع</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'accepted' && $isUser): ?>
                            <!-- User: Confirm Arrival -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="user_arrived">
                                <button type="submit" class="btn btn-primary">✅ أكد الوصول</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'arrived_confirmed' && $isWorker): ?>
                            <!-- Worker: Checking Completed -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="checking_completed">
                                <button type="submit" class="btn btn-success">🔍 اكتمل الكشف</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'checking_completed' && $isUser): ?>
                            <!-- User: Confirm Payment -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="confirm_payment">
                                <button type="submit" class="btn btn-success">💳 أؤكد دفع 300 ج.م</button>
                            </form>
                            <div class="alert alert-success" style="margin-top: 10px;">
                                ✅ تم الكشف عن المشكلة. يرجى تأكيد دفع 300 ج.م رسم الكشف
                            </div>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'checking_paid' && $isWorker): ?>
                            <!-- Worker: Submit Price -->
                            <div class="price-form">
                                <form method="POST">
                                    <div class="form-group">
                                        <label>السعر المقترح للإصلاح (ج.م):</label>
                                        <input type="number" name="fixing_price" min="1" step="0.01" required placeholder="أدخل السعر">
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" name="action" value="submit_price" class="btn btn-primary" style="flex: 1;">
                                            💬 إرسال السعر
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'negotiating_price'): ?>
                            <?php if ($isUser): ?>
                                <!-- User: Agree or Edit Price -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="agree_price">
                                    <button type="submit" class="btn btn-success">✅ أوافق على السعر</button>
                                </form>
                                
                                <div class="price-form" style="margin-top: 10px;">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>اقتراح سعر آخر (ج.م):</label>
                                            <input type="number" name="fixing_price" min="1" step="0.01" required placeholder="السعر المقترح">
                                        </div>
                                        <button type="submit" name="action" value="edit_price" class="btn btn-warning" style="width: 100%;">
                                            💬 اقتراح سعر مختلف
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    💬 في انتظار رد العميل على السعر المقترح: <strong><?php echo $request['fixing_price']; ?> ج.م</strong>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'fixing_started' && $isWorker): ?>
                            <!-- Worker: Finished -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="worker_finished">
                                <button type="submit" class="btn btn-success">✨ انتهيت من الإصلاح</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'fixing_completed' && $isUser): ?>
                            <!-- User: Complete Task -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="complete_task">
                                <button type="submit" class="btn btn-success">🎉 إكمال المهمة</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'completed' && !$userHasRated): ?>
                            <!-- Rating Section -->
                            <div class="rating-section">
                                <strong>⭐ قيّم <?php echo $isWorker ? 'العميل' : 'الفني'; ?></strong>
                                <form method="POST">
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label>اختر التقييم:</label><br>
                                        <button type="submit" name="rating" value="1" class="rating-btn">⭐ ضعيف جداً</button>
                                        <button type="submit" name="rating" value="2" class="rating-btn">⭐⭐ ضعيف</button>
                                        <button type="submit" name="rating" value="3" class="rating-btn">⭐⭐⭐ متوسط</button>
                                        <button type="submit" name="rating" value="4" class="rating-btn">⭐⭐⭐⭐ جيد</button>
                                        <button type="submit" name="rating" value="5" class="rating-btn">⭐⭐⭐⭐⭐ ممتاز</button>
                                    </div>
                                    <div class="form-group">
                                        <textarea name="review_text" placeholder="أضف تعليقاً (اختياري)" style="height: 80px;"></textarea>
                                    </div>
                                    <input type="hidden" name="action" value="submit_rating">
                                    <input type="hidden" name="service_request_id" value="<?php echo $requestId; ?>">
                                    <button type="submit" class="btn btn-primary" style="width: 100%;">إرسال التقييم</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
