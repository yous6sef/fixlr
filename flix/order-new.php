<?php
// ========================================
// FLIX Service Request (Order) - Bilingual
// ========================================

include 'lang.php';
include 'db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login-new.php?lang=' . $lang);
    exit;
}

$error_message = '';
$success_message = '';
$request_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type_id = $_POST['service_type'] ?? '';
    $device_id = $_POST['device'] ?? '';
    $problem_description = $_POST['description'] ?? '';
    $city = $_POST['city'] ?? '';
    $budget = $_POST['budget'] ?? 0;
    
    // Validate input
    $errors = [];
    if (empty($service_type_id)) $errors[] = t('required_field');
    if (empty($problem_description)) $errors[] = t('required_field');
    if (empty($city)) $errors[] = t('required_field');
    if ($budget <= 0) $errors[] = t('required_field');
    
    if (count($errors) > 0) {
        $error_message = implode(', ', $errors);
    } else {
        try {
            $checking_fee = 300; // Fixed checking fee in EGP
            $total_price = $checking_fee + $budget;
            
            // Insert service request
            $insert = pg_query_params($db,
                "INSERT INTO service_requests 
                 (user_id, service_type_id, device_id, city_id, problem_description, checking_fee, fixing_price, total_price, status, created_at) 
                 VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, NOW())
                 RETURNING id",
                [$_SESSION['user_id'], $service_type_id, $device_id ?: null, $city, $problem_description, $checking_fee, $budget, $total_price, 'pending']
            );
            
            if ($insert) {
                $result = pg_fetch_assoc($insert);
                $request_id = $result['id'];
                $success_message = t('request_submitted') . ' - ID: ' . $request_id;
            } else {
                $error_message = t('error');
            }
        } catch (Exception $e) {
            $error_message = t('error') . ': ' . $e->getMessage();
        }
    }
}

global $services, $cities;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('create_service_request'); ?> - FLIX</title>
    <link rel="stylesheet" href="css/premium-ui.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding: var(--spacing-lg);
        }
        
        .header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            color: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transition: all 0.3s ease;
        }
        
        .lang-switcher a.active {
            background: rgba(255, 255, 255, 0.9);
            color: var(--color-primary);
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .form-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: var(--spacing-lg);
        }
        
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .section-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            color: white;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .form-grid.full {
            grid-template-columns: 1fr;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--color-neutral-700);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--color-neutral-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .service-card {
            border: 2px solid var(--color-neutral-200);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .service-card:hover {
            border-color: var(--color-primary);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
        }
        
        .service-card.selected {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            border-color: var(--color-primary);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        
        .service-icon {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .service-name {
            font-weight: 600;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-xs);
        }
        
        .service-desc {
            font-size: 0.85rem;
            color: var(--color-neutral-600);
        }
        
        .price-summary {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            border-left: 4px solid var(--color-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-sm);
            font-size: 0.95rem;
        }
        
        .price-item.total {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--color-primary);
            padding-top: var(--spacing-sm);
            border-top: 1px solid rgba(14, 165, 233, 0.3);
        }
        
        .buttons {
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
            
            .service-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo t('create_service_request'); ?></h1>
        <div class="lang-switcher">
            <a href="<?php echo getLangLink('ar'); ?>" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
            <a href="<?php echo getLangLink('en'); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <br><br>
                <a href="track-new.php?request_id=<?php echo $request_id; ?>&lang=<?php echo $lang; ?>" style="color: inherit; font-weight: 600; text-decoration: underline;">
                    <?php echo t('track_request'); ?> →
                </a>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="requestForm">
            <!-- Step 1: Select Service -->
            <div class="form-card">
                <div class="form-section-title">
                    <span class="section-number">1</span>
                    <span><?php echo t('select_service'); ?></span>
                </div>
                
                <div class="service-grid" id="serviceGrid">
                    <?php foreach ($services as $service): ?>
                        <label class="service-card" data-service-id="<?php echo $service['id']; ?>">
                            <input type="radio" name="service_type" value="<?php echo $service['id']; ?>" style="display: none;">
                            <div class="service-icon"><?php echo $service['icon']; ?></div>
                            <div class="service-name"><?php echo htmlspecialchars(getName($service)); ?></div>
                            <div class="service-desc"><?php echo htmlspecialchars(getDesc($service)); ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Step 2: Describe Problem -->
            <div class="form-card">
                <div class="form-section-title">
                    <span class="section-number">2</span>
                    <span><?php echo t('describe_problem'); ?></span>
                </div>
                
                <div class="form-grid full">
                    <div class="form-group">
                        <label><?php echo t('problem_description'); ?> *</label>
                        <textarea name="description" placeholder="<?php echo t('problem_description'); ?>" required></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Location & Budget -->
            <div class="form-card">
                <div class="form-section-title">
                    <span class="section-number">3</span>
                    <span><?php echo t('your_location'); ?></span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo t('city'); ?> *</label>
                        <select name="city" required>
                            <option value="">-- <?php echo t('select_city'); ?> --</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>">
                                    <?php echo htmlspecialchars(getName($city)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('budget'); ?> (<?php echo t('egp'); ?>) *</label>
                        <input type="number" name="budget" placeholder="300" min="1" required>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Price Summary -->
            <div class="form-card">
                <div class="form-section-title">
                    <span class="section-number">4</span>
                    <span><?php echo t('estimated_cost'); ?></span>
                </div>
                
                <div class="price-summary">
                    <div class="price-item">
                        <span><?php echo t('checking_fee'); ?>:</span>
                        <span>300 <?php echo t('egp'); ?></span>
                    </div>
                    <div class="price-item">
                        <span><?php echo t('fixing_fee'); ?> (<?php echo t('estimated_budget'); ?>):</span>
                        <span id="fixingFee">0 <?php echo t('egp'); ?></span>
                    </div>
                    <div class="price-item total">
                        <span><?php echo t('total_cost'); ?>:</span>
                        <span id="totalCost">300 <?php echo t('egp'); ?></span>
                    </div>
                </div>
                
                <div class="buttons">
                    <a href="usermain.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo t('cancel'); ?></a>
                    <button type="submit" class="btn btn-primary"><?php echo t('submit_request'); ?></button>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        const serviceCards = document.querySelectorAll('.service-card');
        const budgetInput = document.querySelector('input[name="budget"]');
        const egpLabel = '<?php echo t("egp"); ?>';
        
        // Service selection
        serviceCards.forEach(card => {
            card.addEventListener('click', function() {
                serviceCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // Update price display
        budgetInput?.addEventListener('input', function() {
            const budget = parseInt(this.value) || 0;
            const total = 300 + budget;
            
            document.getElementById('fixingFee').textContent = budget + ' ' + egpLabel;
            document.getElementById('totalCost').textContent = total + ' ' + egpLabel;
        });
        
        // Form validation before submit
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const selectedService = document.querySelector('input[name="service_type"]:checked');
            if (!selectedService) {
                e.preventDefault();
                alert('<?php echo $lang === "ar" ? "الرجاء اختيار خدمة" : "Please select a service"; ?>');
            }
        });
    </script>
</body>
</html>
