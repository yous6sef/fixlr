<?php
/**
 * Worker Payments Page - صفحة الدفع للفنيين
 * Workers submit their daily revenue via Instapay and track commission
 */
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Verify user is a worker
$userStmt = $conn->prepare("SELECT user_type, name FROM users WHERE id = ? AND user_type = 'worker'");
$userStmt->execute([$userId]);
$worker = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    echo "You are not a worker";
    exit();
}

$error = '';
$success = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'submit_payment') {
        $revenue_id = $_POST['revenue_id'] ?? null;
        $amount_sent = $_POST['amount_sent'] ?? null;
        $transaction_id = $_POST['transaction_id'] ?? null;
        
        if (!$revenue_id || !$amount_sent || !$transaction_id) {
            $error = 'يرجى ملء جميع الحقول';
        } else {
            // Get revenue details
            $revenueStmt = $conn->prepare("SELECT * FROM worker_daily_revenue WHERE id = ? AND worker_id = ?");
            $revenueStmt->execute([$revenue_id, $userId]);
            $revenue = $revenueStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revenue) {
                $error = 'لم يتم العثور على السجل';
            } else {
                $commission_amount = ($revenue['total_revenue'] * 20) / 100;
                $remaining = $revenue['total_revenue'] - $commission_amount;
                
                if (round($amount_sent, 2) != round($commission_amount, 2)) {
                    $error = 'المبلغ غير صحيح. يجب أن يكون: ' . $commission_amount . ' ج.م';
                } else {
                    try {
                        $conn->beginTransaction();
                        
                        // Handle file upload
                        $receipt_image = null;
                        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['size'] > 0) {
                            $upload_dir = __DIR__ . '/uploads/receipts/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $filename = 'receipt_' . time() . '_' . basename($_FILES['receipt_image']['name']);
                            $upload_path = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_path)) {
                                $receipt_image = 'uploads/receipts/' . $filename;
                            }
                        }
                        
                        // Update revenue record
                        $updateStmt = $conn->prepare("
                            UPDATE worker_daily_revenue 
                            SET 
                                commission_amount = ?,
                                remaining_amount = ?,
                                instapay_transaction_id = ?,
                                receipt_image = ?,
                                payment_status = 'submitted',
                                submitted_at = NOW()
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$commission_amount, $remaining, $transaction_id, $receipt_image, $revenue_id]);
                        
                        $conn->commit();
                        $success = 'تم إرسال الدفع بنجاح! في انتظار التأكيد';
                        
                        // Refresh page
                        header('refresh:2;url=' . $_SERVER['PHP_SELF']);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = 'خطأ: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Get today's date
$today = date('Y-m-d');

// Get all revenue records
$stmt = $conn->prepare("
    SELECT * FROM worker_daily_revenue 
    WHERE worker_id = ?
    ORDER BY date_of_revenue DESC
");
$stmt->execute([$userId]);
$revenues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalRevenue = 0;
$totalCommission = 0;
$pendingPayment = 0;

foreach ($revenues as $revenue) {
    $totalRevenue += $revenue['total_revenue'];
    if ($revenue['payment_status'] === 'confirmed') {
        $totalCommission += $revenue['commission_amount'];
    } elseif ($revenue['payment_status'] === 'pending') {
        $commission = ($revenue['total_revenue'] * 20) / 100;
        $pendingPayment += $commission;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الدفع والعمولات</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }

        .revenue-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-right: 5px solid;
        }

        .revenue-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .revenue-date {
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

        .status-pending {
            background: #ff9800;
        }

        .status-submitted {
            background: #2196f3;
        }

        .status-confirmed {
            background: #4caf50;
        }

        .revenue-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }

        .detail-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-top: 6px;
        }

        .commission-highlight {
            color: #ff6f00;
        }

        .revenue-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
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
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .payment-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #667eea;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .instruction-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            margin: 15px 0;
            font-size: 13px;
            color: #1565c0;
        }

        .instruction-box strong {
            display: block;
            margin-bottom: 8px;
        }

        .instruction-box ol {
            margin-right: 20px;
            margin-top: 8px;
        }

        .instruction-box li {
            margin-bottom: 6px;
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
        }

        .receipt-preview {
            width: 100%;
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 6px;
            margin-top: 10px;
        }

        .transaction-info {
            background: white;
            padding: 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💳 الدفع والعمولات</h1>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">إجمالي الإيرادات</div>
                    <div class="stat-number"><?php echo number_format($totalRevenue, 2); ?> ج.م</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">في الانتظار (20%)</div>
                    <div class="stat-number"><?php echo number_format($pendingPayment, 2); ?> ج.م</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">العمولات المؤكدة</div>
                    <div class="stat-number"><?php echo number_format($totalCommission, 2); ?> ج.م</div>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>📋 كيفية الدفع:</strong><br>
            نحن نأخذ عمولة 20% من إيراداتك اليومية. يجب عليك إرسال هذه العمولة إلى <strong>test@instapay</strong> في نهاية كل يوم عمل مع المستند الثبوتي.
        </div>

        <?php if (empty($revenues)): ?>
            <div class="empty-state">
                <h2>لا توجد إيرادات حتى الآن</h2>
                <p>ستظهر إيراداتك هنا عند إكمال المهام</p>
            </div>
        <?php else: ?>
            <?php foreach ($revenues as $revenue): 
                $commission = ($revenue['total_revenue'] * 20) / 100;
                $remaining = $revenue['total_revenue'] - $commission;
                
                $borderColor = 'pending' === $revenue['payment_status'] ? '#ff9800' : 
                              ('submitted' === $revenue['payment_status'] ? '#2196f3' : '#4caf50');
            ?>
                <div class="revenue-card" style="border-color: <?php echo $borderColor; ?>">
                    <div class="revenue-header">
                        <div>
                            <div class="revenue-date">📅 <?php echo date('d/m/Y', strtotime($revenue['date_of_revenue'])); ?></div>
                        </div>
                        <div class="status-badge status-<?php echo htmlspecialchars($revenue['payment_status']); ?>">
                            <?php 
                            $statusLabels = [
                                'pending' => 'معلق',
                                'submitted' => 'مرسل',
                                'confirmed' => 'مؤكد'
                            ];
                            echo $statusLabels[$revenue['payment_status']];
                            ?>
                        </div>
                    </div>

                    <div class="revenue-details">
                        <div class="detail-item">
                            <div class="detail-label">💰 الإيراد الكلي</div>
                            <div class="detail-value"><?php echo $revenue['total_revenue']; ?> ج.م</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">📊 العمولة (20%)</div>
                            <div class="detail-value commission-highlight"><?php echo number_format($commission, 2); ?> ج.م</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">💵 صافي الإيراد</div>
                            <div class="detail-value"><?php echo number_format($remaining, 2); ?> ج.م</div>
                        </div>
                    </div>

                    <?php if ($revenue['instapay_transaction_id']): ?>
                        <div class="transaction-info">
                            ✅ <strong>رقم الحوالة:</strong> <?php echo htmlspecialchars($revenue['instapay_transaction_id']); ?><br>
                            <strong>المبلغ المرسل:</strong> <?php echo number_format($revenue['commission_amount'], 2); ?> ج.م<br>
                            <strong>تاريخ الإرسال:</strong> <?php echo date('d/m/Y H:i', strtotime($revenue['submitted_at'])); ?>
                            <?php if ($revenue['receipt_image']): ?>
                                <br><img src="<?php echo htmlspecialchars($revenue['receipt_image']); ?>" alt="Receipt" class="receipt-preview">
                            <?php endif; ?>
                        </div>
                    <?php elseif ($revenue['payment_status'] === 'pending'): ?>
                        <div class="payment-form">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="instruction-box">
                                    <strong>خطوات الدفع:</strong>
                                    <ol>
                                        <li>أرسل <strong><?php echo number_format($commission, 2); ?> ج.م</strong> إلى test@instapay</li>
                                        <li>انسخ رقم الحوالة من الرسالة</li>
                                        <li>حمّل صورة المستند الثبوتي</li>
                                        <li>أكمل النموذج أدناه</li>
                                    </ol>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>رقم حوالة Instapay *</label>
                                        <input type="text" name="transaction_id" required placeholder="أدخل رقم الحوالة">
                                    </div>
                                    <div class="form-group">
                                        <label>المبلغ المرسل (ج.م) *</label>
                                        <input type="number" name="amount_sent" value="<?php echo number_format($commission, 2); ?>" readonly style="background: #f0f0f0;">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>صورة المستند الثبوتي *</label>
                                    <input type="file" name="receipt_image" accept="image/*" required>
                                </div>

                                <div style="display: flex; gap: 10px;">
                                    <button type="submit" class="btn btn-success" style="flex: 1;">✅ تأكيد الدفع</button>
                                    <input type="hidden" name="revenue_id" value="<?php echo $revenue['id']; ?>">
                                    <input type="hidden" name="action" value="submit_payment">
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
