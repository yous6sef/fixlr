<?php
session_start();
include('lang.php');
include('db.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?lang=' . $lang);
    exit;
}

// Get task ID
$taskId = $_GET['taskId'] ?? $_POST['taskId'] ?? null;
if (!$taskId) {
    header('Location: user_dashboard.php?lang=' . $lang);
    exit;
}

// Get task details
$DEMO_MODE = true;
$connection = null;

if ($DEMO_MODE) {
    // Use mock task data
    $task = [
        'id' => $taskId,
        'userId' => $_SESSION['user_id'],
        'specialization' => 'Plumbing',
        'description' => 'Water leaking from the main kitchen sink. The pipes need inspection and repair.',
        'address' => '123 Nile Street, Cairo, Egypt',
        'currentStatus' => 'REQUESTED',
        'urgency' => 'Normal',
        'checkingFee' => 300,
        'fixingPrice' => 0,
        'totalPrice' => 300,
        'userName' => 'Ahmed Hassan',
        'workerName' => null,
        'workerPhone' => null,
        'workerId' => null,
        'createdAt' => date('Y-m-d H:i:s')
    ];
} else {
    $taskQuery = "SELECT t.*, u.fullName as userName, w.id as workerId, wu.fullName as workerName, wu.phoneNumber as workerPhone
                 FROM tasks t
                 LEFT JOIN users u ON t.userId = u.id
                 LEFT JOIN workers w ON t.workerId = w.id
                 LEFT JOIN users wu ON w.userId = wu.id
                 WHERE t.id = $1";

    $taskResult = pg_query_params($connection, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
}

if (!$task) {
    header('Location: user_dashboard.php?lang=' . $lang);
    exit;
}

// Check authorization
if ($task['userId'] !== $_SESSION['user_id'] && $_SESSION['user_type'] !== 'admin') {
    header('Location: user_dashboard.php?lang=' . $lang);
    exit;
}

// Define state progression
$stateOrder = ['REQUESTED', 'ACCEPTED', 'ARRIVED', 'ARRIVAL_CONFIRMED', 'CHECKING', 'CHECKING_COMPLETED', 'DECISION', 'PRICE_PROPOSED', 'PRICE_ACCEPTED', 'FIXING', 'COMPLETED'];

$currentStateIndex = array_search($task['currentStatus'], $stateOrder);

// Handle user actions
$actionMessage = '';
$actionError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    try {
        switch ($action) {
            case 'confirm_arrival':
                if (!$DEMO_MODE) {
                    $result = pg_query_params($connection,
                        "UPDATE tasks SET currentStatus = 'ARRIVAL_CONFIRMED' WHERE id = $1",
                        [$taskId]
                    );
                } else {
                    $task['currentStatus'] = 'ARRIVAL_CONFIRMED';
                    $result = true;
                }
                if ($result) {
                    $actionMessage = $lang === 'ar' ? 'تم تأكيد الوصول' : 'Arrival confirmed';
                    header('Refresh:2; url=track.php?taskId=' . $taskId . '&lang=' . $lang);
                }
                break;

            case 'proceed_with_fix':
                if (!$DEMO_MODE) {
                    $result = pg_query_params($connection,
                        "UPDATE tasks SET currentStatus = 'DECISION', userDecisionProceedWithFix = true WHERE id = $1",
                        [$taskId]
                    );
                } else {
                    $task['currentStatus'] = 'DECISION';
                    $result = true;
                }
                if ($result) {
                    $actionMessage = $lang === 'ar' ? 'تم الموافقة على المتابعة' : 'Proceeding with fix';
                    header('Refresh:2; url=track.php?taskId=' . $taskId . '&lang=' . $lang);
                }
                break;

            case 'cancel_task':
                if (!$DEMO_MODE) {
                    $result = pg_query_params($connection,
                        "UPDATE tasks SET currentStatus = 'CANCELLED', userDecisionProceedWithFix = false WHERE id = $1",
                        [$taskId]
                    );
                } else {
                    $task['currentStatus'] = 'CANCELLED';
                    $result = true;
                }
                if ($result) {
                    $actionMessage = $lang === 'ar' ? 'تم إلغاء الطلب' : 'Task cancelled';
                    header('Refresh:2; url=track.php?taskId=' . $taskId . '&lang=' . $lang);
                }
                break;

            case 'accept_price':
                if (!$DEMO_MODE) {
                    $result = pg_query_params($connection,
                        "UPDATE tasks SET currentStatus = 'PRICE_ACCEPTED' WHERE id = $1",
                        [$taskId]
                    );
                } else {
                    $task['currentStatus'] = 'PRICE_ACCEPTED';
                    $result = true;
                }
                if ($result) {
                    $actionMessage = $lang === 'ar' ? 'تم قبول السعر' : 'Price accepted';
                    header('Refresh:2; url=track.php?taskId=' . $taskId . '&lang=' . $lang);
                }
                break;

            case 'confirm_completion':
                if (!$DEMO_MODE) {
                    $result = pg_query_params($connection,
                        "UPDATE tasks SET currentStatus = 'COMPLETED' WHERE id = $1",
                        [$taskId]
                    );
                } else {
                    $task['currentStatus'] = 'COMPLETED';
                    $result = true;
                }
                if ($result) {
                    $actionMessage = $lang === 'ar' ? 'تم تأكيد الاكتمال' : 'Completion confirmed';
                    header('Refresh:2; url=track.php?taskId=' . $taskId . '&lang=' . $lang);
                }
                break;

            case 'submit_rating':
                $rating = $_POST['rating'] ?? 0;
                $comment = $_POST['comment'] ?? '';
                
                if (!$DEMO_MODE) {
                    $ratingQuery = "INSERT INTO ratings (taskId, ratedByUserId, ratedToWorkerId, userRating, userComment)
                                   VALUES ($1, $2, $3, $4, $5)
                                   ON CONFLICT (taskId, ratedByUserId) DO UPDATE
                                   SET userRating = $4, userComment = $5";
                    
                    $result = pg_query_params($connection, $ratingQuery, [
                        $taskId,
                        $_SESSION['user_id'],
                        $task['workerId'],
                        $rating,
                        $comment
                    ]);
                } else {
                    $userRating = ['userRating' => $rating, 'userComment' => $comment];
                    $result = true;
                }
                
                if ($result) {
                    $actionMessage = $lang === 'ar' ? 'تم تقديم التقييم' : 'Rating submitted';
                    header('Refresh:2; url=track.php?taskId=' . $taskId . '&lang=' . $lang);
                }
                break;
        }
    } catch (Exception $e) {
        $actionError = $lang === 'ar' ? 'حدث خطأ: ' : 'Error: ' . $e->getMessage();
    }
}

// Get current rating if exists
if (!$DEMO_MODE) {
    $ratingQuery = "SELECT * FROM ratings WHERE taskId = $1 AND ratedByUserId = $2";
    $ratingResult = pg_query_params($connection, $ratingQuery, [$taskId, $_SESSION['user_id']]);
    $userRating = pg_fetch_assoc($ratingResult);
} else {
    $userRating = null; // In demo mode, no pre-existing rating
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'تتبع الطلب' : 'Track Request'; ?> - FLIX</title>
    <link rel="stylesheet" href="css/app.css">
    <style>
        :root {
            --primary: #1A6B4A;
            --primary-light: #2D9A6C;
            --surface: #FFFFFF;
            --surface-light: #F7F8F6;
            --warning: #F59E0B;
            --success: #16A34A;
        }

        body {
            background: var(--surface-light);
        }

        .track-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem 1rem;
        }

        .track-card {
            background: var(--surface);
            border-radius: 14px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #E5E4E1;
            padding-bottom: 1.5rem;
        }

        .header h1 {
            color: var(--primary);
            font-size: 1.5rem;
            margin: 0;
        }

        .task-id {
            color: #8A9389;
            font-size: 0.9rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-pending {
            background: #FEF3E2;
            color: #9A6400;
        }

        .status-active {
            background: #EFF6FF;
            color: #1E40AF;
        }

        .status-completed {
            background: #E8F5EE;
            color: var(--primary);
        }

        .status-cancelled {
            background: #FEE2E2;
            color: #DC2626;
        }

        .timeline {
            position: relative;
            padding: 2rem 0;
            margin: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #D4D3D0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 50px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 5px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #D4D3D0;
            border: 3px solid var(--surface);
        }

        .timeline-item.active::before {
            background: var(--primary);
        }

        .timeline-item.completed::before {
            background: var(--success);
        }

        .timeline-label {
            color: #8A9389;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .timeline-content {
            color: #141714;
            font-weight: 500;
        }

        .worker-info {
            background: var(--surface-light);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #E5E4E1;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #8A9389;
            font-weight: 500;
        }

        .info-value {
            color: #141714;
            font-weight: 600;
        }

        .price-section {
            background: #E8F5EE;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary);
        }

        .price-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            color: #141714;
        }

        .price-total {
            border-top: 2px solid var(--primary);
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 1.5rem 0;
        }

        .btn-action {
            flex: 1;
            min-width: 150px;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-action {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .btn-secondary-action {
            background: #E5E4E1;
            color: #141714;
        }

        .btn-danger-action {
            background: #DC2626;
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .rating-section {
            background: var(--surface-light);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .rating-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .star-rating {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .star {
            font-size: 2rem;
            cursor: pointer;
            transition: all 0.2s;
            color: #D4D3D0;
        }

        .star:hover,
        .star.selected {
            color: #FFB800;
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            color: #141714;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D4D3D0;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .message.success {
            background: #E8F5EE;
            color: var(--success);
            border: 1px solid #D4E8E0;
        }

        .message.error {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        @media (max-width: 640px) {
            .track-container {
                padding: 1rem;
            }

            .track-card {
                padding: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                min-width: auto;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="track-container">
        <div class="track-card">
            <div class="header">
                <div>
                    <h1><?php echo $lang === 'ar' ? 'تتبع الطلب' : 'Track Request'; ?></h1>
                    <div class="task-id"><?php echo $lang === 'ar' ? 'رقم الطلب:' : 'Request #'; ?><?php echo $taskId; ?></div>
                </div>
                <span class="status-badge <?php
                    echo match($task['currentStatus']) {
                        'REQUESTED', 'ACCEPTED', 'ARRIVED', 'ARRIVAL_CONFIRMED' => 'status-pending',
                        'CHECKING', 'CHECKING_COMPLETED', 'DECISION', 'PRICE_PROPOSED', 'PRICE_ACCEPTED', 'FIXING' => 'status-active',
                        'COMPLETED' => 'status-completed',
                        default => 'status-cancelled'
                    };
                ?>">
                    <?php echo $task['currentStatus']; ?>
                </span>
            </div>

            <?php if ($actionMessage): ?>
                <div class="message success">✓ <?php echo htmlspecialchars($actionMessage); ?></div>
            <?php endif; ?>
            <?php if ($actionError): ?>
                <div class="message error">✗ <?php echo htmlspecialchars($actionError); ?></div>
            <?php endif; ?>

            <!-- Timeline -->
            <div class="timeline">
                <?php foreach ($stateOrder as $index => $state): ?>
                    <div class="timeline-item <?php echo $index <= $currentStateIndex ? 'completed' : ''; ?> <?php echo $state === $task['currentStatus'] ? 'active' : ''; ?>">
                        <div class="timeline-label"><?php echo $state; ?></div>
                        <div class="timeline-content">
                            <?php 
                            switch ($state) {
                                case 'REQUESTED': echo $lang === 'ar' ? 'جاري البحث عن عمال' : 'Looking for workers'; break;
                                case 'ACCEPTED': echo $lang === 'ar' ? 'قَبِل العامل الطلب' : 'Worker accepted'; break;
                                case 'ARRIVED': echo $lang === 'ar' ? 'وصل العامل' : 'Worker arrived'; break;
                                case 'ARRIVAL_CONFIRMED': echo $lang === 'ar' ? 'تم تأكيد الوصول' : 'Arrival confirmed'; break;
                                case 'CHECKING': echo $lang === 'ar' ? 'جاري الفحص' : 'Checking in progress'; break;
                                case 'CHECKING_COMPLETED': echo $lang === 'ar' ? 'انتهى الفحص' : 'Checking completed'; break;
                                case 'DECISION': echo $lang === 'ar' ? 'في انتظار قرارك' : 'Awaiting your decision'; break;
                                case 'PRICE_PROPOSED': echo $lang === 'ar' ? 'اقتراح السعر' : 'Price proposed'; break;
                                case 'PRICE_ACCEPTED': echo $lang === 'ar' ? 'تم قبول السعر' : 'Price accepted'; break;
                                case 'FIXING': echo $lang === 'ar' ? 'جاري الإصلاح' : 'Fixing in progress'; break;
                                case 'COMPLETED': echo $lang === 'ar' ? 'مكتمل' : 'Completed'; break;
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Task Details -->
            <div class="worker-info">
                <div class="info-row">
                    <span class="info-label"><?php echo $lang === 'ar' ? 'التخصص' : 'Specialization'; ?></span>
                    <span class="info-value"><?php echo htmlspecialchars($task['specialization']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php echo $lang === 'ar' ? 'الأولوية' : 'Urgency'; ?></span>
                    <span class="info-value"><?php echo htmlspecialchars($task['urgency'] ?? 'Normal'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php echo $lang === 'ar' ? 'العنوان' : 'Address'; ?></span>
                    <span class="info-value"><?php echo htmlspecialchars($task['address'] ?? ''); ?></span>
                </div>
            </div>

            <!-- Worker Info (when assigned) -->
            <?php if ($task['workerName']): ?>
                <div class="worker-info">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">
                        <?php echo $lang === 'ar' ? 'معلومات العامل' : 'Worker Information'; ?>
                    </h3>
                    <div class="info-row">
                        <span class="info-label"><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></span>
                        <span class="info-value"><?php echo htmlspecialchars($task['workerName']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?php echo $lang === 'ar' ? 'الهاتف' : 'Phone'; ?></span>
                        <span class="info-value"><?php echo htmlspecialchars($task['workerPhone']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Price Information (when proposed) -->
            <?php if (in_array($task['currentStatus'], ['PRICE_PROPOSED', 'PRICE_ACCEPTED', 'FIXING', 'COMPLETED'])): ?>
                <div class="price-section">
                    <div class="price-title"><?php echo $lang === 'ar' ? 'تفاصيل الأسعار' : 'Price Details'; ?></div>
                    <div class="price-row">
                        <span><?php echo $lang === 'ar' ? 'رسم الفحص' : 'Inspection Fee'; ?></span>
                        <span>300 EGP</span>
                    </div>
                    <div class="price-row">
                        <span><?php echo $lang === 'ar' ? 'سعر الإصلاح' : 'Fixing Price'; ?></span>
                        <span><?php echo number_format($task['fixingPrice'] ?? 0, 2); ?> EGP</span>
                    </div>
                    <div class="price-row price-total">
                        <span><?php echo $lang === 'ar' ? 'المجموع' : 'Total'; ?></span>
                        <span><?php echo number_format(($task['fixingPrice'] ?? 0) + 300, 2); ?> EGP</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons (based on state) -->
            <?php if ($task['currentStatus'] === 'ARRIVAL_CONFIRMED' && $task['userId'] === $_SESSION['user_id']): ?>
                <div class="action-buttons">
                    <form method="POST" style="flex: 1; min-width: 150px;">
                        <input type="hidden" name="action" value="proceed_with_fix">
                        <button type="submit" class="btn-action btn-primary-action">
                            <?php echo $lang === 'ar' ? 'متابعة الإصلاح' : 'Proceed with Fix'; ?>
                        </button>
                    </form>
                    <form method="POST" style="flex: 1; min-width: 150px;">
                        <input type="hidden" name="action" value="cancel_task">
                        <button type="submit" class="btn-action btn-danger-action" onclick="return confirm('<?php echo $lang === 'ar' ? 'هل تريد إلغاء الطلب؟' : 'Are you sure?'; ?>')">
                            <?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?>
                        </button>
                    </form>
                </div>
            <?php elseif ($task['currentStatus'] === 'PRICE_PROPOSED' && $task['userId'] === $_SESSION['user_id']): ?>
                <div class="action-buttons">
                    <form method="POST" style="flex: 1; min-width: 150px;">
                        <input type="hidden" name="action" value="accept_price">
                        <button type="submit" class="btn-action btn-primary-action">
                            <?php echo $lang === 'ar' ? 'قبول السعر' : 'Accept Price'; ?>
                        </button>
                    </form>
                </div>
            <?php elseif ($task['currentStatus'] === 'FIXING' && $task['userId'] === $_SESSION['user_id']): ?>
                <div class="action-buttons">
                    <form method="POST" style="flex: 1; min-width: 150px;">
                        <input type="hidden" name="action" value="confirm_completion">
                        <button type="submit" class="btn-action btn-primary-action">
                            <?php echo $lang === 'ar' ? 'تأكيد الاكتمال' : 'Confirm Completion'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Rating Section (when completed) -->
            <?php if ($task['currentStatus'] === 'COMPLETED' && $task['userId'] === $_SESSION['user_id']): ?>
                <div class="rating-section">
                    <div class="rating-title">
                        <?php echo $lang === 'ar' ? 'قيّم هذا العامل' : 'Rate this Worker'; ?>
                    </div>
                    
                    <?php if ($userRating): ?>
                        <p><?php echo $lang === 'ar' ? 'شكراً لتقييمك!' : 'Thank you for your rating!'; ?></p>
                        <div class="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $userRating['userRating'] ? 'selected' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <p style="color: #4A5249; font-size: 0.9rem;"><?php echo htmlspecialchars($userRating['userComment']); ?></p>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_rating">
                            <input type="hidden" name="rating" id="ratingValue" value="5">
                            
                            <div class="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i === 5 ? 'selected' : ''; ?>" onclick="setRating(<?php echo $i; ?>)">★</span>
                                <?php endfor; ?>
                            </div>

                            <div class="form-group">
                                <label><?php echo $lang === 'ar' ? 'تعليقك (اختياري)' : 'Your Comment (Optional)'; ?></label>
                                <textarea name="comment" placeholder="<?php echo $lang === 'ar' ? 'شارك تجربتك...' : 'Share your experience...'; ?>"></textarea>
                            </div>

                            <button type="submit" class="btn-action btn-primary-action">
                                <?php echo $lang === 'ar' ? 'إرسال التقييم' : 'Submit Rating'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="user_dashboard.php?lang=<?php echo $lang; ?>" style="color: #4A5249; text-decoration: none;">
                    ← <?php echo $lang === 'ar' ? 'العودة إلى لوحة التحكم' : 'Back to Dashboard'; ?>
                </a>
            </div>
        </div>
    </div>

    <script>
        function setRating(value) {
            document.getElementById('ratingValue').value = value;
            const stars = document.querySelectorAll('.star-rating .star');
            stars.forEach((star, index) => {
                if (index < value) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }
    </script>
</body>
</html>
