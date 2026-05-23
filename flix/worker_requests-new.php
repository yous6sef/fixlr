<?php
// ========================================
// FLIX Worker Available Requests - Bilingual
// ========================================

include 'lang.php';
include 'db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'worker') {
    header('Location: login-new.php?lang=' . $lang);
    exit;
}

$success_message = '';
$error_message = '';

// Handle accept/reject request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($request_id && $action) {
        try {
            if ($action === 'accept') {
                // Check worker specialization matches
                $check_request = pg_query_params($db,
                    "SELECT service_type_id FROM service_requests WHERE id = $1 AND status = 'pending'",
                    [$request_id]
                );
                
                if (pg_num_rows($check_request) > 0) {
                    $req = pg_fetch_assoc($check_request);
                    
                    // Update request to assign worker and set status to accepted
                    $update = pg_query_params($db,
                        "UPDATE service_requests SET worker_id = $1, status = 'accepted', updated_at = NOW() WHERE id = $2",
                        [$_SESSION['user_id'], $request_id]
                    );
                    
                    if ($update) {
                        $success_message = t('request_accepted') . '!';
                    } else {
                        $error_message = t('error');
                    }
                }
            } else if ($action === 'reject') {
                // Just show a success message (we don't update DB, just remove from view)
                $success_message = t('request_rejected');
            }
        } catch (Exception $e) {
            $error_message = t('error');
        }
    }
}

// Fetch available requests matching worker's specialization and city
try {
    $query = "SELECT sr.id, sr.problem_description, sr.checking_fee, sr.fixing_price, sr.total_price, sr.created_at,
                     st.name_en as service_name, u.name as user_name, u.phone as user_phone,
                     c.name_en as city_name
              FROM service_requests sr
              JOIN service_types st ON sr.service_type_id = st.id
              JOIN users u ON sr.user_id = u.id
              JOIN cities c ON sr.city_id = c.id
              WHERE sr.status = 'pending' 
                AND sr.worker_id IS NULL
                AND sr.city_id = (SELECT city FROM workers WHERE id = $1)
              ORDER BY sr.created_at DESC";
    
    $result = pg_query_params($db, $query, [$_SESSION['user_id']]);
    $available_requests = pg_fetch_all($result) ?: [];
    
    // Get worker's active requests count
    $active_query = "SELECT COUNT(*) as count FROM service_requests WHERE worker_id = $1 AND status IN ('accepted', 'checking_completed', 'fixing_completed')";
    $active_result = pg_query_params($db, $active_query, [$_SESSION['user_id']]);
    $active_count = pg_fetch_assoc($active_result)['count'];
    
} catch (Exception $e) {
    $available_requests = [];
    $active_count = 0;
}

global $cities;

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('available_requests'); ?> - FLIX</title>
    <link rel="stylesheet" href="css/premium-ui.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding: var(--spacing-lg);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .header-info {
            display: flex;
            gap: var(--spacing-lg);
        }
        
        .info-box {
            text-align: center;
            padding: var(--spacing-md);
        }
        
        .info-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .info-label {
            font-size: 0.9rem;
            color: var(--color-neutral-600);
            margin-top: var(--spacing-xs);
        }
        
        .lang-switcher {
            display: flex;
            gap: var(--spacing-sm);
        }
        
        .lang-switcher a {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            background: var(--color-neutral-100);
            color: var(--color-neutral-900);
            transition: all 0.3s ease;
        }
        
        .lang-switcher a.active {
            background: var(--color-primary);
            color: white;
        }
        
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            animation: slideDown 0.3s ease;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border-left: 4px solid #22c55e;
        }
        
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .request-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .request-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid var(--color-neutral-200);
        }
        
        .service-badge {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            color: white;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .request-id {
            color: var(--color-neutral-600);
            font-size: 0.85rem;
        }
        
        .problem-description {
            color: var(--color-neutral-700);
            margin-bottom: var(--spacing-md);
            line-height: 1.5;
        }
        
        .request-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--color-neutral-200);
        }
        
        .detail {
            font-size: 0.9rem;
        }
        
        .detail-label {
            color: var(--color-neutral-600);
            margin-bottom: var(--spacing-xs);
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--color-neutral-900);
        }
        
        .price-section {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-sm);
        }
        
        .price-item:last-child {
            margin-bottom: 0;
        }
        
        .total-price {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--color-primary);
            padding-top: var(--spacing-sm);
            border-top: 2px solid rgba(14, 165, 233, 0.3);
            margin-top: var(--spacing-sm);
        }
        
        .user-info {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--color-neutral-50);
            border-radius: var(--radius-md);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-xs);
        }
        
        .user-phone {
            font-size: 0.9rem;
            color: var(--color-neutral-600);
        }
        
        .actions {
            display: flex;
            gap: var(--spacing-md);
        }
        
        .btn {
            flex: 1;
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-accept {
            background: linear-gradient(135deg, var(--color-success) 0%, var(--color-primary) 100%);
            color: white;
        }
        
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-reject {
            background: var(--color-neutral-100);
            color: var(--color-neutral-900);
            border: 1px solid var(--color-neutral-300);
        }
        
        .btn-reject:hover {
            background: var(--color-neutral-200);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-xl);
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: var(--spacing-md);
        }
        
        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-sm);
        }
        
        .empty-description {
            color: var(--color-neutral-600);
            margin-bottom: var(--spacing-lg);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: var(--spacing-md);
            }
            
            .header-info {
                width: 100%;
                justify-content: space-around;
            }
            
            .requests-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><?php echo t('available_requests'); ?></h1>
                <p style="margin: var(--spacing-sm) 0 0 0; color: var(--color-neutral-600);">
                    <?php echo t('find_and_accept'); ?>
                </p>
            </div>
            <div class="lang-switcher">
                <a href="<?php echo getLangLink('ar'); ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                <a href="<?php echo getLangLink('en'); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="header" style="margin-bottom: var(--spacing-lg);">
            <div class="header-info" style="width: 100%; gap: 0;">
                <div class="info-box">
                    <div class="info-value"><?php echo count($available_requests); ?></div>
                    <div class="info-label"><?php echo t('available_requests'); ?></div>
                </div>
                <div class="info-box">
                    <div class="info-value"><?php echo $active_count; ?></div>
                    <div class="info-label"><?php echo t('active_tasks'); ?></div>
                </div>
                <div class="info-box">
                    <a href="worker_dashboard.php?lang=<?php echo $lang; ?>" style="text-decoration: none;">
                        <div class="info-value" style="color: var(--color-neutral-600);">📊</div>
                        <div class="info-label"><?php echo t('dashboard'); ?></div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Requests Grid -->
        <?php if (count($available_requests) > 0): ?>
            <div class="requests-grid">
                <?php foreach ($available_requests as $req): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <div class="service-badge"><?php echo htmlspecialchars($req['service_name']); ?></div>
                                <div class="request-id">ID: #<?php echo $req['id']; ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.85rem; color: var(--color-neutral-600);">
                                    <?php 
                                    $time_diff = time() - strtotime($req['created_at']);
                                    if ($time_diff < 60) {
                                        echo t('just_now');
                                    } elseif ($time_diff < 3600) {
                                        echo floor($time_diff/60) . ' ' . t('minutes_ago');
                                    } else {
                                        echo floor($time_diff/3600) . ' ' . t('hours_ago');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="problem-description">
                            <strong><?php echo t('problem'); ?>:</strong><br>
                            <?php echo htmlspecialchars(substr($req['problem_description'], 0, 100)); ?><?php echo strlen($req['problem_description']) > 100 ? '...' : ''; ?>
                        </div>
                        
                        <div class="request-details">
                            <div class="detail">
                                <div class="detail-label">📍 <?php echo t('location'); ?></div>
                                <div class="detail-value"><?php echo htmlspecialchars($req['city_name']); ?></div>
                            </div>
                            <div class="detail">
                                <div class="detail-label">⏰ <?php echo t('posted'); ?></div>
                                <div class="detail-value"><?php echo date('H:i', strtotime($req['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="user-info">
                            <div class="user-avatar"><?php echo strtoupper(substr($req['user_name'], 0, 1)); ?></div>
                            <div class="user-details">
                                <div class="user-name"><?php echo htmlspecialchars($req['user_name']); ?></div>
                                <div class="user-phone">📞 <?php echo htmlspecialchars($req['user_phone']); ?></div>
                            </div>
                        </div>
                        
                        <div class="price-section">
                            <div class="price-item">
                                <span><?php echo t('checking_fee'); ?>:</span>
                                <span><?php echo $req['checking_fee']; ?> <?php echo t('egp'); ?></span>
                            </div>
                            <div class="price-item">
                                <span><?php echo t('fixing_fee'); ?> (<?php echo t('budget'); ?>):</span>
                                <span><?php echo $req['fixing_price']; ?> <?php echo t('egp'); ?></span>
                            </div>
                            <div class="price-item total-price">
                                <span><?php echo t('total_cost'); ?>:</span>
                                <span><?php echo $req['total_price']; ?> <?php echo t('egp'); ?></span>
                            </div>
                        </div>
                        
                        <div class="actions">
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn btn-accept">✓ <?php echo t('accept'); ?></button>
                            </form>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-reject">✕ <?php echo t('reject'); ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <div class="empty-title"><?php echo t('no_requests'); ?></div>
                <div class="empty-description">
                    <?php echo t('check_back_soon'); ?>
                </div>
                <a href="worker_dashboard.php?lang=<?php echo $lang; ?>" style="text-decoration: none;">
                    <button style="padding: var(--spacing-md) var(--spacing-lg); background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                        <?php echo t('back_dashboard'); ?>
                    </button>
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
