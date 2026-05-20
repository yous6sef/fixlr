<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/google_ai.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = (string) $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT * FROM users WHERE id::text = :id');
$stmt->bindParam(':id', $id, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';
$aiSuggestion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialization = trim($_POST['special'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $budget = trim($_POST['budget'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (isset($_POST['generate_description'])) {
        if ($specialization === '' || $address === '' || $city === '') {
            $error = 'يرجى اختيار نوع الخدمة والمدينة والعنوان قبل طلب الوصف الذكي.';
        } else {
            $aiSuggestion = ai_generate_service_description($specialization, $address, $city);
            $description = $aiSuggestion;
        }
    } elseif (isset($_POST['submit_order'])) {
        if ($specialization === '' || $address === '' || $city === '' || $budget === '' || $description === '') {
            $error = 'يرجى ملء جميع حقول الطلب.';
        } elseif (!is_numeric($budget) || $budget <= 0) {
            $error = 'يرجى إدخال سعر صحيح للميزانية.';
        } else {
            $checkDuplicate = $conn->prepare('SELECT COUNT(*) FROM service_requests WHERE us_id::text = :us_id AND LOWER(specialization) = LOWER(:specialization) AND LOWER(city) = LOWER(:city) AND LOWER(address) = LOWER(:address) AND LOWER(description) = LOWER(:description) AND budget = :budget AND status = :status');
            $pendingStatus = 'pending';
            $checkDuplicate->bindParam(':us_id', $id, PDO::PARAM_STR);
            $checkDuplicate->bindParam(':specialization', $specialization, PDO::PARAM_STR);
            $checkDuplicate->bindParam(':city', $city, PDO::PARAM_STR);
            $checkDuplicate->bindParam(':address', $address, PDO::PARAM_STR);
            $checkDuplicate->bindParam(':description', $description, PDO::PARAM_STR);
            $checkDuplicate->bindParam(':budget', $budget, PDO::PARAM_STR);
            $checkDuplicate->bindParam(':status', $pendingStatus, PDO::PARAM_STR);
            $checkDuplicate->execute();

            if ($checkDuplicate->fetchColumn() > 0) {
                $error = 'يوجد طلب مماثل قيد التنفيذ بالفعل. الرجاء تعديل الطلب أو الانتظار حتى يتم معالجته.';
            } else {
                $commission = round($budget * 0.2, 2);
                $insert = $conn->prepare('INSERT INTO service_requests (us_id, specialization, description, budget, city, address, username, status, commission_amount, commission_percentage, platform_fee, payment_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
                $insert->execute([$id, $specialization, $description, $budget, $city, $address, $user['name'] ?? '', 'pending', $commission, 0.2, $commission, 'unpaid']);
                $success = 'تم إنشاء الطلب بنجاح. سنبدأ البحث عن فني مناسب.';
            }
        }
    }
}

$ordersStmt = $conn->prepare('SELECT * FROM service_requests WHERE us_id::text = :id ORDER BY created_at DESC');
$ordersStmt->bindParam(':id', $id, PDO::PARAM_STR);
$ordersStmt->execute();
$userOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$activeOrderStmt = $conn->prepare("SELECT status FROM service_requests WHERE us_id::text = :id AND status IN ('pending','accepted') ORDER BY created_at DESC LIMIT 1");
$activeOrderStmt->bindParam(':id', $id, PDO::PARAM_STR);
$activeOrderStmt->execute();
$activeOrder = $activeOrderStmt->fetch(PDO::FETCH_ASSOC);

function orderStatusLabel($status) {
    if ($status === 'completed') return 'مكتمل';
    if ($status === 'accepted') return 'تم قبول الطلب';
    if ($status === 'pending') return 'قيد البحث';
    return 'حالة غير معروفة';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | تتبع الطلبات</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .status-dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; margin-left: 6px; }
    </style>
</head>
<body class="min-h-screen pb-24">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            <div class="lg:w-1/2 bg-white rounded-3xl shadow-xl p-6">
                <h1 class="text-2xl font-black mb-4">أضف طلب خدمة جديد</h1>
                <?php if ($error): ?>
                    <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 p-4"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 p-4"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($activeOrder)): ?>
                    <div class="mb-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-700 p-4">
                        <p class="font-semibold">لديك طلب قيد التنفيذ بالفعل.</p>
                        <p class="mt-2">الحالة الحالية: <strong><?= htmlspecialchars(orderStatusLabel($activeOrder['status'])) ?></strong></p>
                        <a href="track.php" class="mt-4 inline-flex items-center rounded-2xl bg-indigo-600 px-4 py-3 text-white font-semibold hover:bg-indigo-700 transition">انتقل إلى تتبع الطلب</a>
                    </div>
                <?php endif; ?>
                <form method="post" class="space-y-5">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">نوع الخدمة</label>
                        <select name="special" class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">اختر نوع الخدمة</option>
                            <option value="سباك" <?= (isset($_POST['special']) && $_POST['special'] === 'سباك') ? 'selected' : '' ?>>سباك</option>
                            <option value="كهربائي" <?= (isset($_POST['special']) && $_POST['special'] === 'كهربائي') ? 'selected' : '' ?>>كهربائي</option>
                            <option value="نجار" <?= (isset($_POST['special']) && $_POST['special'] === 'نجار') ? 'selected' : '' ?>>نجار</option>
                            <option value="دهان" <?= (isset($_POST['special']) && $_POST['special'] === 'دهان') ? 'selected' : '' ?>>دهان</option>
                            <option value="تنظيف" <?= (isset($_POST['special']) && $_POST['special'] === 'تنظيف') ? 'selected' : '' ?>>تنظيف</option>
                            <option value="صيانة عامة" <?= (isset($_POST['special']) && $_POST['special'] === 'صيانة عامة') ? 'selected' : '' ?>>صيانة عامة</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">العنوان</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">المدينة</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">وصف الخدمة</label>
                        <textarea name="description" rows="4" required class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <?php if ($aiSuggestion): ?>
                        <div class="rounded-2xl bg-blue-50 border border-blue-200 text-blue-700 p-4">
                            <p class="font-semibold mb-2">اقتراح وصفي من AI</p>
                            <p><?= htmlspecialchars($aiSuggestion) ?></p>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">ميزانية تقريبية (EGP)</label>
                        <input type="number" min="1" name="budget" value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>" required class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div class="flex gap-3 flex-col sm:flex-row">
                        <button type="submit" name="submit_order" class="flex-1 bg-indigo-600 text-white rounded-2xl py-3 font-bold hover:bg-indigo-700 transition">إرسال الطلب</button>
                        <button type="submit" name="generate_description" class="flex-1 bg-slate-600 text-white rounded-2xl py-3 font-bold hover:bg-slate-700 transition">اقتراح وصف AI</button>
                    </div>
                </form>
            </div>
            <div class="lg:w-1/2 space-y-6">
                <div class="bg-white rounded-3xl shadow-xl p-6">
                    <h2 class="text-xl font-black mb-4">طلباتي الأخيرة</h2>
                    <?php if (empty($userOrders)): ?>
                        <p class="text-gray-500">لم يتم تقديم أي طلب بعد.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($userOrders as $order): ?>
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                    <?php
                                        $statusClass = 'bg-amber-500';
                                        $statusText = 'قيد البحث';
                                        if ($order['status'] === 'accepted') {
                                            $statusClass = 'bg-indigo-600';
                                            $statusText = 'مقبول';
                                        } elseif ($order['status'] === 'completed') {
                                            $statusClass = 'bg-emerald-500';
                                            $statusText = 'مكتمل';
                                        }
                                    ?>
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-slate-500 text-sm"><?= orderStatusLabel($order['status']) ?></span>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700"><span class="status-dot <?= $statusClass ?>"></span><?= $statusText ?></span>
                                    </div>
                                    <h3 class="font-bold text-slate-800 mb-2"><?= htmlspecialchars($order['specialization']) ?></h3>
                                    <p class="text-slate-600 mb-3"><?= htmlspecialchars($order['description']) ?></p>
                                    <div class="grid grid-cols-2 gap-3 text-sm text-slate-500">
                                        <div>المدينة: <?= htmlspecialchars($order['city']) ?></div>
                                        <div>الميزانية: <?= htmlspecialchars($order['budget']) ?> EGP</div>
                                        <div>الحالة: <?= htmlspecialchars($order['payment_status'] ?? 'غير معروف') ?></div>
                                        <div class="col-span-2">العنوان: <?= htmlspecialchars($order['address']) ?></div>
                                        <?php if (!empty($order['worker_price'])): ?>
                                            <div>سعر الفني: <?= htmlspecialchars($order['worker_price']) ?> EGP</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
