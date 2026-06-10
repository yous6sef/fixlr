<?php
session_start();
include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../user/login.php?lang=' . $lang);
    exit;
}

$connection = $conn;

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $admin = ['name' => 'Admin'];
}

$stats = [
    'total_users' => 0,
    'total_workers' => 0,
    'pending_workers' => 0,
    'approved_workers' => 0,
    'completed_tasks' => 0,
    'total_revenue' => 0,
    'pending_remittance' => 0
];

$pendingWorkers = [];
$pendingPayments = [];

// Handle approval/rejection

// Handle approval/rejection
$message = '';
$messageType = '';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'approve_worker') {
    $workerId = $_POST['workerId'];
    $stmt = $conn->prepare("UPDATE workers SET status = 'APPROVED', approvedAt = NOW(), approvedByAdminId = ? WHERE id = ?");
    $result = $stmt->execute([$_SESSION['user_id'], $workerId]);
    if ($result) {
        $message = $lang === 'ar' ? 'تم الموافقة على العامل' : 'Worker approved successfully';
        $messageType = 'success';
    }
}
elseif ($_POST['action'] === 'reject_worker') {
    $workerId = $_POST['workerId'];
    $reason = $_POST['rejectionReason'] ?? '';
    $stmt = $conn->prepare("UPDATE workers SET status = 'REJECTED', rejectedAt = NOW(), rejectionReason = ? WHERE id = ?");
    $result = $stmt->execute([$reason, $workerId]);
    if ($result) {
        $message = $lang === 'ar' ? 'تم رفض العامل' : 'Worker rejected';
        $messageType = 'success';
    }
}
elseif ($_POST['action'] === 'verify_payment') {
    $paymentId = $_POST['paymentId'];
    $stmt = $conn->prepare("UPDATE payments SET status = 'VERIFIED', verifiedAt = NOW(), verifiedByAdminId = ? WHERE id = ?");
    $result = $stmt->execute([$_SESSION['user_id'], $paymentId]);
    if ($result) {
        $message = $lang === 'ar' ? 'تم التحقق من الدفع' : 'Payment verified';
        $messageType = 'success';
    }
}
elseif ($_POST['action'] === 'reject_payment') {
    $paymentId = $_POST['paymentId'];
    $reason = $_POST['rejectionReason'] ?? '';
    $stmt = $conn->prepare("UPDATE payments SET status = 'REJECTED', rejectedAt = NOW(), rejectionReason = ? WHERE id = ?");
    $result = $stmt->execute([$reason, $paymentId]);
    if ($result) {
        $message = $lang === 'ar' ? 'تم رفض الدفع' : 'Payment rejected';
        $messageType = 'success';
    }
    }
}

// Get updated system statistics
$statsQuery = "SELECT 
    COALESCE((SELECT COUNT(*) FROM users WHERE role = 'user'), 0) as total_users,
    COALESCE((SELECT COUNT(*) FROM workers WHERE role = 'worker'), 0) as total_workers,
    COALESCE((SELECT COUNT(*) FROM workers WHERE status = 'PENDING_APPROVAL'), 0) as pending_workers,
    COALESCE((SELECT COUNT(*) FROM workers WHERE status = 'APPROVED'), 0) as approved_workers,
    COALESCE((SELECT COUNT(*) FROM service_requests WHERE status = 'COMPLETED'), 0) as completed_tasks,
    COALESCE((SELECT SUM(total_price) FROM service_requests WHERE status = 'COMPLETED'), 0) as total_revenue";

$stmt = $conn->prepare($statsQuery);
$stmt->execute();

$stats = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt = $conn->prepare("
    SELECT 
        workers.id,
        workers.national_id,
        workers.id_front_path,
        workers.id_back_path,
        workers.certificate_path,
        workers.cv_path,
        workers.specialization,
        workers.location,
        workers.city,
        workers.created_at
    FROM workers
    LIMIT 50
");

$stmt->execute();

$stmt = $conn->prepare("SELECT * FROM workers WHERE status = 'PENDING_APPROVAL' ORDER BY created_at DESC");
$stmt->execute();
$pendingWorkers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'لوحة الإدارة - إحصائيات وإدارة النظام' : 'Admin Dashboard - System Management & Analytics';
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        :root {
            --primary: #1A6B4A;
            --primary-light: #2D9A6C;
            --surface: #FFFFFF;
            --surface-light: #F7F8F6;
            --danger: #DC2626;
            --success: #16A34A;
        }

        body { background: var(--surface-light); }

        .admin-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 14px;
        }

        .header h1 {
            color: var(--primary);
            font-size: 1.75rem;
        }

        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .stat-label {
            color: #8A9389;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .message.success {
            background: #E8F5EE;
            color: var(--success);
            border: 1px solid #D4E8E0;
        }

        .message.error {
            background: #FEE2E2;
            color: var(--danger);
            border: 1px solid #FECACA;
        }

        .section {
            background: var(--surface);
            border-radius: 14px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .section-title {
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th {
            background: var(--surface-light);
            color: #141714;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #D4D3D0;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #E5E4E1;
            color: #4A5249;
        }

        tr:hover {
            background: var(--surface-light);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #FEF3E2;
            color: #9A6400;
        }

        .badge-approved {
            background: #E8F5EE;
            color: var(--primary);
        }

        .badge-rejected {
            background: #FEE2E2;
            color: var(--danger);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-approve {
            background: var(--success);
            color: white;
        }

        .btn-reject {
            background: var(--danger);
            color: white;
        }

        .btn-verify {
            background: var(--primary);
            color: white;
        }

        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .document-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            word-break: break-all;
            font-size: 0.85rem;
        }

        .document-link:hover {
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: 14px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #141714;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D4D3D0;
            border-radius: 8px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .close-modal {
            background: #D4D3D0;
            color: #141714;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #8A9389;
        }

        @media (max-width: 640px) {
            .admin-container {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            th, td {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .table-wrapper {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <div>
                <h1><?php echo $lang === 'ar' ? 'لوحة التحكم الإدارية' : 'Admin Dashboard'; ?></h1>
                <p><?php echo $lang === 'ar' ? 'مرحبا ' . htmlspecialchars($admin['name']) : 'Welcome ' . htmlspecialchars($admin['name']); ?></p>
            </div>
            <a href="../user/logout.php?lang=<?php echo $lang; ?>" class="logout-btn">
                <?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Logout'; ?>
            </a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label"><?php echo $lang === 'ar' ? 'إجمالي المستخدمين' : 'Total Users'; ?></div>
                <div class="stat-value"><?php echo $stats['total_users'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo $lang === 'ar' ? 'إجمالي العمال' : 'Total Workers'; ?></div>
                <div class="stat-value"><?php echo $stats['total_workers'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo $lang === 'ar' ? 'قيد الموافقة' : 'Pending Approval'; ?></div>
                <div class="stat-value"><?php echo $stats['pending_workers'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo $lang === 'ar' ? 'الموافق عليهم' : 'Approved'; ?></div>
                <div class="stat-value"><?php echo $stats['approved_workers'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo $lang === 'ar' ? 'المهام المكتملة' : 'Completed Tasks'; ?></div>
                <div class="stat-value"><?php echo $stats['completed_tasks'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo $lang === 'ar' ? 'إجمالي الإيرادات' : 'Total Revenue'; ?></div>
                <div class="stat-value"><?php echo number_format($stats['total_revenue'] ?? 0, 0); ?> EGP</div>
            </div>
        </div>

        <!-- Worker Approvals Section -->
        <div class="section">
            <h2 class="section-title">
                <?php echo $lang === 'ar' ? 'الموافقة على العمال' : 'Worker Approvals'; ?> 
                (<?php echo count($pendingWorkers) ?? 0; ?>)
            </h2>

            <?php if (empty($pendingWorkers)): ?>
                <div class="no-data">
                    <?php echo $lang === 'ar' ? 'لا توجد طلبات قيد الانتظار' : 'No pending requests'; ?>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <?php foreach ($pendingWorkers as $worker): ?>
                        <a href="worker_details.php?id=<?php echo $worker['id']; ?>&lang=<?php echo $lang; ?>" class="document-link">
                        <div class="worker-card">
                                <?php echo $lang === 'ar' ? 'عرض التفاصيل' : 'View Details'; ?>
                            <h3><?php echo htmlspecialchars($worker['name']); ?></h3>
                            <p><?php echo htmlspecialchars($worker['email'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($worker['phone']); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment Verification Section -->
        <div class="section">
            <h2 class="section-title">
                <?php echo $lang === 'ar' ? 'التحقق من الدفعات' : 'Payment Verification'; ?> 
                (<?php echo count($pendingPayments) ?? 0; ?>)
            </h2>

            <?php if (empty($pendingPayments)): ?>
                <div class="no-data">
                    <?php echo $lang === 'ar' ? 'لا توجد دفعات قيد الانتظار' : 'No pending payments'; ?>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo $lang === 'ar' ? 'اسم العامل' : 'Worker Name'; ?></th>
                                <th><?php echo $lang === 'ar' ? 'المبلغ' : 'Amount'; ?></th>
                                <th><?php echo $lang === 'ar' ? 'معرف العملية' : 'Transaction ID'; ?></th>
                                <th><?php echo $lang === 'ar' ? 'الإيصال' : 'Receipt'; ?></th>
                                <th><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?></th>
                                <th><?php echo $lang === 'ar' ? 'الإجراءات' : 'Actions'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['fullName']); ?></td>
                                    <td><?php echo number_format($payment['amount'], 2); ?> EGP</td>
                                    <td><?php echo htmlspecialchars($payment['transactionId'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($payment['receiptImageUrl']): ?>
                                            <a href="<?php echo $payment['receiptImageUrl']; ?>" class="document-link" target="_blank">
                                                <?php echo $lang === 'ar' ? 'عرض' : 'View'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($payment['createdAt'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="verify_payment">
                                                <input type="hidden" name="paymentId" value="<?php echo $payment['id']; ?>">
                                                <button type="submit" class="btn-small btn-verify">
                                                    <?php echo $lang === 'ar' ? 'تحقق' : 'Verify'; ?>
                                                </button>
                                            </form>
                                            <button class="btn-small btn-reject" onclick="openRejectModal(<?php echo $payment['id']; ?>, 'payment')">
                                                <?php echo $lang === 'ar' ? 'رفض' : 'Reject'; ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <?php echo $lang === 'ar' ? 'سبب الرفض' : 'Rejection Reason'; ?>
            </div>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="action" id="actionInput">
                <input type="hidden" name="workerId" id="workerIdInput">
                <input type="hidden" name="paymentId" id="paymentIdInput">
                
                <div class="form-group">
                    <label><?php echo $lang === 'ar' ? 'السبب (اختياري)' : 'Reason (Optional)'; ?></label>
                    <textarea name="rejectionReason" placeholder="<?php echo $lang === 'ar' ? 'اشرح سبب الرفض' : 'Explain the rejection reason'; ?>"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="close-modal" onclick="closeRejectModal()">
                        <?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?>
                    </button>
                    <button type="submit" class="btn-small btn-reject">
                        <?php echo $lang === 'ar' ? 'تأكيد الرفض' : 'Confirm Rejection'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id, type) {
            document.getElementById('rejectModal').classList.add('show');
            document.getElementById('actionInput').value = type === 'worker' ? 'reject_worker' : 'reject_payment';
            if (type === 'worker') {
                document.getElementById('workerIdInput').value = id;
                document.getElementById('paymentIdInput').value = '';
            } else {
                document.getElementById('paymentIdInput').value = id;
                document.getElementById('workerIdInput').value = '';
            }
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('show');
        }

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
</body>
</html>
