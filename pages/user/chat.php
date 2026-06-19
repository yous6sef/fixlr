<?php
session_start();
include('../../core/lang.php');
include('../../core/db.php');

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;
$requestId = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if (!$requestId) {
    header('Location: ./user_requests.php?lang=' . $lang); exit();
}

$requestStmt = $conn->prepare("SELECT * FROM service_requests WHERE id = ?");
$requestStmt->execute([$requestId]);
$request = $requestStmt->fetch(PDO::FETCH_ASSOC);

if (!$request || intval($request['user_id']) !== intval($_SESSION['user_id'])) {
    header('Location: ./user_requests.php?lang=' . $lang); exit();
}

$rootRequestId = !empty($request['request_id']) ? intval($request['request_id']) : intval($request['id']);
$assignedStmt = $conn->prepare("SELECT sr.*, w.name AS worker_name FROM service_requests sr LEFT JOIN workers w ON w.id = sr.worker_id WHERE (sr.request_id = :root OR sr.id = :root) AND sr.status = 'accepted' ORDER BY sr.created_at DESC LIMIT 1");
$assignedStmt->bindParam(':root', $rootRequestId, PDO::PARAM_INT);
$assignedStmt->execute();
$assigned = $assignedStmt->fetch(PDO::FETCH_ASSOC);

$messages = [];
if ($assigned) {
    $messagesStmt = $conn->prepare("SELECT * FROM chat_messages WHERE request_id = :request_id ORDER BY created_at ASC");
    $messagesStmt->bindParam(':request_id', $rootRequestId, PDO::PARAM_INT);
    $messagesStmt->execute();
    $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
}

function safeEcho($v) { return htmlspecialchars($v ?? ''); }
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php $pageTitle = $lang === 'ar' ? 'الدردشة - المستخدم' : 'Chat - User'; include('../../core/seo.php'); ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        :root{--primary:#1A6B4A;--surface:#FFF;--surface-light:#F7F8F6;--text:#141714}
        body{background:var(--surface-light);font-family:inherit;padding:2rem}
        .chat-container{max-width:900px;margin:0 auto;background:var(--surface);border-radius:12px;padding:1rem;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
        .messages{height:360px;overflow:auto;padding:1rem;border:1px solid #EEE;border-radius:8px;background:#FAFBFA}
        .message{margin-bottom:0.75rem;max-width:75%}
        .message.me{margin-left:auto;background:linear-gradient(135deg,var(--primary),#2D9A6C);color:white;padding:0.6rem;border-radius:10px}
        .message.other{background:#fff;padding:0.6rem;border-radius:10px;border:1px solid #E6E6E6}
        .chat-input{display:flex;gap:0.5rem;margin-top:0.75rem}
        .chat-input input{flex:1;padding:0.8rem;border:1px solid #D4D3D0;border-radius:8px}
        .btn{padding:0.7rem 1rem;border-radius:8px;border:none;background:var(--primary);color:#fff;font-weight:700}
        .notice{padding:1rem;background:#F7F8F6;border:1px solid #E6E6E6;border-radius:10px;margin-bottom:1rem;}
    </style>
</head>
<body>
    <div class="chat-container">
        <h2><?php echo $lang === 'ar' ? 'الدردشة مع الفني' : 'Chat with Worker'; ?></h2>
        <?php if (!$assigned): ?>
            <div class="notice">
                <?php echo $lang === 'ar' ? 'لا يوجد عامل معين لهذا الطلب بعد. يرجى قبول عرض عامل أولاً.' : 'No assigned worker for this request yet. Please accept an offer first.'; ?>
            </div>
            <a href="./request_detail.php?lang=<?php echo $lang; ?>&id=<?php echo intval($requestId); ?>" class="btn"><?php echo $lang === 'ar' ? 'عودة إلى تفاصيل الطلب' : 'Back to Request Details'; ?></a>
        <?php else: ?>
            <div class="notice" style="margin-bottom:1rem;">
                <?php echo $lang === 'ar' ? 'المحادثة مع' : 'Chatting with'; ?> <?php echo safeEcho($assigned['worker_name']); ?>
            </div>
            <div class="messages" id="messages" aria-live="polite">
                <?php if (empty($messages)): ?>
                    <div class="message other"><?php echo $lang === 'ar' ? 'ابدأ المحادثة مع العامل المعين.' : 'Start the conversation with the assigned worker.'; ?></div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo intval($message['sender_id']) === intval($_SESSION['user_id']) ? 'me' : 'other'; ?>">
                            <?php echo nl2br(safeEcho($message['message'])); ?>
                            <div style="font-size:0.75rem;color:#6B7280;margin-top:0.35rem;text-align:right;">
                                <?php echo safeEcho(date('Y-m-d H:i', strtotime($message['created_at']))); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form id="chatForm" class="chat-input" onsubmit="return false;">
                <input id="msgInput" placeholder="<?php echo $lang === 'ar' ? 'اكتب رسالة...' : 'Type a message...'; ?>">
                <button id="sendBtn" class="btn"><?php echo $lang === 'ar' ? 'إرسال' : 'Send'; ?></button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($assigned): ?>
    <script>
        const messagesEl = document.getElementById('messages');
        const msgInput = document.getElementById('msgInput');
        const sendBtn = document.getElementById('sendBtn');
        const requestId = <?php echo json_encode($rootRequestId, JSON_HEX_TAG); ?>;

        function addMessage(text, me) {
            const msg = document.createElement('div');
            msg.className = 'message ' + (me ? 'me' : 'other');
            msg.innerHTML = text.replace(/\n/g, '<br>');
            messagesEl.appendChild(msg);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        async function sendMessage() {
            const value = msgInput.value.trim();
            if (!value) return;

            const response = await fetch('../../api/api.php?action=send_chat_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'request_id=' + encodeURIComponent(requestId) + '&message=' + encodeURIComponent(value)
            });
            const result = await response.json();
            if (result.success) {
                addMessage(value, true);
                msgInput.value = '';
            } else {
                alert(result.message || 'Unable to send message.');
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        msgInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendMessage();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
