<?php
// ========================================
// FLIX Worker Earnings Dashboard - Bilingual
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

// Handle payment request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    if ($action === 'request_payment') {
        try {
            // Get today's earnings
            $earnings_query = "SELECT 
                            SUM(checking_fee + fixing_price) as total_earned,
                            SUM((checking_fee + fixing_price) * 0.8) as net_earnings
                            FROM service_requests 
                            WHERE worker_id = $1 
                            AND status = 'completed'
                            AND DATE(updated_at) = CURRENT_DATE";
            
            $result = pg_query_params($db, $earnings_query, [$_SESSION['user_id']]);
            $earnings = pg_fetch_assoc($result);
            
            if ($earnings['total_earned'] > 0) {
                // Insert payment request
                $insert = pg_query_params($db,
                    "INSERT INTO payments (worker_id, amount, payment_type, payment_status, created_at)
                     VALUES ($1, $2, $3, $4, NOW())",
                    [$_SESSION['user_id'], $earnings['net_earnings'], 'earnings', 'pending']
                );
                
                if ($insert) {
                    $success_message = t('payment_requested');
                } else {
                    $error_message = t('error');
                }
            } else {
                $error_message = t('no_earnings_today');
            }
        } catch (Exception $e) {
            $error_message = t('error');
        }
    }
}

// Fetch today's earnings
try {
    $today_earnings = pg_query_params($db,
        "SELECT 
            COUNT(*) as tasks_completed,
            SUM(checking_fee + fixing_price) as total_earned,
            SUM((checking_fee + fixing_price) * 0.2) as commission,
            SUM((checking_fee + fixing_price) * 0.8) as net_earnings,
            SUM(checking_fee) as checking_total,
            SUM(fixing_price) as fixing_total
         FROM service_requests 
         WHERE worker_id = $1 
         AND status = 'completed'
         AND DATE(updated_at) = CURRENT_DATE",
        [$_SESSION['user_id']]
    );
    
    $today = pg_fetch_assoc($today_earnings);
} catch (Exception $e) {
    $today = ['tasks_completed' => 0, 'total_earned' => 0, 'commission' => 0, 'net_earnings' => 0];
}

// Fetch completed requests today (detailed list)
try {
    $requests_query = "SELECT sr.id, sr.checking_fee, sr.fixing_price, sr.total_price, sr.updated_at,
                              st.name_en as service_name, u.name as user_name
                       FROM service_requests sr
                       JOIN service_types st ON sr.service_type_id = st.id
                       JOIN users u ON sr.user_id = u.id
                       WHERE sr.worker_id = $1 
                       AND sr.status = 'completed'
                       AND DATE(sr.updated_at) = CURRENT_DATE
                       ORDER BY sr.updated_at DESC";
    
    $result = pg_query_params($db, $requests_query, [$_SESSION['user_id']]);
    $completed_requests = pg_fetch_all($result) ?: [];
} catch (Exception $e) {
    $completed_requests = [];
}

// Fetch payment history (last 10 requests)
try {
    $history_query = "SELECT sr.id, sr.checking_fee, sr.fixing_price, sr.total_price, sr.updated_at,
                             st.name_en as service_name
                      FROM service_requests sr
                      JOIN service_types st ON sr.service_type_id = st.id
                      WHERE sr.worker_id = $1 
                      AND sr.status = 'completed'
                      ORDER BY sr.updated_at DESC
                      LIMIT 10";
    
    $result = pg_query_params($db, $history_query, [$_SESSION['user_id']]);
    $earnings_history = pg_fetch_all($result) ?: [];
} catch (Exception $e) {
    $earnings_history = [];
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('my_earnings'); ?> - FLIX</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-md);
        }
        
        .stat-label {
            color: var(--color-neutral-600);
            font-size: 0.9rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .stat-unit {
            font-size: 0.9rem;
            color: var(--color-neutral-600);
        }
        
        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-lg);
        }
        
        .breakdown-box {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--color-primary);
            margin-bottom: var(--spacing-lg);
        }
        
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-md) 0;
            border-bottom: 1px solid rgba(14, 165, 233, 0.2);
        }
        
        .breakdown-item:last-child {
            border-bottom: none;
        }
        
        .breakdown-item.total {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--color-success);
            padding-top: var(--spacing-md);
            border-top: 2px solid rgba(14, 165, 233, 0.3);
            margin-top: var(--spacing-md);
        }
        
        .commission-highlight {
            background: #fef3c7;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-sm);
            color: #b45309;
            font-weight: 600;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-md);
        }
        
        .table thead {
            background: var(--color-neutral-100);
        }
        
        .table th {
            padding: var(--spacing-md);
            text-align: left;
            font-weight: 600;
            color: var(--color-neutral-700);
            border-bottom: 2px solid var(--color-neutral-300);
        }
        
        .table td {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--color-neutral-200);
        }
        
        .table tbody tr:hover {
            background: var(--color-neutral-50);
        }
        
        .btn {
            padding: var(--spacing-md) var(--spacing-lg);
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
            width: 100%;
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
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-lg);
            color: var(--color-neutral-600);
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.85rem;
            }
            
            .table th,
            .table td {
                padding: var(--spacing-sm);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>💰 <?php echo t('my_earnings'); ?></h1>
                <p style="margin: var(--spacing-sm) 0 0 0; color: var(--color-neutral-600);">
                    <?php echo t('todays_earnings'); ?>
                </p>
            </div>
            <div class="lang-switcher">
                <a href="<?php echo getLangLink('ar'); ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                <a href="<?php echo getLangLink('en'); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Today's Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">✓</div>
                <div class="stat-label"><?php echo t('tasks_completed'); ?></div>
                <div class="stat-value"><?php echo $today['tasks_completed']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-label"><?php echo t('total_earned'); ?></div>
                <div class="stat-value"><?php echo $today['total_earned'] ?: 0; ?><span class="stat-unit"> <?php echo t('egp'); ?></span></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-label"><?php echo t('commission'); ?> (20%)</div>
                <div class="stat-value" style="color: #f59e0b;"><?php echo $today['commission'] ?: 0; ?><span class="stat-unit"> <?php echo t('egp'); ?></span></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-label"><?php echo t('net_earnings'); ?></div>
                <div class="stat-value" style="color: var(--color-success);"><?php echo $today['net_earnings'] ?: 0; ?><span class="stat-unit"> <?php echo t('egp'); ?></span></div>
            </div>
        </div>
        
        <!-- Earnings Breakdown -->
        <div class="card">
            <div class="card-title">📈 <?php echo t('earnings_breakdown'); ?></div>
            
            <div class="breakdown-box">
                <div class="breakdown-item">
                    <span><?php echo t('checking_fee'); ?> (<?php echo $today['tasks_completed']; ?> × 300):</span>
                    <span><?php echo $today['checking_total'] ?: 0; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="breakdown-item">
                    <span><?php echo t('fixing_fees'); ?>:</span>
                    <span><?php echo $today['fixing_total'] ?: 0; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="breakdown-item">
                    <span><?php echo t('subtotal'); ?>:</span>
                    <span><?php echo $today['total_earned'] ?: 0; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="breakdown-item">
                    <span class="commission-highlight">- <?php echo t('platform_commission'); ?> (20%):</span>
                    <span class="commission-highlight">- <?php echo $today['commission'] ?: 0; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="breakdown-item total">
                    <span><?php echo t('your_net_earnings'); ?>:</span>
                    <span><?php echo $today['net_earnings'] ?: 0; ?> <?php echo t('egp'); ?></span>
                </div>
            </div>
            
            <?php if ($today['total_earned'] > 0): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="request_payment">
                <button type="submit" class="btn btn-primary">
                    📱 <?php echo t('submit_payment_request'); ?>
                </button>
            </form>
            <?php else: ?>
            <div class="empty-state">
                <p><?php echo t('no_completed_tasks_today'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Today's Completed Tasks -->
        <?php if (count($completed_requests) > 0): ?>
        <div class="card">
            <div class="card-title">✓ <?php echo t('completed_today'); ?></div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo t('services'); ?></th>
                        <th><?php echo t('customer'); ?></th>
                        <th><?php echo t('checking_fee'); ?></th>
                        <th><?php echo t('fixing_fee'); ?></th>
                        <th><?php echo t('total'); ?></th>
                        <th><?php echo t('your_share'); ?> (80%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completed_requests as $req): 
                        $your_share = ($req['checking_fee'] + $req['fixing_price']) * 0.8;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($req['service_name']); ?></td>
                        <td><?php echo htmlspecialchars($req['user_name']); ?></td>
                        <td><?php echo $req['checking_fee']; ?> <?php echo t('egp'); ?></td>
                        <td><?php echo $req['fixing_price']; ?> <?php echo t('egp'); ?></td>
                        <td><strong><?php echo $req['total_price']; ?> <?php echo t('egp'); ?></strong></td>
                        <td style="color: var(--color-success); font-weight: 600;"><?php echo number_format($your_share, 0); ?> <?php echo t('egp'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Earnings History -->
        <?php if (count($earnings_history) > 0): ?>
        <div class="card">
            <div class="card-title">📊 <?php echo t('earnings_history'); ?> (<?php echo t('last'); ?> 10)</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo t('services'); ?></th>
                        <th><?php echo t('checking_fee'); ?></th>
                        <th><?php echo t('fixing_fee'); ?></th>
                        <th><?php echo t('total'); ?></th>
                        <th><?php echo t('commission'); ?> (20%)</th>
                        <th><?php echo t('your_share'); ?> (80%)</th>
                        <th><?php echo t('date'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($earnings_history as $hist): 
                        $commission = ($hist['checking_fee'] + $hist['fixing_price']) * 0.2;
                        $your_share = ($hist['checking_fee'] + $hist['fixing_price']) * 0.8;
                    ?>
                    <tr>
                        <td>#<?php echo $hist['id']; ?></td>
                        <td><?php echo htmlspecialchars($hist['service_name']); ?></td>
                        <td><?php echo $hist['checking_fee']; ?> <?php echo t('egp'); ?></td>
                        <td><?php echo $hist['fixing_price']; ?> <?php echo t('egp'); ?></td>
                        <td><?php echo $hist['total_price']; ?> <?php echo t('egp'); ?></td>
                        <td style="color: #f59e0b; font-weight: 600;">-<?php echo number_format($commission, 0); ?> <?php echo t('egp'); ?></td>
                        <td style="color: var(--color-success); font-weight: 600;"><?php echo number_format($your_share, 0); ?> <?php echo t('egp'); ?></td>
                        <td><?php echo date('d/m H:i', strtotime($hist['updated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <div class="card" style="text-align: center;">
            <a href="worker_dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">
                ← <?php echo t('back_dashboard'); ?>
            </a>
        </div>
    </div>
</body>
</html>
