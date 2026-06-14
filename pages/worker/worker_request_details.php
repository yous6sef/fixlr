<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
include('../../core/db.php');
$lang = $_GET['lang'] ?? 'en';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM workers WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);
$worker_city = $worker['city'];
$worker_specialization = $worker['specialization'];

$stmt = $conn->prepare("SELECT * FROM service_requests WHERE city = :city AND specialization = :specialization AND status = 'REQUESTED' LIMIT 20");
$stmt->bindParam(':city', $worker_city);
$stmt->bindParam(':specialization', $worker_specialization);
$stmt->execute();
$available_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM service_requests WHERE worker_id = :worker_id AND status = 'pricing' ");
$stmt->bindParam(':worker_id', $user_id);
$stmt->execute();
$ongoing_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(isset($_POST['accept_request'])) {
    $request_id = $_POST['request_id'];
    $stmt = $conn->prepare("UPDATE service_requests SET worker_id = :worker_id, status = 'pricing' WHERE id = :request_id");
    $stmt->bindParam(':worker_id', $user_id);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->execute();
    header('Location: worker_request_details.php?request_id=' . $request_id . '&lang=' . $lang);
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = isset($_GET['request_id']) 
            ? ($lang === 'ar' ? 'فرصة خدمة #' . intval($_GET['request_id']) : 'Service Opportunity #' . intval($_GET['request_id']))
            : ($lang === 'ar' ? 'تفاصيل الفرصة' : 'Opportunity Details');
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'الفرص المتاحة - طلبات الخدمات الجديدة' : 'Available Opportunities - New Requests'; ?></h1>
        </div>

        <div class="card">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($available_requests as $request): ?>
                <div class="provider-card">
                    <div class="provider-avatar">R1</div>
                    <div class="provider-info">
                        <div class="provider-name"><?php echo htmlspecialchars($request['problem_description']); ?></div>
                        <div class="provider-role"><?php echo htmlspecialchars($request['address_description']); ?></div>
                    </div>
                </div>

                <a href="update_price.php?order_id=<?php echo $request['id']; ?>" style="background: orange; color: white; padding: 0.75rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%;">
                    <?php echo $lang === 'ar' ? 'اضافة السعر' : 'Add Price'; ?>
                </a>

                <?php endforeach; ?>
            </div>
        </div><br><br>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
