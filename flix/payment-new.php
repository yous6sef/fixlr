<?php
// ========================================
// FLIX Payment - Bilingual
// ========================================

include 'lang.php';
include 'db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login-new.php?lang=' . $lang);
    exit;
}

$request_id = $_GET['request_id'] ?? null;
$error_message = '';
$success_message = '';

if (!$request_id) {
    header('Location: usermain.php?lang=' . $lang);
    exit;
}

// Fetch request details
try {
    $query = "SELECT sr.*, st.name_en as service_name, w.name as worker_name
              FROM service_requests sr
              LEFT JOIN service_types st ON sr.service_type_id = st.id
              LEFT JOIN workers w ON sr.worker_id = w.id
              WHERE sr.id = $1 AND sr.user_id = $2";
    
    $result = pg_query_params($db, $query, [$request_id, $_SESSION['user_id']]);
    
    if (!$result || pg_num_rows($result) === 0) {
        header('Location: usermain.php?lang=' . $lang);
        exit;
    }
    
    $request = pg_fetch_assoc($result);
} catch (Exception $e) {
    header('Location: usermain.php?lang=' . $lang);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'card';
    $amount_type = $_POST['amount_type'] ?? 'checking'; // checking or total
    
    try {
        if ($amount_type === 'checking') {
            $amount = $request['checking_fee'];
            $payment_type = 'checking_fee';
        } else {
            $amount = $request['total_price'];
            $payment_type = 'full_payment';
        }
        
        // Generate transaction reference
        $transaction_id = 'TRX-' . time() . '-' . rand(1000, 9999);
        
        // Insert payment record
        $insert = pg_query_params($db,
            "INSERT INTO payments (service_request_id, worker_id, user_id, amount, payment_type, payment_status, transaction_id, receipt_image, created_at)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW())",
            [$request_id, $request['worker_id'], $_SESSION['user_id'], $amount, $payment_type, 'completed', $transaction_id, null]
        );
        
        if ($insert) {
            $success_message = t('payment_successful');
            header("Refresh: 2; url=receipt-new.php?request_id=$request_id&lang=$lang");
        } else {
            $error_message = t('payment_failed');
        }
    } catch (Exception $e) {
        $error_message = t('error');
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('confirm_payment'); ?> - FLIX</title>
    <link rel="stylesheet" href="css/premium-ui.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding: var(--spacing-lg);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-lg);
        }
        
        .payment-summary {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            border-left: 4px solid var(--color-primary);
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid rgba(14, 165, 233, 0.2);
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-item.total {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--color-primary);
            padding-top: var(--spacing-md);
        }
        
        .payment-options {
            margin-bottom: var(--spacing-lg);
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            padding: var(--spacing-md);
            border: 2px solid var(--color-neutral-200);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option:hover {
            border-color: var(--color-primary);
            background: rgba(14, 165, 233, 0.05);
        }
        
        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            margin-right: var(--spacing-md);
            cursor: pointer;
        }
        
        .payment-option.selected {
            border-color: var(--color-primary);
            background: rgba(14, 165, 233, 0.1);
        }
        
        .payment-info {
            margin-left: 0;
        }
        
        .payment-method-name {
            font-weight: 600;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-xs);
        }
        
        .payment-method-desc {
            font-size: 0.9rem;
            color: var(--color-neutral-600);
        }
        
        .amount-selection {
            margin-bottom: var(--spacing-lg);
        }
        
        .amount-option {
            display: flex;
            align-items: center;
            padding: var(--spacing-md);
            border: 1px solid var(--color-neutral-300);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            cursor: pointer;
        }
        
        .amount-option input[type="radio"] {
            margin-right: var(--spacing-md);
            cursor: pointer;
        }
        
        .amount-details {
            flex: 1;
        }
        
        .amount-label {
            font-weight: 600;
            margin-bottom: var(--spacing-xs);
        }
        
        .amount-value {
            font-size: 1.2rem;
            color: var(--color-primary);
            font-weight: 700;
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
        
        .buttons {
            display: flex;
            gap: var(--spacing-md);
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
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-title">💳 <?php echo t('confirm_payment'); ?></div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Summary -->
        <div class="card">
            <div class="card-title"><?php echo t('service_details'); ?></div>
            <div class="payment-summary">
                <div class="summary-item">
                    <span><?php echo t('services'); ?>:</span>
                    <span><?php echo htmlspecialchars($request['service_name']); ?></span>
                </div>
                <div class="summary-item">
                    <span><?php echo t('checking_fee'); ?>:</span>
                    <span><?php echo $request['checking_fee']; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="summary-item">
                    <span><?php echo t('fixing_fee'); ?>:</span>
                    <span><?php echo $request['fixing_price']; ?> <?php echo t('egp'); ?></span>
                </div>
                <div class="summary-item total">
                    <span><?php echo t('total_cost'); ?>:</span>
                    <span><?php echo $request['total_price']; ?> <?php echo t('egp'); ?></span>
                </div>
            </div>
        </div>
        
        <form method="POST" action="">
            <!-- Amount Selection -->
            <div class="card">
                <div class="card-title"><?php echo t('payment_method'); ?></div>
                
                <div class="amount-selection">
                    <div class="amount-option">
                        <input type="radio" name="amount_type" value="checking" checked id="amount_checking">
                        <div class="amount-details">
                            <div class="amount-label"><?php echo t('checking_fee'); ?> <?php echo $lang === 'ar' ? 'فقط' : 'Only'; ?></div>
                            <div class="amount-value"><?php echo $request['checking_fee']; ?> <?php echo t('egp'); ?></div>
                        </div>
                    </div>
                    
                    <div class="amount-option">
                        <input type="radio" name="amount_type" value="total" id="amount_total">
                        <div class="amount-details">
                            <div class="amount-label"><?php echo t('total_cost'); ?> (<?php echo t('checking_fee'); ?> + <?php echo t('fixing_fee'); ?>)</div>
                            <div class="amount-value"><?php echo $request['total_price']; ?> <?php echo t('egp'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Method Selection -->
            <div class="card">
                <div class="card-title"><?php echo t('payment_method'); ?></div>
                
                <div class="payment-options">
                    <label class="payment-option" data-method="card">
                        <input type="radio" name="payment_method" value="card" checked>
                        <div class="payment-info">
                            <div class="payment-method-name">💳 <?php echo t('card'); ?></div>
                            <div class="payment-method-desc"><?php echo $lang === 'ar' ? 'بطاقة ائتمان أو خصم' : 'Credit or Debit Card'; ?></div>
                        </div>
                    </label>
                    
                    <label class="payment-option" data-method="wallet">
                        <input type="radio" name="payment_method" value="wallet">
                        <div class="payment-info">
                            <div class="payment-method-name">💰 <?php echo t('wallet'); ?></div>
                            <div class="payment-method-desc"><?php echo $lang === 'ar' ? 'محفظتك الرقمية' : 'Your Digital Wallet'; ?></div>
                        </div>
                    </label>
                    
                    <label class="payment-option" data-method="transfer">
                        <input type="radio" name="payment_method" value="transfer">
                        <div class="payment-info">
                            <div class="payment-method-name">🏦 <?php echo t('transfer'); ?></div>
                            <div class="payment-method-desc"><?php echo $lang === 'ar' ? 'تحويل بنكي' : 'Bank Transfer'; ?></div>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="card">
                <div class="buttons">
                    <a href="track-new.php?request_id=<?php echo $request_id; ?>&lang=<?php echo $lang; ?>" class="btn btn-secondary">
                        <?php echo t('cancel'); ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo t('pay_now'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        const paymentOptions = document.querySelectorAll('.payment-option');
        const radioButtons = document.querySelectorAll('input[name="payment_method"]');
        
        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                paymentOptions.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // Set initial selected state
        document.querySelector('.payment-option').classList.add('selected');
    </script>
</body>
</html>
