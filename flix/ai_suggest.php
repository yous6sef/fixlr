<?php
require_once __DIR__ . '/google_ai.php';

header('Content-Type: application/json; charset=UTF-8');

$service = trim($_POST['service'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');

if ($service === '' || $address === '' || $city === '') {
    echo json_encode(['success' => false, 'message' => 'يرجى ملء نوع الخدمة والموقع والمدينة.']);
    exit;
}

$suggestion = ai_generate_service_description($service, $address, $city);

echo json_encode(['success' => true, 'description' => $suggestion]);
