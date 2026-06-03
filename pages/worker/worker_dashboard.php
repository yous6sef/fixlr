<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker')
    { header('Location: ../user/login.php'); exit(); }

include('../../core/lang.php');
include('../../core/db.php');
$lang = $_GET['lang'] ?? 'en';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name FROM workers WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$worker = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT total_completed_tasks FROM workers WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$total_completed_tasks = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT city,specialization,average_rating FROM workers WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$worker2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
$worker_city = $worker2[0]['city'];
$worker_specialization = $worker2[0]['specialization'];
$worker_rating = $worker2[0]['average_rating'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM service_requests WHERE city = :city AND specialization = :specialization AND status = 'REQUESTED' LIMIT 20");
$stmt->bindParam(':city', $worker_city);
$stmt->bindParam(':specialization', $worker_specialization);
$stmt->execute();
$available_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM service_requests WHERE id = :worker_id");
$stmt->bindParam(':worker_id', $user_id);
$stmt->execute();
$ongoing_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="./worker_dashboard.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="./worker_dashboard.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'مرحبا ' . htmlspecialchars($worker[0]['name']) : 'Hi ' . htmlspecialchars($worker[0]['name']); ?></h1>
            <p><?php echo $lang === 'ar' ? 'الطلبات المتاحة' : 'Available Opportunities'; ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($available_requests); ?></div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'متاح' : 'Available'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($ongoing_requests); ?></div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'جاري' : 'Ongoing'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($total_completed_tasks['total_completed_tasks']); ?></div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($worker_rating); ?></div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'التقييم' : 'Rating'; ?></div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الطلبات المتاحة' : 'Available Jobs'; ?></h3>
            <?php foreach ($available_requests as $request): ?>
            <div class="provider-card">
                <div class="provider-avatar" style="background: #E8F5EE;">P1</div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $request['problem_description']; ?></div>
                    <div class="provider-role"><?php echo $request['address_description']; ?></div>
                </div>
                <div class="provider-price">300 EGP</div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الإجراءات' : 'Actions'; ?></h3>
            <a href="./worker_available_requests.php?lang=<?php echo $lang; ?>" class="btn btn-primary btn-block"><?php echo $lang === 'ar' ? 'عرض الفرص' : 'Browse Opportunities'; ?></a>
            <a href="./worker_orders.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'الطلبات المقبولة' : 'My Jobs'; ?></a>
            <a href="./worker_payments.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'الأرباح' : 'Earnings'; ?></a>
            <a href="../user/logout.php" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Sign Out'; ?></a>
        </div>
    </div>
</body>
</html>
