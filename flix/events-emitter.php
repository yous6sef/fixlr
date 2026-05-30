<?php
/**
 * events-emitter.php
 * 
 * Module for emitting real-time events from PHP backend to WebSocket server
 * This bridges PHP code to the Node.js socket.io server running on port 3000
 * 
 * Usage:
 *   require 'events-emitter.php';
 *   emitToUser($userId, 'worker.offered', ['workerId' => 789, 'price' => 50]);
 *   emitToWorker($workerId, 'user.accepted', ['requestId' => 123]);
 */

define('SOCKET_SERVER_URL', 'http://localhost:3000');

/**
 * Send event to specific user
 * 
 * @param int|string $userId User ID
 * @param string $event Event type (e.g., 'worker.offered')
 * @param array $data Event data
 * @param int|null $requestId Request ID (optional, for context)
 * @return bool Success
 */
function emitToUser($userId, $event, $data = [], $requestId = null) {
  $payload = [
    'event' => $event,
    'toUserId' => $userId,
    'toWorkerId' => null,
    'requestId' => $requestId,
    'broadcast' => null,
    'data' => $data
  ];
  
  return _sendToSocket($payload, "user $userId");
}

/**
 * Send event to specific worker
 * 
 * @param int|string $workerId Worker ID
 * @param string $event Event type (e.g., 'user.countered')
 * @param array $data Event data
 * @param int|null $requestId Request ID (optional, for context)
 * @return bool Success
 */
function emitToWorker($workerId, $event, $data = [], $requestId = null) {
  $payload = [
    'event' => $event,
    'toUserId' => null,
    'toWorkerId' => $workerId,
    'requestId' => $requestId,
    'broadcast' => null,
    'data' => $data
  ];
  
  return _sendToSocket($payload, "worker $workerId");
}

/**
 * Send event to all parties involved in a request
 * (both user and worker)
 * 
 * @param int $requestId Request ID
 * @param string $event Event type
 * @param array $data Event data
 * @return bool Success
 */
function broadcastRequest($requestId, $event, $data = []) {
  $payload = [
    'event' => $event,
    'toUserId' => null,
    'toWorkerId' => null,
    'requestId' => $requestId,
    'broadcast' => null,
    'data' => $data
  ];
  
  return _sendToSocket($payload, "request $requestId");
}

/**
 * Broadcast event to all users
 * 
 * @param string $event Event type
 * @param array $data Event data
 * @return bool Success
 */
function broadcastToAllUsers($event, $data = []) {
  $payload = [
    'event' => $event,
    'toUserId' => null,
    'toWorkerId' => null,
    'requestId' => null,
    'broadcast' => 'users',
    'data' => $data
  ];
  
  return _sendToSocket($payload, 'all users');
}

/**
 * Broadcast event to all workers
 * 
 * @param string $event Event type
 * @param array $data Event data
 * @return bool Success
 */
function broadcastToAllWorkers($event, $data = []) {
  $payload = [
    'event' => $event,
    'toUserId' => null,
    'toWorkerId' => null,
    'requestId' => null,
    'broadcast' => 'workers',
    'data' => $data
  ];
  
  return _sendToSocket($payload, 'all workers');
}

/**
 * Log event to real_time_events table
 * 
 * @param PDO $conn Database connection
 * @param array $eventData Event details
 * @return bool Success
 */
function logEvent($conn, $eventData) {
  try {
    $stmt = $conn->prepare("
      INSERT INTO real_time_events 
      (event_type, request_id, from_user_id, to_user_id, event_data, created_at) 
      VALUES (:event_type, :request_id, :from_user_id, :to_user_id, :event_data, NOW())
    ");
    
    $stmt->execute([
      ':event_type' => $eventData['event_type'],
      ':request_id' => $eventData['request_id'] ?? null,
      ':from_user_id' => $eventData['from_user_id'] ?? null,
      ':to_user_id' => $eventData['to_user_id'] ?? null,
      ':event_data' => json_encode($eventData['data'] ?? [])
    ]);
    
    return true;
  } catch (Exception $e) {
    error_log("[Event Log Error] " . $e->getMessage());
    return false;
  }
}

/**
 * Internal: Send payload to socket server
 */
function _sendToSocket($payload, $recipient) {
  $json = json_encode($payload);
  
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => SOCKET_SERVER_URL . '/emit-event',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $json,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Content-Length: ' . strlen($json)
    ]
  ]);
  
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);
  
  if ($error) {
    error_log("[Socket Emit Error] $recipient: $error");
    return false;
  }
  
  if ($httpCode !== 200) {
    error_log("[Socket Emit Failed] $recipient: HTTP $httpCode - $response");
    return false;
  }
  
  // Uncomment for debugging:
  // error_log("[Socket Emit OK] $recipient: " . $payload['event']);
  
  return true;
}

/**
 * Check if socket server is available
 */
function isSocketServerAvailable() {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => SOCKET_SERVER_URL . '/health',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_CONNECTTIMEOUT => 2
  ]);
  
  curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  
  return $httpCode === 200;
}

?>
