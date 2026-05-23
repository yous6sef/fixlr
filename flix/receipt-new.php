<?php
// ========================================
// FLIX Receipt & Rating - Bilingual
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

// Fetch request and payment details
try {
    $query = "SELECT sr.*, st.name_en as service_name, w.name as worker_name, w.phone as worker_phone, w.total_rating, w.total_reviews,
                     (SELECT AVG(rating) FROM ratings WHERE rated_user_id = sr.worker_id AND rater_type = 'user') as worker_avg_rating
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

// Check if rating already exists
$rating_exists = false;
try {
    $check_rating = pg_query_params($db,
        "SELECT id FROM ratings WHERE service_request_id = $1 AND rater_id = $2",
        [$request_id, $_SESSION['user_id']]
    );
    $rating_exists = pg_num_rows($check_rating) > 0;
} catch (Exception $e) {
    // Continue without checking
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$rating_exists) {
    $rating = $_POST['rating'] ?? 0;
    $review = $_POST['review'] ?? '';
    
    if ($rating < 1 || $rating > 5) {
        $error_message = t('required_field');
    } else {
        try {
            $insert = pg_query_params($db,
                "INSERT INTO ratings (service_request_id, rater_id, rated_user_id, rating, review_text, rater_type, created_at)
                 VALUES ($1, $2, $3, $4, $5, $6, NOW())",
                [$request_id, $_SESSION['user_id'], $request['worker_id'], $rating, $review, 'user']
            );
            
            if ($insert) {
                // Update worker's total rating and reviews count
                $update_worker = pg_query_params($db,
                    "UPDATE workers SET total_rating = total_rating + $1, total_reviews = total_reviews + 1 WHERE id = $2",
                    [$rating, $request['worker_id']]
                );
                
                $success_message = t('thank_you') . ' - ' . t('rating') . '!';
                $rating_exists = true;
            } else {
                $error_message = t('error');
            }
        } catch (Exception $e) {
            $error_message = t('error');
        }
    }
}

// Calculate worker's average rating
$avg_rating = 0;
if ($request['total_reviews'] > 0) {
    $avg_rating = $request['total_rating'] / $request['total_reviews'];
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('service_receipt'); ?> - FLIX</title>
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
        
        .header {
            text-align: center;
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .header-icon {
            font-size: 3rem;
            margin-bottom: var(--spacing-md);
        }
        
        .header h1 {
            font-size: 2rem;
            margin: 0 0 var(--spacing-sm) 0;
            color: var(--color-success);
        }
        
        .header p {
            margin: 0;
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
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--color-neutral-900);
            margin-bottom: var(--spacing-md);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .detail-item {
            border-bottom: 1px solid var(--color-neutral-200);
            padding-bottom: var(--spacing-md);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--color-neutral-600);
            font-size: 0.9rem;
            margin-bottom: var(--spacing-xs);
        }
        
        .detail-value {
            font-size: 1.1rem;
            color: var(--color-neutral-900);
        }
        
        .worker-card {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--color-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .worker-info h3 {
            margin: 0 0 var(--spacing-sm) 0;
            font-size: 1.2rem;
            color: var(--color-neutral-900);
        }
        
        .worker-rating {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin: var(--spacing-sm) 0 var(--spacing-md) 0;
        }
        
        .star {
            color: #fbbf24;
        }
        
        .rating-value {
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .review-count {
            font-size: 0.9rem;
            color: var(--color-neutral-600);
        }
        
        .price-box {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.1) 100%);
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
        
        .rating-section {
            margin-top: var(--spacing-lg);
        }
        
        .stars {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            margin: var(--spacing-lg) 0;
            font-size: 3rem;
        }
        
        .star-btn {
            background: none;
            border: none;
            cursor: pointer;
            opacity: 0.3;
            transition: all 0.3s ease;
            font-size: 2.5rem;
        }
        
        .star-btn:hover,
        .star-btn.active {
            opacity: 1;
            transform: scale(1.2);
        }
        
        .form-group {
            margin-bottom: var(--spacing-md);
        }
        
        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--color-neutral-700);
        }
        
        .form-group textarea {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--color-neutral-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
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
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .worker-card {
                flex-direction: column;
                gap: var(--spacing-md);
            }
            
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">✅</div>
            <h1><?php echo t('congratulations'); ?></h1>
            <p><?php echo t('service_completed'); ?></p>
        </div>
        
        <!-- Service Receipt -->
        <div class="card">
            <div class="card-title">📋 <?php echo t('service_receipt'); ?></div>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label"><?php echo t('services'); ?></div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['service_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><?php echo t('date_completed'); ?></div>
                    <div class="detail-value"><?php echo date('Y-m-d H:i'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><?php echo t('problem_description'); ?></div>
                    <div class="detail-value"><?php echo htmlspecialchars(substr($request['problem_description'], 0, 50)) . '...'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Request ID</div>
                    <div class="detail-value">#<?php echo $request_id; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Worker Details -->
        <div class="card">
            <div class="card-title">👨‍🔧 <?php echo t('worker_details'); ?></div>
            
            <div class="worker-card">
                <div class="worker-info">
                    <h3><?php echo htmlspecialchars($request['worker_name']); ?></h3>
                    <div class="worker-rating">
                        <span class="star">⭐</span>
                        <span class="rating-value"><?php echo number_format($avg_rating, 1); ?>/5</span>
                        <span class="review-count">(<?php echo $request['total_reviews']; ?> <?php echo t('reviews'); ?>)</span>
                    </div>
                    <a href="tel:<?php echo htmlspecialchars($request['worker_phone']); ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">
                        📞 <?php echo htmlspecialchars($request['worker_phone']); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Payment Summary -->
        <div class="card">
            <div class="card-title">💰 <?php echo t('payment_summary'); ?></div>
            
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
        
        <!-- Rating Form -->
        <?php if (!$rating_exists): ?>
        <div class="card">
            <div class="card-title">⭐ <?php echo t('rate_worker'); ?></div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="ratingForm">
                <div class="form-group">
                    <label><?php echo t('rating'); ?> *</label>
                    <div class="stars" id="starRating">
                        <button type="button" class="star-btn" data-rating="1">⭐</button>
                        <button type="button" class="star-btn" data-rating="2">⭐</button>
                        <button type="button" class="star-btn" data-rating="3">⭐</button>
                        <button type="button" class="star-btn" data-rating="4">⭐</button>
                        <button type="button" class="star-btn" data-rating="5">⭐</button>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                </div>
                
                <div class="form-group">
                    <label><?php echo t('write_review'); ?> (<?php echo t('optional'); ?>)</label>
                    <textarea name="review" placeholder="<?php echo t('share_your_experience'); ?>"></textarea>
                </div>
                
                <div class="buttons">
                    <a href="usermain.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">
                        <?php echo t('skip'); ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo t('submit_rating'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="alert alert-success">
                ✅ <?php echo t('thank_you_for_rating'); ?>
            </div>
            
            <div class="buttons">
                <a href="order-new.php?lang=<?php echo $lang; ?>" class="btn btn-primary">
                    <?php echo t('book_again'); ?>
                </a>
                <a href="usermain.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">
                    <?php echo t('back_home'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const starBtns = document.querySelectorAll('.star-btn');
        const ratingInput = document.getElementById('ratingInput');
        
        starBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                
                starBtns.forEach((b, index) => {
                    if (index < rating) {
                        b.classList.add('active');
                    } else {
                        b.classList.remove('active');
                    }
                });
            });
        });
        
        // Form validation
        document.getElementById('ratingForm').addEventListener('submit', function(e) {
            if (parseInt(ratingInput.value) === 0) {
                e.preventDefault();
                alert('<?php echo $lang === "ar" ? "الرجاء اختيار تقييم" : "Please select a rating"; ?>');
            }
        });
    </script>
</body>
</html>
