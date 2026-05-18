<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/google_ai.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_worker']) && !empty($_POST['worker_id'])) {
        $workerId = $_POST['worker_id'];
        $stmt = $conn->prepare("UPDATE workers SET approved = 'yes', status = 'active', paused = 'no', unpaid_streak_days = 0, updated_at = NOW() WHERE id::text = :id");
        $stmt->bindParam(':id', $workerId, PDO::PARAM_STR);
        $stmt->execute();
        $message = 'تمت الموافقة على حساب الفني بنجاح.';
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
        exit();
    }

    if (isset($_POST['decline_worker']) && !empty($_POST['worker_id'])) {
        $workerId = $_POST['worker_id'];
        $stmt = $conn->prepare("UPDATE workers SET approved = 'no', status = 'inactive', updated_at = NOW() WHERE id::text = :id");
        $stmt->bindParam(':id', $workerId, PDO::PARAM_STR);
        $stmt->execute();
        $message = 'تم رفض حساب الفني.';
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
        exit();
    }

    if (isset($_POST['pause_worker']) && !empty($_POST['worker_id'])) {
        $workerId = $_POST['worker_id'];
        $stmt = $conn->prepare("UPDATE workers SET paused = 'yes', updated_at = NOW() WHERE id::text = :id");
        $stmt->bindParam(':id', $workerId, PDO::PARAM_STR);
        $stmt->execute();
        $message = 'تم إيقاف حساب الفني مؤقتًا حتى يتم حل مشكلة الدفع.';
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
        exit();
    }

    if (isset($_POST['confirm_payment']) && is_numeric($_POST['request_id'])) {
        $requestId = (int)$_POST['request_id'];
        $stmt = $conn->prepare("UPDATE service_requests SET payment_status = 'paid', payment_confirmed_at = NOW() WHERE id = :id");
        $stmt->bindParam(':id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        $stmt2 = $conn->prepare("SELECT worker_id FROM service_requests WHERE id = :id LIMIT 1");
        $stmt2->bindParam(':id', $requestId, PDO::PARAM_INT);
        $stmt2->execute();
        $request = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($request && !empty($request['worker_id'])) {
            $workerId = $request['worker_id'];
            $stmt3 = $conn->prepare("UPDATE workers SET unpaid_streak_days = 0, paused = 'no', last_payment_confirmation_at = NOW() WHERE id::text = :id");
            $stmt3->bindParam(':id', $workerId, PDO::PARAM_STR);
            $stmt3->execute();
        }

        $message = 'تم تأكيد استلام الدفع وتحديث حالة الفاتورة.';
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
        exit();
    }
}

$statsQuery = $conn->query("SELECT
    COALESCE(SUM(CASE WHEN status = 'completed' THEN COALESCE(worker_price, 0) END), 0) AS total_revenue,
    COALESCE(SUM(CASE WHEN status = 'completed' AND DATE(completed_at) = CURRENT_DATE THEN COALESCE(worker_price, 0) END), 0) AS today_revenue,
    COALESCE(SUM(CASE WHEN status = 'completed' AND payment_status = 'unpaid' THEN COALESCE(commission_amount, 0) END), 0) AS pending_commission,
    COALESCE(SUM(CASE WHEN status = 'completed' AND payment_status = 'paid' THEN COALESCE(commission_amount, 0) END), 0) AS collected_commission,
    COUNT(*) FILTER (WHERE status = 'pending') AS pending_requests,
    COUNT(*) FILTER (WHERE status = 'accepted') AS accepted_requests,
    COUNT(*) FILTER (WHERE status = 'completed') AS completed_requests,
    COUNT(*) AS total_requests
FROM service_requests")->fetch(PDO::FETCH_ASSOC);

$workersStats = $conn->query("SELECT
    COUNT(*) AS total_workers,
    COUNT(*) FILTER (WHERE approved = 'pending') AS waiting_approval,
    COUNT(*) FILTER (WHERE approved = 'yes' AND status = 'active' AND paused != 'yes') AS active_workers,
    COUNT(*) FILTER (WHERE paused = 'yes' OR unpaid_streak_days >= 7) AS paused_workers
FROM workers")->fetch(PDO::FETCH_ASSOC);

$pendingWorkers = $conn->prepare("SELECT id, name, specialization, city, email, phone, created_at, id_front_path, id_back_path, certificate_path, cv_path, national_id FROM workers WHERE approved = 'pending' ORDER BY created_at DESC LIMIT 10");
$pendingWorkers->execute();
$pendingWorkers = $pendingWorkers->fetchAll(PDO::FETCH_ASSOC);

$pausedWorkers = $conn->prepare("SELECT id, name, specialization, city, email, phone, unpaid_streak_days, paused FROM workers WHERE paused = 'yes' OR unpaid_streak_days >= 7 ORDER BY unpaid_streak_days DESC LIMIT 10");
$pausedWorkers->execute();
$pausedWorkers = $pausedWorkers->fetchAll(PDO::FETCH_ASSOC);

$recentRequests = $conn->prepare("SELECT sr.id, sr.specialization, sr.city, sr.budget, sr.status, sr.payment_status, sr.created_at, u.name AS user_name, w.name AS worker_name
FROM service_requests sr
LEFT JOIN users u ON u.id = sr.us_id
LEFT JOIN workers w ON w.id::text = sr.worker_id::text
ORDER BY sr.created_at DESC
LIMIT 15");
$recentRequests->execute();
$recentRequests = $recentRequests->fetchAll(PDO::FETCH_ASSOC);

$pendingCommRequests = $conn->prepare("SELECT id, specialization, city, budget, commission_amount, payment_status, completed_at FROM service_requests WHERE status = 'completed' AND payment_status = 'unpaid' ORDER BY completed_at DESC LIMIT 15");
$pendingCommRequests->execute();
$pendingCommRequests = $pendingCommRequests->fetchAll(PDO::FETCH_ASSOC);

$aiInsights = '';
if (isset($_GET['use_ai']) && $_GET['use_ai'] === '1') {
    $summaryText = "إجمالي الإيرادات: " . number_format($statsQuery['total_revenue'], 2) . " EGP. إيراد اليوم: " . number_format($statsQuery['today_revenue'], 2) . " EGP. العمولة المعلقة: " . number_format($statsQuery['pending_commission'], 2) . " EGP. عدد الطلبات المكتملة: " . $statsQuery['completed_requests'] . ". عدد الفنيين المعلّقين: " . $workersStats['paused_workers'] . ".";
    $aiInsights = ai_generate_admin_insights($summaryText);
}

// Payment History - Detailed transaction records
$paymentHistory = $conn->prepare("
    SELECT
        wp.id,
        wp.worker_id,
        w.name AS worker_name,
        wp.request_id,
        wp.amount_paid,
        wp.commission_amount,
        wp.status AS payment_status,
        wp.confirmed_at,
        sr.completed_at,
        sr.budget,
        sr.specialization
    FROM worker_payments wp
    LEFT JOIN workers w ON w.id::text = wp.worker_id::text
    LEFT JOIN service_requests sr ON sr.id = wp.request_id
    ORDER BY wp.confirmed_at DESC
    LIMIT 30
");
$paymentHistory->execute();
$paymentHistory = $paymentHistory->fetchAll(PDO::FETCH_ASSOC);

// Commission Breakdown - Breakdown by payment status
$commissionBreakdown = $conn->query("
    SELECT 
        payment_status,
        COUNT(*) AS count,
        COALESCE(SUM(commission_amount), 0) AS total_commission,
        COALESCE(AVG(commission_amount), 0) AS avg_commission,
        MIN(commission_amount) AS min_commission,
        MAX(commission_amount) AS max_commission
    FROM service_requests
    WHERE status = 'completed'
    GROUP BY payment_status
")->fetchAll(PDO::FETCH_ASSOC);

// Users with Request Count - Active users and their request history
$usersWithRequestCount = $conn->prepare("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.phone,
        u.location,
        COUNT(sr.id) AS total_requests,
        COALESCE(SUM(CASE WHEN sr.status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_requests,
        COALESCE(SUM(CASE WHEN sr.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_requests,
        MAX(sr.created_at) AS last_request_date
    FROM users u
    LEFT JOIN service_requests sr ON sr.us_id = u.id
    WHERE u.role = 'user'
    GROUP BY u.id, u.name, u.email, u.phone, u.location
    ORDER BY total_requests DESC
    LIMIT 25
");
$usersWithRequestCount->execute();
$usersWithRequestCount = $usersWithRequestCount->fetchAll(PDO::FETCH_ASSOC);

// Chat Previews - Recent chat messages for active requests
$chatPreviews = $conn->prepare("
    SELECT
        cm.id,
        cm.request_id,
        sr.specialization,
        sr.status AS request_status,
        u.name AS user_name,
        w.name AS worker_name,
        cm.sender_type,
        cm.sender_id,
        SUBSTRING(cm.message, 1, 80) AS message_preview,
        cm.created_at,
        COUNT(*) OVER (PARTITION BY cm.request_id) AS total_request_messages
    FROM chat_messages cm
    LEFT JOIN service_requests sr ON sr.id = cm.request_id
    LEFT JOIN users u ON u.id = sr.us_id
    LEFT JOIN workers w ON w.id::text = sr.worker_id::text
    WHERE sr.status IN ('pending', 'accepted', 'completed')
    ORDER BY cm.created_at DESC
    LIMIT 40
");
$chatPreviews->execute();
$chatPreviews = $chatPreviews->fetchAll(PDO::FETCH_ASSOC);

// Worker Performance - Detailed performance metrics
$workerPerformance = $conn->prepare("
    SELECT 
        w.id,
        w.name,
        w.specialization,
        w.city,
        w.approved,
        w.status,
        w.total_earnings,
        COUNT(DISTINCT sr.id) AS total_completed_jobs,
        COALESCE(COUNT(DISTINCT CASE WHEN rw.rating >= 4 THEN sr.id END), 0) AS high_rated_jobs,
        COALESCE(AVG(rw.rating), 0) AS avg_rating,
        COALESCE(COUNT(DISTINCT rw.id), 0) AS review_count,
        COALESCE(SUM(CASE WHEN sr.payment_status = 'paid' THEN sr.worker_price ELSE 0 END), 0) AS paid_earnings,
        COALESCE(SUM(CASE WHEN sr.payment_status = 'unpaid' THEN sr.worker_price ELSE 0 END), 0) AS unpaid_earnings,
        MAX(sr.completed_at) AS last_completed_job
    FROM workers w
    LEFT JOIN service_requests sr ON sr.worker_id::text = w.id::text AND sr.status = 'completed'
    LEFT JOIN reviews_worker rw ON rw.worker_id::text = w.id::text AND rw.request_id = sr.id
    WHERE w.approved = 'yes'
    GROUP BY w.id, w.name, w.specialization, w.city, w.approved, w.status, w.total_earnings
    ORDER BY total_completed_jobs DESC, avg_rating DESC
    LIMIT 30
");
$workerPerformance->execute();
$workerPerformance = $workerPerformance->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | لوحة تحكم الإدارة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8fafc; color: #1f2937; }
        .glass-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); }
        .tab-btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .tab-btn.inactive {
            background: rgba(255, 255, 255, 0.1);
            color: #64748b;
            border-color: rgba(255, 255, 255, 0.2);
        }
        .tab-btn.inactive:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #475569;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        details {
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        details:hover {
            background: rgba(255, 255, 255, 0.7);
            border-color: rgba(102, 126, 234, 0.5);
        }
        summary {
            font-weight: 600;
            color: #1f2937;
            display: flex;
            justify-content: space-between;
            align-items: center;
            outline: none;
        }
        summary::marker { color: #667eea; }
        .detail-content {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="min-h-screen">
    <header class="bg-white shadow sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-5 flex flex-col md:flex-row justify-between gap-4 md:items-center">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">لوحة إدارة فليكس</h1>
                <p class="text-slate-500 mt-1">لوحة تحكم المركزية لمراقبة الإيرادات، الطلبات، الفنانين، ودفع العمولة.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="logout.php" class="px-4 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition">تسجيل الخروج</a>
                <a href="admin.php?use_ai=1" class="px-4 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">توليد تقرير AI</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-8">
        <?php if ($message): ?>
            <div class="rounded-3xl border border-green-200 bg-emerald-50 text-emerald-800 p-5 font-semibold"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="flex flex-wrap gap-4 mb-8">
            <button class="tab-btn active" onclick="showTab('financial')">المالية</button>
            <button class="tab-btn inactive" onclick="showTab('support')">الدعم</button>
            <button class="tab-btn inactive" onclick="showTab('acceptance')">القبول</button>
        </div>

        <!-- Financial Tab -->
        <div id="financial" class="tab-content active">
            <h2 class="text-2xl font-bold mb-6">المالية والمعاملات</h2>

            <!-- Financial Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="glass-card p-6 rounded-3xl shadow">
                    <h3 class="text-lg font-semibold text-slate-700">إجمالي الإيرادات</h3>
                    <p class="text-3xl font-bold text-green-600 mt-2"><?= number_format($statsQuery['total_revenue'], 2) ?> EGP</p>
                </div>
                <div class="glass-card p-6 rounded-3xl shadow">
                    <h3 class="text-lg font-semibold text-slate-700">إيراد اليوم</h3>
                    <p class="text-3xl font-bold text-blue-600 mt-2"><?= number_format($statsQuery['today_revenue'], 2) ?> EGP</p>
                </div>
                <div class="glass-card p-6 rounded-3xl shadow">
                    <h3 class="text-lg font-semibold text-slate-700">العمولة المعلقة</h3>
                    <p class="text-3xl font-bold text-orange-600 mt-2"><?= number_format($statsQuery['pending_commission'], 2) ?> EGP</p>
                </div>
                <div class="glass-card p-6 rounded-3xl shadow">
                    <h3 class="text-lg font-semibold text-slate-700">العمولة المجمعة</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2"><?= number_format($statsQuery['collected_commission'], 2) ?> EGP</p>
                </div>
            </div>

            <!-- Commission Breakdown -->
            <div class="glass-card p-6 rounded-3xl shadow mb-8">
                <h3 class="text-xl font-bold mb-4">تفصيل العمولات</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-right p-2">حالة الدفع</th>
                                <th class="text-right p-2">عدد</th>
                                <th class="text-right p-2">إجمالي العمولة</th>
                                <th class="text-right p-2">متوسط العمولة</th>
                                <th class="text-right p-2">أدنى</th>
                                <th class="text-right p-2">أعلى</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissionBreakdown as $cb): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?= htmlspecialchars($cb['payment_status']) ?></td>
                                    <td class="p-2"><?= $cb['count'] ?></td>
                                    <td class="p-2"><?= number_format($cb['total_commission'], 2) ?> EGP</td>
                                    <td class="p-2"><?= number_format($cb['avg_commission'], 2) ?> EGP</td>
                                    <td class="p-2"><?= number_format($cb['min_commission'], 2) ?> EGP</td>
                                    <td class="p-2"><?= number_format($cb['max_commission'], 2) ?> EGP</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="glass-card p-6 rounded-3xl shadow">
                <h3 class="text-xl font-bold mb-4">تاريخ الدفعات</h3>
                <div class="space-y-4">
                    <?php foreach ($paymentHistory as $payment): ?>
                        <details>
                            <summary>
                                <span>دفعة لـ <?= htmlspecialchars($payment['worker_name']) ?> - <?= number_format($payment['amount_paid'], 2) ?> EGP</span>
                                <span class="text-sm text-slate-500"><?= htmlspecialchars($payment['confirmed_at']) ?></span>
                            </summary>
                            <div class="detail-content">
                                <p><strong>الخدمة:</strong> <?= htmlspecialchars($payment['specialization']) ?></p>
                                <p><strong>المبلغ المدفوع:</strong> <?= number_format($payment['amount_paid'], 2) ?> EGP</p>
                                <p><strong>العمولة:</strong> <?= number_format($payment['commission_amount'], 2) ?> EGP</p>
                                <p><strong>تاريخ الإتمام:</strong> <?= htmlspecialchars($payment['completed_at']) ?></p>
                                <!-- Placeholder for uploads/images -->
                                <p><strong>الرفقات:</strong> لا توجد رفات حالياً (يمكن إضافة صور أو ملفات هنا)</p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Support Tab -->
        <div id="support" class="tab-content">
            <h2 class="text-2xl font-bold mb-6">الدعم والطلبات</h2>

            <!-- Recent Requests -->
            <div class="glass-card p-6 rounded-3xl shadow mb-8">
                <h3 class="text-xl font-bold mb-4">الطلبات الأخيرة</h3>
                <div class="space-y-4">
                    <?php foreach ($recentRequests as $request): ?>
                        <details>
                            <summary>
                                <span>طلب <?= htmlspecialchars($request['specialization']) ?> - <?= htmlspecialchars($request['user_name']) ?></span>
                                <span class="text-sm text-slate-500"><?= htmlspecialchars($request['created_at']) ?></span>
                            </summary>
                            <div class="detail-content">
                                <p><strong>العميل:</strong> <?= htmlspecialchars($request['user_name']) ?></p>
                                <p><strong>الفني:</strong> <?= htmlspecialchars($request['worker_name'] ?? 'غير محدد') ?></p>
                                <p><strong>المدينة:</strong> <?= htmlspecialchars($request['city']) ?></p>
                                <p><strong>الميزانية:</strong> <?= htmlspecialchars($request['budget']) ?> EGP</p>
                                <p><strong>الحالة:</strong> <?= htmlspecialchars($request['status']) ?> / <?= htmlspecialchars($request['payment_status']) ?></p>
                                <!-- Placeholder for media -->
                                <p><strong>الرفقات:</strong> لا توجد رفات حالياً</p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Chat Previews -->
            <div class="glass-card p-6 rounded-3xl shadow">
                <h3 class="text-xl font-bold mb-4">معاينة الدردشات</h3>
                <div class="space-y-4">
                    <?php foreach ($chatPreviews as $chat): ?>
                        <details>
                            <summary>
                                <span>دردشة في طلب <?= htmlspecialchars($chat['specialization']) ?> - <?= htmlspecialchars($chat['message_preview']) ?>...</span>
                                <span class="text-sm text-slate-500"><?= htmlspecialchars($chat['created_at']) ?></span>
                            </summary>
                            <div class="detail-content">
                                <p><strong>العميل:</strong> <?= htmlspecialchars($chat['user_name']) ?></p>
                                <p><strong>الفني:</strong> <?= htmlspecialchars($chat['worker_name'] ?? 'غير محدد') ?></p>
                                <p><strong>المرسل:</strong> <?= htmlspecialchars($chat['sender_type']) ?></p>
                                <p><strong>الرسالة الكاملة:</strong> <?= htmlspecialchars($chat['message_preview']) ?>...</p>
                                <p><strong>إجمالي الرسائل في الطلب:</strong> <?= $chat['total_request_messages'] ?></p>
                                <!-- Placeholder for media -->
                                <p><strong>الرفقات:</strong> لا توجد رفات حالياً</p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Acceptance Tab -->
        <div id="acceptance" class="tab-content">
            <h2 class="text-2xl font-bold mb-6">قبول الفنيين</h2>

            <!-- Pending Workers -->
            <div class="glass-card p-6 rounded-3xl shadow mb-8">
                <h3 class="text-xl font-bold mb-4">الفنيون في انتظار الموافقة</h3>
                <div class="space-y-4">
                    <?php foreach ($pendingWorkers as $worker): ?>
                        <details>
                            <summary>
                                <span>فني: <?= htmlspecialchars($worker['name']) ?> - <?= htmlspecialchars($worker['specialization']) ?></span>
                                <span class="text-sm text-slate-500"><?= htmlspecialchars($worker['created_at']) ?></span>
                            </summary>
                            <div class="detail-content">
                                <p><strong>الاسم:</strong> <?= htmlspecialchars($worker['name']) ?></p>
                                <p><strong>التخصص:</strong> <?= htmlspecialchars($worker['specialization']) ?></p>
                                <p><strong>المدينة:</strong> <?= htmlspecialchars($worker['city']) ?></p>
                                <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($worker['email']) ?></p>
                                <p><strong>الهاتف:</strong> <?= htmlspecialchars($worker['phone']) ?></p>
                                <!-- Media Attachments -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php if (!empty($worker['id_front_path'])): ?>
                                        <div>
                                            <p class="text-sm font-semibold">صورة الهوية - أمامية</p>
                                            <a href="<?= htmlspecialchars($worker['id_front_path']) ?>" target="_blank" class="block mt-2">
                                                <img src="<?= htmlspecialchars($worker['id_front_path']) ?>" alt="ID Front" class="w-40 h-auto rounded shadow" />
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($worker['id_back_path'])): ?>
                                        <div>
                                            <p class="text-sm font-semibold">صورة الهوية - خلفية</p>
                                            <a href="<?= htmlspecialchars($worker['id_back_path']) ?>" target="_blank" class="block mt-2">
                                                <img src="<?= htmlspecialchars($worker['id_back_path']) ?>" alt="ID Back" class="w-40 h-auto rounded shadow" />
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($worker['certificate_path'])): ?>
                                        <div>
                                            <p class="text-sm font-semibold">الشهادة / مستند</p>
                                            <a href="<?= htmlspecialchars($worker['certificate_path']) ?>" target="_blank" class="block mt-2 text-indigo-600 hover:underline">فتح الشهادة</a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($worker['cv_path'])): ?>
                                        <div>
                                            <p class="text-sm font-semibold">السيرة الذاتية</p>
                                            <a href="<?= htmlspecialchars($worker['cv_path']) ?>" target="_blank" class="block mt-2 text-indigo-600 hover:underline">تحميل السيرة الذاتية</a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (empty($worker['id_front_path']) && empty($worker['id_back_path']) && empty($worker['certificate_path']) && empty($worker['cv_path'])): ?>
                                        <div class="col-span-full text-sm text-slate-500">لا توجد مرفقات حالياً</div>
                                    <?php endif; ?>
                                </div>

                                <form method="post" class="mt-4 flex gap-2">
                                    <input type="hidden" name="worker_id" value="<?= htmlspecialchars($worker['id']) ?>">
                                    <button type="submit" name="approve_worker" class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700">موافقة</button>
                                    <button type="submit" name="decline_worker" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">رفض</button>
                                </form>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Paused Workers -->
            <div class="glass-card p-6 rounded-3xl shadow">
                <h3 class="text-xl font-bold mb-4">الفنيون الموقوفون</h3>
                <div class="space-y-4">
                    <?php foreach ($pausedWorkers as $worker): ?>
                        <details>
                            <summary>
                                <span>فني: <?= htmlspecialchars($worker['name']) ?> - أيام غير مدفوعة: <?= $worker['unpaid_streak_days'] ?></span>
                            </summary>
                            <div class="detail-content">
                                <p><strong>الاسم:</strong> <?= htmlspecialchars($worker['name']) ?></p>
                                <p><strong>التخصص:</strong> <?= htmlspecialchars($worker['specialization']) ?></p>
                                <p><strong>المدينة:</strong> <?= htmlspecialchars($worker['city']) ?></p>
                                <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($worker['email']) ?></p>
                                <p><strong>الهاتف:</strong> <?= htmlspecialchars($worker['phone']) ?></p>
                                <p><strong>أيام غير مدفوعة:</strong> <?= $worker['unpaid_streak_days'] ?></p>
                                <p><strong>موقوف:</strong> <?= $worker['paused'] === 'yes' ? 'نعم' : 'لا' ?></p>
                                <!-- Placeholder for media -->
                                <p><strong>الرفقات:</strong> لا توجد رفات حالياً</p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- AI Insights -->
        <?php if ($aiInsights): ?>
            <div class="glass-card p-6 rounded-3xl shadow">
                <h3 class="text-xl font-bold mb-4">رؤى AI</h3>
                <p class="text-slate-700 leading-relaxed"><?= htmlspecialchars($aiInsights) ?></p>
            </div>
        <?php endif; ?>
    </main>
    </main>

    <script>
        function showTab(tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            const buttons = document.querySelectorAll('.tab-btn');
            tabs.forEach(tab => tab.classList.remove('active'));
            buttons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('inactive');
            });
            document.getElementById(tabId).classList.add('active');
            event.target.classList.remove('inactive');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
