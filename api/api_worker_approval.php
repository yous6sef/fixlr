<?php
session_start();
include('../core/db.php');

header('Content-Type: application/json');

// Admin guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? null;
$workerId = $_POST['worker_id'] ?? null;

if (!$action || !$workerId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

if (!in_array($action, ['accept', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Determine the new status
    $newStatus = ($action === 'accept') ? 'approved' : 'rejected';
    
    // Update worker status
    $stmt = $conn->prepare("UPDATE workers SET status = ? WHERE id = ?");
    $result = $stmt->execute([$newStatus, $workerId]);
    
    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $action === 'accept' ? 'Worker approved successfully' : 'Worker rejected successfully',
            'status' => $newStatus
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update worker status']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
