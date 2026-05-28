<?php
/**
 * API: Submit Rating
 */
session_start();
include('../core/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$userId = $_SESSION['user_id'];
$requestId = $_POST['request_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$reviewText = $_POST['review_text'] ?? '';

if (!$requestId || !$rating || !is_numeric($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit();
}

try {
    // Get request details
    $reqStmt = $conn->prepare("SELECT user_id, worker_id FROM service_requests WHERE id = ? AND status = 'completed'");
    $reqStmt->execute([$requestId]);
    $request = $reqStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or not completed']);
        exit();
    }

    // Determine who is rating whom
    if ($userId == $request['user_id']) {
        $ratedUserId = $request['worker_id'];
        $raterType = 'user';
    } elseif ($userId == $request['worker_id']) {
        $ratedUserId = $request['user_id'];
        $raterType = 'worker';
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Insert rating
    $stmt = $conn->prepare("
        INSERT INTO ratings 
        (service_request_id, rater_id, rated_user_id, rating, review_text, rater_type)
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT (service_request_id, rater_id)
        DO UPDATE SET
            rating = EXCLUDED.rating,
            review_text = EXCLUDED.review_text,
            rated_user_id = EXCLUDED.rated_user_id,
            rater_type = EXCLUDED.rater_type
    ");
    $stmt->execute([$requestId, $userId, $ratedUserId, $rating, $reviewText, $raterType]);

    // Update user's average rating
    $ratingStmt = $conn->prepare("
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as total_reviews
        FROM ratings
        WHERE rated_user_id = ?
    ");
    $ratingStmt->execute([$ratedUserId]);
    $ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);

    $updateStmt = $conn->prepare("
        UPDATE users 
        SET total_rating = ?, total_reviews = ?
        WHERE id = ?
    ");
    $updateStmt->execute([round($ratingData['avg_rating'], 2), $ratingData['total_reviews'], $ratedUserId]);

    echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
