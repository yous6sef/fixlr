<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (string) $_SESSION['user_id'];
$orderStmt = $conn->prepare("SELECT * FROM service_requests WHERE us_id::text = :id AND status IN ('pending', 'accepted', 'completed') ORDER BY created_at DESC LIMIT 1");
$orderStmt->bindParam(':id', $userId, PDO::PARAM_STR);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: order.php');
    exit();
}

if ($order['status'] === 'completed') {
    header('Location: receipt.php');
    exit();
}

$worker = null;
if (!empty($order['worker_id'])) {
    $workerStmt = $conn->prepare('SELECT * FROM workers WHERE id::text = :worker_id LIMIT 1');
    $workerStmt->bindParam(':worker_id', $order['worker_id'], PDO::PARAM_STR);
    $workerStmt->execute();
    $worker = $workerStmt->fetch(PDO::FETCH_ASSOC);
}

$statusLabel = $order['status'] === 'accepted' ? 'تم قبول الطلب' : 'قيد البحث عن فني';
$statusClass = $order['status'] === 'accepted' ? 'bg-indigo-600' : 'bg-amber-500';
$progressValue = $order['status'] === 'accepted' ? 75 : 40;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | تتبع الطلب</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://js.api.here.com/v3/3.1/mapsjs-core.js"></script>
    <script src="https://js.api.here.com/v3/3.1/mapsjs-service.js"></script>
    <script src="https://js.api.here.com/v3/3.1/mapsjs-ui.js"></script>
    <script src="https://js.api.here.com/v3/3.1/mapsjs-mapevents.js"></script>
    <link rel="stylesheet" href="https://js.api.here.com/v3/3.1/mapsjs-ui.css" />
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; color: #1e293b; }
    #map-container { width: 100%; height: 320px; border-radius: 1.75rem; overflow: hidden; }</style>
</head>
<body class="min-h-screen py-6">
    <div class="max-w-5xl mx-auto px-4">
        <header class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-500">مرحلة طلب الخدمة</p>
                <h1 class="text-3xl font-black text-slate-900">تتبع تقدم الطلب</h1>
            </div>
            <a href="order.php" class="inline-flex items-center justify-center rounded-3xl bg-emerald-500 px-5 py-3 text-white font-semibold shadow-lg shadow-emerald-200/50 hover:bg-emerald-600 transition">طلب جديد</a>
        </header>

        <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <div>
                            <p class="text-sm text-slate-500">الحالة الحالية</p>
                            <h2 class="text-xl font-black text-slate-900"><?= htmlspecialchars($statusLabel) ?></h2>
                        </div>
                        <span class="rounded-full px-4 py-2 text-white text-sm font-bold <?= $statusClass ?>"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div>
                                <p class="text-slate-500 text-sm">خدمة</p>
                                <p class="text-lg font-bold text-slate-900"><?= htmlspecialchars($order['specialization']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-slate-500 text-sm">الميزانية</p>
                                <p class="text-xl font-black text-rose-600"><?= htmlspecialchars($order['budget']) ?> EGP</p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 text-slate-600 text-sm">
                            <div><span class="font-semibold">المدينة:</span> <?= htmlspecialchars($order['city']) ?></div>
                            <div><span class="font-semibold">العنوان:</span> <?= htmlspecialchars($order['address']) ?></div>
                            <div class="sm:col-span-2"><span class="font-semibold">التفاصيل:</span> <?= htmlspecialchars($order['description']) ?></div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-700">خط سير الطلب</span>
                            <span class="text-sm text-slate-500"><?= $order['status'] === 'accepted' ? 'تم القبول' : 'قيد البحث' ?></span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-200 overflow-hidden">
                            <div id="order-progress" class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-indigo-600" style="width: <?= $progressValue ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-xl font-black text-slate-900">مواقع الطلب</h3>
                            <p class="text-sm text-slate-500">خريطة تفاعلية للعنوان</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">Live</span>
                    </div>
                    <div id="map-container" class="mb-4 rounded-[1.75rem] border border-slate-200"></div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">تفاصيل الفني</h3>
                    <?php if ($worker): ?>
                        <div class="space-y-4">
                            <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                                <p class="text-sm text-slate-500">اسم الفني</p>
                                <p class="mt-2 text-lg font-bold text-slate-900"><?= htmlspecialchars($worker['name']) ?></p>
                            </div>
                            <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                                <p class="text-sm text-slate-500">الهاتف</p>
                                <p class="mt-2 text-lg font-bold text-slate-900"><?= htmlspecialchars($worker['phone'] ?? 'غير متوفر') ?></p>
                            </div>
                            <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                                <p class="text-sm text-slate-500">التخصص</p>
                                <p class="mt-2 text-lg font-bold text-slate-900"><?= htmlspecialchars($worker['specialization'] ?? 'غير محدد') ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="rounded-3xl bg-emerald-50 p-4 border border-emerald-200 text-emerald-700">
                            جاري البحث عن فني مناسب لموقعك وطلبك.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl border border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 mb-4">إجراءات سريعة</h3>
                    <div class="space-y-3">
                        <a href="order.php" class="block rounded-3xl bg-slate-900 text-white text-center py-3 font-semibold hover:bg-slate-800 transition">طلب خدمة جديدة</a>
                        <a href="receipt.php" class="block rounded-3xl border border-slate-300 text-slate-900 text-center py-3 font-semibold hover:bg-slate-100 transition">عرض الإيصال الأخير</a>
                        <a href="logout.php" class="block rounded-3xl bg-red-500 text-white text-center py-3 font-semibold hover:bg-red-600 transition">تسجيل الخروج</a>
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

        // Add UI controls
        H.ui.UI.createDefault(map, defaultLayers);
        const mapEvents = new H.mapevents.MapEvents(map);
        new H.mapevents.Behavior(mapEvents);

        // Geocode the service address
        const geocoder = platform.getSearchService();
        const address = '<?= addslashes($order['address']) ?>, <?= addslashes($order['city']) ?>';
        
        geocoder.geocode({ query: address }, (result) => {
            if (result.items.length > 0) {
                const location = result.items[0].position;
                map.setCenter(location);
                map.setZoom(15);
                
                // Add marker for service location
                const marker = new H.map.Marker(location, {
                    volatility: true
                });
                map.addObject(marker);
                
                // Create info bubble
                const bubble = new H.ui.InfoBubble(location, {
                    content: `<div style="padding: 10px; text-align: right; font-family: 'IBM Plex Sans Arabic'; font-size: 12px;"><strong>📍 موقع الخدمة</strong><br>${address}</div>`
                });
                map.ui.addBubble(bubble);
            }
        });

        // Auto-refresh status every 6 seconds
        const statusEl = document.querySelector('h2');
        const progressEl = document.getElementById('order-progress');
        const fetchLive = async () => {
            try {
                const response = await fetch('order_updates.php?view=user&json=1');
                if (!response.ok) return;
                const data = await response.json();
                const latest = data.userOrders?.[0];
                if (!latest) return;
                if (latest.status === 'completed') {
                    window.location.href = 'receipt.php';
                    return;
                }
                const label = latest.status === 'accepted' ? 'تم قبول الطلب' : 'قيد البحث عن فني';
                statusEl.textContent = label;
                progressEl.style.width = latest.status === 'accepted' ? '75%' : '40%';
            } catch (error) {
                console.warn('Live update failed', error);
            }
        };
        setInterval(fetchLive, 6000);
    </script>
</body>
</html>
