<?php
session_start();
require_once __DIR__ . '/../../core/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit();
}

$workerId = (string) $_SESSION['user_id'];

// Get worker data
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = :id");
$stmt->bindParam(':id', $workerId, PDO::PARAM_INT);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    session_unset();
    session_destroy();
    header('Location: ../user/login.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['location'] ?? '';
    $city = $_POST['city'] ?? ''; // ستستقبل القيمة العربية المحددة من القائمة

    $updateStmt = $conn->prepare("
        UPDATE workers
        SET email = :email, phone = :phone, location = :location, city = :city, updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->bindParam(':email', $email);
    $updateStmt->bindParam(':phone', $phone);
    $updateStmt->bindParam(':location', $location);
    $updateStmt->bindParam(':city', $city);
    $updateStmt->bindParam(':id', $workerId);
    $updateStmt->execute();

    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=profile_updated');
    exit();
}

// Get worker statistics
$statsStmt = $conn->prepare("
    SELECT
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_services,
        COUNT(CASE WHEN status = 'accepted' THEN 1 END) as active_services,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN checking_fee END), 0) as total_earnings,
        AVG(CASE WHEN status = 'completed' THEN rw.rating END) as avg_rating
    FROM service_requests sr
    LEFT JOIN reviews_worker rw ON rw.request_id = sr.id
    WHERE sr.worker_id = :worker_id
");
$statsStmt->bindParam(':worker_id', $workerId, PDO::PARAM_INT);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get recent reviews (Fixed sr.user_id matching your current schema)
$reviewsStmt = $conn->prepare("
    SELECT rw.rating, rw.comment, rw.created_at, u.name as user_name
    FROM reviews_worker rw
    LEFT JOIN service_requests sr ON sr.id = rw.request_id
    LEFT JOIN users u ON u.id = sr.user_id
    WHERE rw.worker_id = :worker_id
    ORDER BY rw.created_at DESC
    LIMIT 5
");
$reviewsStmt->bindParam(':worker_id', $workerId, PDO::PARAM_INT);
$reviewsStmt->execute();
$recentReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'ملفي المهني - بيانات التخصص' : 'Professional Profile - Skills & Details';
        include('../../core/seo.php');
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; color: #1e293b; }</style>
</head>
<body class="min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">
        <header class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-500">إدارة الملف الشخصي</p>
                <h1 class="text-3xl font-black text-slate-900">الملف الشخصي</h1>
            </div>
            <div class="flex gap-3">
                <a href="./worker_available_requests.php" class="inline-flex items-center justify-center rounded-3xl bg-blue-500 px-5 py-3 text-white font-semibold hover:bg-blue-600 transition">الطلبات المتاحة</a>
                <a href="./worker_dashboard.php" class="inline-flex items-center justify-center rounded-3xl bg-emerald-500 px-5 py-3 text-white font-semibold hover:bg-emerald-600 transition">لوحة التحكم</a>
            </div>
        </header>

        <div class="mb-6">
            <nav class="flex space-x-1 bg-slate-100 p-1 rounded-2xl rtl:space-x-reverse">
                <a href="./worker_available_requests.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الطلبات المتاحة</a>
                <a href="./worker_dashboard.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">لوحة التحكم</a>
                <a href="./worker_profile.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl bg-white text-slate-900 shadow-sm">الملف الشخصي</a>
            </nav>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
            <div class="rounded-3xl border border-green-300 bg-green-50 p-6 text-right text-green-800 shadow-sm mb-6">
                تم تحديث الملف الشخصي بنجاح!
            </div>
        <?php endif; ?>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">الإحصائيات</h3>
                    <div class="space-y-4">
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">الخدمات المكتملة:</span>
                                <span class="font-bold text-lg text-slate-900"><?= number_format($stats['completed_services'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">الخدمات النشطة:</span>
                                <span class="font-bold text-lg text-blue-600"><?= number_format($stats['active_services'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">إجمالي الأرباح:</span>
                                <span class="font-bold text-lg text-green-600"><?= number_format($stats['total_earnings'] ?? 0, 2) ?> EGP</span>
                            </div>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">متوسط التقييم:</span>
                                <span class="font-bold text-lg text-yellow-600">
                                    <?php
                                    $rating = $stats['avg_rating'] ?? 0;
                                    echo $rating > 0 ? number_format($rating, 1) . ' ⭐' : 'غير مقيم';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">حالة الحساب</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">حالة الموافقة:</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                <?php
                                $approved = $worker['approved'] ?? 'pending';
                                if ($approved === 'yes') {
                                    echo 'bg-green-100 text-green-600';
                                } elseif ($approved === 'no') {
                                    echo 'bg-red-100 text-red-600';
                                } else {
                                    echo 'bg-yellow-100 text-yellow-600';
                                }
                                ?>">
                                <?php
                                if ($approved === 'yes') {
                                    echo 'مُعتمد';
                                } elseif ($approved === 'no') {
                                    echo 'مرفوض';
                                } else {
                                    echo 'قيد المراجعة';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">الحالة:</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                <?php
                                $status = $worker['status'] ?? 'no';
                                echo $status === 'active' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
                                ?>">
                                <?= $status === 'active' ? 'متاح' : 'غير متاح' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">التخصص:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($worker['specialization'] ?? 'غير محدد') ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">المدينة الحالية:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($worker['city'] ?? 'غير محدد') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-6">تحديث البيانات الشخصية</h3>
                    <form method="POST" class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">الاسم</label>
                                <input type="text" value="<?= htmlspecialchars($worker['name'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-slate-50" readonly>
                                <p class="text-xs text-slate-500 mt-1">لا يمكن تعديل الاسم</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">البريد الإلكتروني</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($worker['email'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">رقم الهاتف</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($worker['phone'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">المدينة</label>
                                <select name="city" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="">اختر المدينة</option>
                                    <option value="اكتوبر السادس" <?= ($worker['city'] ?? '') === 'اكتوبر السادس' ? 'selected' : '' ?>>اكتوبر السادس</option>
                                    <option value="الشيخ زايد" <?= ($worker['city'] ?? '') === 'الشيخ زايد' ? 'selected' : '' ?>>الشيخ زايد</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">عند حفظ الاختيار، ستظهر لك الطلبات المخصصة لهذه المدينة فقط.</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">العنوان التفصيلي</label>
                            <textarea name="location" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="أدخل عنوانك التفصيلي"><?= htmlspecialchars($worker['location'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="w-full bg-indigo-500 text-white py-3 rounded-xl font-bold hover:bg-indigo-600 transition">
                            حفظ التغييرات
                        </button>
                    </form>
                </div>

                <?php if (!empty($recentReviews)): ?>
                    <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                        <h3 class="text-lg font-black text-slate-900 mb-4">آخر التقييمات</h3>
                        <div class="space-y-4">
                            <?php foreach ($recentReviews as $review): ?>
                                <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-semibold text-slate-900"><?= htmlspecialchars($review['user_name'] ?? 'عميل') ?></span>
                                        <div class="flex items-center gap-1">
                                            <span class="text-yellow-500">⭐</span>
                                            <span class="text-sm font-bold text-slate-900"><?= htmlspecialchars($review['rating']) ?>/5</span>
                                        </div>
                                    </div>
                                    <?php if (!empty($review['comment'])): ?>
                                        <p class="text-slate-600 text-sm mb-2"><?= htmlspecialchars($review['comment']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-slate-400"><?= date('d/m/Y', strtotime($review['created_at'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">الوثائق والملفات</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-slate-700">البطاقة الشخصية (الوجه الأمامي)</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                <?= !empty($worker['id_front_path']) ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                <?= !empty($worker['id_front_path']) ? 'مرفوع' : 'غير مرفوع' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-slate-700">البطاقة الشخصية (الوجه الخلفي)</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                <?= !empty($worker['id_back_path']) ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                <?= !empty($worker['id_back_path']) ? 'مرفوع' : 'غير مرفوع' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-slate-700">الشهادة المهنية</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                <?= !empty($worker['certificate_path']) ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                <?= !empty($worker['certificate_path']) ? 'مرفوع' : 'غير مرفوع' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-slate-700">السيرة الذاتية</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                <?= !empty($worker['cv_path']) ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                <?= !empty($worker['cv_path']) ? 'مرفوع' : 'غير مرفوع' ?>
                            </span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-4">لتحديث الوثائق، يرجى التواصل مع الإدارة</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
