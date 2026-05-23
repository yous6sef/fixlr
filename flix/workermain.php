<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = (string) $_SESSION['user_id'];

// Get worker data
$stmt = $conn->prepare("SELECT * FROM workers WHERE id::text = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_STR);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Check for active accepted order
$activeStmt = $conn->prepare("SELECT status FROM service_requests WHERE worker_id::text = :id AND status = 'accepted' ORDER BY created_at DESC LIMIT 1");
$activeStmt->bindParam(':id', $id, PDO::PARAM_STR);
$activeStmt->execute();
$activeOrder = $activeStmt->fetch(PDO::FETCH_ASSOC);

if ($activeOrder) {
    header('Location: worker_track.php');
    exit();
}

// Check for completed orders needing receipt
$completedStmt = $conn->prepare("SELECT id FROM service_requests WHERE worker_id::text = :id AND status = 'completed' ORDER BY updated_at DESC LIMIT 1");
$completedStmt->bindParam(':id', $id, PDO::PARAM_STR);
$completedStmt->execute();
$completedOrder = $completedStmt->fetch(PDO::FETCH_ASSOC);

if ($completedOrder) {
    header('Location: worker_receipt.php');
    exit();
}

// Default to worker orders/dashboard page
header('Location: worker_orders.php');
exit();


$isActive = $worker['status'] ?? 'no';
$specialization = $worker['specialization'] ?? '';
$city = $worker['city'] ?? '';
$notApproved = !isset($worker['approved']) || $worker['approved'] !== 'yes';
$isPaused = ($worker['paused'] ?? 'no') === 'yes' || (($worker['unpaid_streak_days'] ?? 0) >= 7);

$todayRevenueStmt = $conn->prepare("SELECT COALESCE(SUM(COALESCE(worker_price, budget)), 0) AS total FROM service_requests WHERE worker_id = :id AND status = 'completed' AND DATE(completed_at) = CURRENT_DATE");
$todayRevenueStmt->bindParam(':id', $id, PDO::PARAM_STR);
$todayRevenueStmt->execute();
$todayRevenue = $todayRevenueStmt->fetch(PDO::FETCH_ASSOC)['total'];

$commissionDueStmt = $conn->prepare("SELECT COALESCE(SUM(commission_amount), 0) AS total FROM service_requests WHERE worker_id = :id AND status = 'completed' AND payment_status = 'unpaid'");
$commissionDueStmt->bindParam(':id', $id, PDO::PARAM_STR);
$commissionDueStmt->execute();
$commissionDue = $commissionDueStmt->fetch(PDO::FETCH_ASSOC)['total'];

$userRatingsStmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) AS avg_rating, COALESCE(COUNT(id), 0) AS total_reviews FROM reviews_user WHERE worker_id = :id");
$userRatingsStmt->bindParam(':id', $id, PDO::PARAM_STR);
$userRatingsStmt->execute();
$userRatingStats = $userRatingsStmt->fetch(PDO::FETCH_ASSOC);
$avgUserRating = round($userRatingStats['avg_rating'] ?? 0, 1);
$reviewCount = intval($userRatingStats['total_reviews'] ?? 0);

$canAccept = !$notApproved && $isActive === 'active' && !$isPaused;

$sssss = $conn->prepare("SELECT id,us_id FROM service_requests WHERE worker_id = :id AND status = 'accepted' LIMIT 1");
$sssss->bindParam(':id', $id, PDO::PARAM_STR);
$sssss->execute();
$row = $sssss->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $usid = $row['us_id'];
    $request_id = $row['id'];
    $_SESSION['request_id'] = $request_id;

    $ssss = $conn->prepare("SELECT phone FROM users WHERE id = :id");
    $ssss->bindValue(':id', (int)$usid, PDO::PARAM_INT);
    $ssss->execute();
    $userdata = $ssss->fetch(PDO::FETCH_ASSOC);
}

$ssaaa = $conn->prepare("SELECT id,us_id FROM service_requests WHERE worker_id = :id AND status = 'pending' LIMIT 1");
$ssaaa->bindParam(':id', $id, PDO::PARAM_STR);
$ssaaa->execute();
$arow = $ssaaa->fetch(PDO::FETCH_ASSOC);

if ($arow) {
    $usid = $arow['us_id'];
    $request_id = $arow['id'];
    $_SESSION['request_id'] = $request_id;

    $ssssaa = $conn->prepare("SELECT phone FROM users WHERE id = :id");
    $ssssaa->bindValue(':id', (int)$usid, PDO::PARAM_INT);
    $ssssaa->execute();
    $userdata = $ssssaa->fetch(PDO::FETCH_ASSOC);
}

// منطق تحديث الحالة (متاح / غير متاح)
if ($isActive == "active") {
    $a = "متاح لاستقبال الطلبات";
    $b = "تبديل إلى غير متاح";
} else {
    $a = "غير متاح لاستقبال الطلبات";
    $b = "تبديل إلى متاح";
}

$success = '';
$error = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = 'تم تحديث عرض السعر بنجاح.';
}
// معالجة الطلبات (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'] ?? null;

    // 1. تحديث حالة الاتصال للعامل
    if (isset($_POST['action']) && $_POST['action'] == 'status') {
        $newStatus = ($isActive == "active") ? "no" : "active";
        $stm = $conn->prepare("UPDATE workers SET status = :status WHERE id = :id");
        $stm->bindParam(':status', $newStatus);
        $stm->bindParam(':id', $id);
        $stm->execute();
        header("Location: " . $_SERVER['PHP_SELF']); // إعادة تحميل لتحديث البيانات
        exit();
    }

    // 2. تحديث المدينة
    elseif (isset($_POST['places'])) {
        $newCity = $_POST['places'];
        $st = $conn->prepare("UPDATE workers SET city = :city WHERE id = :id");
        $st->bindParam(':city', $newCity);
        $st->bindParam(':id', $id );
        $st->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 3. قبول الطلب
    elseif (isset($_POST['accept'])) {
        $order_id = $_POST['order_id'] ?? null;
        if ($order_id && is_numeric($order_id)) {
            $conn->beginTransaction();
            try {
                $getRequest = $conn->prepare("SELECT id, us_id FROM service_requests WHERE id = :id AND status = 'pending' LIMIT 1");
                $getRequest->bindParam(':id', $order_id, PDO::PARAM_INT);
                $getRequest->execute();
                $requestRow = $getRequest->fetch(PDO::FETCH_ASSOC);

                if ($requestRow) {
                    $usid = $requestRow['us_id'];
                    $aaa = $conn->prepare("INSERT INTO tasks (worker_id, us_id, request_id, start_time, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())");
                    $aaa->execute([$id, $usid, $order_id]);

                    $aa = $conn->prepare("UPDATE service_requests SET status = 'accepted', worker_id = :worker_id, negotiation_state = 'accepted', negotiation_started_at = COALESCE(negotiation_started_at, NOW()), negotiation_ended_at = NOW() WHERE id = :id AND status = 'pending' AND specialization = :specialization AND city = :city");
                    $aa->bindParam(':id', $order_id, PDO::PARAM_INT);
                    $aa->bindParam(':worker_id', $id, PDO::PARAM_STR);
                    $aa->bindParam(':specialization', $specialization, PDO::PARAM_STR);
                    $aa->bindParam(':city', $city, PDO::PARAM_STR);
                    $aa->execute();
                    if ($aa->rowCount() > 0) {
                        $success = 'تم قبول الطلب بنجاح.';
                    } else {
                        $error = 'تعذر قبول الطلب. ربما تم التعامل معه من قبل عامل آخر.';
                    }
                }
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'حدث خطأ أثناء قبول الطلب. حاول لاحقاً.';
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    elseif(isset($_POST['complete'])) {
        $order_id = $_POST['order_id'] ?? null;
        if ($order_id && is_numeric($order_id)) {
            $getRequest = $conn->prepare("SELECT budget, worker_price FROM service_requests WHERE id = :id AND worker_id = :worker_id AND status = 'accepted' LIMIT 1");
            $getRequest->bindParam(':id', $order_id, PDO::PARAM_INT);
            $getRequest->bindParam(':worker_id', $id, PDO::PARAM_STR);
            $getRequest->execute();
            $requestRow = $getRequest->fetch(PDO::FETCH_ASSOC);

            if ($requestRow) {
                $finalPrice = $requestRow['worker_price'] > 0 ? $requestRow['worker_price'] : $requestRow['budget'];
                $finalPrice = number_format((float)$finalPrice, 2, '.', '');
                $commission = number_format(round((float)$finalPrice * 0.2, 2), 2, '.', '');
                $updateRequest = $conn->prepare("UPDATE service_requests SET status = 'completed', completed_at = NOW(), payment_status = 'unpaid', commission_amount = :commission, platform_fee = :commission, worker_price = :worker_price WHERE id = :id");
                $updateRequest->bindValue(':commission', $commission, PDO::PARAM_STR);
                $updateRequest->bindValue(':worker_price', $finalPrice, PDO::PARAM_STR);
                $updateRequest->bindParam(':id', $order_id, PDO::PARAM_INT);
                $updateRequest->execute();

                $updateWorker = $conn->prepare("UPDATE workers SET total_earnings = COALESCE(total_earnings, 0) + :earned, updated_at = NOW() WHERE id = :id");
                $updateWorker->bindValue(':earned', $finalPrice, PDO::PARAM_STR);
                $updateWorker->bindParam(':id', $id, PDO::PARAM_STR);
                $updateWorker->execute();
            }
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 4. رفض الطلب
    elseif(isset($_POST['reject'])){
        $order_id = $_POST['order_id'] ?? null;
        if ($order_id && is_numeric($order_id)) {
            $sssa = $conn->prepare("UPDATE service_requests SET refusers = COALESCE(refusers, 0) + 1, worker_price = NULL, worker_id = NULL, negotiation_state = 'rejected', negotiation_ended_at = NOW() WHERE id = :id AND status = 'pending'");
            $sssa->bindParam(':id', $order_id, PDO::PARAM_INT);
            $sssa->execute();
            if ($sssa->rowCount() > 0) {
                $success = 'تم رفض الطلب وسيظهر مرة أخرى لعامل آخر.';
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            } else {
                $error = 'تعذر رفض الطلب. حاول مرة أخرى.';
            }
        }
    }

    elseif (isset($_POST['review_user']) && $order_id) {
        $rating = $_POST['rating'] ?? null;
        $comment = trim($_POST['comment'] ?? '');

        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            $error = 'الرجاء اختيار تقييم صحيح بين 1 و 5.';
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews_user (worker_id, user_id, request_id, rating, comment, created_at) SELECT :worker_id, us_id, :request_id, :rating, :comment, NOW() FROM service_requests WHERE id = :request_id AND worker_id = :worker_id AND status = 'completed' AND NOT EXISTS (SELECT 1 FROM reviews_user ru WHERE ru.request_id = :request_id AND ru.worker_id = :worker_id) LIMIT 1");
            $stmt->bindParam(':worker_id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
            $stmt->bindParam(':request_id', $order_id, PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?review_success=1");
                exit();
            } else {
                $error = 'لم يتم إرسال التقييم. قد يكون الطلب غير مكتمل أو تم تقييمه مسبقاً.';
            }
        }
    }

    elseif(isset($_POST['price'])){
        $order_id = $_POST['order_id'] ?? null;
        $worker_price = $_POST['worker_price'] ?? null;
        if ($order_id && is_numeric($worker_price) && $worker_price > 0) {
            $aaa = $conn->prepare("UPDATE service_requests SET worker_price = :worker_price, worker_id = :worker_id, negotiation_state = 'countered', negotiation_started_at = COALESCE(negotiation_started_at, NOW()), negotiation_ended_at = NULL WHERE id = :id AND status = 'pending'");
            $aaa->bindParam(':id', $order_id, PDO::PARAM_INT);
            $aaa->bindParam(':worker_price', $worker_price, PDO::PARAM_STR);
            $aaa->bindParam(':worker_id', $id, PDO::PARAM_STR);
            $aaa->execute();
            if ($aaa->rowCount() > 0) {
                $success = 'تم تقديم عرض السعر بنجاح.';
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            } else {
                $error = 'تعذر إرسال عرض السعر. حاول مرة أخرى.';
            }
        } else {
            $error = 'الرجاء إدخال سعر صالح.';
        }
    }
}

// جلب الطلبات بناءً على التخصص والمدينة
$limitRefusers = 3;
$s = $conn->prepare("SELECT * FROM service_requests WHERE specialization = :specialization AND city = :city 
                     AND status = 'pending' AND refusers < :limit LIMIT 1");
$s->bindParam(':specialization', $specialization, PDO::PARAM_STR);
$s->bindParam(':city', $city, PDO::PARAM_STR);
$s->bindParam(':limit', $limitRefusers, PDO::PARAM_INT);
$s->execute();
$myorder = $s->fetchAll(PDO::FETCH_ASSOC);

$ss = $conn->prepare("SELECT * FROM tasks WHERE worker_id = :id ");
$ss->bindParam(':id', $id, PDO::PARAM_STR);
$ss->execute();
$myorders = $ss->fetchAll(PDO::FETCH_ASSOC);

$ssaa = $conn->prepare("SELECT sr.*, u.phone AS user_phone, EXISTS(SELECT 1 FROM reviews_user ru WHERE ru.request_id = sr.id AND ru.worker_id = :id) AS user_reviewed FROM service_requests sr LEFT JOIN users u ON u.id = sr.us_id WHERE sr.worker_id = :id AND sr.status IN ('accepted', 'completed') ORDER BY sr.created_at DESC");
$ssaa->bindParam(':id', $id, PDO::PARAM_STR);
$ssaa->execute();
$orders = $ssaa->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flix | لوحة تحكم الفني - الطلبات المتاحة</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    
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

    <style>
        /* Custom Styles for Glossy Look */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0f2f1 100%);
        }
        .neon-shadow-cta {
            box-shadow: 0 0 20px rgba(52, 211, 163, 0.7);
        }
        .glass-card {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .sidebar {
            min-height: calc(100vh - 4rem); 
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
        }
        /* Custom scrollbar for better look */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: #a5b4fc; /* Indigo 300 */
            border-radius: 3px;
        }
        .sidebar::-webkit-scrollbar-track {
            background-color: #e0e7ff; /* Indigo 100 */
        }
        /* Modal Backdrop */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        /* Bottom Navigation Bar Styles */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-top: 1px solid rgba(165, 180, 252, 0.3);
            z-index: 40;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        }
        .star-rating {
            display: inline-flex;
            justify-content: flex-end;
            direction: rtl;
            gap: 0.2rem;
            font-size: 1.5rem;
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
        .bottom-nav-container {
            max-width: 7xl;
            margin: 0 auto;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .nav-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s duration;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #4b5563;
        }
        .nav-btn:hover {
            background: rgba(165, 180, 252, 0.1);
            color: #4f46e5;
            transform: translateY(-2px);
        }
        .nav-btn.active {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        /* Adjust main content to account for bottom nav */
        body {
            padding-bottom: 6rem;
        }
        .logout{
            color: red;
            font-style: italic;
            
        }
    </style>

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
            realTimeClient.connect('<?php echo $_SESSION['user_id']; ?>', 'worker');

            // Listen for order events
            realTimeClient.on('worker.offered', (data) => {
                console.log('Offer submitted:', data);
                setTimeout(refreshWorkerOrders, 1000);
            });

            realTimeClient.on('user.accepted', (data) => {
                console.log('User accepted offer:', data);
                setTimeout(refreshWorkerOrders, 1000);
            });

            realTimeClient.on('user.countered', (data) => {
                console.log('User counter offer:', data);
                setTimeout(refreshWorkerOrders, 1000);
            });

            realTimeClient.on('worker.completed', (data) => {
                console.log('Order completed:', data);
                setTimeout(refreshWorkerOrders, 1000);
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
                    <p class="text-gray-600 mb-4">شكراً لك على تقييم العميل</p>
                    <button onclick="this.parentElement.parentElement.remove()" class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700 transition">
                        حسناً
                    </button>
                </div>
            `;
            document.body.appendChild(popup);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (popup.parentElement) {
                    popup.remove();
                }
            }, 5000);
        }
            });
        });
    </script>
</head>
<body dir="rtl" class="min-h-screen">

    <div id="cta-message" class="fixed top-5 left-1/2 -translate-x-1/2 bg-emerald-500 text-gray-900 p-3 rounded-lg shadow-xl neon-shadow-cta z-[100] transition-opacity duration-500 opacity-0 hidden font-bold">
        ...
    </div>

    <!-- Header Navigation Bar -->
    <header class="bg-white/90 backdrop-blur-md shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4 space-x-reverse">
                <h1 class="text-2xl font-extrabold text-indigo-800">فليكس <span class="text-red-500">الفني</span></h1>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <span id="user-display" class="text-sm font-medium text-gray-600">فني (ID: <?= htmlspecialchars($worker['id']) ?>)</span>
                <a href="logout.php" class="logout">
                    تسجيل الخروج
                </a>
            </div>
        </div>
    </header>

    <?php if (isset($notApproved) && $notApproved) : ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <div class="rounded-3xl border border-yellow-300 bg-yellow-50 p-6 text-right text-yellow-800 shadow-sm">
                حسابك قيد المراجعة من قبل الإدارة. لن تتمكن من استقبال الطلبات حتى يتم الموافقة عليك.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isPaused) : ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <div class="rounded-3xl border border-red-300 bg-red-50 p-6 text-right text-red-800 shadow-sm">
                تم إيقاف حسابك مؤقتًا بسبب تأخر الدفع لمدة 7 أيام أو أكثر. يرجى التواصل مع الإدارة لتأكيد الدفع وإعادة التشغيل.
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="rounded-3xl border border-indigo-200 bg-indigo-50 p-6 text-right text-indigo-900 shadow-sm">
            <p class="text-lg font-semibold">إيراد اليوم</p>
            <p class="text-4xl font-black mt-3"><?= number_format($todayRevenue, 2) ?> EGP</p>
            <p class="mt-2 text-sm text-indigo-700">الربح من الطلبات المكتملة اليوم.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-white p-4 shadow-sm border border-indigo-100">
                    <p class="text-sm text-slate-500">المبلغ المستحق للدفع اليوم (20%)</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600"><?= number_format($commissionDue, 2) ?> EGP</p>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm border border-indigo-100">
                    <p class="text-sm text-slate-500">عدد أيام التأخير</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900"><?= htmlspecialchars($worker['unpaid_streak_days'] ?? 0) ?> يوم</p>
                </div>
                <div class="mt-4 rounded-2xl bg-white p-4 shadow-sm border border-indigo-100">
                    <p class="text-sm text-slate-500">متوسط تقييم العملاء</p>
                    <p class="mt-2 text-2xl font-bold text-amber-500"><?= number_format($avgUserRating, 1) ?> / 5</p>
                    <p class="text-sm text-slate-400">من <?= $reviewCount ?> تقييم<?= $reviewCount === 1 ? '' : 'ات' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div id="root" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Left Sidebar: Worker Tools and Status -->
            <div id="worker-sidebar" class="lg:w-1/3 lg:sticky lg:top-20 sidebar">
                <div class="p-6 glass-card rounded-3xl shadow-xl border border-white/50 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-3 flex items-center">
                        <span id="icon-briefcase"></span> حالة العمل
                    </h2>
                    <p class="text-lg font-semibold text-emerald-600 mb-4"><?= htmlspecialchars($a) ?></p>
                    <form method="POST">
                    <input type="hidden" name="action" value="status">
                    <button id="toggle-availability"
                     class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition duration-300">
                     <?= htmlspecialchars($b) ?>
                    </button>
                    </form>
                </div>
                
                <div class="p-6 glass-card rounded-3xl shadow-xl border border-white/50">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-3 flex items-center">
                        <span id="icon-mappin-sidebar"></span> برجاء تحديد مكان العمل اليوم
                    </h2>
                    <p><?= htmlspecialchars($worker['city']) ?> : مكان العمل الحالي</p><br>
                    <form method="POST">
                    <input type="hidden" name="action" value="places">
                    <select name="places" id="places" required>
                        <option value="">اختر مدينة</option>
                        <option value="october">6 اكتوبر</option>
                        <option value="zayed">الشيخ زايد</option>
                    </select><br><br>
                    <button 
                        id="track-location-btn"
                        data-action="getGeolocation"
                        class="w-full mt-3 bg-red-500 text-white py-2 rounded-xl text-sm font-semibold hover:bg-red-600 transition duration-300 flex items-center justify-center">
                        <span id="icon-mappin-btn"></span> ارسال
                    </button>
                    </form>
                    <div id="worker-location-details" class="text-xs mt-1 text-gray-500 hidden"></div>
                </div>
            </div>

            <!-- Right Main Area: Incoming Orders List -->
            <div id="orders-list-area" class="lg:w-2/3">
                <?php if (!empty($myorder)): ?>
                    <?php foreach ($myorder as $order): ?>
                        <div class="p-6 bg-white/70 rounded-2xl shadow-lg border border-indigo-200/50 mb-4" data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="incoming">
                            <div class="flex justify-between items-start border-b pb-3 mb-3">
                                <div>
                                    <h4 class="text-xl font-extrabold text-gray-800">طلب خدمة جديد</h4>
                                    <span class="text-sm text-gray-600">العميل: <?= htmlspecialchars($order['username']) ?></span>
                                    </div>
                                <span class="text-xl font-bold text-indigo-600">
                                <?= htmlspecialchars($order['budget']) ?> جنيه
                                </span>
                        </div>

                        <p class="mb-4 text-gray-700 font-semibold">العنوان: <?= htmlspecialchars($order['address']) ?></p>

                        <?php if ($canAccept): ?>
                            <form method="POST" class="grid grid-cols-3 gap-3">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                
                                <button type="submit" name="accept" class="bg-emerald-500 text-white p-3 rounded-xl font-bold hover:bg-emerald-600 transition">
                                    قبول
                                </button>

                                <button type="submit" name="reject" class="bg-red-500 text-white p-3 rounded-xl font-bold hover:bg-red-600 transition">
                                   رفض
                                </button>

                                <a
                                    href="update_price.php?order_id=<?php echo $order['id']; ?>"
                                    class="bg-yellow-400 text-gray-800 p-3 rounded-xl font-bold hover:bg-yellow-500 transition">
                                    تعديل السعر
                                </a>
                            </form>
                        <?php else: ?>
                            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-right text-red-700">
                                لا يمكنك قبول طلبات جديدة حتى يتم حل حالة حسابك أو الموافقة عليه من الإدارة.
                            </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php elseif(!empty($orders)): ?>
                        <?php foreach ($orders as $myorder): ?>
                            <div class="p-6 bg-white/70 rounded-2xl shadow-lg border border-indigo-200/50 mb-4" data-order-id="<?= htmlspecialchars($myorder['id']) ?>" data-status="<?= htmlspecialchars($myorder['status']) ?>">
                                <div class="flex flex-col gap-4 border-b pb-3 mb-3">
                                    <div class="flex justify-between items-center gap-4 flex-wrap">
                                        <div>
                                            <h4 class="text-xl font-extrabold text-gray-800">خدمة <?= htmlspecialchars($myorder['specialization']) ?></h4>
                                            <p class="text-sm text-gray-600 mt-1">العميل: <?= htmlspecialchars($myorder['username']) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-slate-500">الحالة: <span class="font-semibold"><?= htmlspecialchars($myorder['status'] === 'completed' ? 'مكتمل' : 'مقبول') ?></span></p>
                                            <p class="text-2xl font-bold text-indigo-600"><?= htmlspecialchars($myorder['worker_price'] ?: $myorder['budget']) ?> جنيه</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <p class="text-gray-700 font-semibold">العنوان: <?= htmlspecialchars($myorder['address']) ?></p>
                                    <p class="text-gray-700 font-semibold">الوصف: <?= htmlspecialchars($myorder['description']) ?></p>
                                    <p class="text-gray-700 font-semibold">رقم الهاتف: <?= htmlspecialchars($myorder['user_phone'] ?? '') ?></p>
                                    <?php if ($myorder['status'] === 'completed'): ?>
                                        <div class="rounded-2xl bg-emerald-50 p-4 border border-emerald-200 text-emerald-700">
                                            هذه الخدمة مكتملة، والعمولة المستحقة هي <?= htmlspecialchars($myorder['commission_amount'] ?? 0) ?> EGP.
                                        </div>
                                        <?php if (!$myorder['user_reviewed'] || $myorder['user_reviewed'] === 'f'): ?>
                                            <form method="post" class="mt-4 space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                                                <input type="hidden" name="order_id" value="<?= $myorder['id'] ?>">
                                                <div>
                                                    <label class="block text-sm font-semibold mb-2">تقييم العميل</label>
                                                    <div class="star-rating">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input type="radio" id="worker-star-<?= $myorder['id'] ?>-<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                                            <label for="worker-star-<?= $myorder['id'] ?>-<?= $i ?>" aria-label="<?= $i ?> نجوم">★</label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-semibold mb-2">ملاحظة</label>
                                                    <textarea name="comment" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="اكتب ملاحظتك عن العميل"></textarea>
                                                </div>
                                                <button type="submit" name="review_user" class="bg-indigo-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-indigo-700 transition">إرسال تقييم العميل</button>
                                            </form>
                                        <?php else: ?>
                                            <div class="rounded-2xl bg-blue-50 p-4 border border-blue-200 text-blue-700">
                                                لقد قمت بالفعل بتقييم هذا العميل.
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($myorder['status'] === 'accepted'): ?>
                                        <form method="post" class="flex flex-wrap gap-3">
                                            <input type="hidden" name="order_id" value="<?= $myorder['id'] ?>">
                                            <button type="submit" name="complete" class="bg-emerald-600 text-white p-3 rounded-xl font-bold hover:bg-emerald-700 transition">إنهاء الطلب</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php else : ?>
                        <p class="text-center text-gray-500">لا توجد طلبات جديدة حالياً في مدينتك.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav">
        <div class="bottom-nav-container">
            <button class="nav-btn active" data-nav="tasks" onclick="navigateTo('tasks')">
                <span class="text-2xl">📋</span>
                <span>الطلبات</span>
            </button>
            <a class="nav-btn" data-nav="profile" href="profile.php">
                <span class="text-2xl">👤</span>
                <span>الملف الشخصي</span>
            </button>
            <a class="nav-btn" data-nav="payments" href="payments.php">
                <span class="text-2xl">💳</span>
                <span>المدفوعات</span>
            </button>
        </div>
    </nav>
    
    <!-- Counter-Offer Modal -->
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
                <div class="mb-6">
                    <label for="new-price" class="block text-sm font-medium text-gray-700 mb-2">السعر الجديد (EGP):</label>
                    <input type="number" id="new-price" name="worker_price" placeholder="أدخل السعر المقترح" min="1" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" />
                </div>

                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-800 font-semibold rounded-xl hover:bg-gray-400 transition duration-300">
                        إلغاء
                    </button>
                    <button id="submit-counter-offer" type="submit" name="price" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition duration-300 shadow-md shadow-indigo-500/50">
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
            submitBtn.dataset.requestId = requestId;
            modal.classList.remove("hidden");
        }

        function closeModal(originalPrice, requestId) {
            document.getElementById("original-price-value").textContent = originalPrice;
            submitBtn.dataset.requestId = requestId;
            modal.classList.add("hidden");
        }

        async function refreshWorkerOrders() {
            try {
                const response = await fetch('order_updates.php?view=worker');
                if (!response.ok) return;
                const html = await response.text();
                const ordersArea = document.getElementById('orders-list-area');
                if (ordersArea) {
                    ordersArea.innerHTML = html;
                }
            } catch (error) {
                console.error('Failed to refresh worker orders:', error);
            }
        }

        setInterval(refreshWorkerOrders, 8000);
        window.addEventListener('load', refreshWorkerOrders);

    </script>

</body>
</html>