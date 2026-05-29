<?php
require_once __DIR__ . '/config.php';

function ai_generate_service_description($serviceType, $address, $city) {
    $prompt = "اكتب وصفًا موجزًا وواضحًا لطلب خدمة " . trim($serviceType) . ". الموقع: " . trim($address) . ", المدينة: " . trim($city) . ". اجعل الوصف مناسبًا للفنيين باللغة العربية وواضحًا للعملاء.";
    $payload = [
        'prompt' => [
            'text' => $prompt,
        ],
        'temperature' => 0.7,
        'maxOutputTokens' => 120,
    ];

    return ai_call_google_api($payload);
}

function ai_call_google_api($payload) {
    $apiKey = AI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta2/models/" . AI_MODEL . ":generate?key=" . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return "(اقتراح الوصف غير متاح الآن، يرجى إدخال الوصف بنفسك.)";
    }

    $data = json_decode($result, true);
    if (!empty($data['candidates'][0]['output'])) {
        return trim($data['candidates'][0]['output']);
    }

    if (!empty($data['outputText'])) {
        return trim($data['outputText']);
    }

    return "(اقتراح الوصف غير متاح الآن، يرجى إدخال الوصف بنفسك.)";
}

function ai_generate_admin_insights($summaryText) {
    $prompt = "أنت مساعد إداري لواجهة فليكس. اقرأ الملخص التالي وقدم تقريرًا قصيرًا باللغة العربية يشرح أداء المنصة اليوم والتوصيات لإدارة الإيرادات والتأخيرات في الدفع: \n" . trim($summaryText);
    $payload = [
        'prompt' => [
            'text' => $prompt,
        ],
        'temperature' => 0.65,
        'maxOutputTokens' => 130,
    ];

    return ai_call_google_api($payload);
}
