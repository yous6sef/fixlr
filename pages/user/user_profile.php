<?php
session_start();
include('../../core/db.php');
include('../../core/lang.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT id, name, email, phone, city, address, user_type, account_status, total_rating, total_reviews, total_tasks FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ../user/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $pageTitle = $lang === 'ar' ? 'ملفي الشخصي - البيانات والإعدادات' : 'My Profile - Account Settings';
        include('../../core/seo.php');
    ?>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
        }
        .card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            padding: 32px;
        }
        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #1f2937;
        }
        .header a {
            background: #4f46e5;
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .header a:hover {
            background: #4338ca;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
        .field {
            background: #f8fafc;
            border-radius: 14px;
            padding: 18px;
        }
        .field h2 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #374151;
        }
        .field p {
            margin: 0;
            color: #111827;
            font-size: 15px;
            line-height: 1.7;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }
        .footer a {
            background: #ef4444;
            color: #fff;
            padding: 14px 22px;
            border-radius: 12px;
            text-decoration: none;
        }
        .footer a.secondary {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>الملف الشخصي</h1>
                <div>
                    <a class="secondary" href="./user_dashboard.php">العودة إلى اللوحة</a>
                    <a href="./logout.php">تسجيل الخروج</a>
                </div>
            </div>
            <div class="profile-grid">
                <div class="field">
                    <h2>الاسم</h2>
                    <p><?= htmlspecialchars($user['name']) ?></p>
                </div>
                <div class="field">
                    <h2>البريد الإلكتروني</h2>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div class="field">
                    <h2>رقم الهاتف</h2>
                    <p><?= htmlspecialchars($user['phone']) ?></p>
                </div>
                <div class="field">
                    <h2>المدينة</h2>
                    <p><?= htmlspecialchars($user['city']) ?></p>
                </div>
                <div class="field">
                    <h2>العنوان</h2>
                    <p><?= htmlspecialchars($user['address']) ?: 'غير موجود' ?></p>
                </div>
                <div class="field">
                    <h2>نوع الحساب</h2>
                    <p><?= htmlspecialchars($user['user_type']) ?></p>
                </div>
                <div class="field">
                    <h2>حالة الحساب</h2>
                    <p><?= htmlspecialchars($user['account_status']) ?></p>
                </div>
                <div class="field">
                    <h2>التقييم</h2>
                    <p><?= round($user['total_rating'], 1) ?> / 5 (<?= intval($user['total_reviews']) ?> تقييم)</p>
                </div>
                <div class="field">
                    <h2>عدد المهام</h2>
                    <p><?= intval($user['total_tasks']) ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
