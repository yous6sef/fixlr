<?php
// Lightweight integration script: insert a task for given user id and list recent tasks
require_once(__DIR__ . '/../core/db.php');

$userId = $argv[1] ?? 1;
$serviceType = $argv[2] ?? 'plumbing';
$specialization = $argv[3] ?? 'Plumbing';

try {
    global $conn;
    $statusHistory = json_encode([['status' => 'REQUESTED', 'timestamp' => date('c')]]);
    $stmt = $conn->prepare("INSERT INTO tasks (userId, city, service_type, specialization, description, currentStatus, urgency, address, googleMapsLink, addressDescription, problemDescription, statusHistory, createdAt, updatedAt) VALUES (:userId, :city, :service_type, :specialization, :description, 'REQUESTED', :urgency, :address, :googleMapsLink, :addressDescription, :problemDescription, :statusHistory, NOW(), NOW())");

    $stmt->execute([
        ':userId' => $userId,
        ':city' => '6th of October City',
        ':service_type' => $serviceType,
        ':specialization' => $specialization,
        ':description' => 'Integration test task created by script',
        ':urgency' => 'Normal',
        ':address' => 'Test Address Example',
        ':googleMapsLink' => 'https://maps.google.com/',
        ':addressDescription' => 'Near the big mall',
        ':problemDescription' => 'Leaking pipe near sink',
        ':statusHistory' => $statusHistory
    ]);

    $id = $conn->lastInsertId() ?: pg_last_oid(null);
    echo "Inserted task id: {$id}\n\n";

    $list = $conn->prepare("SELECT id, specialization, currentStatus, createdAt FROM tasks WHERE userId = :userId ORDER BY createdAt DESC LIMIT 5");
    $list->execute([':userId' => $userId]);
    $rows = $list->fetchAll(PDO::FETCH_ASSOC);
    echo "Recent tasks for user {$userId}:\n";
    foreach ($rows as $r) {
        echo "#{$r['id']} - {$r['specialization']} - {$r['currentStatus']} - {$r['createdAt']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
