<?php
session_start();
require_once __DIR__ . '/core/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit();
}

$userId = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'user';

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($view === 'worker') {
    $userId = (string) $_SESSION['user_id'];
    
    // Get active accepted order for worker
    $activeStmt = $conn->prepare("SELECT sr.* FROM service_requests sr WHERE sr.worker_id::text = :worker_id AND sr.status = 'accepted' ORDER BY sr.created_at DESC LIMIT 1");
    $activeStmt->bindParam(':worker_id', $userId, PDO::PARAM_STR);
    $activeStmt->execute();
    $activeOrder = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($_GET['json']) && $_GET['json'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'activeOrder' => $activeOrder ?: [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    $workerStmt = $conn->prepare('SELECT * FROM workers WHERE id::text = :id LIMIT 1');
    $workerStmt->bindParam(':id', $userId, PDO::PARAM_STR);
    $workerStmt->execute();
    $worker = $workerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$worker) {
        echo '<div class="text-center text-red-600">لم يتم العثور على بيانات العامل.</div>';
        exit();
    }

    $pendingStmt = $conn->prepare('SELECT * FROM service_requests WHERE specialization = :specialization AND city = :city AND status = :status AND refusers < :limit ORDER BY created_at ASC LIMIT 1');
    $pendingStmt->bindParam(':specialization', $worker['specialization'], PDO::PARAM_STR);
    $pendingStmt->bindParam(':city', $worker['city'], PDO::PARAM_STR);
    $pendingStmt->bindValue(':status', 'pending', PDO::PARAM_STR);
    $pendingStmt->bindValue(':limit', 3, PDO::PARAM_INT);
    $pendingStmt->execute();
    $pendingOrders = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

    $acceptedStmt = $conn->prepare('SELECT sr.*, u.phone AS user_phone FROM service_requests sr LEFT JOIN users u ON u.id::text = sr.us_id::text WHERE sr.worker_id::text = :id AND sr.status IN (\'accepted\', \'completed\') ORDER BY sr.created_at DESC');
    $acceptedStmt->bindParam(':id', $userId, PDO::PARAM_STR);
    $acceptedStmt->execute();
    $orders = $acceptedStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($pendingOrders)) {
        foreach ($pendingOrders as $order) {
            echo '<div class="p-6 bg-white/70 rounded-2xl shadow-lg border border-indigo-200/50 mb-4">';
            echo '<div class="flex justify-between items-start border-b pb-3 mb-3">';
            echo '<div><h4 class="text-xl font-extrabold text-gray-800">طلب خدمة جديد</h4><span class="text-sm text-gray-600">العميل: ' . escape($order['username']) . '</span></div>';
            echo '<span class="text-xl font-bold text-indigo-600">' . escape($order['budget']) . ' جنيه</span>';
            echo '</div>';
            echo '<p class="mb-4 text-gray-700 font-semibold">العنوان: ' . escape($order['address']) . '</p>';
            echo '<form method="POST" class="grid grid-cols-3 gap-3">';
            echo '<input type="hidden" name="order_id" value="' . escape($order['id']) . '">';
            echo '<button type="submit" name="accept" class="bg-emerald-500 text-white p-3 rounded-xl font-bold hover:bg-emerald-600 transition">قبول</button>';
            echo '<button type="submit" name="reject" class="bg-red-500 text-white p-3 rounded-xl font-bold hover:bg-red-600 transition">رفض</button>';
            echo '<a href="update_price.php?order_id=' . escape($order['id']) . '" class="bg-yellow-400 text-gray-800 p-3 rounded-xl font-bold hover:bg-yellow-500 transition">تعديل السعر</a>';
            echo '</form>';
            echo '</div>';
        }
    } else {
        echo '<p class="text-center text-gray-500">لا توجد طلبات جديدة حالياً في مدينتك.</p>';
    }

    if (!empty($orders)) {
        foreach ($orders as $myorder) {
            echo '<div class="p-6 bg-white/70 rounded-2xl shadow-lg border border-indigo-200/50 mb-4">';
            echo '<div class="flex flex-col gap-4 border-b pb-3 mb-3">';
            echo '<div class="flex justify-between items-center gap-4 flex-wrap">';
            echo '<div><h4 class="text-xl font-extrabold text-gray-800">خدمة ' . escape($myorder['specialization']) . '</h4><p class="text-sm text-gray-600 mt-1">العميل: ' . escape($myorder['username']) . '</p></div>';
            echo '<div class="text-right"><p class="text-sm text-slate-500">الحالة: <span class="font-semibold">' . escape($myorder['status'] === 'completed' ? 'مكتمل' : 'مقبول') . '</span></p><p class="text-2xl font-bold text-indigo-600">' . escape($myorder['worker_price'] ?: $myorder['budget']) . ' جنيه</p></div>';
            echo '</div></div>';
            echo '<div class="space-y-3"><p class="text-gray-700 font-semibold">العنوان: ' . escape($myorder['address']) . '</p><p class="text-gray-700 font-semibold">الوصف: ' . escape($myorder['description']) . '</p><p class="text-gray-700 font-semibold">رقم الهاتف: ' . escape($myorder['user_phone'] ?? '') . '</p>';
            if ($myorder['status'] === 'completed') {
                echo '<div class="rounded-2xl bg-emerald-50 p-4 border border-emerald-200 text-emerald-700">هذه الخدمة مكتملة، والعمولة المستحقة هي ' . escape($myorder['commission_amount'] ?? 0) . ' EGP.</div>';
            }
            if ($myorder['status'] === 'accepted') {
                echo '<form method="post" class="flex flex-wrap gap-3"><input type="hidden" name="order_id" value="' . escape($myorder['id']) . '"><button type="submit" name="complete" class="bg-emerald-600 text-white p-3 rounded-xl font-bold hover:bg-emerald-700 transition">إنهاء الطلب</button></form>';
            }
            echo '</div></div>';
        }
    }
    exit();
}


// User view updates
$userOrdersStmt = $conn->prepare('SELECT * FROM service_requests WHERE us_id::text = :id ORDER BY created_at DESC');
$userOrdersStmt->bindParam(':id', $userId, PDO::PARAM_STR);
$userOrdersStmt->execute();
$userOrders = $userOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

$pendingOffersStmt = $conn->prepare('SELECT * FROM service_requests WHERE us_id::text = :id AND status = :status AND worker_price IS NOT NULL ORDER BY created_at DESC');
$pendingStatus = 'pending';
$pendingOffersStmt->bindParam(':id', $userId, PDO::PARAM_STR);
$pendingOffersStmt->bindParam(':status', $pendingStatus, PDO::PARAM_STR);
$pendingOffersStmt->execute();
$pendingOffers = $pendingOffersStmt->fetchAll(PDO::FETCH_ASSOC);

$pendingRequestsStmt = $conn->prepare('SELECT * FROM service_requests WHERE us_id::text = :id AND status = :status AND worker_price IS NULL ORDER BY created_at DESC');
$pendingRequestsStmt->bindParam(':id', $userId, PDO::PARAM_STR);
$pendingRequestsStmt->bindParam(':status', $pendingStatus, PDO::PARAM_STR);
$pendingRequestsStmt->execute();
$pendingRequests = $pendingRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['json']) && $_GET['json'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'pendingRequests' => $pendingRequests,
        'pendingOffers' => $pendingOffers,
        'userOrders' => $userOrders,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if (!empty($pendingRequests)) {
    foreach ($pendingRequests as $order) {
        echo '<div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all mb-4">';
        echo '<div class="p-6">';
        echo '<div class="flex justify-between items-start mb-6">';
        echo '<div class="flex-1"><div class="mb-2"><span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase tracking-wider"><span class="status-dot bg-blue-500"></span> قيد البحث</span></div><h3 class="text-xl font-black text-slate-800 leading-tight">' . escape($order['description']) . '</h3><p class="text-sm text-slate-400 mt-1 flex items-center gap-1">' . escape($order['address']) . '</p></div>';
        echo '<div class="text-left"><p class="text-[10px] text-slate-400 font-bold uppercase">السعر</p><p class="text-2xl font-black text-rose-600">' . escape($order['budget']) . '<span class="text-xs">EGP</span></p></div>';
        echo '</div></div></div>';
    }
}

if (!empty($pendingOffers)) {
    foreach ($pendingOffers as $order) {
        echo '<div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all mb-4">';
        echo '<div class="p-6">';
        echo '<div class="flex justify-between items-start mb-6">';
        echo '<div class="flex-1"><div class="mb-2"><span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100 uppercase tracking-wider"><span class="status-dot bg-rose-500"></span> عرض سعر جديد</span></div><h3 class="text-xl font-black text-slate-800 leading-tight">' . escape($order['description']) . '</h3><p class="text-sm text-slate-400 mt-1 flex items-center gap-1">' . escape($order['address']) . '</p></div>';
        echo '<div class="text-left"><p class="text-[10px] text-slate-400 font-bold uppercase">السعر</p><p class="text-2xl font-black text-rose-600">' . escape($order['worker_price']) . '<span class="text-xs">EGP</span></p><p class="text-[9px] text-slate-400 line-through">ميزانيتك: ' . escape($order['budget']) . '</p></div>';
        echo '</div>';
        echo '<form method="POST" class="flex flex-wrap gap-3">';
        echo '<input type="hidden" name="order_id" value="' . escape($order['id']) . '">';
        echo '<button type="submit" name="accept" class="bg-emerald-500 text-white p-3 rounded-xl font-bold hover:bg-emerald-600 transition">قبول</button>';
        echo '<button type="submit" name="reject" class="bg-red-500 text-white p-3 rounded-xl font-bold hover:bg-red-600 transition">رفض</button>';
        echo '<a href="update_price.php?order_id=' . escape($order['id']) . '" class="bg-yellow-400 text-gray-800 p-3 rounded-xl font-bold hover:bg-yellow-500 transition">تعديل السعر</a>';
        echo '</form></div></div>';
    }
}

if (!empty($userOrders)) {
    foreach ($userOrders as $order) {
        echo '<div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 mb-4">';
        $statusText = 'قيد البحث';
        $statusClass = 'bg-amber-500';
        if ($order['status'] === 'accepted') {
            $statusText = 'مقبول';
            $statusClass = 'bg-indigo-600';
        } elseif ($order['status'] === 'completed') {
            $statusText = 'مكتمل';
            $statusClass = 'bg-emerald-500';
        }
        echo '<div class="flex justify-between items-center mb-3"><span class="text-slate-500 text-sm">' . escape($statusText) . '</span><span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700"><span class="status-dot ' . escape($statusClass) . '"></span>' . escape($statusText) . '</span></div>';
        echo '<h3 class="font-bold text-slate-800 mb-2">' . escape($order['specialization']) . '</h3>';
        echo '<p class="text-slate-600 mb-3">' . escape($order['description']) . '</p>';
        echo '<div class="grid grid-cols-2 gap-3 text-sm text-slate-500"><div>المدينة: ' . escape($order['city']) . '</div><div>الميزانية: ' . escape($order['budget']) . ' EGP</div><div>الحالة: ' . escape($order['payment_status'] ?? 'غير معروف') . '</div><div class="col-span-2">العنوان: ' . escape($order['address']) . '</div>';
        if (!empty($order['worker_price'])) {
            echo '<div>سعر الفني: ' . escape($order['worker_price']) . ' EGP</div>';
        }
        echo '</div></div>';
    }
} else {
    echo '<p class="text-gray-500">لم يتم تقديم أي طلب بعد.</p>';
}
