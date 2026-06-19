<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header('Location: ../user/login.php');
    exit();
}

include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? 'en';
$user_id = $_SESSION['user_id'];

// Get worker's city and specialization
$stmt = $conn->prepare("SELECT city, specialization FROM workers WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    header('Location: worker_dashboard.php?lang=' . $lang);
    exit();
}

$worker_city = $worker['city'];
$worker_specialization = $worker['specialization'];

// Get available requests matching worker's city and specialization
$stmt = $conn->prepare("
    SELECT 
        id, 
        request_id,
        problem_description, 
        address_description, 
        checking_fee,
        google_maps_link,
        city,
        specialization,
        status,
        created_at,
        user_id
    FROM service_requests 
    WHERE city = :city 
        AND specialization = :specialization 
        AND status IN ('REQUESTED', 'pending')
        AND worker_id IS NULL
    ORDER BY created_at DESC
");
$stmt->bindParam(':city', $worker_city, PDO::PARAM_STR);
$stmt->bindParam(':specialization', $worker_specialization, PDO::PARAM_STR);
$stmt->execute();
$available_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'الفرص المتاحة - اختر مهمتك التالية' : 'Available Opportunities - Choose Your Next Job';
        $pageDescription = $lang === 'ar' ? 'استعرض المهام المتاحة في تخصصك ومدينتك' : 'Browse available tasks in your specialty and city';
        include('../../core/seo.php');
    ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        .opportunities-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .opportunity-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #0ea5e9;
            transition: all 0.3s ease;
        }

        .opportunity-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .opportunity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .opportunity-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .opportunity-fee {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
        }

        .opportunity-description {
            color: #64748b;
            font-size: 14px;
            margin: 10px 0;
            line-height: 1.5;
        }

        .opportunity-meta {
            display: flex;
            gap: 20px;
            margin: 12px 0;
            flex-wrap: wrap;
            font-size: 13px;
            color: #4b5563;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .opportunity-location {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #0284c7;
            margin: 10px 0;
        }

        .opportunity-location a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 500;
        }

        .opportunity-location a:hover {
            text-decoration: underline;
        }

        .opportunity-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-view {
            flex: 1;
            padding: 10px 16px;
            background: #0ea5e9;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: #0284c7;
            transform: translateY(-2px);
        }

        .btn-maps {
            padding: 10px 16px;
            background: #f3f4f6;
            color: #059669;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-maps:hover {
            background: #e5e7eb;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-state h2 {
            font-size: 20px;
            margin: 16px 0 8px;
            color: #1e293b;
        }

        .filters-bar {
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-badge {
            background: white;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            color: #1e293b;
        }

        .filter-badge strong {
            color: #0ea5e9;
        }

        @media (max-width: 600px) {
            .opportunities-container {
                padding: 12px;
            }

            .opportunity-card {
                padding: 16px;
            }

            .opportunity-header {
                flex-direction: column;
            }

            .opportunity-fee {
                width: 100%;
                text-align: center;
                margin-top: 10px;
            }

            .opportunity-meta {
                flex-direction: column;
                gap: 8px;
            }

            .opportunity-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="lang-switcher">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
        </div>

        <div class="page-header">
            <h1><?php echo $lang === 'ar' ? 'الفرص المتاحة' : 'Available Opportunities'; ?></h1>
            <p><?php echo $lang === 'ar' ? 'المهام المناسبة لتخصصك في مدينتك' : 'Tasks matching your specialty in your city'; ?></p>
        </div>

        <div class="opportunities-container">
            <div class="filters-bar">
                <span class="filter-badge">
                    📍 <?php echo $lang === 'ar' ? 'المدينة:' : 'City:'; ?> <strong><?php echo htmlspecialchars($worker_city); ?></strong>
                </span>
                <span class="filter-badge">
                    🔧 <?php echo $lang === 'ar' ? 'التخصص:' : 'Specialty:'; ?> <strong><?php echo htmlspecialchars($worker_specialization); ?></strong>
                </span>
                <span class="filter-badge">
                    ✅ <?php echo count($available_requests); ?> <?php echo $lang === 'ar' ? 'فرصة متاحة' : 'opportunities available'; ?>
                </span>
            </div>

            <?php if (empty($available_requests)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎯</div>
                    <h2><?php echo $lang === 'ar' ? 'لا توجد فرص متاحة حالياً' : 'No opportunities available right now'; ?></h2>
                    <p><?php echo $lang === 'ar' ? 'تحقق لاحقاً من فرص جديدة في تخصصك' : 'Check back later for new opportunities in your specialty'; ?></p>
                    <a href="worker_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-primary" style="margin-top: 20px; display: inline-block;">
                        <?php echo $lang === 'ar' ? '← العودة إلى لوحة التحكم' : '← Back to Dashboard'; ?>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($available_requests as $req): ?>
                    <div class="opportunity-card">
                        <div class="opportunity-header">
                            <div>
                                <h3 class="opportunity-title"><?php echo htmlspecialchars($req['problem_description']); ?></h3>
                                <div class="opportunity-description">
                                    📌 <?php echo htmlspecialchars($req['address_description'] ?? 'No address provided'); ?>
                                </div>
                            </div>
                            <div class="opportunity-fee">
                                💰 <?php echo htmlspecialchars($req['checking_fee']); ?> EGP
                            </div>
                        </div>

                        <div class="opportunity-meta">
                            <div class="meta-item">
                                📅 <?php 
                                    $createdTime = strtotime($req['created_at']);
                                    $diff = time() - $createdTime;
                                    if ($diff < 3600) {
                                        echo $lang === 'ar' ? 'منذ ' . floor($diff/60) . ' دقيقة' : floor($diff/60) . ' mins ago';
                                    } elseif ($diff < 86400) {
                                        echo $lang === 'ar' ? 'منذ ' . floor($diff/3600) . ' ساعة' : floor($diff/3600) . ' hours ago';
                                    } else {
                                        echo $lang === 'ar' ? 'منذ ' . floor($diff/86400) . ' يوم' : floor($diff/86400) . ' days ago';
                                    }
                                ?>
                            </div>
                            <div class="meta-item">
                                🏷️ <?php echo $lang === 'ar' ? 'طلب #' : 'Request #'; ?><?php echo htmlspecialchars($req['id']); ?>
                            </div>
                        </div>

                        <?php if (!empty($req['google_maps_link'])): ?>
                            <div class="opportunity-location">
                                📍 <a href="<?php echo htmlspecialchars($req['google_maps_link']); ?>" target="_blank">
                                    <?php echo $lang === 'ar' ? 'شاهد الموقع على الخريطة' : 'View on Map'; ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="opportunity-actions">
                            <a href="worker_request_details.php?request_id=<?php echo $req['id']; ?>&lang=<?php echo $lang; ?>" class="btn-view">
                                👁️ <?php echo $lang === 'ar' ? 'عرض التفاصيل' : 'View Details'; ?>
                            </a>
                            <?php if (!empty($req['google_maps_link'])): ?>
                                <a href="<?php echo htmlspecialchars($req['google_maps_link']); ?>" target="_blank" class="btn-maps">
                                    🗺️ <?php echo $lang === 'ar' ? 'خريطة' : 'Map'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div style="margin-top: 40px; text-align: center;">
                <a href="worker_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-secondary" style="display: inline-block;">
                    ← <?php echo $lang === 'ar' ? 'العودة إلى لوحة التحكم' : 'Back to Dashboard'; ?>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
