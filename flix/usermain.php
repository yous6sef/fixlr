<?php 

session_start();

include("db.php");

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) { 
  header("Location: login.php");
  exit();
}

$id = (string) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id::text = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_STR);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// FIXED: Removed duplicate logic - now just display the dashboard directly
// Don't redirect, render the dashboard

$userRatingStmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) AS avg_rating, COALESCE(COUNT(id), 0) AS review_count FROM reviews_user WHERE user_id::text = :id");
$userRatingStmt->bindParam(':id', $id, PDO::PARAM_STR);
$userRatingStmt->execute();
$userRating = $userRatingStmt->fetch(PDO::FETCH_ASSOC);

$completedOrdersStmt = $conn->prepare("SELECT COUNT(*) AS completed_count FROM service_requests WHERE us_id::text = :id AND status = 'completed'");
$completedOrdersStmt->bindParam(':id', $id, PDO::PARAM_STR);
$completedOrdersStmt->execute();
$completedOrders = $completedOrdersStmt->fetch(PDO::FETCH_ASSOC);

$totalRequestsStmt = $conn->prepare("SELECT COUNT(*) AS total_requests FROM service_requests WHERE us_id::text = :id");
$totalRequestsStmt->bindParam(':id', $id, PDO::PARAM_STR);
$totalRequestsStmt->execute();
$totalRequests = $totalRequestsStmt->fetch(PDO::FETCH_ASSOC);

$stm = $conn->prepare("SELECT w.*, COALESCE(AVG(rw.rating), 0) AS avg_rating, COALESCE(COUNT(rw.id), 0) AS review_count FROM workers w LEFT JOIN reviews_worker rw ON rw.worker_id = w.id WHERE w.status = 'active' AND w.approved = 'yes' GROUP BY w.id ORDER BY avg_rating DESC, review_count DESC LIMIT 10");
$stm->execute();
$actworkers = $stm->fetchAll(PDO::FETCH_ASSOC);


$error = '';
$success = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = 'تم تحديث الطلب بنجاح.';
}
if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $order_id = $_POST['order_id'] ?? NULL;
    $budget = $_POST['budget'] ?? NULL;

    if (isset($_POST['change']) && $order_id && $budget){
        if (!is_numeric($budget) || $budget <= 0) {
            $error = 'الرجاء إدخال سعر صحيح.';
        } else {
            $st = $conn->prepare("UPDATE service_requests SET budget = :budget, negotiation_state = 'countered', negotiation_started_at = COALESCE(negotiation_started_at, NOW()), negotiation_ended_at = NULL WHERE id = :id AND us_id::text = :user_id AND status = 'pending'");
            $st->bindParam(':id',$order_id,PDO::PARAM_INT);
            $st->bindParam(':budget',$budget,PDO::PARAM_STR);
            $st->bindParam(':user_id',$id,PDO::PARAM_STR);
            if ($st->execute()) {
                $success = 'تم إرسال العرض المضاد بنجاح.';
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            }
        }
    }
    elseif(isset($_POST['accept']) && $order_id ){
        $accept = $conn->prepare("UPDATE service_requests SET status = 'accepted', negotiation_state = 'accepted', negotiation_started_at = COALESCE(negotiation_started_at, NOW()), negotiation_ended_at = NOW() WHERE id = :id AND us_id::text = :user_id AND status = 'pending'");
        $accept->bindParam(':id', $order_id, PDO::PARAM_INT);
        $accept->bindParam(':user_id', $id, PDO::PARAM_STR);
        if ($accept->execute() && $accept->rowCount() > 0) {
            $success = 'تم قبول العرض وسيتم تأكيد الطلب الآن.';
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            $error = 'تعذر قبول الطلب. حاول مرة أخرى لاحقاً.';
        }
    }
    elseif(isset($_POST['reject']) && $order_id){
        $reject = $conn->prepare("UPDATE service_requests SET worker_price = NULL, worker_id = NULL, negotiation_state = 'rejected', negotiation_ended_at = NOW() WHERE id = :id AND us_id::text = :user_id AND status = 'pending'");
        $reject->bindParam(':id', $order_id, PDO::PARAM_INT);
        $reject->bindParam(':user_id', $id, PDO::PARAM_STR);
        if ($reject->execute() && $reject->rowCount() > 0) {
            $success = 'تم رفض العرض. يمكنك انتظار عروض جديدة أو تعديل الطلب.';
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            $error = 'تعذر رفض العرض. حاول مرة أخرى لاحقاً.';
        }
    }
    elseif (isset($_POST['review_worker']) && $order_id) {
        $rating = $_POST['rating'] ?? null;
        $comment = trim($_POST['comment'] ?? '');

        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            $error = 'الرجاء اختيار تقييم صحيح بين 1 و 5.';
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews_worker (worker_id, user_id, request_id, rating, comment, created_at) SELECT worker_id, :user_id, :request_id, :rating, :comment, NOW() FROM service_requests WHERE id = :request_id AND us_id::text = :user_id AND status = 'completed' AND worker_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM reviews_worker rw WHERE rw.request_id = :request_id AND rw.user_id::text = :user_id) LIMIT 1");
            $stmt->bindParam(':user_id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':request_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?review_success=1");
                exit();
            } else {
                $error = 'لم يتم إرسال التقييم. قد تكون قد قيمت هذا الطلب سابقاً أو أنه غير مكتمل بعد.';
            }
        }
    }
}

$ssaa = $conn->prepare("SELECT sr.*, w.name AS worker_name, EXISTS(SELECT 1 FROM reviews_worker rw WHERE rw.request_id = sr.id AND rw.user_id::text = :id::text) AS worker_reviewed FROM service_requests sr LEFT JOIN workers w ON w.id::text = sr.worker_id::text WHERE sr.us_id::text = :id::text AND sr.status IN ('accepted', 'completed') ORDER BY sr.created_at DESC");
$ssaa->bindParam(':id', $id, PDO::PARAM_STR);
$ssaa->execute();
$orders = $ssaa->fetchAll(PDO::FETCH_ASSOC);

$ss = $conn->prepare("SELECT * FROM service_requests WHERE us_id::text = :id AND status = 'pending' AND worker_price IS NOT NULL ORDER BY created_at DESC");
$ss->bindParam(':id', $id, PDO::PARAM_STR);
$ss->execute();
$worders = $ss->fetchAll(PDO::FETCH_ASSOC);

$sss = $conn->prepare("SELECT * FROM service_requests WHERE us_id::text = :id AND status = 'pending' AND worker_price IS NULL ORDER BY created_at DESC");
$sss->bindParam(':id', $id, PDO::PARAM_STR);
$sss->execute();
$pendingRequests = $sss->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Flix | طلب خدمة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4ff, #e0f2f1);
        }
        .glass {
            background: rgba(255,255,255,.9);
            backdrop-filter: blur(8px);
        }
        .star-rating {
            display: inline-flex;
            justify-content: flex-end;
            direction: rtl;
            gap: 0.2rem;
            font-size: 1.45rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            color: #cbd5e1;
            transition: color 0.2s ease;
        }
        .star-rating input:checked ~ label {
            color: #fbbf24;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #fbbf24;
        }
        /* Rating persistence classes */
        .star-rating.rating-1 input:nth-child(10) ~ label,
        .star-rating.rating-1 input:nth-child(10) ~ label ~ label,
        .star-rating.rating-1 input:nth-child(10) ~ label ~ label ~ label,
        .star-rating.rating-1 input:nth-child(10) ~ label ~ label ~ label ~ label,
        .star-rating.rating-1 input:nth-child(10) ~ label ~ label ~ label ~ label ~ label { color: #fbbf24; }

        .star-rating.rating-2 input:nth-child(8) ~ label,
        .star-rating.rating-2 input:nth-child(8) ~ label ~ label,
        .star-rating.rating-2 input:nth-child(8) ~ label ~ label ~ label,
        .star-rating.rating-2 input:nth-child(8) ~ label ~ label ~ label ~ label { color: #fbbf24; }

        .star-rating.rating-3 input:nth-child(6) ~ label,
        .star-rating.rating-3 input:nth-child(6) ~ label ~ label,
        .star-rating.rating-3 input:nth-child(6) ~ label ~ label ~ label { color: #fbbf24; }

        .star-rating.rating-4 input:nth-child(4) ~ label,
        .star-rating.rating-4 input:nth-child(4) ~ label ~ label { color: #fbbf24; }

        .star-rating.rating-5 input:nth-child(2) ~ label { color: #fbbf24; }

        .brand-gradient {
            background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
        }
        .glass-card {
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(15, 23, 42, 0.08);
        }
        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
            border-radius: 999px;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            font-weight: 700;
        }
        .icon-card {
            background: #f8fafc;
            border-radius: 1.75rem;
            padding: 1.1rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .icon-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
        .location-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.95rem 1.2rem;
            border-radius: 999px;
            font-weight: 700;
            color: #ffffff;
            background: #10b981;
            border: none;
            cursor: pointer;
            transition: background 0.25s ease, transform 0.25s ease;
        }
        .location-button:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .small-note {
            color: #475569;
            font-size: 0.95rem;
        }
    </style>

    <!-- FIREBASE IMPORTS (Auth and Firestore) -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInAnonymously, signInWithCustomToken, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, doc, setDoc, updateDoc, collection, setLogLevel, onSnapshot, query, where, orderBy, limit } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";
        
        // Expose Firebase modules and Global Configs to the global scope
        window.firebase = { initializeApp, getAuth, signInAnonymously, signInWithCustomToken, onAuthStateChanged, getFirestore, doc, setDoc, updateDoc, collection, setLogLevel, onSnapshot, query, where, orderBy, limit };
        window.globalAppId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
        window.globalFirebaseConfig = typeof __firebase_config !== 'undefined' ? JSON.parse(__firebase_config) : {};
        window.globalInitialAuthToken = typeof __initial_auth_token !== 'undefined' ? __initial_auth_token : null;
    </script>

    <!-- CSS Stylesheets for Real-time Features -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/loaders.css">
    <link rel="stylesheet" href="css/responsive.css">

    <!-- Socket.IO Library -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>

    <!-- Notification System -->
    <script src="js/notifications.js"></script>

    <!-- WebSocket Real-Time Client -->
    <script src="js/socket-client.js"></script>

    <!-- Real-Time Dashboard Initialization -->
    <script>
        // Initialize real-time updates when DOM loads
        let realTimeClient = null;
        document.addEventListener('DOMContentLoaded', function() {
            // Create real-time client instance
            realTimeClient = new RealTimeClient();
            realTimeClient.connect('<?php echo $_SESSION['user_id']; ?>', 'user');

            // Listen for marketplace events
            realTimeClient.on('worker.offered', (data) => {
                console.log('New offer:', data);
                setTimeout(refreshUserOrders, 1000);
            });

            realTimeClient.on('user.accepted', (data) => {
                console.log('Order accepted:', data);
                setTimeout(refreshUserOrders, 1000);
            });

            realTimeClient.on('worker.completed', (data) => {
                console.log('Order completed:', data);
                setTimeout(refreshUserOrders, 1000);
            });

            // Initialize star rating persistence
            initializeStarRatings();

            // Check for review submission success
            checkReviewSubmission();
        });

        function initializeStarRatings() {
            // Handle star rating clicks to maintain selection
            document.querySelectorAll('.star-rating input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const rating = this.value;
                    const form = this.closest('form');
                    const starContainer = form.querySelector('.star-rating');

                    // Remove previous selection styling
                    starContainer.classList.remove('rating-1', 'rating-2', 'rating-3', 'rating-4', 'rating-5');
                    // Add current selection styling
                    starContainer.classList.add('rating-' + rating);
                });
            });
        }

        function checkReviewSubmission() {
            // Check URL parameters for success messages
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('review_success') === '1') {
                showReviewSuccessPopup();
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        function showReviewSuccessPopup() {
            // Create popup element
            const popup = document.createElement('div');
            popup.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            popup.innerHTML = `
                <div class="bg-white rounded-2xl p-6 max-w-sm mx-4 text-center">
                    <div class="text-4xl mb-4">✅</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">تم إرسال التقييم بنجاح!</h3>
                    <p class="text-gray-600 mb-4">شكراً لك على تقييم الخدمة</p>
                    <button onclick="this.parentElement.parentElement.remove()" class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700 transition">
                        حسناً
                    </button>
                </div>
            `;
            document.body.appendChild(popup);
        }

    </script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<header class="bg-white/90 backdrop-blur shadow sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-indigo-800">
                Flix <span class="text-emerald-500">Service</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">لوحة المستخدم - طلب خدمة بسرعة وراحة</p>
        </div>
        <div class="flex items-center gap-4">
            <span id="user-display" class="text-sm font-medium text-slate-600">مرحباً، <?= htmlspecialchars($user['name'] ?: 'مستخدم') ?></span>
            <a href="logout.php" class="font-semibold text-red-600 hover:text-red-800 transition duration-300">
                تسجيل الخروج
            </a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
        <section class="glass-card rounded-[2rem] p-6 shadow-xl border border-slate-200">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name'] ?: 'مستخدم') ?>&background=10b981&color=ffffff&rounded=true&size=128" alt="Avatar" class="w-20 h-20 rounded-full border-4 border-white shadow-lg">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-emerald-600 font-bold">ملفك الشخصي</p>
                        <h2 class="text-3xl font-black text-slate-900 leading-tight"><?= htmlspecialchars($user['name'] ?: 'العميل') ?></h2>
                        <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($user['email'] ?? $user['phone'] ?? 'بدون بيانات اتصال') ?></p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                        <p class="text-sm text-slate-500">التقييم</p>
                        <p class="text-xl font-extrabold text-indigo-700"><?= round($userRating['avg_rating'] ?? 0, 1) ?></p>
                        <p class="text-xs text-slate-400"><?= intval($userRating['review_count']) ?> تقييم</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                        <p class="text-sm text-slate-500">الطلبات</p>
                        <p class="text-xl font-extrabold text-indigo-700"><?= intval($totalRequests['total_requests']) ?></p>
                        <p class="text-xs text-slate-400">إجمالي الطلبات</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                        <p class="text-sm text-slate-500">اكتملت</p>
                        <p class="text-xl font-extrabold text-indigo-700"><?= intval($completedOrders['completed_count']) ?></p>
                        <p class="text-xs text-slate-400">طلب ناجح</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">الفئة</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/home-page.png" alt="Home" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">خدمة منزلية</p>
                </div>
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">اللون</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/paint-palette.png" alt="Brand" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">ألوان فليكس</p>
                </div>
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">سهولة</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/road-sign.png" alt="Ease" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">طلب سريع</p>
                </div>
            </div>
        </section>

        <aside class="glass-card rounded-[2rem] p-6 shadow-xl border border-slate-200">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-3xl bg-emerald-500 flex items-center justify-center text-white text-2xl font-bold">F</div>
                <div>
                    <p class="text-sm text-slate-500">أخر زيارة</p>
                    <p class="text-xl font-black text-slate-900">مرحباً بعودتك</p>
                </div>
            </div>
            <div class="mt-6 space-y-4">
                <div class="profile-badge">
                    <img src="https://img.icons8.com/ios-filled/18/059669/map-pin.png" alt="Location">
                    <?= htmlspecialchars($user['location'] ?: $user['city'] ?: 'حدد موقعك') ?>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                    <p class="text-sm text-slate-500">مركز المساعدة</p>
                    <p class="mt-2 text-base text-slate-700">استخدم موقعك الحالي لملء العنوان تلقائياً وتسريع طلب الخدمة.</p>
                </div>
            </div>
        </aside>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <section class="lg:col-span-1">
            <div class="glass rounded-3xl shadow-xl p-6 border border-slate-200">
                <h2 class="text-xl font-bold text-slate-900 mb-6 border-b pb-3">إنشاء طلب خدمة</h2>
                <?php if (!empty($error)): ?>
                    <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 p-4"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 p-4"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <form method="post" action="order.php" class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">نوع الخدمة</label>
                        <select class="w-full px-4 py-3 rounded-xl border" required name="special">
                            <option value="">اختر نوع الخدمة</option>
                            <option value="سباك">سباك</option>
                            <option value="كهربائي">كهربائي</option>
                            <option value="نجار">نجار</option>
                            <option value="دهان">دهان</option>
                            <option value="تنظيف">تنظيف</option>
                            <option value="صيانة عامة">صيانة عامة</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">عنوان الخدمة</label>
                        <input type="text" class="w-full px-4 py-3 rounded-xl border" required name="address" placeholder="مثال: شارع النصر، أمام المركز التجاري">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">وصف الخدمة</label>
                        <input type="text" class="w-full px-4 py-3 rounded-xl border" required name="description" placeholder="مثال: تسريب مياه في المطبخ">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">المدينة</label>
                        <input type="text" id="user-city" name="city" class="w-full px-4 py-3 rounded-xl border" required placeholder="اكتب المدينة أو استخدم الموقع" autocomplete="off">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block font-medium mb-1">عرض السعر (EGP)</label>
                            <input type="number" class="w-full px-4 py-3 rounded-xl border" required name="budget" placeholder="مثال: 350">
                        </div>
                        <div class="flex items-end">
                            <button type="button" id="fill-location-btn" onclick="fillCurrentLocation()" class="location-button w-full">
                                <img src="https://img.icons8.com/ios-filled/20/ffffff/near-me.png" alt="Location" class="w-4 h-4">
                                استخدم موقعي الآن
                            </button>
                        </div>
                    </div>
                    <p class="small-note">سوف يُطلب منك إعطاء الإذن للوصول إلى الموقع لتعبئة العنوان تلقائياً.</p>
                    <div class="bg-emerald-50 p-4 rounded-xl text-sm text-slate-700">💵 الدفع نقدًا عند إتمام الخدمة</div>
                    <button type="submit" name="submit_order" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold text-lg py-3 rounded-xl transition">إرسال طلب الخدمة</button>
                </form>
            </div>
        </section>

        <section class="lg:col-span-2">
            <div id="user-orders-area" class="glass rounded-3xl shadow-xl p-6 border border-slate-200 space-y-6">
                <?php if (!empty($pendingRequests)) : ?>
                    <div class="space-y-4">
                        <div class="text-lg font-bold text-slate-900">طلبات قيد المعالجة</div>
                        <?php foreach ($pendingRequests as $pending) : ?>
                            <div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all">
                                <div class="p-6">
                                    <div class="flex justify-between items-start gap-4 mb-5">
                                        <div class="flex-1">
                                            <div class="mb-3">
                                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase tracking-wider">
                                                    <span class="status-dot w-2.5 h-2.5 rounded-full bg-blue-500"></span> قيد البحث
                                                </span>
                                            </div>
                                            <h3 class="text-xl font-black text-slate-800 leading-tight"><?= htmlspecialchars($pending['description']) ?></h3>
                                            <p class="text-sm text-slate-400 mt-2 flex items-center gap-2">
                                                <span class="inline-block w-3.5 h-3.5 text-emerald-500">📍</span>
                                                <?= htmlspecialchars($pending['address']) ?>
                                            </p>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase">السعر</p>
                                            <p class="text-2xl font-black text-rose-600"><?= htmlspecialchars($pending['budget']) ?><span class="text-xs">EGP</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($worders)) : ?>
                    <div class="space-y-4">
                        <div class="text-lg font-bold text-slate-900">عروض جديدة من الفنيين</div>
                        <?php foreach ($worders as $offer) : ?>
                            <div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all">
                                <div class="p-6">
                                    <div class="flex flex-col lg:flex-row justify-between gap-4 mb-5">
                                        <div class="flex-1">
                                            <div class="mb-3">
                                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100 uppercase tracking-wider">
                                                    <span class="status-dot w-2.5 h-2.5 rounded-full bg-rose-500"></span> عرض سعر جديد
                                                </span>
                                            </div>
                                            <h3 class="text-xl font-black text-slate-800 leading-tight"><?= htmlspecialchars($offer['description']) ?></h3>
                                            <p class="text-sm text-slate-400 mt-2 flex items-center gap-2">
                                                <span class="inline-block w-3.5 h-3.5 text-emerald-500">📍</span>
                                                <?= htmlspecialchars($offer['address']) ?>
                                            </p>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase">السعر المقترح</p>
                                            <p class="text-2xl font-black text-rose-600"><?= htmlspecialchars($offer['worker_price']) ?><span class="text-xs">EGP</span></p>
                                            <p class="text-[9px] text-slate-400 line-through">ميزانيتك: <?= htmlspecialchars($offer['budget']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-3 mt-4">
                                        <form method="post" class="flex-1 min-w-[150px]">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($offer['id']) ?>">
                                            <button type="submit" name="accept" class="w-full bg-emerald-500 text-white p-3 rounded-xl font-bold hover:bg-emerald-600 transition">قبول العرض</button>
                                        </form>
                                        <form method="post" class="flex-1 min-w-[150px]">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($offer['id']) ?>">
                                            <button type="submit" name="reject" class="w-full bg-red-500 text-white p-3 rounded-xl font-bold hover:bg-red-600 transition">رفض العرض</button>
                                        </form>
                                        <button type="button" onclick="openModal('<?= htmlspecialchars($offer['budget']) ?>', '<?= htmlspecialchars($offer['id']) ?>')" class="bg-yellow-400 text-slate-900 p-3 rounded-xl font-bold hover:bg-yellow-500 transition">عرض مضاد</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($orders)) : ?>
                    <div class="space-y-4">
                        <div class="text-lg font-bold text-slate-900">سجل الطلبات</div>
                        <?php foreach ($orders as $order) : ?>
                            <div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all">
                                <div class="p-6">
                                    <div class="flex flex-col lg:flex-row justify-between gap-4 mb-5">
                                        <div class="flex-1">
                                            <div class="mb-3">
                                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100 uppercase tracking-wider">
                                                    <span class="status-dot w-2.5 h-2.5 rounded-full bg-rose-500"></span> <?= $order['status'] === 'completed' ? 'تمت الخدمة' : 'طلب مقبول' ?>
                                                </span>
                                            </div>
                                            <h3 class="text-xl font-black text-slate-800 leading-tight"><?= htmlspecialchars($order['description']) ?></h3>
                                            <p class="text-sm text-slate-400 mt-2 flex items-center gap-2">
                                                <span class="inline-block w-3.5 h-3.5 text-emerald-500">📍</span>
                                                <?= htmlspecialchars($order['address']) ?>
                                            </p>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase">السعر</p>
                                            <p class="text-2xl font-black text-rose-600"><?= htmlspecialchars($order['worker_price'] ?: $order['budget']) ?><span class="text-xs">EGP</span></p>
                                            <?php if (!empty($order['worker_price'])) : ?>
                                                <p class="text-[9px] text-slate-400 line-through">ميزانيتك: <?= htmlspecialchars($order['budget']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($order['status'] === 'accepted') : ?>
                                        <div class="rounded-2xl bg-yellow-50 p-4 border border-yellow-200 text-yellow-700">تم قبول الطلب ويمكنك انتظار بدء العمل أو متابعة حالة الفني.</div>
                                    <?php elseif ($order['status'] === 'completed') : ?>
                                        <div class="rounded-2xl bg-emerald-50 p-4 border border-emerald-200 text-emerald-700">تم اكتمال الطلب. شكراً لاستخدامك فليكس.</div>
                                        <?php if (!$order['worker_reviewed'] || $order['worker_reviewed'] === 'f') : ?>
                                            <form method="post" class="bg-slate-50 p-4 rounded-2xl border border-slate-200 mt-4">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                                <div class="mb-4">
                                                    <label class="block text-sm font-semibold mb-2">تقييم الفني</label>
                                                    <div class="star-rating">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input type="radio" id="user-star-<?= $order['id'] ?>-<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                                            <label for="user-star-<?= $order['id'] ?>-<?= $i ?>" aria-label="<?= $i ?> نجوم">★</label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="block text-sm font-semibold mb-2">ملاحظات</label>
                                                    <textarea name="comment" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="اكتب ملاحظتك عن الفني"></textarea>
                                                </div>
                                                <button type="submit" name="review_worker" class="bg-indigo-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-indigo-700 transition">إرسال تقييم الفني</button>
                                            </form>
                                        <?php else: ?>
                                            <div class="rounded-2xl bg-blue-50 p-4 border border-blue-200 text-blue-700 mt-4">لقد قمت بالفعل بتقييم الفني لهذا الطلب.</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (empty($pendingRequests) && empty($worders) && empty($orders)) : ?>
                    <div class="rounded-3xl bg-white p-8 border border-slate-200 text-center shadow-sm">
                        <p class="text-lg font-semibold text-slate-900">لا توجد طلبات حتى الآن</p>
                        <p class="text-slate-500 mt-2">ابدأ بطلب خدمة جديدة وسنوافيك بالعروض والتحديثات هنا.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<script>
    async function fillCurrentLocation() {
        const btn = document.getElementById('fill-location-btn');
        if (!navigator.geolocation) {
            alert('هذه الميزة غير مدعومة في متصفحك.');
            return;
        }
        btn.disabled = true;
        btn.textContent = 'جاري التحديد...';

        navigator.geolocation.getCurrentPosition(async (position) => {
            const { latitude, longitude } = position.coords;
            const addressInput = document.querySelector('input[name="address"]');
            const cityInput = document.getElementById('user-city');
            if (addressInput) {
                addressInput.value = `موقعي الحالي - ${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;
            }

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.display_name && addressInput) {
                        addressInput.value = data.display_name;
                    }
                    if (data.address && cityInput) {
                        const city = data.address.city || data.address.town || data.address.village || data.address.county || data.address.state;
                        if (city) {
                            cityInput.value = city;
                        }
                    }
                }
            } catch (error) {
                console.warn('Reverse geocode failed:', error);
            }

            btn.textContent = 'تم التحديد';
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = 'استخدم موقعي الآن';
            }, 1500);
        }, (error) => {
            alert('تعذر الحصول على الموقع. تأكد من سماح المتصفح بالوصول للموقع.');
            btn.disabled = false;
            btn.textContent = 'استخدم موقعي الآن';
        }, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        });
    }

    async function refreshUserOrders() {
        try {
            const response = await fetch('order_updates.php?view=user');
            if (!response.ok) return;
            const html = await response.text();
            const ordersArea = document.getElementById('user-orders-area');
            if (ordersArea) {
                ordersArea.innerHTML = html;
            }
        } catch (error) {
            console.error('Failed to refresh orders:', error);
        }
    }

    setInterval(refreshUserOrders, 8000);
    window.addEventListener('load', refreshUserOrders);
</script>
</body>
</html>
                        <p class="text-xs text-slate-400">خدمة ناجحة</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">الفئة</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/home-page.png" alt="Home" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">خدمة منزلية</p>
                </div>
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">اللون</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/paint-palette.png" alt="Brand" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">ألوان فليكس</p>
                </div>
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">سهولة</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/road-sign.png" alt="Ease" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">طلب سريع</p>
                </div>
            </div>
        </section>

        <aside class="glass-card rounded-[2rem] p-6 shadow-xl border border-slate-200">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-3xl bg-emerald-500 flex items-center justify-center text-white text-2xl font-bold">F</div>
                <div>
                    <span class="text-sm text-slate-500">أخر زيارة</span>
                    <p class="text-xl font-black text-slate-900">مرحباً بعودتك</p>
                </div>
            </div>
            <div class="mt-6 space-y-4">
                <div class="profile-badge">
                    <img src="https://img.icons8.com/ios-filled/18/059669/map-pin.png" alt="Location">
                    <?= htmlspecialchars($user['location'] ?: $user['city'] ?: 'حدد موقعك') ?>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                    <p class="text-sm text-slate-500">مركز المساعدة</p>
                    <p class="mt-2 text-base text-slate-700">استخدم موقعك الحالي لملء العنوان تلقائياً وتسريع طلب الخدمة.</p>
                </div>
            </div>
        </aside>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap
<!-- HEADER -->
<header class="bg-white/90 backdrop-blur shadow sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-indigo-800">
                Flix <span class="text-emerald-500">Service</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">لوحة المستخدم - طلب خدمة بسرعة وراحة</p>
        </div>
        <div class="flex items-center gap-4">
            <span id="user-display" class="text-sm font-medium text-slate-600">مرحباً، <?= htmlspecialchars($user['name'] ?: 'مستخدم') ?></span>
            <a href="logout.php" class="font-semibold text-red-600 hover:text-red-800 transition duration-300">
                تسجيل الخروج
            </a>
        </div>
    </div>
</header>

<!-- CONTENT -->
<main class="max-w-7xl mx-auto px-4 mt-8">
    <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr] mb-8">
        <section class="glass-card rounded-[2rem] p-6 shadow-xl border border-slate-200">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name'] ?: 'مستخدم') ?>&background=10b981&color=ffffff&rounded=true&size=128" alt="Avatar" class="w-20 h-20 rounded-full border-4 border-white shadow-lg">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-emerald-600 font-bold">ملفك الشخصي</p>
                        <h2 class="text-3xl font-black text-slate-900 leading-tight"><?= htmlspecialchars($user['name'] ?: 'العميل') ?></h2>
                        <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($user['email'] ?? $user['phone'] ?? 'بدون بيانات اتصال') ?></p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                        <p class="text-sm text-slate-500">التقييم</p>
                        <p class="text-xl font-extrabold text-indigo-700"><?= round($userRating['avg_rating'] ?? 0, 1) ?></p>
                        <p class="text-xs text-slate-400"><?= intval($userRating['review_count']) ?> تقييم</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                        <p class="text-sm text-slate-500">الطلبات</p>
                        <p class="text-xl font-extrabold text-indigo-700"><?= intval($totalRequests['total_requests']) ?></p>
                        <p class="text-xs text-slate-400">إجمالي الطلبات</p>
                    </div>
                        <input type="text" id="user-city" name="city" class="w-full px-4 py-3 rounded-xl border" required placeholder="اكتب المدينة أو استخدم الموقع" autocomplete="off"
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">الفئة</span>
                        <img srcflex items-center justify-between gap-3 mt-4">
                        <button type="button" id="fill-location-btn" onclick="fillCurrentLocation()" class="location-button w-full">
                            <img src="https://img.icons8.com/ios-filled/20/ffffff/near-me.png" alt="Location" class="w-4 h-4">
                            استخدم موقعي الآن
                        </button>
                    </div>
                    <p class="small-note mt-2">سوف يُطلب منك إعطاء الإذن للوصول إلى الموقع لتعبئة العنوان تلقائياً.</p>

                    <div class="="https://img.icons8.com/ios-filled/28/059669/home-page.png" alt="Home" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">خدمة منزلية</p>
                </div>
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">اللون</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/paint-palette.png" alt="Brand" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">ألوان فليكس</p>
                </div>
                <div class="icon-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm">سهولة</span>
                        <img src="https://img.icons8.com/ios-filled/28/059669/road-sign.png" alt="Ease" class="w-7 h-7">
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-900">طلب سريع</p>
                </div>
            </div>
        </section>

        <aside class="glass-card rounded-[2rem] p-6 shadow-xl border border-slate-200">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-3xl bg-emerald-500 flex items-center justify-center text-white text-2xl font-bold">F</div>
                <div>
                    <span class="text-sm text-slate-500">أخر زيارة</span>
                    <p class="text-xl font-black text-slate-900">مرحباً بعودتك</p>
                </div>
            </div>
            <div class="mt-6 space-y-4">
                <div class="profile-badge">
                    <img src="https://img.icons8.com/ios-filled/18/059669/map-pin.png" alt="Location">
                    <?= htmlspecialchars($user['location'] ?: $user['city'] ?: 'حدد موقعك') ?>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 border border-slate-200">
                    <p class="text-sm text-slate-500">مركز المساعدة</p>
                    <p class="mt-2 text-base text-slate-700">استخدم موقعك الحالي لملء العنوان تلقائياً وتسريع طلب الخدمة.</p>
                </div>
            </div>
        </aside>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- REQUEST FORM -->
        <section class="lg:col-span-1">
            <div class="glass rounded-3xl shadow-xl p-6 border">
                <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-3">
                    إنشاء طلب خدمة
                </h2>

                    <?php if (!empty($error)): ?>
                        <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 p-4"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 p-4"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post" action="order.php">
                        <div>
                            <label class="block font-medium mb-1">نوع الخدمة</label>
                            <select class="w-full px-4 py-3 rounded-xl border" required name="special">
                                <option value="">اختر نوع الخدمة</option>
                                <option value="سباك">سباك</option>
                                <option value="كهربائي">كهربائي</option>
                                <option value="نجار">نجار</option>
                                <option value="دهان">دهان</option>
                                <option value="تنظيف">تنظيف</option>
                                <option value="صيانة عامة">صيانة عامة</option>
                            </select>
                        </div>

                    <div>
                        <label class="block font-medium mb-1">عنوان الخدمة</label>
                        <input type="text" class="w-full px-4 py-3 rounded-xl border" required name="address">
                    </div>

                    <div>
                        <label class="block font-medium mb-1">وصف الخدمة</label>
                        <input type="text" class="w-full px-4 py-3 rounded-xl border" required name="description">
                    </div>

                    <div>
                        <label class="block font-medium mb-1">المدينة</label>
                        <input type="text" id="user-city" name="city" class="w-full px-4 py-3 rounded-xl border" required placeholder="اكتب المدينة أو استخدم الموقع" autocomplete="off">
                    </div>

                    <div>
                        <label class="block font-medium mb-1">عرض السعر (EGP)</label>
                        <input type="number" class="w-full px-4 py-3 rounded-xl border" required name="budget">
                    </div>

                    <div class="flex items-center justify-between gap-3 mt-4">
                        <button type="button" id="fill-location-btn" onclick="fillCurrentLocation()" class="location-button w-full">
                            <img src="https://img.icons8.com/ios-filled/20/ffffff/near-me.png" alt="Location" class="w-4 h-4">
                            استخدم موقعي الآن
                        </button>
                    </div>
                    <p class="small-note mt-2">سوف يُطلب منك إعطاء الإذن للوصول إلى الموقع لتعبئة العنوان تلقائياً.</p>

                    <div class="bg-emerald-50 p-4 rounded-xl text-sm">
                        💵 الدفع نقدًا عند إتمام الخدمة
                    </div>

                    <button type="submit" name="submit_order" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold text-lg py-3 rounded-xl">
                        إرسال طلب الخدمة
                    </button>

                </form>
            </div>
        </section>

        <!-- WORKERS -->
        <section class="lg:col-span-2">
            <div id="user-orders-area" class="glass rounded-3xl shadow-xl p-6 border">

                <div class="space-y-4">
                    <?php if (!empty($pendingRequests)) : ?>
                        <?php foreach ($pendingRequests as $pending) : ?>
                            <div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all" data-order-id="<?= htmlspecialchars($pending['id']) ?>" data-status="pending">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="flex-1">
                                            <div class="mb-2">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase tracking-wider">
                                                    <span class="status-dot bg-blue-500"></span> قيد البحث
                                                </span>
                                            </div>
                                            <h3 class="text-xl font-black text-slate-800 leading-tight"><?= htmlspecialchars($pending['description']); ?></h3>
                                            <p class="text-sm text-slate-400 mt-1 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <?= htmlspecialchars($pending['address']) ?>
                                            </p>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase">السعر</p>
                                            <p class="text-2xl font-black text-rose-600"><?= htmlspecialchars($pending['budget']) ?><span class="text-xs">EGP</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if(!empty($orders)) :?>
                        <?php foreach($orders as $order) : ?>
        <div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all" data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="<?= htmlspecialchars($order['status']) ?>">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex-1">
                        <div class="mb-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100 uppercase tracking-wider">
                                <span class="status-dot bg-rose-500"></span> <?= $order['status'] === 'completed' ? 'تمت الخدمة' : 'طلب مقبول' ?>
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 leading-tight"><?= htmlspecialchars($order['description']);?></h3>
                        <p class="text-sm text-slate-400 mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                           <?= htmlspecialchars($order['address']) ?>
                        </p>
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] text-slate-400 font-bold uppercase">السعر</p>
                        <p class="text-2xl font-black text-rose-600"><?= htmlspecialchars($order['worker_price'] ?: $order['budget']) ?><span class="text-xs">EGP</span></p>
                        <p class="text-[9px] text-slate-400 line-through">ميزانيتك: <?= htmlspecialchars($order['budget']) ?></p>
                    </div>
                </div>
                <div class="space-y-4">
                    <?php if ($order['status'] === 'accepted'): ?>
                        <div class="rounded-2xl bg-yellow-50 p-4 border border-yellow-200 text-yellow-700">
                            تم قبول الطلب ويمكنك انتظار بدء العمل أو متابعة حالة الفني.
                        </div>
                    <?php elseif ($order['status'] === 'completed'): ?>
                        <div class="rounded-2xl bg-emerald-50 p-4 border border-emerald-200 text-emerald-700">
                            تم اكتمال الطلب. شكراً لاستخدامك فليكس.
                        </div>
                        <?php if (!$order['worker_reviewed'] || $order['worker_reviewed'] === 'f'): ?>
                            <form method="post" class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold mb-2">تقييم الفني</label>
                                    <div class="star-rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" id="user-star-<?= $order['id'] ?>-<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                            <label for="user-star-<?= $order['id'] ?>-<?= $i ?>" aria-label="<?= $i ?> نجوم">★</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-semibold mb-2">ملاحظات</label>
                                    <textarea name="comment" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="اكتب ملاحظتك عن الفني"></textarea>
                                </div>
                                <button type="submit" name="review_worker" class="bg-indigo-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-indigo-700 transition">إرسال تقييم الفني</button>
                            </form>
                        <?php else: ?>
                            <div class="rounded-2xl bg-blue-50 p-4 border border-blue-200 text-blue-700">
                                لقد قمت بالفعل بتقييم الفني لهذا الطلب.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach ?>
        <?php elseif(!empty($worders)): ?>
            <?php foreach($worders as $orders) : ?>
        <div class="order-card bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all" data-order-id="<?= htmlspecialchars($orders['id']) ?>" data-status="offer">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex-1">
                        <div class="mb-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100 uppercase tracking-wider">
                                <span class="status-dot bg-rose-500"></span> عرض سعر جديد
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 leading-tight"><?= htmlspecialchars($orders['description']);?></h3>
                        <p class="text-sm text-slate-400 mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                           <?= htmlspecialchars($orders['address']) ?>
                       fillCurrentLocation() {
            const btn = document.getElementById('fill-location-btn');
            if (!navigator.geolocation) {
                alert('هذه الميزة غير مدعومة في متصفحك.');
                return;
            }
            btn.disabled = true;
            btn.textContent = 'جاري التحديد...';

            navigator.geolocation.getCurrentPosition(async (position) => {
                const { latitude, longitude } = position.coords;
                const addressInput = document.getElementsByName('address')[0];
                const cityInput = document.getElementById('user-city');
                addressInput.value = `موقعي الحالي - ${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`);
                    if (response.ok) {
                        const data = await response.json();
                        if (data.display_name) {
                            addressInput.value = data.display_name;
                        }
                        if (data.address) {
                            const city = data.address.city || data.address.town || data.address.village || data.address.county || data.address.state;
                            if (city) {
                                cityInput.value = city;
                            }
                        }
                    }
                } catch (error) {
                    console.warn('Reverse geocode failed:', error);
                }

                btn.textContent = 'تم التحديد';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = 'استخدم موقعي الآن';
                }, 1500);
            }, (error) => {
                alert('تعذر الحصول على الموقع. تأكد من سماح المتصفح بالوصول للموقع.');
                btn.disabled = false;
                btn.textContent = 'استخدم موقعي الآن';
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });
        }

        async function  </p>
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] text-slate-400 font-bold uppercase">السعر المقترح</p>
                        <p class="text-2xl font-black text-rose-600"><?= htmlspecialchars($orders['worker_price']) ?><span class="text-xs">EGP</span></p>
                        <p class="text-[9px] text-slate-400 line-through">ميزانيتك: <?= htmlspecialchars($orders['budget']) ?></p>
                    </div>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4 border border-amber-200 text-amber-700 mt-4">
                    الفني قدّم عرض سعر مختلف. يمكنك قبوله أو رفضه أو تقديم عرض مضاد.
                </div>
                <form method="post" class="flex flex-wrap gap-3 mt-4">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($orders['id']) ?>">
                    <button type="submit" name="accept" class="bg-emerald-500 text-white p-3 rounded-xl font-bold hover:bg-emerald-600 transition">
                        قبول العرض
                    </button>
                    <button type="submit" name="reject" class="bg-red-500 text-white p-3 rounded-xl font-bold hover:bg-red-600 transition">
                        رفض العرض
                    </button>
                    <button type="button" onclick="openModal('<?= htmlspecialchars($orders['budget']) ?>', '<?= htmlspecialchars($orders['id']) ?>')" class="bg-yellow-400 text-gray-800 p-3 rounded-xl font-bold hover:bg-yellow-500 transition">
                        عرض مضاد
                    </button>
                </form>
                </div>
            </div>
        </div>
                <?php endforeach;?>
                    <?php else :?>
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">
                    الفنيون المتاحون
                    </h2>
                    <?php foreach ($actworkers as $row) : ?>
                        <div class="bg-white/70 rounded-2xl p-4 shadow flex justify-between items-center mb-4">
                            <div>
                                <h4 class="font-bold"><?= htmlspecialchars($row['name']) ?></h4>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($row['specialization']) ?></p>
                                <div class="mt-2 flex items-center gap-2 text-sm text-slate-500">
                                    <div class="flex items-center gap-1">
                                        <?php $avgWorkerRating = round($row['avg_rating'] ?? 0, 1); ?>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="<?= $i <= round($row['avg_rating'] ?? 0) ? 'text-amber-400' : 'text-gray-300' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span><?= $avgWorkerRating ?> / 5</span>
                                    <span class="text-xs text-gray-400">(<?= intval($row['review_count'] ?? 0) ?> تقييم)</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                    <?php endif ?>

                </div>
            </div>
        </section>

    </div>
</main>

<div id="counter-offer-modal" class="fixed inset-0 z-[110] hidden items-center justify-center modal-backdrop transition-opacity duration-300">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-lg mx-4 glass-card border border-indigo-200/50" dir="rtl">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">تقديم عرض سعر مضاد</h3>
            <p class="text-sm text-gray-600 mb-4">
                الرجاء إدخال السعر الجديد الذي تقترحه لهذه الخدمة.
            </p>
            <div id="original-price-display" class="mb-4 p-3 bg-indigo-50 rounded-lg text-sm font-semibold text-indigo-700">
                السعر الأصلي المطلوب: <span id="original-price-value" class="mr-1"></span> EGP
            </div>
            <form method="post">
                <input type="hidden" name="order_id" id="modal-order-id">
                <div class="mb-6">
                    <label for="new-price" class="block text-sm font-medium text-gray-700 mb-2">السعر الجديد (EGP):</label>
                    <input type="number" id="new-price" name="budget" required placeholder="أدخل السعر المقترح" min="1" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" />
                </div>

                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-800 font-semibold rounded-xl hover:bg-gray-400 transition duration-300">
                        إلغاء
                    </button>
                    <button id="submit-counter-offer" type="submit" name="change" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition duration-300 shadow-md shadow-indigo-500/50">
                        إرسال العرض
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("counter-offer-modal");
        const cancelBtn = modal.querySelector("button");
        const submitBtn = document.getElementById("submit-counter-offer");

        function openModal(originalPrice, requestId) {
            document.getElementById("original-price-value").textContent = originalPrice;
            document.getElementById("modal-order-id").value = requestId;
            modal.classList.remove("hidden");
        }

        function closeModal() {
            modal.classList.add("hidden");
        }

        async function fillCurrentLocation() {
            const btn = document.getElementById('fill-location-btn');
            if (!navigator.geolocation) {
                alert('هذه الميزة غير مدعومة في متصفحك.');
                return;
            }
            btn.disabled = true;
            btn.textContent = 'جاري التحديد...';

            navigator.geolocation.getCurrentPosition(async (position) => {
                const { latitude, longitude } = position.coords;
                const addressInput = document.getElementsByName('address')[0];
                const cityInput = document.getElementById('user-city');
                addressInput.value = `موقعي الحالي - ${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`);
                    if (response.ok) {
                        const data = await response.json();
                        if (data.display_name) {
                            addressInput.value = data.display_name;
                        }
                        if (data.address) {
                            const city = data.address.city || data.address.town || data.address.village || data.address.county || data.address.state;
                            if (city) {
                                cityInput.value = city;
                            }
                        }
                    }
                } catch (error) {
                    console.warn('Reverse geocode failed:', error);
                }

                btn.textContent = 'تم التحديد';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = 'استخدم موقعي الآن';
                }, 1500);
            }, (error) => {
                alert('تعذر الحصول على الموقع. تأكد من سماح المتصفح بالوصول للموقع.');
                btn.disabled = false;
                btn.textContent = 'استخدم موقعي الآن';
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });
        }

        async function refreshUserOrders() {
            try {
                const response = await fetch('order_updates.php?view=user');
                if (!response.ok) return;
                const html = await response.text();
                const ordersArea = document.getElementById('user-orders-area');
                if (ordersArea) {
                    ordersArea.innerHTML = html;
                }
            } catch (error) {
                console.error('Failed to refresh orders:', error);
            }
        }

        setInterval(refreshUserOrders, 8000);
        window.addEventListener('load', refreshUserOrders);

    </script>

</body>

</html>