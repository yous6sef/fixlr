<?php
/**
 * FLIX MARKETPLACE - CORE API ENDPOINTS
 * Task State Machine Implementation
 * All endpoints enforce strict state validation
 */

session_start();
header('Content-Type: application/json');

include('db.php');
include('lang.php');

// ============ RESPONSE HELPER ============
function apiResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

// ============ AUTH CHECK ============
function requireAuth($userType = null) {
    if (!isset($_SESSION['user_id'])) {
        apiResponse(false, 'Unauthorized', null, 401);
    }
    if ($userType && $_SESSION['user_type'] !== $userType && $_SESSION['user_type'] !== 'admin') {
        apiResponse(false, 'Forbidden: Insufficient permissions', null, 403);
    }
}

// ============ VALID STATE TRANSITIONS ============
$stateTransitions = [
    'REQUESTED' => ['ACCEPTED', 'CANCELLED'],
    'ACCEPTED' => ['ARRIVED', 'CANCELLED'],
    'ARRIVED' => ['ARRIVAL_CONFIRMED'],
    'ARRIVAL_CONFIRMED' => ['CHECKING'],
    'CHECKING' => ['CHECKING_COMPLETED'],
    'CHECKING_COMPLETED' => ['DECISION'],
    'DECISION' => ['PRICE_PROPOSED', 'CANCELLED'],  // If user says NO, cancel
    'PRICE_PROPOSED' => ['PRICE_ACCEPTED', 'CANCELLED'],
    'PRICE_ACCEPTED' => ['FIXING'],
    'FIXING' => ['COMPLETED'],
    'COMPLETED' => [],
    'CANCELLED' => []
];

$action = $_GET['action'] ?? $_POST['action'] ?? null;

// ============================================================================
// ACTION: CREATE TASK (User creates a new service request)
// ============================================================================
if ($action === 'create_task') {
    requireAuth('user');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!$data['specialization'] || !$data['description'] || !$data['city']) {
        apiResponse(false, 'Missing required fields', null, 400);
    }
    
    // Create task in REQUESTED state
    $query = "INSERT INTO tasks (userId, city, specialization, description, currentStatus) 
              VALUES ($1, $2, $3, $4, 'REQUESTED')
              RETURNING id, currentStatus, createdAt";
    
    $result = pg_query_params($POSTGRES_CONN, $query, [
        $_SESSION['user_id'],
        $data['city'],
        $data['specialization'],
        $data['description']
    ]);
    
    $task = pg_fetch_assoc($result);
    
    apiResponse(true, 'Task created. Notifications sent to available workers.', $task, 201);
}

// ============================================================================
// ACTION: GET AVAILABLE TASKS FOR WORKER
// Filters by: city, specialization, NOT currently assigned
// ============================================================================
else if ($action === 'get_available_tasks') {
    requireAuth('worker');
    
    // Get worker's profile
    $workerQuery = "SELECT id, workLocation, specializations FROM workers WHERE userId = $1";
    $workerResult = pg_query_params($POSTGRES_CONN, $workerQuery, [$_SESSION['user_id']]);
    $worker = pg_fetch_assoc($workerResult);
    
    if (!$worker || $worker['status'] === 'PENDING_APPROVAL') {
        apiResponse(false, 'Worker not approved or not found', null, 403);
    }
    
    if ($worker['isCurrentlyAssigned']) {
        apiResponse(false, 'You already have an active task. Complete it first.', null, 400);
    }
    
    // Get tasks matching worker's location and specializations
    $specs = json_decode($worker['specializations'], true);
    $specPlaceholders = implode(',', array_fill(0, count($specs), '$2'));
    
    $taskQuery = "SELECT id, userId, description, specialization, city, currentStatus, createdAt
                  FROM tasks 
                  WHERE city = $1 
                  AND specialization = ANY($2::text[])
                  AND currentStatus = 'REQUESTED'
                  AND id NOT IN (SELECT taskId FROM tasks WHERE workerId = $3)
                  ORDER BY createdAt DESC
                  LIMIT 20";
    
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [
        $worker['workLocation'],
        '{' . implode(',', $specs) . '}',
        $_SESSION['user_id']
    ]);
    
    $tasks = pg_fetch_all($taskResult);
    
    apiResponse(true, 'Available tasks retrieved', ['tasks' => $tasks ?: []]);
}

// ============================================================================
// ACTION: WORKER ACCEPTS TASK
// State: REQUESTED → ACCEPTED
// ============================================================================
else if ($action === 'accept_task') {
    requireAuth('worker');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    
    // Get task
    $taskQuery = "SELECT id, currentStatus, workerId FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if (!$task) {
        apiResponse(false, 'Task not found', null, 404);
    }
    
    // Validate state transition
    if (!in_array('ACCEPTED', $stateTransitions[$task['currentStatus']] ?? [])) {
        apiResponse(false, "Cannot move from {$task['currentStatus']} to ACCEPTED", null, 400);
    }
    
    // Get worker
    $workerQuery = "SELECT id, isCurrentlyAssigned FROM workers WHERE userId = $1";
    $workerResult = pg_query_params($POSTGRES_CONN, $workerQuery, [$_SESSION['user_id']]);
    $worker = pg_fetch_assoc($workerResult);
    
    // Check if worker already has active task
    if ($worker['isCurrentlyAssigned']) {
        apiResponse(false, 'You already have an active task', null, 400);
    }
    
    // Update task
    pg_query_params($POSTGRES_CONN, 
        "UPDATE tasks SET currentStatus = 'ACCEPTED', workerId = $1, assignedAt = NOW() WHERE id = $2",
        [$worker['id'], $taskId]
    );
    
    // Lock worker
    pg_query_params($POSTGRES_CONN,
        "UPDATE workers SET isCurrentlyAssigned = TRUE WHERE id = $1",
        [$worker['id']]
    );
    
    apiResponse(true, 'Task accepted. You are now assigned.', ['taskId' => $taskId, 'status' => 'ACCEPTED']);
}

// ============================================================================
// ACTION: WORKER ARRIVES
// State: ACCEPTED → ARRIVED
// ============================================================================
else if ($action === 'worker_arrived') {
    requireAuth('worker');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    
    $taskQuery = "SELECT currentStatus, workerId FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['currentStatus'] !== 'ACCEPTED') {
        apiResponse(false, 'Task must be ACCEPTED before marking arrival', null, 400);
    }
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'ARRIVED' WHERE id = $1",
        [$taskId]
    );
    
    apiResponse(true, 'You have arrived. Waiting for user confirmation.', ['status' => 'ARRIVED']);
}

// ============================================================================
// ACTION: USER CONFIRMS ARRIVAL
// State: ARRIVED → ARRIVAL_CONFIRMED
// ============================================================================
else if ($action === 'confirm_arrival') {
    requireAuth('user');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    
    $taskQuery = "SELECT userId, currentStatus FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['userId'] != $_SESSION['user_id']) {
        apiResponse(false, 'Unauthorized: This is not your task', null, 403);
    }
    
    if ($task['currentStatus'] !== 'ARRIVED') {
        apiResponse(false, 'Task must be in ARRIVED state', null, 400);
    }
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'ARRIVAL_CONFIRMED' WHERE id = $1",
        [$taskId]
    );
    
    apiResponse(true, 'Arrival confirmed. Worker can now begin checking.', ['status' => 'ARRIVAL_CONFIRMED']);
}

// ============================================================================
// ACTION: WORKER STARTS CHECKING
// State: ARRIVAL_CONFIRMED → CHECKING
// 300 EGP CHECKING FEE APPLIES
// ============================================================================
else if ($action === 'start_checking') {
    requireAuth('worker');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    
    $taskQuery = "SELECT currentStatus FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['currentStatus'] !== 'ARRIVAL_CONFIRMED') {
        apiResponse(false, 'Task must be in ARRIVAL_CONFIRMED state', null, 400);
    }
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'CHECKING' WHERE id = $1",
        [$taskId]
    );
    
    apiResponse(true, 'Checking started. 300 EGP checking fee applied.', 
        ['status' => 'CHECKING', 'checkingFee' => 300.00]);
}

// ============================================================================
// ACTION: WORKER COMPLETES CHECKING
// State: CHECKING → CHECKING_COMPLETED
// ============================================================================
else if ($action === 'complete_checking') {
    requireAuth('worker');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    $diagnosis = $_POST['diagnosis'] ?? null;
    
    $taskQuery = "SELECT currentStatus FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['currentStatus'] !== 'CHECKING') {
        apiResponse(false, 'Task must be in CHECKING state', null, 400);
    }
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'CHECKING_COMPLETED' WHERE id = $1",
        [$taskId]
    );
    
    apiResponse(true, 'Checking completed. Waiting for user decision.', ['status' => 'CHECKING_COMPLETED']);
}

// ============================================================================
// ACTION: USER DECIDES (Proceed with fix or Cancel)
// If YES: CHECKING_COMPLETED → DECISION → PRICE_PROPOSED
// If NO: CHECKING_COMPLETED → CANCELLED (300 EGP charged)
// ============================================================================
else if ($action === 'user_decision') {
    requireAuth('user');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    $proceedWithFix = ($_POST['proceed'] ?? 'no') === 'yes';
    
    $taskQuery = "SELECT userId, currentStatus, workerId FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['userId'] != $_SESSION['user_id']) {
        apiResponse(false, 'Unauthorized', null, 403);
    }
    
    if ($task['currentStatus'] !== 'CHECKING_COMPLETED') {
        apiResponse(false, 'Task must be in CHECKING_COMPLETED state', null, 400);
    }
    
    if (!$proceedWithFix) {
        // Cancel: User pays 300 EGP checking fee, worker unlocks
        pg_query_params($POSTGRES_CONN,
            "UPDATE tasks SET currentStatus = 'CANCELLED', userDecisionProceedWithFix = FALSE WHERE id = $1",
            [$taskId]
        );
        
        // Unlock worker
        pg_query_params($POSTGRES_CONN,
            "UPDATE workers SET isCurrentlyAssigned = FALSE WHERE id = $1",
            [$task['workerId']]
        );
        
        apiResponse(true, 'Task cancelled. You owe 300 EGP checking fee.', 
            ['status' => 'CANCELLED', 'amountDue' => 300.00]);
    } else {
        // Proceed: Move to DECISION state
        pg_query_params($POSTGRES_CONN,
            "UPDATE tasks SET currentStatus = 'DECISION', userDecisionProceedWithFix = TRUE WHERE id = $1",
            [$taskId]
        );
        
        apiResponse(true, 'Proceeding with fix. Waiting for worker price proposal.', ['status' => 'DECISION']);
    }
}

// ============================================================================
// ACTION: WORKER PROPOSES PRICE
// State: DECISION → PRICE_PROPOSED
// ============================================================================
else if ($action === 'propose_price') {
    requireAuth('worker');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    $fixingPrice = floatval($_POST['price'] ?? 0);
    
    if ($fixingPrice <= 0) {
        apiResponse(false, 'Price must be greater than 0', null, 400);
    }
    
    $taskQuery = "SELECT currentStatus FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['currentStatus'] !== 'DECISION') {
        apiResponse(false, 'Task must be in DECISION state', null, 400);
    }
    
    // Total = checking fee + fixing price
    $totalPrice = 300 + $fixingPrice;
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'PRICE_PROPOSED', fixingPrice = $1, totalPrice = $2 WHERE id = $3",
        [$fixingPrice, $totalPrice, $taskId]
    );
    
    apiResponse(true, 'Price proposed. Waiting for user acceptance.', 
        ['status' => 'PRICE_PROPOSED', 'fixingPrice' => $fixingPrice, 'totalPrice' => $totalPrice]);
}

// ============================================================================
// ACTION: USER ACCEPTS PRICE
// State: PRICE_PROPOSED → PRICE_ACCEPTED
// ============================================================================
else if ($action === 'accept_price') {
    requireAuth('user');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    
    $taskQuery = "SELECT userId, currentStatus FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['userId'] != $_SESSION['user_id']) {
        apiResponse(false, 'Unauthorized', null, 403);
    }
    
    if ($task['currentStatus'] !== 'PRICE_PROPOSED') {
        apiResponse(false, 'Task must be in PRICE_PROPOSED state', null, 400);
    }
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'PRICE_ACCEPTED' WHERE id = $1",
        [$taskId]
    );
    
    apiResponse(true, 'Price accepted. Worker can now proceed with fixing.', ['status' => 'PRICE_ACCEPTED']);
}

// ============================================================================
// ACTION: WORKER MARKS FIXING COMPLETE
// State: PRICE_ACCEPTED → FIXING
// ============================================================================
else if ($action === 'mark_fixing_complete') {
    requireAuth('worker');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    
    $taskQuery = "SELECT currentStatus FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['currentStatus'] !== 'PRICE_ACCEPTED') {
        apiResponse(false, 'Task must be in PRICE_ACCEPTED state', null, 400);
    }
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'FIXING' WHERE id = $1",
        [$taskId]
    );
    
    apiResponse(true, 'Work marked complete. Waiting for user confirmation.', ['status' => 'FIXING']);
}

// ============================================================================
// ACTION: USER CONFIRMS TASK COMPLETION & CLOSES TASK
// State: FIXING → COMPLETED
// Triggers: Rating prompt, Worker unlocked, Task closed
// ============================================================================
else if ($action === 'confirm_completion') {
    requireAuth('user');
    
    $taskId = $_GET['id'] ?? $_POST['id'];
    
    $taskQuery = "SELECT userId, currentStatus, workerId, totalPrice FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($task['userId'] != $_SESSION['user_id']) {
        apiResponse(false, 'Unauthorized', null, 403);
    }
    
    if ($task['currentStatus'] !== 'FIXING') {
        apiResponse(false, 'Task must be in FIXING state', null, 400);
    }
    
    // Mark task completed
    pg_query_params($POSTGRES_CONN,
        "UPDATE tasks SET currentStatus = 'COMPLETED', completedAt = NOW() WHERE id = $1",
        [$taskId]
    );
    
    // Unlock worker
    pg_query_params($POSTGRES_CONN,
        "UPDATE workers SET isCurrentlyAssigned = FALSE WHERE id = $1",
        [$task['workerId']]
    );
    
    // Add to worker's earnings (20% goes to platform, 80% to worker)
    $workerEarnings = $task['totalPrice'] * 0.8;
    $platformFee = $task['totalPrice'] * 0.2;
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE workers SET availableBalance = availableBalance + $1, totalEarnings = totalEarnings + $2, pendingRemittance = pendingRemittance + $3 WHERE id = $4",
        [$workerEarnings, $task['totalPrice'], $platformFee, $task['workerId']]
    );
    
    apiResponse(true, 'Task completed! You will now be prompted for mutual ratings.', 
        ['status' => 'COMPLETED', 'taskId' => $taskId, 'promptRating' => true]);
}

// ============================================================================
// ACTION: SUBMIT MUTUAL RATINGS
// ============================================================================
else if ($action === 'submit_ratings') {
    requireAuth();
    
    $taskId = $_POST['taskId'];
    $userRating = intval($_POST['userRating'] ?? 0);
    $userComment = $_POST['userComment'] ?? '';
    $workerRating = intval($_POST['workerRating'] ?? 0);
    $workerComment = $_POST['workerComment'] ?? '';
    
    if ($userRating < 1 || $userRating > 5) {
        apiResponse(false, 'Invalid rating value', null, 400);
    }
    
    // Determine who is rating
    $taskQuery = "SELECT userId, workerId FROM tasks WHERE id = $1";
    $taskResult = pg_query_params($POSTGRES_CONN, $taskQuery, [$taskId]);
    $task = pg_fetch_assoc($taskResult);
    
    if ($_SESSION['user_type'] === 'user') {
        // User rating worker
        pg_query_params($POSTGRES_CONN,
            "INSERT INTO ratings (taskId, ratedByUserId, ratedToWorkerId, userRating, userComment)
             VALUES ($1, $2, $3, $4, $5)
             ON CONFLICT (taskId) DO UPDATE SET userRating = $4, userComment = $5",
            [$taskId, $task['userId'], $task['workerId'], $userRating, $userComment]
        );
    } else if ($_SESSION['user_type'] === 'worker') {
        // Worker rating user
        pg_query_params($POSTGRES_CONN,
            "INSERT INTO ratings (taskId, ratedByWorkerId, ratedToUserId, workerRating, workerComment)
             VALUES ($1, $2, $3, $4, $5)
             ON CONFLICT (taskId) DO UPDATE SET workerRating = $4, workerComment = $5",
            [$taskId, $task['workerId'], $task['userId'], $workerRating, $workerComment]
        );
    }
    
    // Recalculate average ratings
    $userQuery = "SELECT AVG(userRating) as avgRating, COUNT(*) as total FROM ratings WHERE ratedToUserId = $1";
    $userResult = pg_query_params($POSTGRES_CONN, $userQuery, [$task['userId']]);
    $userStats = pg_fetch_assoc($userResult);
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE users SET totalRating = $1, totalReviews = $2 WHERE id = $3",
        [$userStats['avgRating'] ?: 0, $userStats['total'] ?: 0, $task['userId']]
    );
    
    // Same for worker
    $workerQuery = "SELECT AVG(userRating) as avgRating, COUNT(*) as total FROM ratings WHERE ratedToWorkerId = $1";
    $workerResult = pg_query_params($POSTGRES_CONN, $workerQuery, [$task['workerId']]);
    $workerStats = pg_fetch_assoc($workerResult);
    
    pg_query_params($POSTGRES_CONN,
        "UPDATE users SET totalRating = $1, totalReviews = $2 WHERE id = (SELECT userId FROM workers WHERE id = $3)",
        [$workerStats['avgRating'] ?: 0, $workerStats['total'] ?: 0, $task['workerId']]
    );
    
    apiResponse(true, 'Rating submitted successfully', ['taskId' => $taskId]);
}

// ============================================================================
// ACTION: GET TASK DETAILS
// ============================================================================
else if ($action === 'get_task') {
    requireAuth();
    
    $taskId = $_GET['id'];
    
    $query = "SELECT t.*, u.fullName as userName, u.email as userEmail, w.id as workerId, wu.fullName as workerName
              FROM tasks t
              LEFT JOIN users u ON t.userId = u.id
              LEFT JOIN workers w ON t.workerId = w.id
              LEFT JOIN users wu ON w.userId = wu.id
              WHERE t.id = $1";
    
    $result = pg_query_params($POSTGRES_CONN, $query, [$taskId]);
    $task = pg_fetch_assoc($result);
    
    if (!$task) {
        apiResponse(false, 'Task not found', null, 404);
    }
    
    apiResponse(true, 'Task details retrieved', $task);
}

else {
    apiResponse(false, 'Invalid action', null, 400);
}
?>
