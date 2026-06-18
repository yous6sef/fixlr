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
    <?php $pageTitle = $lang === 'ar' ? 'رسوم المهمة - الفني' : 'Task Fees - Worker'; include('../../core/seo.php'); ?>
    <link rel="stylesheet" href="../../public/css/app.css">
    <style>
        :root{--primary:#1A6B4A;--danger:#DC2626}
        body{background:#F7F8F6;font-family:inherit;padding:2rem}
        .card{max-width:800px;margin:0 auto;background:#fff;padding:1rem;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
        .row{display:flex;gap:1rem;align-items:center}
        .input{padding:0.7rem;border:1px solid #D4D3D0;border-radius:8px;width:150px}
        .btn{padding:0.6rem 1rem;border-radius:8px;border:none;background:var(--primary);color:#fff;font-weight:700}
        .muted{color:#6B6F6B}
        .rating{display:flex;gap:6px;margin-top:0.5rem}
        .star{font-size:1.4rem;cursor:pointer;color:#D1D5DB}
        .star.active{color:gold}
    </style>
</head>
<body>
    <div class="card">
        <h2><?php echo $lang === 'ar' ? 'رسوم المهمة' : 'Task Fees'; ?></h2>
        <p class="muted"><?php echo $lang === 'ar' ? 'أضف رسومًا اختيارية للمهمة أو اتركها فارغة.' : 'Add optional fees for the task or skip if none.'; ?></p>

        <div style="margin-top:1rem;">
            <label><?php echo $lang === 'ar' ? 'الرسوم الإضافية (EGP)' : 'Additional Fees (EGP)'; ?></label>
            <div class="row" style="margin-top:0.5rem">
                <input id="feeInput" class="input" type="number" placeholder="0">
                <button id="saveFee" class="btn"><?php echo $lang === 'ar' ? 'حفظ' : 'Save'; ?></button>
                <div style="margin-left:auto;color:#4A5249;"><?php echo $lang === 'ar' ? 'آخر تحديث: لا يوجد' : 'Last update: none'; ?></div>
            </div>
        </div>

        <hr style="margin:1rem 0">

        <h3><?php echo $lang === 'ar' ? 'التقييم المتبادل' : 'Mutual Rating'; ?></h3>
        <div style="display:flex;gap:2rem;flex-wrap:wrap;">
            <div style="flex:1;min-width:220px">
                <div><?php echo $lang === 'ar' ? 'تقييم الفني للمستخدم' : 'Worker rates User'; ?></div>
                <div class="rating" id="rateUser">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <textarea id="rateUserText" rows="3" style="width:100%;margin-top:0.5rem;border:1px solid #D4D3D0;padding:0.6rem;border-radius:8px" placeholder="<?php echo $lang === 'ar' ? 'وصف التقييم (اختياري)' : 'Description (optional)'; ?>"></textarea>
            </div>

            <div style="flex:1;min-width:220px">
                <div><?php echo $lang === 'ar' ? 'تقييم المستخدم للفني' : 'User rates Worker'; ?></div>
                <div class="rating" id="rateWorker">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <textarea id="rateWorkerText" rows="3" style="width:100%;margin-top:0.5rem;border:1px solid #D4D3D0;padding:0.6rem;border-radius:8px" placeholder="<?php echo $lang === 'ar' ? 'وصف التقييم (اختياري)' : 'Description (optional)'; ?>"></textarea>
            </div>
        </div>

        <div style="text-align:<?php echo $lang === 'ar' ? 'right' : 'left'; ?>; margin-top:1rem;">
            <button id="submitRatings" class="btn"><?php echo $lang === 'ar' ? 'إرسال' : 'Submit'; ?></button>
        </div>
    </div>

    <script>
        (function(){
            // fee save demo
            document.getElementById('saveFee').addEventListener('click', function(){
                const v = document.getElementById('feeInput').value;
                if(!v){
                    alert('<?php echo $lang === 'ar' ? 'لم يتم إضافة رسوم.' : 'No fee added.'; ?>');
                    return;
                }
                alert('<?php echo $lang === 'ar' ? 'الرسوم محفوظة (واجهة فقط).' : 'Fee saved (UI only).'; ?>\n' + v + ' EGP');
            });

            function setupRating(id){
                const container = document.getElementById(id);
                container.addEventListener('click', function(e){
                    if(!e.target.classList.contains('star')) return;
                    const v = +e.target.dataset.value;
                    Array.from(container.children).forEach(s=> s.classList.toggle('active', +s.dataset.value <= v));
                });
            }
            setupRating('rateUser');
            setupRating('rateWorker');

            document.getElementById('submitRatings').addEventListener('click', function(){
                const userStars = document.querySelectorAll('#rateUser .star.active').length;
                const workerStars = document.querySelectorAll('#rateWorker .star.active').length;
                const userText = document.getElementById('rateUserText').value.trim();
                const workerText = document.getElementById('rateWorkerText').value.trim();
                alert('<?php echo $lang === 'ar' ? 'التقييمات للعرض فقط (واجهة فقط).' : 'Ratings are demo-only (UI only).'; ?>\nWorker→User: '+userStars+'\nUser→Worker: '+workerStars);
            });
        })();
    </script>
</body>
</html>
