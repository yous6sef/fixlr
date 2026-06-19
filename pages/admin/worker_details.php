<?php
session_start();
include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;

// Admin guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../user/login.php?lang=' . $lang);
    exit;
}

$workerId = $_GET['id'] ?? null;
if (!$workerId) {
    header('Location: admin_dashboard.php?lang=' . $lang);
    exit;
}

$stmt = $conn->prepare("SELECT name, email, phone, profile_image_url, bio, specialization, city, location, national_id, status, id_front_path, id_back_path, certificate_path FROM workers WHERE id = ? LIMIT 1");
$stmt->execute([$workerId]);
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    header('Location: admin_dashboard.php?lang=' . $lang);
    exit;
}

$specs = [];
if (!empty($worker['specializations'])) {
    $specs = json_decode($worker['specializations'], true);
    if (!is_array($specs)) $specs = [];
}

function esc($s) { return htmlspecialchars($s ?? ''); }
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php
        $pageTitle = isset($worker) && !empty($worker['name']) 
            ? ($lang === 'ar' ? 'تفاصيل الفني - ' . htmlspecialchars($worker['name']) : 'Worker Details - ' . htmlspecialchars($worker['name']))
            : ($lang === 'ar' ? 'تفاصيل الفني' : 'Worker Details');
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        .details-container { max-width:1100px; margin:2rem auto; padding:1.5rem; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; }
        .back-link { text-decoration:none; color:#1A6B4A; font-weight:600; }
        .card { background:#fff; padding:1.25rem; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.06); }
        .btn-action { padding:0.65rem 1.2rem; border-radius:8px; border:none; font-weight:600; cursor:pointer; font-size:0.95rem; transition:all 0.3s ease; }
        .btn-accept { background:#10b981; color:#fff; margin-left:0.5rem; }
        .btn-accept:hover { background:#059669; box-shadow:0 4px 12px rgba(16,185,129,0.3); }
        .btn-reject { background:#ef4444; color:#fff; margin-left:0.5rem; }
        .btn-reject:hover { background:#dc2626; box-shadow:0 4px 12px rgba(239,68,68,0.3); }
        .action-buttons { display:flex; align-items:center; }
        @media (max-width:600px) { .action-buttons { flex-wrap:wrap; } .btn-action { padding:0.5rem 0.9rem; font-size:0.85rem; } }
        .grid { display:grid; grid-template-columns: 320px 1fr; gap:1.25rem; }
        .profile-img { width:100%; height:320px; object-fit:cover; border-radius:10px; background:#f3f4f6; display:block; }
        .meta { margin-top:0.75rem; }
        .meta h2 { margin:0; color:#0f5132; }
        .meta p { margin:0.25rem 0; color:#4b5563; }
        .info-row { display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem; }
        .info-item { background:#f8faf8; padding:0.75rem 1rem; border-radius:8px; color:#265f45; font-weight:600; }
        .docs-list a { display:inline-block; margin-right:0.75rem; margin-bottom:0.5rem; color:#084c30; text-decoration:underline; }
        .section-title { font-size:1.1rem; font-weight:700; color:#154f3b; margin-bottom:0.75rem; }
        .labels { color:#6b7280; font-size:0.95rem; }
        .value { color:#111827; font-weight:600; }
        @media (max-width:800px) { .grid { grid-template-columns: 1fr; } .profile-img { height:260px; } }
    </style>
</head>
<body>
    <div class="details-container">
        <div class="topbar">
            <a class="back-link" href="admin_dashboard.php?lang=<?php echo $lang; ?>">← <?php echo $lang === 'ar' ? 'عودة' : 'Back to dashboard'; ?></a>
            <div>
                <a class="btn-small" href="admin_dashboard.php?lang=<?php echo $lang; ?>"><?php echo $lang === 'ar' ? 'قائمة العمال' : 'Workers'; ?></a>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <?php if (!empty($worker['profileImage'])): ?>
                    <img src="<?php echo esc($worker['profileImage']); ?>" alt="<?php echo esc($worker['fullName']); ?>" class="profile-img">
                <?php else: ?>
                    <div class="profile-img" style="display:flex;align-items:center;justify-content:center;font-size:2rem;color:#065f46;font-weight:700;">
                        <?php echo strtoupper(substr($worker['fullName'] ?? 'N',0,1)); ?>
                    </div>
                <?php endif; ?>

                <div class="meta">
                    <h2><?php echo esc($worker['name']); ?></h2>
                    <p class="labels"><?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?>: <span class="value"><?php echo esc($worker['email'] ?? ''); ?></span></p>
                    <p class="labels"><?php echo $lang === 'ar' ? 'الهاتف' : 'Phone'; ?>: <span class="value"><?php echo esc($worker['phone']); ?></span></p>
                </div>

                <div style="margin-top:1rem;">
                    <div class="section-title"><?php echo $lang === 'ar' ? 'السيرة الذاتية / نبذة' : 'Bio / Profile'; ?></div>
                    <div class="card" style="background:transparent; box-shadow:none; padding:0;">
                        <p class="labels"><?php echo esc($worker['bio']) ?: ($lang === 'ar' ? 'لا توجد سيرة ذاتية' : 'No bio available'); ?></p>
                    </div>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom:1rem;">
                    <div class="section-title"><?php echo $lang === 'ar' ? 'معلومات أساسية' : 'Basic Information'; ?></div>
                    <div style="display:flex;flex-direction:column;gap:.75rem;">
                        <div>
                            <div class="labels"><?php echo $lang === 'ar' ? 'التخصصات' : 'Specializations'; ?></div>
                            <div class="value"><?php echo esc($worker['specialization'] ?? '') ?: ($lang === 'ar' ? 'غير محدد' : 'Unspecified'); ?></div>
                        </div>
                        <div>
                            <div class="labels"><?php echo $lang === 'ar' ? 'المدينة' : 'City'; ?></div>
                            <div class="value"><?php echo esc($worker['location'] ?? $worker['location'] ?? ''); ?></div>
                        </div>
                        <div>
                            <div class="labels"><?php echo $lang === 'ar' ? 'الرقم القومي' : 'National ID'; ?></div>
                            <div class="value"><?php echo esc($worker['national_id'] ?? $worker['national_id'] ?? ''); ?></div>
                        </div>
                        <div>
                            <div class="labels"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></div>
                            <div class="value"><?php echo esc($worker['status'] ?? ''); ?></div>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-bottom:1rem;">
                    <div class="section-title"><?php echo $lang === 'ar' ? 'الوثائق' : 'Documents'; ?></div>
                    <div class="docs-list">
                        <?php if (!empty($worker['id_front_path'])): ?>
                            <img src="<?php echo esc('../../'.$worker['id_front_path']); ?>" target="_blank" alt="<?php echo $lang === 'ar' ? 'صورة البطاقة - الوجه' : 'ID Card - Front'; ?>" style="max-width:200px; border-radius:8px; margin-bottom:0.5rem;">
                        <?php endif; ?>
                        <?php if (!empty($worker['id_back_path'])): ?>
                            <img src="<?php echo esc('../../'.$worker['id_back_path']); ?>" target="_blank" alt="<?php echo $lang === 'ar' ? 'صورة البطاقة - الظهر' : 'ID Card - Back'; ?>" style="max-width:200px; border-radius:8px; margin-bottom:0.5rem;">
                        <?php endif; ?>
                        <?php if (!empty($worker['certificate_path'])): ?>
                            <img src="<?php echo esc('../../'.$worker['certificate_path']); ?>" target="_blank" alt="<?php echo $lang === 'ar' ? 'شهادة السجل' : 'Criminal Record'; ?>" style="max-width:200px; border-radius:8px; margin-bottom:0.5rem;">
                        <?php endif; ?>
                        <?php if (!empty($worker['resumeUrl'])): ?>
                            <a href="<?php echo esc($worker['resumeUrl']); ?>" target="_blank"><?php echo $lang === 'ar' ? 'السيرة الذاتية' : 'Resume / CV'; ?></a>
                        <?php endif; ?>
                        <?php if (empty($worker['id_front_path']) && empty($worker['id_back_path']) && empty($worker['certificate_path']) && empty($worker['resumeUrl'])): ?>
                            <div class="labels"><?php echo $lang === 'ar' ? 'لا توجد مستندات' : 'No documents uploaded'; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="section-title"><?php echo $lang === 'ar' ? 'محفظة ومقاطع' : 'Portfolio / Links'; ?></div>
                    <div class="labels"><?php echo $lang === 'ar' ? 'إن وجد' : 'If available'; ?></div>
                    <div style="margin-top:.5rem;">
                        <?php if (!empty($worker['resumeUrl'])): ?>
                            <a class="btn-small" href="<?php echo esc($worker['resumeUrl']); ?>" target="_blank"><?php echo $lang === 'ar' ? 'عرض السيرة الذاتية' : 'View Resume'; ?></a>
                        <?php else: ?>
                            <div class="labels"><?php echo $lang === 'ar' ? 'لا يوجد' : 'None'; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:2rem; display:flex; gap:1rem; justify-content:center;">
            <button class="btn-action btn-reject" onclick="rejectWorker(<?php echo $workerId; ?>)"><?php echo $lang === 'ar' ? 'رفض' : 'Reject'; ?></button>
            <button class="btn-action btn-accept" onclick="acceptWorker(<?php echo $workerId; ?>)"><?php echo $lang === 'ar' ? 'قبول' : 'Accept'; ?></button>
        </div>
    </div>

    <script>
        function acceptWorker(workerId) {
            if (confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد من قبول هذا الفني؟' : 'Are you sure you want to accept this worker?'; ?>')) {
                sendApprovalRequest(workerId, 'accept');
            }
        }

        function rejectWorker(workerId) {
            if (confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد من رفض هذا الفني؟' : 'Are you sure you want to reject this worker?'; ?>')) {
                sendApprovalRequest(workerId, 'reject');
            }
        }

        function sendApprovalRequest(workerId, action) {
            const formData = new FormData();
            formData.append('worker_id', workerId);
            formData.append('action', action);

            fetch('../../api/api_worker_approval.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = action === 'accept' 
                        ? '<?php echo $lang === 'ar' ? 'تم قبول الفني بنجاح' : 'Worker accepted successfully'; ?>'
                        : '<?php echo $lang === 'ar' ? 'تم رفض الفني' : 'Worker rejected successfully'; ?>';
                    alert(message);
                    setTimeout(() => {
                        window.location.href = 'admin_dashboard.php?lang=<?php echo $lang; ?>';
                    }, 1000);
                } else {
                    alert(data.message || 'Error processing request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo $lang === 'ar' ? 'حدث خطأ في المعالجة' : 'An error occurred'; ?>');
            });
        }
    </script>
</body>
</html>
