<?php
session_start();
include("core/db.php");
include("core/config.php");

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/user/login.php");
    exit();
}

$id = $_SESSION['user_id'];
// Get order_id from URL
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null ;

if (!$order_id) {
    die("خطأ: رقم الطلب غير مفقود.");
}

$st = $conn->prepare("SELECT * FROM service_requests WHERE id = :id");
$st->bindParam(':id', $order_id, PDO::PARAM_INT);
$st->execute();
$order = $st->fetch(PDO::FETCH_ASSOC);

if($order){
    $order_budget = $order['budget'];
}

// 2. Fetch Worker Data
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    die("خطأ: لم يتم العثور على بيانات العامل.");
}

// 3. Handle Form Submission (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['price'])) {
    $price = $_POST['price'];
    
    if (is_numeric($price) && $price > 0) {
        $ss = $conn->prepare("UPDATE service_requests SET worker_price = :price, worker_id = :id, negotiation_state = 'countered', negotiation_started_at = COALESCE(negotiation_started_at, NOW()), negotiation_ended_at = NULL WHERE id = :order_id AND status = 'pending'");
        $ss->bindParam(':id', $id, PDO::PARAM_STR);
        $ss->bindParam(':price', $price, PDO::PARAM_STR);
        $ss->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        
        if ($ss->execute() && $ss->rowCount() > 0) {
            header("Location: pages/worker/worker_dashboard.php?success=1");
            exit();
        }
        $error = "تعذر تحديث السعر. ربما تم قبول الطلب أو لم يعد متاحًا.";
    } else {
        $error = "الرجاء إدخال سعر صحيح.";
    }
} else {
    $error = "الرجاء إدخال سعر صحيح."; 
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flix | تعديل السعر</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

</head>

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

<body>

    <div id="counter-offer-modal" class="fixed inset-0 z-[110] items-center justify-center modal-backdrop transition-opacity duration-300">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-lg mx-4 glass-card border border-indigo-200/50" dir="rtl">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">تقديم عرض سعر مضاد</h3>
            <p class="text-sm text-gray-600 mb-4">
                الرجاء إدخال السعر الجديد الذي تقترحه لهذه الخدمة.
            </p>
            <div id="original-price-display" class="mb-4 p-3 bg-indigo-50 rounded-lg text-sm font-semibold text-indigo-700">
                السعر الأصلي المطلوب: <span id="original-price-value" class="mr-1"></span> <?= htmlspecialchars($order_budget) ?> EGP
            </div>
            <form method="post" action="?order_id=<?php echo $order_id; ?>">
            <div class="mb-6">
                <label for="new-price" class="block text-sm font-medium text-gray-700 mb-2">السعر الجديد (EGP):</label>
                <input type="number" name="price" id="price" placeholder="أدخل السعر المقترح" min="1" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" required>
            </div>

            <div class="flex justify-end space-x-3 space-x-reverse">
                <a href="pages/worker/worker_dashboard.php"
                class="px-4 py-2 bg-gray-300 text-gray-800 font-semibold rounded-xl hover:bg-gray-400 transition duration-300">
                    إلغاء
                </a>
                <button id="submit-counter-offer" type="submit" data-request-id="" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition duration-300 shadow-md shadow-indigo-500/50">
                    إرسال العرض
                </button>
            </div>
            </form>
        </div>
    </div>
</body>
</html>