<?php
/**
 * api.php
 * 
 * REST API endpoints for real-time updates
 * Serves JSON responses for socket.io client and frontend
 * 
 * Endpoints:
 *  - GET /api.php?action=get_orders
 *  - POST /api.php?action=submit_offer
 *  - POST /api.php?action=accept_order
 *  - POST /api.php?action=reject_order
 *  - POST /api.php?action=counter_offer
 *  - GET /api.php?action=order_status&id=ORDER_ID
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

include("../core/db.php");

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        // ============= GET ORDERS =============
        case 'get_orders':
            $response = getOrders($conn, $userId);
            break;

        // ============= USER ACTIONS =============
        case 'accept_order':
            $response = acceptOrder($conn, $userId, $_POST['order_id'] ?? null);
            break;

        case 'reject_order':
            $response = rejectOrder($conn, $userId, $_POST['order_id'] ?? null);
            break;

        case 'counter_offer':
            $response = submitCounterOffer($conn, $userId, $_POST['order_id'] ?? null, $_POST['budget'] ?? null);
            break;

        // ============= WORKER ACTIONS =============
        case 'submit_offer':
            $response = submitOffer($conn, $userId, $_POST['order_id'] ?? null, $_POST['price'] ?? null);
            break;

        case 'complete_order':
            $response = completeOrder($conn, $userId, $_POST['order_id'] ?? null);
            break;

        // ============= STATUS CHECKS =============
        case 'order_status':
            $response = getOrderStatus($conn, $userId, $_GET['id'] ?? null);
            break;

        case 'get_chat_messages':
            $response = getChatMessages($conn, $userId, $_GET['request_id'] ?? null);
            break;

        case 'send_chat_message':
            $response = sendChatMessage($conn, $userId, $_POST['request_id'] ?? null, $_POST['message'] ?? null);
            break;

        case 'user_stats':
            $response = getUserStats($conn, $userId);
            break;

        case 'worker_stats':
            $response = getWorkerStats($conn, $userId);
            break;

        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Unknown action'];
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit();


// =============== HELPER FUNCTIONS ===============

/**
 * Get all orders for user (pending, accepted, completed)
 */
function getOrders($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                sr.id, sr.description, sr.address, sr.budget, sr.worker_price,
                sr.status, sr.negotiation_state, sr.worker_id, sr.created_at,
                sr.negotiation_started_at, sr.negotiation_ended_at,
                w.name AS worker_name, u.username, u.phone AS user_phone
            FROM service_requests sr
            LEFT JOIN workers w ON w.id = sr.worker_id
            LEFT JOIN users u ON u.id = sr.user_id
            WHERE sr.user_id = :user_id
            ORDER BY sr.created_at DESC
            LIMIT 50
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $orders,
            'count' => count($orders)
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * User accepts a worker's offer
 */
function acceptOrder($conn, $userId, $orderId) {
    if (!$orderId) {
        return ['success' => false, 'message' => 'Order ID required'];
    }

    try {
        // First, verify authorization and get the request_id
        $getRequestId = $conn->prepare("
            SELECT id, request_id, user_id 
            FROM service_requests 
            WHERE id = :order_id
        ");
        $getRequestId->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $getRequestId->execute();
        $orderData = $getRequestId->fetch(PDO::FETCH_ASSOC);
        
        if (!$orderData) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        if (intval($orderData['user_id']) !== intval($userId)) {
            return ['success' => false, 'message' => 'Unauthorized - Order does not belong to this user'];
        }
        
        $requestId = !empty($orderData['request_id']) ? intval($orderData['request_id']) : intval($orderData['id']);

        // Update the accepted order to 'accepted' status
        $stmt = $conn->prepare("
            UPDATE service_requests 
            SET status = 'accepted', 
                negotiation_state = 'accepted',
                negotiation_ended_at = NOW()
            WHERE id = :order_id
        ");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Mark all other worker submissions for the same request_id as rejected
            $deleteCompeting = $conn->prepare("
                UPDATE service_requests 
                SET status = 'rejected', negotiation_state = 'rejected'
                WHERE request_id = :request_id AND id != :accepted_id
            ");
            $deleteCompeting->bindParam(':request_id', $requestId, PDO::PARAM_INT);
            $deleteCompeting->bindParam(':accepted_id', $orderId, PDO::PARAM_INT);
            $deleteCompeting->execute();
            
            return [
                'success' => true,
                'message' => 'Order accepted successfully',
                'orderId' => $orderId,
                'status' => 'accepted'
            ];
        } else {
            return ['success' => false, 'message' => 'Could not accept order'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * User rejects a worker's offer
 */
function rejectOrder($conn, $userId, $orderId) {
    if (!$orderId) {
        return ['success' => false, 'message' => 'Order ID required'];
    }

    try {
        // First verify the order belongs to this user's request
        $verifyStmt = $conn->prepare("
            SELECT sr.id, sr.user_id 
            FROM service_requests sr
            WHERE sr.id = :order_id
        ");
        $verifyStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $verifyStmt->execute();
        $orderData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orderData || intval($orderData['user_id']) !== intval($userId)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $stmt = $conn->prepare("
            UPDATE service_requests 
            SET status = 'rejected',
                worker_price = NULL, 
                worker_id = NULL,
                negotiation_state = 'rejected',
                negotiation_ended_at = NOW()
            WHERE id = :order_id AND user_id = :user_id
        ");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Order rejected successfully',
                'orderId' => $orderId
            ];
        } else {
            return ['success' => false, 'message' => 'Could not reject order'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * User submits counter offer
 */
function submitCounterOffer($conn, $userId, $orderId, $budget) {
    if (!$orderId || !$budget) {
        return ['success' => false, 'message' => 'Order ID and budget required'];
    }

    if (!is_numeric($budget) || $budget <= 0) {
        return ['success' => false, 'message' => 'Invalid budget amount'];
    }

    try {
        $stmt = $conn->prepare("
            UPDATE service_requests 
            SET budget = :budget,
                negotiation_state = 'countered',
                negotiation_ended_at = NULL
            WHERE id = :order_id AND user_id = :user_id AND status = 'pending'
        ");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindParam(':budget', $budget, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Counter offer submitted',
                'orderId' => $orderId,
                'counterPrice' => $budget
            ];
        } else {
            return ['success' => false, 'message' => 'Could not submit counter offer'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Worker submits price offer for a pending order
 */
function submitOffer($conn, $userId, $orderId, $price) {
    if (!$orderId || !$price) {
        return ['success' => false, 'message' => 'Order ID and price required'];
    }

    if (!is_numeric($price) || $price <= 0) {
        return ['success' => false, 'message' => 'Invalid price amount'];
    }

    try {
        $stmt = $conn->prepare("
            UPDATE service_requests 
            SET worker_id = :worker_id,
                worker_price = :price,
                negotiation_state = 'offered',
                negotiation_started_at = COALESCE(negotiation_started_at, NOW())
            WHERE id = :order_id AND worker_id IS NULL AND status = 'pending'
        ");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':worker_id', $userId, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Offer submitted successfully',
                'orderId' => $orderId,
                'price' => $price
            ];
        } else {
            return ['success' => false, 'message' => 'Could not submit offer'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Worker marks order as completed
 */
function completeOrder($conn, $userId, $orderId) {
    if (!$orderId) {
        return ['success' => false, 'message' => 'Order ID required'];
    }

    try {
        $stmt = $conn->prepare("
            UPDATE service_requests 
            SET status = 'completed',
                completed_at = NOW()
            WHERE id = :order_id AND worker_id = :worker_id AND status = 'accepted'
        ");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':worker_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Order marked as completed',
                'orderId' => $orderId,
                'status' => 'completed'
            ];
        } else {
            return ['success' => false, 'message' => 'Could not complete order'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get single order status
 */
function getOrderStatus($conn, $userId, $orderId) {
    if (!$orderId) {
        return ['success' => false, 'message' => 'Order ID required'];
    }

    try {
        $stmt = $conn->prepare("
            SELECT id, status, negotiation_state, worker_price, budget, worker_id
            FROM service_requests 
            WHERE id = :order_id AND (user_id = :user_id OR worker_id = :user_id)
        ");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            return ['success' => true, 'data' => $order];
        } else {
            return ['success' => false, 'message' => 'Order not found'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getRootRequestId($conn, $requestId) {
    $stmt = $conn->prepare("SELECT request_id FROM service_requests WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $requestId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    return !empty($row['request_id']) ? intval($row['request_id']) : intval($requestId);
}

function getAssignedRequestRecord($conn, $rootRequestId) {
    $stmt = $conn->prepare("SELECT * FROM service_requests WHERE (request_id = :root OR id = :root) AND status = 'accepted' ORDER BY created_at DESC LIMIT 1");
    $stmt->bindParam(':root', $rootRequestId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getChatMessages($conn, $userId, $requestId) {
    if (!$requestId) {
        return ['success' => false, 'message' => 'Request ID required'];
    }

    $rootRequestId = getRootRequestId($conn, $requestId);
    if (!$rootRequestId) {
        return ['success' => false, 'message' => 'Request not found'];
    }

    $assigned = getAssignedRequestRecord($conn, $rootRequestId);
    if (!$assigned) {
        return ['success' => false, 'message' => 'No assigned worker for this request'];
    }

    if ($assigned['user_id'] != $userId && $assigned['worker_id'] != $userId) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE request_id = :request_id ORDER BY created_at ASC");
    $stmt->bindParam(':request_id', $rootRequestId, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $messages];
}

function sendChatMessage($conn, $userId, $requestId, $message) {
    if (!$requestId || !is_string($message) || trim($message) === '') {
        return ['success' => false, 'message' => 'Request ID and message are required'];
    }

    $rootRequestId = getRootRequestId($conn, $requestId);
    if (!$rootRequestId) {
        return ['success' => false, 'message' => 'Request not found'];
    }

    $assigned = getAssignedRequestRecord($conn, $rootRequestId);
    if (!$assigned) {
        return ['success' => false, 'message' => 'No assigned worker for this request'];
    }

    if ($assigned['user_id'] != $userId && $assigned['worker_id'] != $userId) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    $senderType = $_SESSION['role'] ?? ($assigned['worker_id'] == $userId ? 'worker' : 'user');
    if (!in_array($senderType, ['user', 'worker', 'admin'], true)) {
        $senderType = 'user';
    }

    $stmt = $conn->prepare("INSERT INTO chat_messages (request_id, sender_type, sender_id, message) VALUES (:request_id, :sender_type, :sender_id, :message)");
    $stmt->bindParam(':request_id', $rootRequestId, PDO::PARAM_INT);
    $stmt->bindParam(':sender_type', $senderType, PDO::PARAM_STR);
    $stmt->bindParam(':sender_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Message sent', 'data' => ['request_id' => $rootRequestId, 'sender_type' => $senderType, 'sender_id' => $userId, 'message' => $message]];
    }

    return ['success' => false, 'message' => 'Could not send message'];
}

/**
 * Get user dashboard stats
 */
function getUserStats($conn, $userId) {
    try {
        $stats = [];

        // Pending orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM service_requests WHERE user_id = :user_id AND status = 'pending'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Accepted orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM service_requests WHERE user_id = :user_id AND status = 'accepted'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['accepted'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Completed orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM service_requests WHERE user_id = :user_id AND status = 'completed'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total spent
        $stmt = $conn->prepare("SELECT COALESCE(SUM(COALESCE(worker_price, budget)), 0) as total FROM service_requests WHERE user_id = :user_id AND status = 'completed'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['totalSpent'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

        return ['success' => true, 'data' => $stats];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get worker dashboard stats
 */
function getWorkerStats($conn, $userId) {
    try {
        $stats = [];

        // Incoming orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending' AND worker_id IS NULL");


        // Active orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM service_requests WHERE worker_id = :user_id AND status = 'accepted'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Completed today
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM service_requests WHERE worker_id = :user_id AND status = 'completed' AND DATE(completed_at) = CURRENT_DATE");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['completedToday'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Today's revenue
        $stmt = $conn->prepare("SELECT COALESCE(SUM(COALESCE(worker_price, budget)), 0) as total FROM service_requests WHERE worker_id = :user_id AND status = 'completed' AND DATE(completed_at) = CURRENT_DATE");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['todayRevenue'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

        // Average rating
        $stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) as avg FROM reviews_user WHERE worker_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $stats['avgRating'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['avg']);

        return ['success' => true, 'data' => $stats];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
