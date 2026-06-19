<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../user/login.php'); exit();
}
include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? 'en';
$taskId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

$taskStmt = $conn->prepare("SELECT * FROM service_requests WHERE id = ? AND user_id = ? AND worker_price IS NOT NULL");
$taskStmt->execute([$taskId, $user_id]);
$task = $taskStmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header('Location: user_requests.php?lang=' . $lang); exit;
}

$rootRequestId = !empty($task['request_id']) ? intval($task['request_id']) : intval($task['id']);
$mainStmt = $conn->prepare("SELECT * FROM service_requests WHERE id = ?");
$mainStmt->execute([$rootRequestId]);
$mainRequest = $mainStmt->fetch(PDO::FETCH_ASSOC);

if (!$mainRequest || intval($mainRequest['user_id']) !== intval($user_id)) {
    header('Location: user_requests.php?lang=' . $lang); exit;
}

$offersStmt = $conn->prepare("SELECT sr.*, w.name AS worker_name, w.average_rating FROM service_requests sr LEFT JOIN workers w ON w.id = sr.worker_id WHERE sr.request_id = ? ORDER BY sr.created_at ASC");
$offersStmt->execute([$rootRequestId]);
$offers = $offersStmt->fetchAll(PDO::FETCH_ASSOC);

$assignedStmt = $conn->prepare("SELECT sr.*, w.name AS worker_name, w.average_rating FROM service_requests sr LEFT JOIN workers w ON w.id = sr.worker_id WHERE (sr.request_id = :root OR sr.id = :root) AND sr.status = 'accepted' ORDER BY sr.created_at DESC LIMIT 1");
$assignedStmt->bindParam(':root', $rootRequestId, PDO::PARAM_INT);
$assignedStmt->execute();
$assignedWorker = $assignedStmt->fetch(PDO::FETCH_ASSOC);

$task_description     = $mainRequest['problem_description'] ?? '';
$task_specialization  = $mainRequest['specialization'] ?? '';
$task_worker_price    = $assignedWorker['worker_price'] ?? 0;
$task_worker_name     = $assignedWorker['worker_name'] ?? '';
$task_worker_rating   = $assignedWorker['worker_rating'] ?? 0;

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
    <?php
        $pageTitle = $lang === 'ar'
            ? 'تفاصيل الطلب #' . intval($rootRequestId)
            : 'Request Details #' . intval($rootRequestId);
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        .offer-card { padding: 1rem; background: #F7F8F6; border-radius: 12px; margin-bottom: 1rem; }
        .offer-actions { display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:0.75rem; }
        .offer-actions button { min-width:120px; }
        .status-pill { display:inline-block; padding:0.35rem 0.75rem; border-radius:999px; font-size:0.85rem; }
        .status-pending { background:#FFF7D6; color:#A56C00; }
        .status-accepted { background:#DEF7EC; color:#166534; }
        .status-rejected { background:#FEE2E2; color:#991B1B; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en&id=<?php echo $taskId; ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar&id=<?php echo $taskId; ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'تفاصيل طلب الخدمة' : 'Service Request Details'; ?></h1>
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

            <?php if (!empty($assignedWorker)): ?>
                <div style="margin-top:1rem;">
                    <a href="./chat.php?lang=<?php echo $lang; ?>&request_id=<?php echo intval($rootRequestId); ?>" class="btn btn-primary btn-block">
                        <?php echo $lang === 'ar' ? 'الدردشة مع العامل المعين' : 'Chat with Assigned Worker'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'العروض الواردة' : 'Offers Received'; ?></h3>
            <?php if (empty($offers)): ?>
                <div style="color: #6B6F6B; padding: 1rem; background: #F7F8F6; border-radius: 8px;">
                    <?php echo $lang === 'ar' ? 'لم تتلقَ أي عروض بعد' : 'No offers yet'; ?>
                </div>
            <?php else: ?>
                <?php foreach ($offers as $offer): ?>
                    <?php $offerStatus = strtolower(trim($offer['status'] ?? '')); ?>
                    <?php $isPendingOffer = !in_array($offerStatus, ['accepted', 'rejected', 'completed']) && !empty($offer['worker_price']); ?>
                    <div class="offer-card">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                            <div style="flex:1;">
                                <div style="color:#141714;font-weight:600;font-size:1rem;">
                                    <?php echo safeEcho($offer['worker_name'] ?? ($lang === 'ar' ? 'فني مجهول' : 'Worker')); ?>
                                </div>
                                <div style="color:#6B7280;font-size:0.9rem; margin-top:0.25rem;">
                                    <?php echo htmlspecialchars(date('Y-m-d', strtotime($offer['created_at']))); ?>
                                    <?php if (!empty($offer['worker_rating'])): ?>
                                        • <?php echo safeEcho($offer['worker_rating']); ?> ★
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top:0.75rem;font-size:0.95rem;color:#141714;">
                                    <?php echo $lang === 'ar' ? 'السعر المقترح' : 'Proposed price'; ?>:
                                    <strong><?php echo safeEcho($offer['worker_price'] ? $offer['worker_price'] . ' EGP' : ($lang === 'ar' ? 'بدون سعر' : 'No price')); ?></strong>
                                </div>
                            </div>
                            <span class="status-pill <?php echo $offerStatus === 'accepted' ? 'status-accepted' : ($offerStatus === 'rejected' ? 'status-rejected' : 'status-pending'); ?>">
                                <?php echo safeEcho($offer['status'] ?: ($lang === 'ar' ? 'قيد الانتظار' : 'pending')); ?>
                            </span>
                        </div>

                        <?php if ($isPendingOffer): ?>
                            <div class="offer-actions">
                                <button class="btn btn-primary offer-action" data-action="accept_order" data-order-id="<?php echo intval($offer['id']); ?>">
                                    <?php echo $lang === 'ar' ? 'قبول العرض' : 'Accept Offer'; ?>
                                </button>
                                <button class="btn btn-secondary offer-action" data-action="reject_order" data-order-id="<?php echo intval($offer['id']); ?>">
                                    <?php echo $lang === 'ar' ? 'رفض العرض' : 'Reject Offer'; ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="statusMessage" style="margin-top:1rem;"></div>
    </div>

    <script>
        const statusMessage = document.getElementById('statusMessage');
        document.querySelectorAll('.offer-action').forEach(button => {
            button.addEventListener('click', async function () {
                const orderId = this.dataset.orderId;
                const action = this.dataset.action;
                statusMessage.textContent = '';
                const response = await fetch('../../api/api.php?action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'order_id=' + encodeURIComponent(orderId)
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    statusMessage.textContent = result.message || 'Unable to perform action.';
                    statusMessage.style.color = '#991B1B';
                }
            });
        });
    </script>
</body>
</html>
