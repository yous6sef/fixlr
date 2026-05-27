<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$workerId = (string) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete'])) {
    $orderId = $_POST['order_id'] ?? null;
    if ($orderId) {
        $update = $conn->prepare("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE id = :id AND worker_id::text = :worker_id AND status = 'accepted'");
        $update->bindParam(':id', $orderId, PDO::PARAM_INT);
        $update->bindParam(':worker_id', $workerId, PDO::PARAM_STR);
        $update->execute();
    }
}

$receiptStmt = $conn->prepare("SELECT sr.*, u.name AS user_name, u.phone AS user_phone FROM service_requests sr LEFT JOIN users u ON u.id::text = sr.us_id::text WHERE sr.worker_id::text = :id AND sr.status = 'completed' ORDER BY sr.updated_at DESC LIMIT 1");
$receiptStmt->bindParam(':id', $workerId, PDO::PARAM_STR);
$receiptStmt->execute();
$receipt = $receiptStmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt) {
    header('Location: workermain.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | إيصال الخدمة - الفني</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; color: #1e293b; }</style>
</head>
<body class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <header class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm text-slate-500">فليكس - سجل الخدمات المكتملة</p>
                    <h1 class="text-3xl font-black text-slate-900">إيصال الخدمة المكتملة</h1>
                </div>
                <div class="flex gap-3">
                    <a href="worker_profile.php" class="inline-flex items-center justify-center rounded-3xl bg-slate-600 px-5 py-3 text-white font-semibold hover:bg-slate-700 transition">الملف الشخصي</a>
                    <a href="worker_payments.php" class="inline-flex items-center justify-center rounded-3xl bg-emerald-500 px-5 py-3 text-white font-semibold hover:bg-emerald-600 transition">المدفوعات</a>
                </div>
            </header>

            <!-- Navigation Tabs -->
            <div class="mb-6">
                <nav class="flex space-x-1 bg-slate-100 p-1 rounded-2xl rtl:space-x-reverse">
                    <a href="worker_orders.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الطلبات</a>
                    <a href="worker_track.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">تتبع الخدمة</a>
                    <a href="worker_receipt.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl bg-white text-slate-900 shadow-sm">الإيصالات</a>
                    <a href="worker_payments.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">المدفوعات</a>
                    <a href="worker_profile.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الملف الشخصي</a>
                </nav>
            </div>

            <div class="rounded-[2rem] bg-white p-8 shadow-2xl border border-slate-200">
                    <p class="text-sm text-slate-500">تاريخ الإتمام</p>
                    <p class="text-xl font-bold text-slate-900"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($receipt['updated_at']))) ?></p>
                </div>
            </header>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-4">
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">نوع الخدمة</p>
                        <p class="mt-3 text-xl font-bold text-slate-900"><?= htmlspecialchars($receipt['specialization']) ?></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">وصف الخدمة</p>
                        <p class="mt-3 text-lg font-semibold text-slate-700"><?= htmlspecialchars($receipt['description']) ?></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">موقع الخدمة</p>
                        <p class="mt-3 text-lg font-semibold text-slate-700"><?= htmlspecialchars($receipt['address']) ?> - <?= htmlspecialchars($receipt['city']) ?></p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-3xl bg-emerald-50 p-5 border border-emerald-200 text-emerald-800">
                        <p class="text-sm font-semibold">الحالة النهائية</p>
                        <p class="mt-3 text-2xl font-black">✓ مكتملة</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">اسم العميل</p>
                        <p class="mt-3 text-lg font-semibold text-slate-700"><?= htmlspecialchars($receipt['user_name'] ?? 'عميل') ?></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">السعر النهائي</p>
                        <p class="mt-3 text-3xl font-black text-rose-600"><?= htmlspecialchars($receipt['worker_price'] ?: $receipt['budget']) ?> EGP</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-[2rem] bg-slate-100 p-6 border border-slate-200">
                <h2 class="text-lg font-black text-slate-900 mb-4">ملخص العمولة</h2>
                <ul class="space-y-3 text-slate-700 text-sm">
                    <li><strong>السعر المتفق عليه:</strong> <?= htmlspecialchars($receipt['worker_price'] ?: $receipt['budget']) ?> EGP</li>
                    <li><strong>عمولة المنصة (20%):</strong> <?= htmlspecialchars(round(($receipt['worker_price'] ?: $receipt['budget']) * 0.2, 2)) ?> EGP</li>
                    <li><strong>المبلغ المستحق لك:</strong> <span class="font-bold text-emerald-600"><?= htmlspecialchars(round(($receipt['worker_price'] ?: $receipt['budget']) * 0.8, 2)) ?> EGP</span></li>
                    <li><strong>حالة الدفع:</strong> <?= htmlspecialchars($receipt['payment_status'] ?? 'انتظار التحويل') ?></li>
                </ul>
            </div>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-3">
                    <a href="worker_orders.php" class="inline-flex justify-center rounded-3xl bg-emerald-500 px-6 py-3 text-white font-semibold shadow-lg shadow-emerald-200/50 hover:bg-emerald-600 transition">الطلبات</a>
                    <a href="worker_profile.php" class="inline-flex justify-center rounded-3xl bg-blue-500 px-6 py-3 text-white font-semibold shadow-lg shadow-blue-200/50 hover:bg-blue-600 transition">الملف الشخصي</a>
                    <a href="worker_payments.php" class="inline-flex justify-center rounded-3xl bg-green-500 px-6 py-3 text-white font-semibold shadow-lg shadow-green-200/50 hover:bg-green-600 transition">المدفوعات</a>
                </div>
                <a href="logout.php" class="inline-flex justify-center rounded-3xl border border-slate-300 px-6 py-3 text-slate-900 font-semibold hover:bg-slate-50 transition">تسجيل الخروج</a>
            </div>
        </div>
    </div>
</body>
</html>
