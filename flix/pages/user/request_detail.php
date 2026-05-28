<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? 'en';
$taskId = $_GET['id'] ?? null;
if (!$taskId) { header('Location: user_requests.php?lang=' . $lang); exit; }

$connection = $conn ?? null;
try {
    $res = pg_query_params($connection, "SELECT t.*, u.fullName as requesterName, wu.fullName as workerName FROM tasks t LEFT JOIN users u ON t.userId = u.id LEFT JOIN workers w ON t.workerId = w.id LEFT JOIN users wu ON w.userId = wu.id WHERE t.id = $1", [$taskId]);
    $task = pg_fetch_assoc($res);
} catch (Exception $e) {
    $task = false;
}

if (!$task) { header('Location: user_requests.php?lang=' . $lang); exit; }

// Authorization: allow owner or admin
if ($task['userId'] != $_SESSION['user_id'] && $_SESSION['user_type'] !== 'admin') {
    header('Location: user_requests.php?lang=' . $lang); exit;
}

function safeEcho($v) { return htmlspecialchars($v ?? ''); }

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Request Details'; ?> - FLIX</title>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style> .map-embed { width:100%; height:320px; border:0; margin-top:0.5rem; }</style>
</head>
<body>
    <div class="page-container">
        <div class="card">
            <h2><?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Request Details'; ?></h2>

            <div style="margin-top:1rem;">
                <strong><?php echo $lang === 'ar' ? 'التخصص' : 'Specialization'; ?>:</strong>
                <?php echo safeEcho($task['specialization']); ?>
            </div>

            <div style="margin-top:0.75rem;">
                <strong><?php echo $lang === 'ar' ? 'حالة الطلب' : 'Status'; ?>:</strong>
                <?php echo safeEcho($task['currentStatus']); ?>
            </div>

            <div style="margin-top:0.75rem;">
                <strong><?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?>:</strong>
                <p><?php echo nl2br(safeEcho($task['description'])); ?></p>
            </div>

            <div style="margin-top:0.75rem;">
                <strong><?php echo $lang === 'ar' ? 'وصف المشكلة' : 'Problem Description'; ?>:</strong>
                <p><?php echo nl2br(safeEcho($task['problemDescription'])); ?></p>
            </div>

            <div style="margin-top:0.75rem;">
                <strong><?php echo $lang === 'ar' ? 'العنوان' : 'Address'; ?>:</strong>
                <p><?php echo nl2br(safeEcho($task['address'])); ?></p>
            </div>

            <?php if (!empty($task['addressDescription'])): ?>
                <div style="margin-top:0.75rem;">
                    <strong><?php echo $lang === 'ar' ? 'تفاصيل الوصول' : 'Access Details'; ?>:</strong>
                    <p><?php echo nl2br(safeEcho($task['addressDescription'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($task['googleMapsLink'])): ?>
                <div style="margin-top:0.75rem;">
                    <strong><?php echo $lang === 'ar' ? 'موقع خرائط جوجل' : 'Google Maps Link'; ?>:</strong>
                    <div><a href="<?php echo safeEcho($task['googleMapsLink']); ?>" target="_blank"><?php echo safeEcho($task['googleMapsLink']); ?></a></div>

                    <?php
                    $g = $task['googleMapsLink'];
                    $embedSrc = null;
                    if (!empty($g) && filter_var($g, FILTER_VALIDATE_URL)) {
                        $host = parse_url($g, PHP_URL_HOST) ?: '';
                        if (preg_match('/(google\.|maps\.app\.goo\.gl|goo\.gl)/i', $host)) {
                            // Only embed when the path contains /maps
                            if (strpos($g, '/maps') !== false) {
                                if (strpos($g, '/embed') === false) {
                                    // Insert /embed after /maps safely
                                    $embedSrc = preg_replace('#/maps#', '/maps/embed', $g, 1);
                                } else {
                                    $embedSrc = $g;
                                }
                            }
                        }
                    }
                    if ($embedSrc) {
                        echo '<iframe class="map-embed" src="' . safeEcho($embedSrc) . '"></iframe>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div style="margin-top:1rem;">
                <a href="./user_requests.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo $lang === 'ar' ? 'العودة' : 'Back'; ?></a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../user/login.php'); exit(); }
include('../../core/lang.php');
$lang = $_GET['lang'] ?? 'en';
$request_id = $_GET['id'] ?? 1;
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
            <a href="?lang=en&id=<?php echo $request_id; ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar&id=<?php echo $request_id; ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Request Details'; ?></h1>
            <p><?php echo $lang === 'ar' ? 'رقم الطلب: #' . $request_id : 'Request ID: #' . $request_id; ?></p>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'معلومات الخدمة' : 'Service Information'; ?></h3>
            <div style="margin-bottom: 2rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'نوع الخدمة' : 'Service Type'; ?>
                    </label>
                    <div style="color: #141714; font-weight: 600;"><?php echo $lang === 'ar' ? 'أنابيب' : 'Plumbing'; ?></div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?>
                    </label>
                    <div style="color: #141714;"><?php echo $lang === 'ar' ? 'إصلاح تسرب مياه في المطبخ' : 'Fix water leak in kitchen'; ?></div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #8A9389; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <?php echo $lang === 'ar' ? 'السعر' : 'Price'; ?>
                    </label>
                    <div style="color: #1A6B4A; font-weight: 600; font-size: 1.25rem;">800 EGP</div>
                </div>
            </div>

            <h3><?php echo $lang === 'ar' ? 'العامل' : 'Worker'; ?></h3>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #F7F8F6; border-radius: 8px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #E8F5EE; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1A6B4A;">AM</div>
                <div>
                    <div style="color: #141714; font-weight: 600;">Ahmed Mohamed</div>
                    <div style="color: #8A9389; font-size: 0.875rem;">4.8 Rating • 45 Jobs</div>
                </div>
            </div>
        </div>

        <div style="padding-bottom: 100px;"></div>
    </div>
</body>
</html>
