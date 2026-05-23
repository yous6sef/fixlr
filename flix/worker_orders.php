<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$workerId = (string) $_SESSION['user_id'];

// Get worker data
$stmt = $conn->prepare("SELECT * FROM workers WHERE id::text = :id");
$stmt->bindParam(':id', $workerId, PDO::PARAM_STR);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$isActive = $worker['status'] ?? 'no';
$specialization = $worker['specialization'] ?? '';
$city = $worker['city'] ?? '';
$notApproved = !isset($worker['approved']) || $worker['approved'] !== 'yes';
$isPaused = ($worker['unpaid_streak_days'] ?? 0) >= 7;

// Check if worker has active task
$activeTaskStmt = $conn->prepare("SELECT sr.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone FROM service_requests sr LEFT JOIN users u ON u.id::text = sr.us_id::text WHERE sr.worker_id::text = :id AND sr.status = 'accepted' ORDER BY sr.created_at DESC LIMIT 1");
$activeTaskStmt->bindParam(':id', $workerId, PDO::PARAM_STR);
$activeTaskStmt->execute();
$activeTask = $activeTaskStmt->fetch(PDO::FETCH_ASSOC);

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'status') {
    $newStatus = ($isActive === "active") ? "no" : "active";
    $stm = $conn->prepare("UPDATE workers SET status = :status WHERE id::text = :id");
    $stm->bindParam(':status', $newStatus);
    $stm->bindParam(':id', $workerId);
    $stm->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle city update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['places'])) {
    $newCity = $_POST['places'];
    $st = $conn->prepare("UPDATE workers SET city = :city WHERE id::text = :id");
    $st->bindParam(':city', $newCity);
    $st->bindParam(':id', $workerId);
    $st->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;

    if (isset($_POST['accept']) && $order_id) {
        if ($activeTask) {
            // Worker has active task, can't accept new ones
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=active_task");
            exit();
        }

        $conn->beginTransaction();
        try {
            $getRequest = $conn->prepare("SELECT id, us_id FROM service_requests WHERE id = :id AND status = 'pending' LIMIT 1");
            $getRequest->bindParam(':id', $order_id, PDO::PARAM_INT);
            $getRequest->execute();
            $requestRow = $getRequest->fetch(PDO::FETCH_ASSOC);

            if ($requestRow) {
                $usid = $requestRow['us_id'];
                $aaa = $conn->prepare("INSERT INTO tasks (worker_id, us_id, request_id, start_time, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())");
                $aaa->execute([$workerId, $usid, $order_id]);

                $aa = $conn->prepare("UPDATE service_requests SET status = 'accepted', worker_id = :worker_id, negotiation_state = 'accepted', negotiation_started_at = COALESCE(negotiation_started_at, NOW()), negotiation_ended_at = NOW() WHERE id = :id AND status = 'pending' AND specialization = :specialization AND city = :city");
                $aa->bindParam(':id', $order_id, PDO::PARAM_INT);
                $aa->bindParam(':worker_id', $workerId, PDO::PARAM_STR);
                $aa->bindParam(':specialization', $specialization, PDO::PARAM_STR);
                $aa->bindParam(':city', $city, PDO::PARAM_STR);
                $aa->execute();
                if ($aa->rowCount() > 0) {
                    header('Location: worker_track.php');
                    exit();
                }
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
        }
    }

    if (isset($_POST['reject']) && $order_id) {
        $sssa = $conn->prepare("UPDATE service_requests SET refusers = COALESCE(refusers, 0) + 1, worker_price = NULL, worker_id = NULL, negotiation_state = 'rejected', negotiation_ended_at = NOW() WHERE id = :id AND status = 'pending'");
        $sssa->bindParam(':id', $order_id, PDO::PARAM_INT);
        $sssa->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['price']) && $order_id) {
        $worker_price = $_POST['worker_price'] ?? null;
        if (is_numeric($worker_price) && $worker_price > 0) {
            $aaa = $conn->prepare("UPDATE service_requests SET worker_price = :worker_price, worker_id = :worker_id, negotiation_state = 'countered', negotiation_started_at = COALESCE(negotiation_started_at, NOW()), negotiation_ended_at = NULL WHERE id = :id AND status = 'pending'");
            $aaa->bindParam(':id', $order_id, PDO::PARAM_INT);
            $aaa->bindParam(':worker_price', $worker_price, PDO::PARAM_STR);
            $aaa->bindParam(':worker_id', $workerId, PDO::PARAM_STR);
            $aaa->execute();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Get pending orders for this worker (only if no active task)
$pendingOrders = [];
if (!$activeTask) {
    $pendingStmt = $conn->prepare('SELECT * FROM service_requests WHERE specialization = :specialization AND city = :city AND status = :status AND refusers < :limit ORDER BY created_at ASC');
    $pendingStmt->bindParam(':specialization', $specialization, PDO::PARAM_STR);
    $pendingStmt->bindParam(':city', $city, PDO::PARAM_STR);
    $pendingStmt->bindValue(':status', 'pending', PDO::PARAM_STR);
    $pendingStmt->bindValue(':limit', 3, PDO::PARAM_INT);
    $pendingStmt->execute();
    $pendingOrders = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
}

$canAccept = !$notApproved && $isActive === 'active' && !$isPaused && !$activeTask;

$statusLabel = $isActive === 'active' ? 'متاح لاستقبال الطلبات' : 'غير متاح لاستقبال الطلبات';
$statusButton = $isActive === 'active' ? 'تبديل إلى غير متاح' : 'تبديل إلى متاح';
?>
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | إدارة الطلبات - الفني</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; color: #1e293b; }</style>
</head>
<body class="min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4">
        <header class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-500">مرحلة إدارة الطلبات</p>
                <h1 class="text-3xl font-black text-slate-900">إدارة الطلبات</h1>
            </div>
            <div class="flex gap-3">
                <a href="worker_profile.php" class="inline-flex items-center justify-center rounded-3xl bg-slate-600 px-5 py-3 text-white font-semibold hover:bg-slate-700 transition">الملف الشخصي</a>
                <a href="worker_payments.php" class="inline-flex items-center justify-center rounded-3xl bg-emerald-500 px-5 py-3 text-white font-semibold hover:bg-emerald-600 transition">المدفوعات</a>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-1 bg-slate-100 p-1 rounded-2xl rtl:space-x-reverse">
                <a href="worker_orders.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl bg-white text-slate-900 shadow-sm">الطلبات</a>
                <a href="worker_track.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">تتبع الخدمة</a>
                <a href="worker_receipt.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الإيصالات</a>
            </nav>
        </div>

        <?php if ($notApproved): ?>
            <div class="rounded-3xl border border-yellow-300 bg-yellow-50 p-6 text-right text-yellow-800 shadow-sm mb-6">
                حسابك قيد المراجعة من قبل الإدارة. لن تتمكن من استقبال الطلبات حتى يتم الموافقة عليك.
            </div>
        <?php endif; ?>

        <?php if ($isPaused): ?>
            <div class="rounded-3xl border border-red-300 bg-red-50 p-6 text-right text-red-800 shadow-sm mb-6">
                تم إيقاف حسابك مؤقتًا بسبب تأخر الدفع لمدة 7 أيام أو أكثر. يرجى التواصل مع الإدارة لتأكيد الدفع وإعادة التشغيل.
            </div>
        <?php endif; ?>

        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <!-- Sidebar -->
            <aside class="space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">حالة العمل</h3>
                    <p class="text-lg font-semibold text-emerald-600 mb-4"><?= htmlspecialchars($statusLabel) ?></p>
                    <form method="POST">
                        <input type="hidden" name="action" value="status">
                        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition">
                            <?= htmlspecialchars($statusButton) ?>
                        </button>
                    </form>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">موقع العمل</h3>
                    <p class="text-sm text-slate-500 mb-4">المدينة الحالية: <?= htmlspecialchars($city) ?></p>
                    <form method="POST">
                        <select name="places" class="w-full px-4 py-3 rounded-xl border border-slate-300 mb-4" required>
                            <option value="">اختر مدينة</option>
                            <option value="october">6 أكتوبر</option>
                            <option value="zayed">الشيخ زايد</option>
                        </select>
                        <button type="submit" class="w-full bg-red-500 text-white py-3 rounded-xl font-bold hover:bg-red-600 transition">
                            تحديث الموقع
                        </button>
                    </form>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">إحصائيات سريعة</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-slate-500">التخصص:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($specialization) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">المدينة:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($city) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">الحالة:</span>
                            <span class="font-semibold text-emerald-600"><?= htmlspecialchars($statusLabel) ?></span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="space-y-6">
                <?php if ($activeTask): ?>
                    <!-- Active Task Section -->
                    <div class="rounded-[2rem] bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-xl border border-blue-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-black text-slate-900">المهمة النشطة</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-600 border border-blue-200 uppercase tracking-wider">قيد التنفيذ</span>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-3">
                                <div class="rounded-3xl bg-white p-4 border border-slate-200">
                                    <p class="text-sm text-slate-500">نوع الخدمة</p>
                                    <p class="mt-2 font-bold text-lg text-slate-900"><?= htmlspecialchars($activeTask['specialization']) ?></p>
                                </div>
                                <div class="rounded-3xl bg-white p-4 border border-slate-200">
                                    <p class="text-sm text-slate-500">الوصف</p>
                                    <p class="mt-2 text-slate-700"><?= htmlspecialchars($activeTask['description']) ?></p>
                                </div>
                                <div class="rounded-3xl bg-white p-4 border border-slate-200">
                                    <p class="text-sm text-slate-500">الموقع</p>
                                    <p class="mt-2 text-slate-700">📍 <?= htmlspecialchars($activeTask['address']) ?>, <?= htmlspecialchars($activeTask['city']) ?></p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="rounded-3xl bg-white p-4 border border-slate-200">
                                    <p class="text-sm text-slate-500">بيانات العميل</p>
                                    <div class="mt-2 space-y-1">
                                        <p class="font-semibold text-slate-900">👤 <?= htmlspecialchars($activeTask['user_name']) ?></p>
                                        <p class="text-slate-600">📧 <?= htmlspecialchars($activeTask['user_email']) ?></p>
                                        <p class="text-slate-600">📱 <?= htmlspecialchars($activeTask['user_phone']) ?></p>
                                    </div>
                                </div>
                                <div class="rounded-3xl bg-white p-4 border border-slate-200">
                                    <p class="text-sm text-slate-500">السعر المتفق عليه</p>
                                    <p class="mt-2 text-2xl font-black text-rose-600"><?= htmlspecialchars($activeTask['worker_price'] ?: $activeTask['budget']) ?> EGP</p>
                                </div>
                                <a href="worker_track.php" class="block rounded-3xl bg-indigo-500 text-white text-center py-3 font-semibold hover:bg-indigo-600 transition">
                                    تتبع الخدمة
                                </a>
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-3xl">
                            <p class="text-yellow-800 text-sm">⚠️ لا يمكنك قبول طلبات جديدة حتى إنهاء هذه المهمة</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$canAccept && !$activeTask): ?>
                    <div class="rounded-3xl border border-red-300 bg-red-50 p-6 text-right text-red-800 shadow-sm">
                        لا يمكنك قبول طلبات جديدة حتى يتم حل حالة حسابك أو الموافقة عليه من الإدارة.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'active_task'): ?>
                    <div class="rounded-3xl border border-red-300 bg-red-50 p-6 text-right text-red-800 shadow-sm">
                        لا يمكنك قبول طلبات جديدة أثناء وجود مهمة نشطة. أنهِ المهمة الحالية أولاً.
                    </div>
                <?php endif; ?>

                <?php if (empty($pendingOrders) && !$activeTask): ?>
                    <div class="rounded-[2rem] bg-white p-8 shadow-xl border border-slate-200 text-center">
                        <div class="text-6xl mb-4">📋</div>
                        <h3 class="text-xl font-black text-slate-900 mb-2">لا توجد طلبات جديدة</h3>
                        <p class="text-slate-500">لا توجد طلبات خدمة متاحة في مدينتك حالياً. تحقق مرة أخرى لاحقاً.</p>
                    </div>
                <?php elseif (!empty($pendingOrders) && !$activeTask): ?>
                    <?php foreach ($pendingOrders as $order): ?>
                        <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                            <div class="flex justify-between items-start mb-6">
                                <div class="flex-1">
                                    <div class="mb-2">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase tracking-wider">
                                            طلب جديد
                                        </span>
                                    </div>
                                    <h3 class="text-xl font-black text-slate-800 leading-tight mb-2">
                                        <?= htmlspecialchars($order['specialization']) ?> - <?= htmlspecialchars($order['description']) ?>
                                    </h3>
                                    <p class="text-sm text-slate-400 flex items-center gap-1 mb-3">
                                        📍 <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>
                                    </p>
                                    <div class="text-sm text-slate-600 space-y-1">
                                        <p><strong>العميل:</strong> <?= htmlspecialchars($order['username']) ?></p>
                                        <p><strong>الميزانية المقترحة:</strong> <?= htmlspecialchars($order['budget']) ?> EGP</p>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs text-slate-400 font-bold uppercase">السعر المطلوب</p>
                                    <p class="text-2xl font-black text-rose-600"><?= htmlspecialchars($order['budget']) ?> EGP</p>
                                </div>
                            </div>

                            <?php if ($canAccept): ?>
                                <div class="space-y-4">
                                    <form method="POST" class="flex gap-3">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                        <button type="submit" name="accept" class="flex-1 bg-emerald-500 text-white p-3 rounded-xl font-bold hover:bg-emerald-600 transition">
                                            قبول الطلب
                                        </button>
                                        <button type="submit" name="reject" class="flex-1 bg-red-500 text-white p-3 rounded-xl font-bold hover:bg-red-600 transition">
                                            رفض الطلب
                                        </button>
                                    </form>

                                    <div class="border-t pt-4">
                                        <h4 class="text-sm font-bold text-slate-900 mb-3">اقتراح سعر مختلف</h4>
                                        <form method="POST" class="flex gap-3">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                            <input type="number" name="worker_price" min="1" placeholder="السعر المقترح" class="flex-1 px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                            <button type="submit" name="price" class="bg-yellow-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-yellow-600 transition">
                                                إرسال العرض
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
