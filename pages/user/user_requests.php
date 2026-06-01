<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
include('../../core/db.php');

$connection = $conn ?? null;
$userId = $_SESSION['user_id'];
$userRequests = [];

$stmt = $conn->prepare("SELECT worker_id FROM service_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
$worker_id = $recentRequests[0]['worker_id'] ?? null;

if ($worker_id) {
    $stmt = $conn->prepare("SELECT name FROM workers WHERE id = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch(PDO::FETCH_ASSOC);
    $worker_name = $worker['name'] ?? null;
}

try {
    $stmt = $conn->prepare("SELECT * FROM service_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $userRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'طلباتي' : 'My Requests'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'طلباتي' : 'My Requests'; ?></h1>
        </div>

        <div class="card">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php if (!empty($userRequests)): ?>
                    <?php foreach ($userRequests as $r): ?>
                        <a href="./request_detail.php?lang=<?php echo $lang; ?>&id=<?php echo htmlspecialchars($r['id']); ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px; text-decoration: none; color: inherit;">
                            <div>
                                <div style="color: #141714; font-weight: 600;"><?php echo htmlspecialchars($r['specialization'] ?? ($lang === 'ar' ? 'طلب' : 'Request')); ?></div>
                                <div style="color: #8A9389; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars(date('Y-m-d', strtotime($r['created_at'])) . ' • ' . ($worker_name ?? ($lang === 'ar' ? 'غير معين' : 'Unassigned'))); ?>
                                </div>
                            </div>
                            <span class="badge <?php echo $r['status'] === 'COMPLETED' ? 'badge-success' : ($r['status'] === 'CANCELLED' ? 'badge-danger' : 'badge-active'); ?>">
                                <?php echo htmlspecialchars($r['status']); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color: #6B6F6B;"><?php echo $lang === 'ar' ? 'لا توجد طلبات' : 'No requests yet'; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
