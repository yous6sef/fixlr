<?php
session_start();
require_once __DIR__ . '/../../core/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit();
}

$workerId = (string) $_SESSION['user_id'];
$orderStmt = $conn->prepare("SELECT sr.*, u.name AS user_name, u.phone AS user_phone, u.location AS user_location FROM service_requests sr LEFT JOIN users u ON u.id::text = sr.us_id::text WHERE sr.worker_id::text = :id AND sr.status = 'accepted' ORDER BY sr.created_at DESC LIMIT 1");
$orderStmt->bindParam(':id', $workerId, PDO::PARAM_STR);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ./worker_dashboard.php');
    exit();
}

$progressValue = 60;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | تتبع الخدمة - الفني</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://js.api.here.com/v3/3.1/mapsjs-core.js"></script>
    <script src="https://js.api.here.com/v3/3.1/mapsjs-service.js"></script>
    <script src="https://js.api.here.com/v3/3.1/mapsjs-ui.js"></script>
    <script src="https://js.api.here.com/v3/3.1/mapsjs-mapevents.js"></script>
    <link rel="stylesheet" href="https://js.api.here.com/v3/3.1/mapsjs-ui.css" />
    <style>
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; color: #1e293b; }
        #map-container { width: 100%; height: 320px; border-radius: 1.75rem; overflow: hidden; }
        .status-dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
    </style>
</head>
<body class="min-h-screen py-6">
    <div class="max-w-5xl mx-auto px-4">
        <header class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-500">مرحلة تنفيذ الخدمة</p>
                <h1 class="text-3xl font-black text-slate-900">متابعة الخدمة الجارية</h1>
            </div>
            <div class="flex gap-3">
                <a href="./worker_profile.php" class="inline-flex items-center justify-center rounded-3xl bg-slate-600 px-5 py-3 text-white font-semibold hover:bg-slate-700 transition">الملف الشخصي</a>
                <a href="./worker_payments.php" class="inline-flex items-center justify-center rounded-3xl bg-emerald-500 px-5 py-3 text-white font-semibold hover:bg-emerald-600 transition">المدفوعات</a>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-1 bg-slate-100 p-1 rounded-2xl rtl:space-x-reverse">
                <a href="./worker_orders.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الطلبات</a>
                <a href="./worker_track.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl bg-white text-slate-900 shadow-sm">تتبع الخدمة</a>
                <a href="./worker_receipt.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الإيصالات</a>
                <a href="./worker_payments.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">المدفوعات</a>
                <a href="./worker_profile.php" class="flex-1 text-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-600 hover:bg-white hover:text-slate-900 transition">الملف الشخصي</a>
            </nav>
        </div>

        <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
                <!-- Map Section -->
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-black text-slate-900">موقع الخدمة</h3>
                            <p class="text-sm text-slate-500">خريطة تفاعلية للعنوان</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">Live</span>
                    </div>
                    <div id="map-container" class="mb-4"></div>
                    <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200 text-sm text-slate-700">
                        <p><span class="font-semibold">العنوان:</span> <?= htmlspecialchars($order['address']) ?></p>
                        <p class="mt-2"><span class="font-semibold">المدينة:</span> <?= htmlspecialchars($order['city']) ?></p>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">تفاصيل الخدمة</h3>
                    <div class="space-y-3 text-slate-700">
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <p class="text-sm text-slate-500">نوع الخدمة</p>
                            <p class="mt-2 font-bold text-lg"><?= htmlspecialchars($order['specialization']) ?></p>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <p class="text-sm text-slate-500">الوصف</p>
                            <p class="mt-2 font-semibold"><?= htmlspecialchars($order['description']) ?></p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                                <p class="text-sm text-slate-500">السعر المتفق عليه</p>
                                <p class="mt-2 text-xl font-black text-rose-600"><?= htmlspecialchars($order['worker_price'] ?: $order['budget']) ?> EGP</p>
                            </div>
                            <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                                <p class="text-sm text-slate-500">الحالة</p>
                                <p class="mt-2 text-sm font-bold text-indigo-600 flex items-center gap-2">
                                    <span class="status-dot bg-indigo-600"></span>
                                    قيد التنفيذ
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-slate-700">خط سير الخدمة</span>
                            <span class="text-sm text-slate-500">60%</span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-600" style="width: 60%"></div>
                        </div>
                    </div>
                    <form method="post" action="worker_receipt.php" class="flex gap-3">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <button type="submit" name="complete" class="flex-1 bg-emerald-600 text-white p-3 rounded-xl font-bold hover:bg-emerald-700 transition">
                            إنهاء الخدمة
                        </button>
                    </form>
                </div>
            </div>

            <!-- Customer Info Sidebar -->
            <aside class="space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">بيانات العميل</h3>
                    <div class="space-y-4">
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <p class="text-sm text-slate-500">اسم العميل</p>
                            <p class="mt-2 text-lg font-bold text-slate-900"><?= htmlspecialchars($order['user_name'] ?? 'عميل') ?></p>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                            <p class="text-sm text-slate-500">رقم الهاتف</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">
                                <a href="tel:<?= htmlspecialchars($order['user_phone'] ?? '#') ?>" class="text-indigo-600 hover:underline">
                                    <?= htmlspecialchars($order['user_phone'] ?? 'غير متوفر') ?>
                                </a>
                            </p>
                        </div>
                        <a href="tel:<?= htmlspecialchars($order['user_phone'] ?? '#') ?>" class="block rounded-3xl bg-emerald-500 text-white text-center py-3 font-semibold hover:bg-emerald-600 transition">
                            اتصل بالعميل
                        </a>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">الإجراءات</h3>
                    <div class="space-y-3">
                        <a href="./worker_orders.php" class="block rounded-3xl bg-slate-900 text-white text-center py-3 font-semibold hover:bg-slate-800 transition">
                            العودة للطلبات
                        </a>
                        <a href="./worker_profile.php" class="block rounded-3xl bg-blue-500 text-white text-center py-3 font-semibold hover:bg-blue-600 transition">
                            الملف الشخصي
                        </a>
                        <a href="./worker_payments.php" class="block rounded-3xl bg-emerald-500 text-white text-center py-3 font-semibold hover:bg-emerald-600 transition">
                            المدفوعات
                        </a>
                        <a href="../user/logout.php" class="block rounded-3xl bg-red-500 text-white text-center py-3 font-semibold hover:bg-red-600 transition">
                            تسجيل الخروج
                        </a>
                    </div>
                </div>
            </aside>
        </section>
    </div>

    <script>
        const HERE_API_KEY = 'bTcdxdMTu88G-q5LfQMBbALRFN7M0BMd4sEWPOLgmU';
        const platform = new H.service.Platform({ apikey: HERE_API_KEY });
        const defaultLayers = platform.createDefaultLayers();
        
        const mapContainer = document.getElementById('map-container');
        const map = new H.Map(mapContainer, defaultLayers.vector.normal.map, {
            center: { lat: 30.0444, lng: 31.2357 },
            zoom: 13,
            pixelRatio: window.devicePixelRatio || 1
        });

        // Add UI
        H.ui.UI.createDefault(map, defaultLayers);
        const mapEvents = new H.mapevents.MapEvents(map);
        new H.mapevents.Behavior(mapEvents);

        // Geocode service address and add marker
        const geocoder = platform.getSearchService();
        const address = '<?= addslashes($order['address']) ?>, <?= addslashes($order['city']) ?>';
        
        geocoder.geocode({ query: address }, (result) => {
            if (result.items.length > 0) {
                const location = result.items[0].position;
                map.setCenter(location);
                
                // Add marker
                const marker = new H.map.Marker(location);
                map.addObject(marker);
                
                // Create popup
                const popup = new H.ui.InfoBubble(location, {
                    content: `<div style="padding: 8px; text-align: right; font-family: Arial;"><strong>موقع الخدمة</strong><br>${address}</div>`
                });
                map.ui.addBubble(popup);
            }
        });

        // Auto-refresh order status
        setInterval(async () => {
            try {
                const response = await fetch('order_updates.php?view=worker&json=1');
                if (!response.ok) return;
                const data = await response.json();
                if (data.activeOrder && data.activeOrder.status === 'completed') {
                    window.location.href = 'worker_receipt.php';
                }
            } catch (error) {
                console.warn('Status update failed', error);
            }
        }, 6000);
    </script>
</body>
</html>
