<?php
// ========================================
// FLIX Request Tracking - Bilingual
// ========================================

include 'lang.php';
include 'db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login-new.php?lang=' . $lang);
    exit;
}

$request_id = $_GET['request_id'] ?? null;

if (!$request_id) {
    header('Location: usermain.php?lang=' . $lang);
    exit;
}

// Fetch request details
try {
    $query = "SELECT sr.*, st.name_en as service_name, w.name as worker_name, w.phone as worker_phone, u.name as user_name
              FROM service_requests sr
              LEFT JOIN service_types st ON sr.service_type_id = st.id
              LEFT JOIN workers w ON sr.worker_id = w.id
              LEFT JOIN users u ON sr.user_id = u.id
              WHERE sr.id = $1";
    
    $result = pg_query_params($db, $query, [$request_id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        header('Location: usermain.php?lang=' . $lang);
        exit;
    }
    
    $request = pg_fetch_assoc($result);
} catch (Exception $e) {
    header('Location: usermain.php?lang=' . $lang);
    exit;
}

// Handle status updates (worker actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_type'] === 'worker') {
    $action = $_POST['action'] ?? '';
    $new_status = null;
    
    switch ($action) {
        case 'start_checking':
            $new_status = 'checking_completed';
            break;
        case 'start_fixing':
            $new_status = 'fixing_completed';
            break;
    }
    
    if ($new_status) {
        $update = pg_query_params($db, 
            "UPDATE service_requests SET status = $1, updated_at = NOW() WHERE id = $2",
            [$new_status, $request_id]
        );
        
        if ($update) {
            $request['status'] = $new_status;
        }
    }
}

// Determine progress stage
$stages = ['pending', 'accepted', 'checking_completed', 'fixing_completed', 'completed'];
$current_stage_index = array_search($request['status'], $stages);
if ($current_stage_index === false) {
    $current_stage_index = 0;
}

// Translate service name
$service_name = $request['service_name'] ?? 'Service';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('track_request'); ?> - FLIX</title>
    <link rel="stylesheet" href="css/premium-ui.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding: var(--spacing-lg);
        }
        
        .container {
            max-width: 1000px;
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
        }
        
        .status-badge {
            display: inline-block;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #b45309;
        }
        
        .status-accepted {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-checking {
            background: #cffafe;
            color: #0e7490;
        }
        
        .status-completed {
            background: #dcfce7;
            color: #15803d;
        }
        
        .progress-container {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .progress-item {
            flex: 1;
            text-align: center;
        }
        
        .progress-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-sm);
            font-weight: 700;
            color: white;
            background: var(--color-neutral-300);
            transition: all 0.3s ease;
        }
        
        .progress-item.active .progress-circle {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            transform: scale(1.1);
        }
        
        .progress-item.completed .progress-circle {
            background: var(--color-success);
        }
        
        .progress-label {
            font-weight: 600;
            color: var(--color-neutral-700);
            font-size: 0.9rem;
        }
        
        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-md);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .info-item {
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--color-neutral-200);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--color-neutral-600);
            font-size: 0.9rem;
            margin-bottom: var(--spacing-xs);
        }
        
        .info-value {
            font-size: 1.1rem;
            color: var(--color-neutral-900);
        }
        
        .worker-card {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--color-primary);
        }
        
        .worker-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .worker-details h3 {
            margin: 0 0 var(--spacing-sm) 0;
            color: var(--color-neutral-900);
        }
        
        .worker-contact {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }
        
        .worker-contact a {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            background: white;
            border: 1px solid var(--color-primary);
            color: var(--color-primary);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .worker-contact a:hover {
            background: var(--color-primary);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-lg);
        }
        
        .btn {
            flex: 1;
            padding: var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(14, 165, 233, 0.3);
        }
        
        .btn-secondary {
            background: var(--color-neutral-100);
            color: var(--color-neutral-900);
            border: 1px solid var(--color-neutral-300);
        }
        
        .btn-secondary:hover {
            background: var(--color-neutral-200);
        }
        
        .price-box {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--color-success);
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .price-item:last-child {
            border-bottom: none;
        }
        
        .price-item.total {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--color-success);
            padding-top: var(--spacing-md);
        }
        
        @media (max-width: 768px) {
            .progress-bar {
                flex-wrap: wrap;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .worker-info {
                flex-direction: column;
                gap: var(--spacing-md);
            }
            
            .worker-contact {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><?php echo t('track_request'); ?></h1>
                <p style="margin: var(--spacing-sm) 0 0 0; color: var(--color-neutral-600);">
                    <?php echo t('request_status'); ?>: <span class="status-badge status-<?php echo str_replace('_', '-', $request['status']); ?>">
                        <?php 
                        $status_map = [
                            'pending' => t('pending'),
                            'accepted' => t('in_progress'),
                            'checking_completed' => t('checking_completed'),
                            'fixing_completed' => t('fixing_completed'),
                            'completed' => t('completed')
                        ];
                        echo $status_map[$request['status']] ?? $request['status'];
                        ?>
                    </span>
                </p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0 0 var(--spacing-sm) 0; color: var(--color-neutral-600); font-size: 0.9rem;">Request ID</p>
                <p style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--color-primary);">#<?php echo $request_id; ?></p>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar">
                <?php 
                $progress_stages = [
                    ['key' => 'pending', 'label' => t('pending')],
                    ['key' => 'accepted', 'label' => t('worker_arrived')],
                    ['key' => 'checking_completed', 'label' => t('checking_completed')],
                    ['key' => 'fixing_completed', 'label' => t('fixing_completed')],
                    ['key' => 'completed', 'label' => t('completed')]
                ];
                
                foreach ($progress_stages as $index => $stage):
                ?>
                    <div class="progress-item <?php echo ($index <= $current_stage_index) ? 'completed' : ''; ?> <?php echo ($index === $current_stage_index) ? 'active' : ''; ?>">
                        <div class="progress-circle"><?php echo $index + 1; ?></div>
                        <div class="progress-label"><?php echo $stage['label']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Service Details -->
        <div class="card">
            <div class="card-title"><?php echo t('service_details'); ?></div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><?php echo t('services'); ?></div>
                    <div class="info-value"><?php echo htmlspecialchars($service_name); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><?php echo t('problem_description'); ?></div>
                    <div class="info-value"><?php echo htmlspecialchars($request['problem_description']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Worker Details (if assigned) -->
        <?php if ($request['worker_id']): ?>
        <div class="card">
            <div class="card-title"><?php echo t('worker_details'); ?></div>
            <div class="worker-card">
                <div class="worker-info">
                    <div class="worker-details">
                        <h3><?php echo htmlspecialchars($request['worker_name']); ?></h3>
                        <p style="margin: 0; color: var(--color-neutral-600);">📍 <?php echo t('available'); ?></p>
                    </div>
                    <div class="worker-contact">
                        <a href="tel:<?php echo htmlspecialchars($request['worker_phone']); ?>">📞 <?php echo t('call'); ?></a>
                        <a href="whatsapp://send?phone=<?php echo urlencode($request['worker_phone']); ?>">💬 WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Price Details -->
        <div class="card">
            <div class="card-title"><?php echo t('estimated_cost'); ?></div>
            <div class="price-box">
                <div class="price-item">
                    <span><?php echo t('checking_fee'); ?>:</span>
                    <span><?php echo $request['checking_fee']; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="price-item">
                    <span><?php echo t('fixing_fee'); ?>:</span>
                    <span><?php echo $request['fixing_price']; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="price-item total">
                    <span><?php echo t('total_cost'); ?>:</span>
                    <span><?php echo $request['total_price']; ?> <?php echo t('egp'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Worker Action Buttons -->
        <?php if ($_SESSION['user_type'] === 'worker' && $request['worker_id'] == $_SESSION['user_id']): ?>
        <div class="card">
            <div class="action-buttons">
                <?php if ($request['status'] === 'accepted'): ?>
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="start_checking">
                        <button type="submit" class="btn btn-primary"><?php echo t('start_checking'); ?></button>
                    </form>
                <?php elseif ($request['status'] === 'checking_completed'): ?>
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="start_fixing">
                        <button type="submit" class="btn btn-primary"><?php echo t('start_fixing'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- User Action Buttons -->
        <?php if ($_SESSION['user_type'] === 'user' && $request['user_id'] == $_SESSION['user_id'] && $request['status'] === 'completed'): ?>
        <div class="card">
            <div class="action-buttons">
                <a href="receipt-new.php?request_id=<?php echo $request_id; ?>&lang=<?php echo $lang; ?>" class="btn btn-primary"><?php echo t('rate_worker'); ?></a>
                <a href="usermain.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo t('back'); ?></a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Go Back Button -->
        <?php if (!($request['status'] === 'completed' && $_SESSION['user_type'] === 'user')): ?>
        <div class="card" style="text-align: center;">
            <a href="usermain.php?lang=<?php echo $lang; ?>" class="btn btn-secondary" style="max-width: 300px; margin: 0 auto;"><?php echo t('back'); ?></a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
