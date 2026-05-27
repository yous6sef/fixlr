i <?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$requestId = isset($_GET['request_id']) && is_numeric($_GET['request_id']) ? (int)$_GET['request_id'] : null;
if (!$requestId) {
    header('Location: admin.php');
    exit();
}

$requestStmt = $conn->prepare("SELECT sr.*, u.name AS user_name, w.name AS worker_name FROM service_requests sr LEFT JOIN users u ON u.id = sr.us_id LEFT JOIN workers w ON w.id::text = sr.worker_id::text WHERE sr.id = :id LIMIT 1");
$requestStmt->bindParam(':id', $requestId, PDO::PARAM_INT);
$requestStmt->execute();
$request = $requestStmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: admin.php');
    exit();
}

$messagesStmt = $conn->prepare("SELECT * FROM chat_messages WHERE request_id = :request_id ORDER BY created_at ASC");
$messagesStmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
$messagesStmt->execute();
$messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فليكس | دردشة الطلب <?= htmlspecialchars($request['id']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background: #f8fafc; }</n    .glass-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); }</n    </style>
</head>
<body class="min-h-screen">
    <header class="bg-white shadow sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">دردشة الطلب #<?= htmlspecialchars($request['id']) ?></h1>
                <p class="text-slate-500 mt-1">العميل: <?= htmlspecialchars($request['user_name']) ?> - الفني: <?= htmlspecialchars($request['worker_name'] ?: 'غير محدد') ?></p>
            </div>
            <a href="admin.php" class="px-4 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">العودة إلى لوحة الإدارة</a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <div class="glass-card rounded-3xl p-6 shadow-sm border border-slate-200">
            <h2 class="text-xl font-bold text-slate-900 mb-4">الرسائل</h2>
            <?php if (empty($messages)): ?>
                <p class="text-slate-500">لا توجد رسائل بعد لهذا الطلب.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($messages as $message): ?>
                        <div class="rounded-3xl p-4 border <?= $message['sender_type'] === 'admin' ? 'border-indigo-300 bg-indigo-50 text-slate-900' : ($message['sender_type'] === 'worker' ? 'border-emerald-300 bg-emerald-50 text-slate-900' : 'border-slate-300 bg-white text-slate-900') ?>">
                            <div class="flex justify-between items-center mb-2 text-sm text-slate-600">
                                <span><?= htmlspecialchars($message['sender_type']) ?> #<?= htmlspecialchars($message['sender_id']) ?></span>
                                <span><?= htmlspecialchars(date('Y-m-d H:i', strtotime($message['created_at']))) ?></span>
                            </div>
                            <p class="text-right"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
