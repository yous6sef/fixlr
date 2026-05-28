<?php
/**
 * API: Get Devices by Service Type
 */
session_start();
include('../core/db.php');

header('Content-Type: application/json');

if (!isset($_GET['service_id'])) {
    echo json_encode(['success' => false, 'devices' => []]);
    exit();
}

$serviceId = $_GET['service_id'];

try {
    $stmt = $conn->prepare("SELECT id, name_ar FROM devices WHERE service_type_id = ? ORDER BY name_ar");
    $stmt->execute([$serviceId]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'devices' => $devices]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'devices' => []]);
}
?>
