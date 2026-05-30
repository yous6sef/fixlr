<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
include('../../core/db.php');

// Gather user-specific stats
$connection = $conn ?? null;
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$user_name = $stmt->fetch(PDO::FETCH_ASSOC);
$name = $user_name['name'];

$totalRequests = 0;
$completedRequests = 0;
$activeRequests = 0;
$totalSpent = 0.00;
$recentRequests = [];

$prepareQuery = function ($sql) {
    return preg_replace('/\$\d+/', '?', $sql);
};

$executeQuery = function ($connection, $sql, $params = []) use ($prepareQuery) {
    if ($connection instanceof PDO) {
        $stmt = $connection->prepare($prepareQuery($sql));
        $stmt->execute($params);
        return $stmt;
    }
    return pg_query_params($connection, $sql, $params);
};

try {
    $res = $executeQuery($connection, "SELECT COUNT(*) as cnt FROM tasks WHERE userId = $1", [$userId]);
    $row = $connection instanceof PDO ? $res->fetch(PDO::FETCH_ASSOC) : pg_fetch_assoc($res);
    $totalRequests = intval($row['cnt'] ?? 0);

    $res = $executeQuery($connection, "SELECT COUNT(*) as cnt FROM tasks WHERE userId = $1 AND currentStatus = 'COMPLETED'", [$userId]);
    $row = $connection instanceof PDO ? $res->fetch(PDO::FETCH_ASSOC) : pg_fetch_assoc($res);
    $completedRequests = intval($row['cnt'] ?? 0);

    $res = $executeQuery($connection, "SELECT COUNT(*) as cnt FROM tasks WHERE userId = $1 AND currentStatus NOT IN ('COMPLETED','CANCELLED','CANCELLED_AFTER_CHECK')", [$userId]);
    $row = $connection instanceof PDO ? $res->fetch(PDO::FETCH_ASSOC) : pg_fetch_assoc($res);
    $activeRequests = intval($row['cnt'] ?? 0);

    $res = $executeQuery($connection, "SELECT COALESCE(SUM(totalPrice),0) as sum FROM tasks WHERE userId = $1 AND currentStatus = 'COMPLETED'", [$userId]);
    $row = $connection instanceof PDO ? $res->fetch(PDO::FETCH_ASSOC) : pg_fetch_assoc($res);
    $totalSpent = floatval($row['sum'] ?? 0.00);

    $res = $executeQuery($connection, "SELECT t.id, t.specialization, t.createdAt, t.currentStatus, wu.fullName as workerName FROM tasks t LEFT JOIN workers w ON t.workerId = w.id LEFT JOIN users wu ON w.userId = wu.id WHERE t.userId = $1 ORDER BY t.createdAt DESC LIMIT 5", [$userId]);
    $recentRequests = $connection instanceof PDO ? $res->fetchAll(PDO::FETCH_ASSOC) : (pg_fetch_all($res) ?: []);
} catch (Exception $e) {
    // ignore DB errors for dashboard view
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        body { padding-bottom: 100px; }
        .header-content { margin-bottom: 1.5rem; }
        .greeting { color: black; margin-bottom: 0.3rem; }
        .user-name { font-size: 1.5rem; font-weight: 600; }
        @media (max-width: 480px) { .page-container { padding-bottom: 0.5rem; } }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="./user_dashboard.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="./user_dashboard.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <div class="header-content">
                <div class="greeting"><?php echo $lang === 'ar' ? 'صباح الخير' : 'Good morning'; ?></div>
                <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($totalRequests); ?></div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'الطلبات' : 'Requests'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($completedRequests); ?></div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'مكتملة' : 'Completed'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($activeRequests); ?></div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'نشطة' : 'Active'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($totalSpent, 2); ?> EGP</div>
                <div class="stat-label"><?php echo $lang === 'ar' ? 'المنفق' : 'Spent'; ?></div>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الطلبات الأخيرة' : 'Recent Requests'; ?></h3>
            <?php if (!empty($recentRequests)): ?>
                <?php foreach ($recentRequests as $r): ?>
                    <a href="./request_detail.php?lang=<?php echo $lang; ?>&id=<?php echo htmlspecialchars($r['id']); ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #F7F8F6; border-radius: 8px; text-decoration: none; color: inherit; margin-bottom: 0.75rem;">
                        <div>
                            <div style="color: #141714; font-weight: 600;"><?php echo htmlspecialchars($r['specialization'] ?? ($lang === 'ar' ? 'طلب' : 'Request')); ?></div>
                            <div style="color: #8A9389; font-size: 0.875rem;">
                                <?php echo htmlspecialchars(($r['workerName'] ?? ($lang === 'ar' ? 'غير معين' : 'Unassigned')) . ' • ' . date('Y-m-d', strtotime($r['createdAt']))); ?>
                            </div>
                        </div>
                        <span class="badge <?php echo $r['currentStatus'] === 'COMPLETED' ? 'badge-success' : ($r['currentStatus'] === 'CANCELLED' ? 'badge-danger' : 'badge-active'); ?>">
                            <?php echo htmlspecialchars($r['currentStatus']); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color: #6B6F6B;"><?php echo $lang === 'ar' ? 'لا يوجد طلبات حالياً' : 'No recent requests'; ?></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'الإجراءات السريعة' : 'Quick Actions'; ?></h3>
            <a href="../../order.php?lang=<?php echo $lang; ?>" class="btn btn-primary btn-block"><?php echo $lang === 'ar' ? 'طلب جديد' : 'New Request'; ?></a>
            <a href="./user_requests.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'عرض الطلبات' : 'View Requests'; ?></a>
            <a href="./profile.php?lang=<?php echo $lang; ?>" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'الملف الشخصي' : 'Profile'; ?></a>
            <a href="./logout.php" class="btn btn-secondary btn-block"><?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Sign Out'; ?></a>
        </div>
    </div>
</body>
</html>
