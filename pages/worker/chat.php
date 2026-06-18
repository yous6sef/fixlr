<?php
session_start();
include('../../core/lang.php');
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php $pageTitle = $lang === 'ar' ? 'الدردشة - الفني' : 'Chat - Worker'; include('../../core/seo.php'); ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        :root{--primary:#1A6B4A;--surface:#FFF;--surface-light:#F7F8F6}
        body{background:var(--surface-light);font-family:inherit;padding:2rem}
        .chat-container{max-width:900px;margin:0 auto;background:var(--surface);border-radius:12px;padding:1rem;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
        .messages{height:360px;overflow:auto;padding:1rem;border:1px solid #EEE;border-radius:8px;background:#FAFBFA}
        .message{margin-bottom:0.75rem;max-width:75%}
        .message.me{margin-left:auto;background:linear-gradient(135deg,var(--primary),#2D9A6C);color:white;padding:0.6rem;border-radius:10px}
        .message.other{background:#fff;padding:0.6rem;border-radius:10px;border:1px solid #E6E6E6}
        .chat-input{display:flex;gap:0.5rem;margin-top:0.75rem}
        .chat-input input{flex:1;padding:0.8rem;border:1px solid #D4D3D0;border-radius:8px}
        .btn{padding:0.7rem 1rem;border-radius:8px;border:none;background:var(--primary);color:#fff;font-weight:700}
        .rating{display:flex;gap:6px;margin-top:1rem}
        .star{font-size:1.4rem;cursor:pointer;color:#D1D5DB}
        .star.active{color:gold}
    </style>
</head>
<body>
    <div class="chat-container">
        <h2><?php echo $lang === 'ar' ? 'الدردشة مع العميل' : 'Chat with User'; ?></h2>
        <div class="messages" id="messages"></div>

        <form id="chatForm" class="chat-input" onsubmit="return false;">
            <input id="msgInput" placeholder="<?php echo $lang === 'ar' ? 'اكتب رسالة...' : 'Type a message...'; ?>">
            <button id="sendBtn" class="btn"><?php echo $lang === 'ar' ? 'إرسال' : 'Send'; ?></button>
        </form>

        <div class="review">
            <div><?php echo $lang === 'ar' ? 'قيّم المستخدم' : 'Rate the User'; ?></div>
            <div class="rating" id="ratingWorker">
                <span class="star" data-value="1">★</span>
                <span class="star" data-value="2">★</span>
                <span class="star" data-value="3">★</span>
                <span class="star" data-value="4">★</span>
                <span class="star" data-value="5">★</span>
            </div>
            <textarea id="reviewText" rows="3" style="width:100%;margin-top:0.5rem;border:1px solid #D4D3D0;padding:0.6rem;border-radius:8px" placeholder="<?php echo $lang === 'ar' ? 'اكتب وصفًا لتقييمك (اختياري)' : 'Write a description for your rating (optional)'; ?>"></textarea>
            <div style="text-align:<?php echo $lang === 'ar' ? 'right' : 'left'; ?>; margin-top:0.5rem;">
                <button id="submitReview" class="btn"><?php echo $lang === 'ar' ? 'إرسال التقييم' : 'Submit Rating'; ?></button>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const messages = document.getElementById('messages');
            const msgInput = document.getElementById('msgInput');
            const sendBtn = document.getElementById('sendBtn');

            function addMessage(text, who){
                const div = document.createElement('div');
                div.className = 'message ' + (who==='me'?'me':'other');
                div.textContent = text;
                messages.appendChild(div);
                messages.scrollTop = messages.scrollHeight;
            }

            sendBtn.addEventListener('click', function(){
                const v = msgInput.value.trim();
                if(!v) return;
                addMessage(v, 'me');
                msgInput.value = '';
                setTimeout(()=> addMessage('<?php echo $lang === 'ar' ? 'حسناً — شكراً، سأقوم بالتحديث.' : 'Okay — thanks, I will update.'; ?>','other'),700);
            });

            document.getElementById('ratingWorker').addEventListener('click', function(e){
                if(!e.target.classList.contains('star')) return;
                const v = +e.target.dataset.value;
                Array.from(this.children).forEach(s=> s.classList.toggle('active', +s.dataset.value <= v));
            });

            document.getElementById('submitReview').addEventListener('click', function(){
                const active = document.querySelectorAll('#ratingWorker .star.active').length;
                const text = document.getElementById('reviewText').value.trim();
                alert('<?php echo $lang === 'ar' ? 'تقييم مرئي فقط (واجهة فقط).' : 'Rating is front-end only (UI demo).'; ?>\nStars: ' + active + '\n' + text);
            });
        })();
    </script>
</body>
</html>
