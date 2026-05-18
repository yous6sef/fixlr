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

// Calculate daily earnings (completed services today)
$today = date('Y-m-d');
$dailyEarningsStmt = $conn->prepare("
    SELECT COALESCE(SUM(sr.worker_price), 0) as daily_earnings
    FROM service_requests sr
    WHERE sr.worker_id::text = :worker_id
    AND sr.status = 'completed'
    AND DATE(sr.completed_at) = :today
");
$dailyEarningsStmt->bindParam(':worker_id', $workerId, PDO::PARAM_STR);
$dailyEarningsStmt->bindParam(':today', $today);
$dailyEarningsStmt->execute();
$dailyEarnings = $dailyEarningsStmt->fetch(PDO::FETCH_ASSOC)['daily_earnings'] ?? 0;

// Calculate platform fee (20%)
$platformFee = $dailyEarnings * 0.2;

// Get pending payments
$pendingPaymentsStmt = $conn->prepare("
    SELECT * FROM worker_payments
    WHERE worker_id::text = :worker_id
    AND status = 'pending'
    ORDER BY created_at DESC
");
$pendingPaymentsStmt->bindParam(':worker_id', $workerId, PDO::PARAM_STR);
$pendingPaymentsStmt->execute();
$pendingPayments = $pendingPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment history
$paymentHistoryStmt = $conn->prepare("
    SELECT * FROM worker_payments
    WHERE worker_id::text = :worker_id
    AND status = 'confirmed'
    ORDER BY confirmed_at DESC
    LIMIT 10
");
$paymentHistoryStmt->bindParam(':worker_id', $workerId, PDO::PARAM_STR);
$paymentHistoryStmt->execute();
$paymentHistory = $paymentHistoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $amount = $_POST['amount'] ?? 0;
    $transactionRef = $_POST['transaction_ref'] ?? '';

    if ($amount > 0 && !empty($transactionRef)) {
        $insertPayment = $conn->prepare("
            INSERT INTO worker_payments (worker_id, amount_paid, commission_amount, status, transaction_ref, created_at)
            VALUES (:worker_id, :amount, :commission, 'pending', :transaction_ref, NOW())
        ");
        $insertPayment->bindParam(':worker_id', $workerId, PDO::PARAM_STR);
        $insertPayment->bindParam(':amount', $amount);
        $insertPayment->bindParam(':commission', $platformFee);
        $insertPayment->bindParam(':transaction_ref', $transactionRef);
        $insertPayment->execute();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=payment_submitted');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | إدارة المدفوعات - الفني</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; color: #1e293b; }</style>
</head>
<body class="min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">
        <header class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-500">إدارة المدفوعات والعمولات</p>
                <h1 class="text-3xl font-black text-slate-900">المدفوعات</h1>
            </div>
            <div class="flex gap-3">
                <a href="worker_profile.php" class="inline-flex items-center justify-center rounded-3xl bg-slate-600 px-5 py-3 text-white font-semibold hover:bg-slate-700 transition">الملف الشخصي</a>
                <a href="worker_orders.php" class="inline-flex items-center justify-center rounded-3xl bg-blue-500 px-5 py-3 text-white font-semibold hover:bg-blue-600 transition">الطلبات</a>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-1 bg-slate-100 p-1 rounded-2xl rtl:space-x-reverse">
                <a href="worker_orders.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الطلبات</a>
                <a href="worker_track.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">تتبع الخدمة</a>
                <a href="worker_receipt.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الإيصالات</a>
                <a href="worker_payments.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl bg-white text-slate-900 shadow-sm">المدفوعات</a>
            </nav>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'payment_submitted'): ?>
            <div class="rounded-3xl border border-green-300 bg-green-50 p-6 text-right text-green-800 shadow-sm mb-6">
                تم إرسال إشعار الدفع بنجاح! سيتم مراجعته من قبل الإدارة خلال 24 ساعة.
            </div>
        <?php endif; ?>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Payment Calculator -->
            <div class="space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">حساب العمولة اليومية</h3>
                    <div class="space-y-4">
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">إجمالي الأرباح اليوم:</span>
                                <span class="font-bold text-lg text-slate-900"><?= number_format($dailyEarnings, 2) ?> EGP</span>
                            </div>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">العمولة المستحقة (20%):</span>
                                <span class="font-bold text-lg text-red-600"><?= number_format($platformFee, 2) ?> EGP</span>
                            </div>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">صافي الأرباح:</span>
                                <span class="font-bold text-lg text-green-600"><?= number_format($dailyEarnings - $platformFee, 2) ?> EGP</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <div class="rounded-[2rem] bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-xl border border-blue-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">تعليمات الدفع</h3>
                    <div class="space-y-4">
                        <div class="rounded-3xl bg-white p-4 border border-slate-200">
                            <h4 class="font-bold text-slate-900 mb-2">طريقة الدفع: إنستاباي</h4>
                            <p class="text-slate-600 mb-2">قم بتحويل العمولة المستحقة إلى الحساب التالي:</p>
                            <div class="bg-slate-50 p-3 rounded-xl">
                                <p class="font-mono text-sm text-slate-800">test@instapay</p>
                            </div>
                        </div>
                        <div class="rounded-3xl bg-white p-4 border border-slate-200">
                            <h4 class="font-bold text-slate-900 mb-2">خطوات الدفع:</h4>
                            <ol class="list-decimal list-inside space-y-1 text-slate-600 text-sm">
                                <li>افتح تطبيق إنستاباي</li>
                                <li>اختر "تحويل"</li>
                                <li>أدخل رقم الحساب: test@instapay</li>
                                <li>أدخل المبلغ: <?= number_format($platformFee, 2) ?> EGP</li>
                                <li>أضف ملاحظة: "عمولة فليكس - [اسمك]"</li>
                                <li>احفظ رقم المعاملة</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <?php if ($platformFee > 0): ?>
                    <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                        <h3 class="text-lg font-black text-slate-900 mb-4">إرسال إشعار الدفع</h3>
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">المبلغ المدفوع</label>
                                <input type="number" name="amount" value="<?= number_format($platformFee, 2) ?>" step="0.01" min="0.01" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" required readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">رقم المعاملة</label>
                                <input type="text" name="transaction_ref" placeholder="أدخل رقم المعاملة من إنستاباي" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <button type="submit" name="submit_payment" class="w-full bg-emerald-500 text-white py-3 rounded-xl font-bold hover:bg-emerald-600 transition">
                                إرسال إشعار الدفع
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="rounded-[2rem] bg-slate-50 p-6 shadow-xl border border-slate-200 text-center">
                        <div class="text-4xl mb-4">💰</div>
                        <h3 class="text-lg font-black text-slate-900 mb-2">لا توجد عمولة مستحقة</h3>
                        <p class="text-slate-500">لم تقم بإنجاز أي خدمات اليوم بعد.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment History -->
            <div class="space-y-6">
                <!-- Pending Payments -->
                <?php if (!empty($pendingPayments)): ?>
                    <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                        <h3 class="text-lg font-black text-slate-900 mb-4">المدفوعات المعلقة</h3>
                        <div class="space-y-3">
                            <?php foreach ($pendingPayments as $payment): ?>
                                <div class="rounded-3xl bg-yellow-50 p-4 border border-yellow-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-sm font-semibold text-slate-900">رقم المعاملة: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-600">قيد المراجعة</span>
                                    </div>
                                    <div class="text-sm text-slate-600">
                                        <p>المبلغ: <?= number_format($payment['amount_paid'], 2) ?> EGP</p>
                                        <p>التاريخ: <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Payment History -->
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">سجل المدفوعات</h3>
                    <?php if (!empty($paymentHistory)): ?>
                        <div class="space-y-3">
                            <?php foreach ($paymentHistory as $payment): ?>
                                <div class="rounded-3xl bg-green-50 p-4 border border-green-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-sm font-semibold text-slate-900">رقم المعاملة: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-600">مؤكد</span>
                                    </div>
                                    <div class="text-sm text-slate-600">
                                        <p>المبلغ: <?= number_format($payment['amount_paid'], 2) ?> EGP</p>
                                        <p>تاريخ التأكيد: <?= date('d/m/Y H:i', strtotime($payment['confirmed_at'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-4xl mb-4">📋</div>
                            <p class="text-slate-500">لا توجد مدفوعات سابقة</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>