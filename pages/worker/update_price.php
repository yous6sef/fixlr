<?php
session_start();
include("../../core/db.php");
include("../../core/config.php");
include('../../core/lang.php');

// 1. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header("Location: pages/user/login.php");
    exit();
}

$lang = $_GET['lang'] ?? 'en';

$id = $_SESSION['user_id'];
// Get order_id from URL
$order_id = $_GET['order_id'] ;

if (!$order_id) {
    die("خطأ: رقم الطلب غير مفقود.");
}

$st = $conn->prepare("SELECT checking_fee FROM service_requests WHERE id = :id");
$st->bindParam(':id', $order_id, PDO::PARAM_INT);
$st->execute();
$order_bud = $st->fetch(PDO::FETCH_ASSOC);
$order_budget = $order_bud['checking_fee'] ?? 0;


// 2. Fetch Worker Data
$stmt = $conn->prepare("SELECT name,average_rating FROM workers WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);
$worker_name = $worker['name'];
$worker_average_rating = $worker['average_rating'] ?? 0;

if (!$worker) {
    die("خطأ: لم يتم العثور على بيانات العامل.");
}

// 3. Handle Form Submission (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['price'])) {
    $price = $_POST['price'];
    
    if (is_numeric($price) && $price > 0) {
        // First, get the original request_id to link worker submissions to it
        $getOriginalRequest = $conn->prepare("SELECT id, request_id FROM service_requests WHERE id = :order_id");
        $getOriginalRequest->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $getOriginalRequest->execute();
        $originalRequest = $getOriginalRequest->fetch(PDO::FETCH_ASSOC);
        
        if (!$originalRequest) {
            $error = "الطلب الأصلي غير موجود";
        } else {
            $requestIdToLink = $originalRequest['request_id'] ?? $originalRequest['id'];
            
            $ss = $conn->prepare("
        INSERT INTO service_requests (user_id, worker_id, worker_price, worker_name, status, worker_rating, problem_description, address, request_id)
        SELECT user_id, :worker_id, :price, :worker_name, status, :worker_average_rating, problem_description, address, :request_id_link
        FROM service_requests
        WHERE id = :order_id
        RETURNING id
    ");

            $ss->bindParam(':worker_id', $id, PDO::PARAM_STR);
            $ss->bindParam(':price', $price, PDO::PARAM_STR);
            $ss->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $ss->bindParam(':worker_name', $worker_name, PDO::PARAM_STR);
            $ss->bindParam(':worker_average_rating', $worker_average_rating, PDO::PARAM_STR);
            $ss->bindParam(':request_id_link', $requestIdToLink, PDO::PARAM_INT);

            
            if ($ss->execute()) {
                header("Location: ../../pages/worker/worker_dashboard.php?success=1&");
                exit();
            }
            $error = "تعذر تحديث السعر. ربما تم قبول الطلب أو لم يعد متاحًا.";
        }
    } else {
        $error = "الرجاء إدخال سعر صحيح.";
    }
} else {
    $error = "الرجاء إدخال سعر صحيح."; 
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'تعديل عرض السعر' : 'Update Service Price';
        $pageDescription = $lang === 'ar' ? 'عدل سعر الخدمة للعميل' : 'Submit your final service price quote';
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en&order_id=<?php echo htmlspecialchars($order_id); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar&order_id=<?php echo htmlspecialchars($order_id); ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'تعديل عرض السعر' : 'Update Service Price'; ?></h1>
            <p><?php echo $lang === 'ar' ? 'قدم سعرك النهائي للخدمة' : 'Submit your final service price quote'; ?></p>
        </div>

        <div class="card">
            <?php if (!empty($error)): ?>
                <div style="background: #FEE2E2; border: 1px solid #FECACA; color: #991B1B; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="?order_id=<?php echo htmlspecialchars($order_id); ?>&lang=<?php echo htmlspecialchars($lang); ?>">
                <div class="form-group">
                    <label class="form-label" for="price">
                        <?php echo $lang === 'ar' ? 'السعر الجديد (EGP)' : 'New Price (EGP)'; ?>
                    </label>
                    <input 
                        type="number" 
                        name="price" 
                        id="price" 
                        placeholder="<?php echo $lang === 'ar' ? 'أدخل السعر المقترح' : 'Enter your proposed price'; ?>" 
                        min="1" 
                        step="0.01"
                        class="form-input" 
                        required
                    >
                    <div style="font-size: 0.8rem; color: #8A9389; margin-top: 0.5rem;">
                        <?php echo $lang === 'ar' ? 'يجب أن يكون السعر أكبر من صفر' : 'Price must be greater than zero'; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <a href="../../pages/worker/worker_dashboard.php?lang=<?php echo htmlspecialchars($lang); ?>" class="btn btn-secondary" style="flex: 1;">
                        <?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?>
                    </a>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <?php echo $lang === 'ar' ? 'إرسال العرض' : 'Submit Offer'; ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'ملاحظات مهمة' : 'Important Notes'; ?></h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="padding: 0.5rem 0; color: #4A5249; font-size: 0.95rem; border-bottom: 1px solid #F7F8F6;">
                    • <?php echo $lang === 'ar' ? 'تأكد من صحة السعر قبل الإرسال' : 'Double-check your price before submitting'; ?>
                </li>
                <li style="padding: 0.5rem 0; color: #4A5249; font-size: 0.95rem; border-bottom: 1px solid #F7F8F6;">
                    • <?php echo $lang === 'ar' ? 'لا يمكن تعديل السعر بعد القبول' : 'Price cannot be modified after acceptance'; ?>
                </li>
                <li style="padding: 0.5rem 0; color: #4A5249; font-size: 0.95rem;">
                    • <?php echo $lang === 'ar' ? 'سيتم إخطار العميل بعرضك' : 'Customer will be notified of your offer'; ?>
                </li>
            </ul>
        </div>
    </div>
</body>
</html>