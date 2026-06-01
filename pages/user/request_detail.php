<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "user") { 
    header('Location: ../user/login.php'); exit(); 
}
include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? 'en';
$taskId = $_GET['id'] ;

$reso = $conn->prepare("SELECT problem_description, user_id, worker_name, worker_rating, specialization, worker_price 
                        FROM service_requests WHERE id = ?");
$reso->execute([$taskId]);
$task = $reso->fetch(PDO::FETCH_ASSOC);

if (!$task) { header('Location: user_requests.php?lang=' . $lang); exit; }

$task_description     = $task['problem_description'] ?? '';
$task_user_id         = $task['user_id'] ?? '';
$task_worker_name     = $task['worker_name'] ?? '';
$task_worker_rating   = $task['working_rating'] ?? 0;
$task_specialization  = $task['specialization'] ?? '';
$task_worker_price    = $task['worker_price'] ?? 0;

// Authorization
if ($task_user_id != $_SESSION['user_id'] && $_SESSION['role'] !== 'user') {
    header('Location: user_requests.php?lang=' . $lang); exit;
}

// Messages
$task_worker_price_zero  = $lang === 'ar' ? 'لم يتم تحديد عامل حتى الان' : 'no worker yet';
$task_worker_rating_zero = $lang === 'ar' ? 'لم يتم تقييم العامل حتى الان' : 'no rating yet';
$task_worker_name_zero   = $lang === 'ar' ? 'لم يتم تحديد عامل حتى الان' : 'no worker yet';

function safeEcho($v) { return htmlspecialchars($v ?? ''); }
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Request Details'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Request Details'; ?></h1>
            <a href="./user_requests.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">
                <?php echo $lang === 'ar' ? 'رجوع' : 'Back'; ?>
            </a>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'معلومات الخدمة' : 'Service Information'; ?></h3>
            <div style="margin-bottom: 2rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block;color:#8A9389;font-size:0.875rem;margin-bottom:0.25rem;">
                        <?php echo $lang === 'ar' ? 'نوع الخدمة' : 'Service Type'; ?>
                    </label>
                    <div style="color:#141714;font-weight:600;"><?php echo safeEcho($task_specialization); ?></div>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;color:#8A9389;font-size:0.875rem;margin-bottom:0.25rem;">
                        <?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?>
                    </label>
                    <div style="color:#141714;"><?php echo nl2br(safeEcho($task_description)); ?></div>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;color:#8A9389;font-size:0.875rem;margin-bottom:0.25rem;">
                        <?php echo $lang === 'ar' ? 'السعر' : 'Price'; ?>
                    </label>
                    <div style="color:#1A6B4A;font-weight:600;font-size:1.25rem;">
                        <?php echo $task_worker_price > 0 
                            ? safeEcho($task_worker_price) . ' EGP' 
                            : $task_worker_price_zero; ?>
                    </div>
                </div>
            </div>

            <h3><?php echo $lang === 'ar' ? 'العامل' : 'Worker'; ?></h3>
            <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:#F7F8F6;border-radius:8px;">
                <?php if (!empty($task_worker_name) && $task_worker_name !== 'null'): ?>
                    <div style="width:50px;height:50px;border-radius:50%;background:#E8F5EE;display:flex;align-items:center;justify-content:center;font-weight:600;color:#1A6B4A;">
                        <?php echo strtoupper(substr($task_worker_name,0,2)); ?>
                    </div>
                    <div>
                        <div style="color:#141714;font-weight:600;"><?php echo safeEcho($task_worker_name); ?></div>
                        <div style="color:#8A9389;font-size:0.875rem;">
                            <?php echo $task_worker_rating > 0 
                                ? safeEcho($task_worker_rating) . ' Rating' 
                                : $task_worker_rating_zero; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="color:#8A9389;font-size:0.875rem;">
                        <?php echo $task_worker_name_zero; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
