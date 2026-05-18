<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (string) $_SESSION['user_id'];
$receiptStmt = $conn->prepare("SELECT sr.*, w.name AS worker_name, w.phone AS worker_phone FROM service_requests sr LEFT JOIN workers w ON w.id::text = sr.worker_id::text WHERE sr.us_id::text = :id AND sr.status = 'completed' ORDER BY sr.updated_at DESC LIMIT 1");
$receiptStmt->bindParam(':id', $userId, PDO::PARAM_STR);
$receiptStmt->execute();
$receipt = $receiptStmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt) {
    header('Location: order.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | إيصال الخدمة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; color: #1e293b; }</style>
</head>
<body class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="rounded-[2rem] bg-white p-8 shadow-2xl border border-slate-200">
            <header class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm text-slate-500">فليكس</p>
                    <h1 class="text-3xl font-black text-slate-900">إيصال الخدمة المكتملة</h1>
                </div>
                <div class="text-right">
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
                        <p class="text-sm text-slate-500">الوصف</p>
                        <p class="mt-3 text-lg font-semibold text-slate-700"><?= htmlspecialchars($receipt['description']) ?></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">العنوان</p>
                        <p class="mt-3 text-lg font-semibold text-slate-700"><?= htmlspecialchars($receipt['address']) ?> - <?= htmlspecialchars($receipt['city']) ?></p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-3xl bg-emerald-50 p-5 border border-emerald-200 text-emerald-800">
                        <p class="text-sm font-semibold">الحالة النهائية</p>
                        <p class="mt-3 text-2xl font-black"><?= htmlspecialchars($receipt['status']) ?></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">الفني</p>
                        <p class="mt-3 text-lg font-semibold text-slate-700"><?= htmlspecialchars($receipt['worker_name'] ?? 'غير محدد') ?></p>
                        <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($receipt['worker_phone'] ?? 'لا يوجد رقم') ?></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-sm text-slate-500">المبلغ المدفوع</p>
                        <p class="mt-3 text-3xl font-black text-rose-600"><?= htmlspecialchars($receipt['worker_price'] ?: $receipt['budget']) ?> EGP</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-[2rem] bg-slate-100 p-6 border border-slate-200">
                <h2 class="text-lg font-black text-slate-900 mb-4">ملخص الخدمة</h2>
                <ul class="space-y-3 text-slate-700 text-sm">
                    <li><strong>اسم العميل:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'عميل فليكس') ?></li>
                    <li><strong>سعر الطلب الأصلي:</strong> <?= htmlspecialchars($receipt['budget']) ?> EGP</li>
                    <li><strong>العمولة المستحقة:</strong> <?= htmlspecialchars($receipt['commission_amount'] ?? '0') ?> EGP</li>
                    <li><strong>حالة الدفع:</strong> <?= htmlspecialchars($receipt['payment_status'] ?? 'غير معروف') ?></li>
                </ul>
            </div>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="order.php" class="inline-flex justify-center rounded-3xl bg-emerald-500 px-6 py-3 text-white font-semibold shadow-lg shadow-emerald-200/50 hover:bg-emerald-600 transition">طلب خدمة جديدة</a>
                <a href="logout.php" class="inline-flex justify-center rounded-3xl border border-slate-300 px-6 py-3 text-slate-900 font-semibold hover:bg-slate-50 transition">تسجيل الخروج</a>
            </div>
        </div>
    </div>
</body>
</html>
