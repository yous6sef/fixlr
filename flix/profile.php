<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM workers WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate stats
$tasksStmt = $conn->prepare("SELECT COUNT(*) as total_tasks FROM service_requests WHERE worker_id = :id AND status = 'completed'");
$tasksStmt->bindParam(':id', $id, PDO::PARAM_STR);
$tasksStmt->execute();
$totalTasks = $tasksStmt->fetch(PDO::FETCH_ASSOC)['total_tasks'];

$ratingStmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating FROM reviews_worker WHERE worker_id = :id");
$ratingStmt->bindParam(':id', $id, PDO::PARAM_STR);
$ratingStmt->execute();
$avgRating = $ratingStmt->fetch(PDO::FETCH_ASSOC)['avg_rating'];

$acceptedStmt = $conn->prepare("SELECT COUNT(*) as total_accepted FROM service_requests WHERE worker_id = :id AND status IN ('accepted', 'completed')");
$acceptedStmt->bindParam(':id', $id, PDO::PARAM_STR);
$acceptedStmt->execute();
$totalAccepted = $acceptedStmt->fetch(PDO::FETCH_ASSOC)['total_accepted'];

$reliability = $totalAccepted > 0 ? round(($totalTasks / $totalAccepted) * 100, 1) : 0;

$grossEarnings = $user['total_earnings'] ?? 0;
$pendingCommission = $user['pending_commission'] ?? 0;
$netEarnings = $grossEarnings - $pendingCommission;

// Get completed tasks
$tasksHistoryStmt = $conn->prepare("SELECT sr.*, u.name as user_name, rw.rating as user_rating FROM service_requests sr LEFT JOIN users u ON u.id = sr.us_id LEFT JOIN reviews_worker rw ON rw.request_id = sr.id AND rw.worker_id::text = sr.worker_id::text WHERE sr.worker_id::text = :id::text AND sr.status = 'completed' ORDER BY sr.completed_at DESC LIMIT 10");
$tasksHistoryStmt->bindParam(':id', $id, PDO::PARAM_STR);
$tasksHistoryStmt->execute();
$tasksHistory = $tasksHistoryStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stm = $conn->prepare("UPDATE workers SET password = :password WHERE id = :id");
        $stm->bindParam(':password', $hashed, PDO::PARAM_STR);
        $stm->bindParam(':id', $id, PDO::PARAM_STR);
        $stm->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flix | الملف الشخصي للفني</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0f2f1 100%);
        }
        .glass-card {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .neon-shadow-cta {
            box-shadow: 0 0 20px rgba(52, 211, 163, 0.7);
        }
        /* Bottom Navigation Bar Styles */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-top: 1px solid rgba(165, 180, 252, 0.3);
            z-index: 40;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        }
        .bottom-nav-container {
            max-width: 7xl;
            margin: 0 auto;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .nav-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s duration;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #4b5563;
        }
        .nav-btn:hover {
            background: rgba(165, 180, 252, 0.1);
            color: #4f46e5;
            transform: translateY(-2px);
        }
        .nav-btn.active {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        body {
            padding-bottom: 6rem;
        }
        /* Task History Item */
        .task-item {
            transition: all 0.3s ease;
        }
        .task-item:hover {
            transform: translateX(-5px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
        }
        .ok{
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s duration;
            cursor: pointer;
            border: none;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            width: 40%;
        }
    </style>
</head>
<body dir="rtl" class="min-h-screen">

    <div id="cta-message" class="fixed top-5 left-1/2 -translate-x-1/2 bg-emerald-500 text-gray-900 p-3 rounded-lg shadow-xl neon-shadow-cta z-[100] transition-opacity duration-500 opacity-0 hidden font-bold">
        ...
    </div>

    <!-- Header Navigation Bar -->
    <header class="bg-white/90 backdrop-blur-md shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4 space-x-reverse">
                <h1 class="text-2xl font-extrabold text-indigo-800">Flix <span class="text-blue-500">الملف الشخصي</span></h1>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <a href='workermain.php' class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition duration-300">
                    العودة
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div id="root" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="space-y-8">
            
            <!-- Worker Profile Card -->
            <div class="p-8 glass-card rounded-3xl shadow-xl border border-white/50">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">👤 معلومات الفني</h2>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Profile Picture Upload -->
                    <div class="space-y-4">
                        <div class="relative p-6 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl border-2 border-dashed border-indigo-300 text-center cursor-pointer hover:border-indigo-500 hover:bg-indigo-100 transition duration-300"
                             onclick="document.getElementById('profile-image-input').click()">
                            <input 
                                type="file" 
                                id="profile-image-input" 
                                accept="image/*" 
                                capture="environment"
                                class="hidden"
                                onchange="handleProfileImageUpload(event)"
                            />
                            <div id="profile-image-preview" class="space-y-2">
                                <p class="text-4xl">📷</p>
                                <p class="text-indigo-700 font-bold text-lg">أضف صورة الملف الشخصي</p>
                                <p class="text-sm text-gray-600">اضغط لاختيار من الكاميرا أو الصور</p>
                            </div>
                        </div>
                        <button 
                            id="save-profile-btn"
                            onclick="saveProfileImage()" 
                            class="w-full px-4 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition duration-300 shadow-lg shadow-emerald-500/50 hidden"
                        >
                            ✓ حفظ الصورة
                        </button>
                    </div>

                    <!-- Profile Info -->
                    <div class="space-y-4">
                        <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200">
                            <p class="text-gray-600 text-sm mb-1">معرف الفني</p>
                            <p class="text-xl font-bold text-indigo-700 font-mono"><?= htmlspecialchars($user['id']) ?></p>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                            <p class="text-gray-600 text-sm mb-1">الاسم</p>
                            <p class="text-xl font-bold text-blue-700"><?= htmlspecialchars($user['name']) ?></p>
                        </div>
                        <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                            <p class="text-gray-600 text-sm mb-1">التخصص</p>
                            <p class="text-xl font-bold text-green-700"><?= htmlspecialchars($user['specialization']) ?></p>
                        </div>
                        <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                            <p class="text-gray-600 text-sm mb-1">تغيير كلمة السر</p>
                            <form method="post">
                            <input class="text-xl text-700" id = "password" name="password" type="password"><br><br>
                            <button type="submit" class="ok">تم</button>
                            </form>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="p-3 bg-yellow-50 rounded-xl border border-yellow-200 text-center">
                                <p class="text-gray-600 text-xs mb-1">الطلبات</p>
                                <p class="text-2xl font-bold text-yellow-700"><?= $totalTasks ?></p>
                            </div>
                            <div class="p-3 bg-purple-50 rounded-xl border border-purple-200 text-center">
                                <p class="text-gray-600 text-xs mb-1">التقييم</p>
                                <p class="text-lg font-bold text-purple-700"><?= number_format($avgRating, 1) ?>⭐</p>
                            </div>
                            <div class="p-3 bg-red-50 rounded-xl border border-red-200 text-center">
                                <p class="text-gray-600 text-xs mb-1">الموثوقية</p>
                                <p class="text-xl font-bold text-red-600"><?= $reliability ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earnings Summary -->
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Gross Earnings -->
                <div class="p-6 glass-card rounded-3xl shadow-xl border border-white/50">
                    <p class="text-gray-600 text-sm mb-2">إجمالي الأرباح</p>
                    <p class="text-4xl font-bold text-gray-800 mb-1"><?= number_format($grossEarnings, 2) ?> EGP</p>
                    <p class="text-xs text-gray-500">قبل الرسوم</p>
                </div>

                <!-- Fees -->
                <div class="p-6 glass-card rounded-3xl shadow-xl border border-white/50">
                    <p class="text-gray-600 text-sm mb-2">الرسوم المستحقة (20%)</p>
                    <p class="text-4xl font-bold text-red-600 mb-1"><?= number_format($pendingCommission, 2) ?> EGP</p>
                    <p class="text-xs text-gray-500">بانتظار الدفع</p>
                </div>

                <!-- Net Earnings -->
                <div class="p-6 glass-card rounded-3xl shadow-xl border border-white/50 ring-2 ring-emerald-400">
                    <p class="text-gray-600 text-sm mb-2">الأرباح الصافية</p>
                    <p class="text-4xl font-bold text-emerald-600 mb-1"><?= number_format($netEarnings, 2) ?> EGP</p>
                    <p class="text-xs text-gray-500">بعد الرسوم</p>
                </div>
            </div>

            <!-- Task History -->
            <div class="p-8 glass-card rounded-3xl shadow-xl border border-white/50">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">📋 سجل الطلبات</h2>
                
                <div class="space-y-4">
                    <?php if (empty($tasksHistory)): ?>
                        <p class="text-center text-gray-500">لا توجد طلبات مكتملة بعد.</p>
                    <?php else: ?>
                        <?php foreach ($tasksHistory as $task): ?>
                            <div class="task-item p-6 bg-gradient-to-r from-emerald-50 to-blue-50 rounded-2xl border border-emerald-200">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($task['description']) ?></h4>
                                        <p class="text-sm text-gray-600">العميل: <?= htmlspecialchars($task['user_name']) ?> | العنوان: <?= htmlspecialchars($task['address']) ?></p>
                                    </div>
                                    <span class="px-4 py-2 bg-emerald-500 text-white rounded-full text-sm font-bold">✓ منتهي</span>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-600">السعر</p>
                                        <p class="text-xl font-bold text-gray-800"><?= number_format($task['final_price'] ?? $task['worker_price'] ?? $task['budget'], 2) ?> EGP</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600">التاريخ</p>
                                        <p class="text-sm font-semibold text-gray-800"><?= date('d M Y', strtotime($task['completed_at'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600">التقييم</p>
                                        <p class="text-lg font-bold text-yellow-500">
                                            <?php 
                                            $rating = $task['user_rating'] ?? 0;
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '⭐' : '☆';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

                    <!-- Task Item 4 -->
                    <div class="task-item p-6 bg-gradient-to-r from-pink-50 to-orange-50 rounded-2xl border border-pink-200">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="text-lg font-bold text-gray-800">تنظيف وصيانة الخطوط</h4>
                                <p class="text-sm text-gray-600">العنوان: شارع الثورة، المنصورة</p>
                            </div>
                            <span class="px-4 py-2 bg-emerald-500 text-white rounded-full text-sm font-bold">✓ منتهي</span>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-600">السعر</p>
                                <p class="text-xl font-bold text-gray-800">900 EGP</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">التاريخ</p>
                                <p class="text-sm font-semibold text-gray-800">5 ديسمبر 2024</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">التقييم</p>
                                <p class="text-lg font-bold text-yellow-500">⭐⭐⭐⭐⭐</p>
                            </div>
                        </div>
                    </div>

                    <!-- Task Item 5 -->
                    <div class="task-item p-6 bg-gradient-to-r from-indigo-50 to-cyan-50 rounded-2xl border border-indigo-200">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="text-lg font-bold text-gray-800">إصلاح محبس الماء الرئيسي</h4>
                                <p class="text-sm text-gray-600">العنوان: شارع صلاح الدين، الإسكندرية</p>
                            </div>
                            <span class="px-4 py-2 bg-emerald-500 text-white rounded-full text-sm font-bold">✓ منتهي</span>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-600">السعر</p>
                                <p class="text-xl font-bold text-gray-800">600 EGP</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">التاريخ</p>
                                <p class="text-sm font-semibold text-gray-800">2 ديسمبر 2024</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">التقييم</p>
                                <p class="text-lg font-bold text-yellow-500">⭐⭐⭐⭐⭐</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View More Button -->
                <button class="w-full mt-6 px-6 py-3 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-xl font-bold hover:from-indigo-600 hover:to-blue-700 transition duration-300">
                    عرض جميع الطلبات
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav">
        <div class="bottom-nav-container">
            <a class="nav-btn" data-nav="tasks" href="workermain.php">
                <span class="text-2xl">📋</span>
                <span>الطلبات</span>
            </button>
            <a class="nav-btn" data-nav="payments" href="payments.php">
                <span class="text-2xl">💳</span>
                <span>المدفوعات</span>
            </button>
            <a class="nav-btn active" data-nav="profile" href="profile.php">
                <span class="text-2xl">👤</span>
                <span>الملف الشخصي</span>
            </button>
        </div>
    </nav>

    <script>
        const showCtaMessage = (message, isError = false) => {
            const ctaMessage = document.getElementById('cta-message');
            if (!ctaMessage) return;
            
            ctaMessage.textContent = message;
            ctaMessage.classList.remove('hidden', 'opacity-0', 'bg-emerald-500', 'bg-red-500');
            ctaMessage.classList.add(isError ? 'bg-red-500' : 'bg-emerald-500');
            ctaMessage.classList.add('opacity-100');

            setTimeout(() => {
                ctaMessage.classList.remove('opacity-100');
                ctaMessage.classList.add('opacity-0');
                setTimeout(() => ctaMessage.classList.add('hidden'), 500); 
            }, 3000);
        };

        let selectedProfileImage = null;

        const handleProfileImageUpload = (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    selectedProfileImage = e.target.result;
                    const preview = document.getElementById('profile-image-preview');
                    preview.innerHTML = `
                        <div class="space-y-4">
                            <img src="${e.target.result}" class="w-full h-64 object-cover rounded-xl shadow-lg">
                            <p class="text-sm text-gray-700 font-semibold">✓ تم اختيار الصورة: ${file.name}</p>
                        </div>
                    `;
                    document.getElementById('save-profile-btn').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        };

        const saveProfileImage = () => {
            if (!selectedProfileImage) {
                showCtaMessage('الرجاء اختيار صورة أولاً', true);
                return;
            }

            // Store in localStorage for demo purposes
            localStorage.setItem('workerProfileImage', selectedProfileImage);
            
            showCtaMessage('تم حفظ صورة الملف الشخصي بنجاح ✓');
            
            // Hide save button
            document.getElementById('save-profile-btn').classList.add('hidden');
        };

        const navigateTo = (page) => {
            const navBtns = document.querySelectorAll('.nav-btn');
            navBtns.forEach(btn => btn.classList.remove('active'));
            
            const activeBtn = document.querySelector(`[data-nav="${page}"]`);
            if (activeBtn) activeBtn.classList.add('active');
            
            if (page === 'tasks') {
                window.location.href = './workermain.html';
            } else if (page === 'payments') {
                window.location.href = './payments.html';
            }
        };

        // Load saved profile image on page load
        window.addEventListener('load', () => {
            const savedImage = localStorage.getItem('workerProfileImage');
            if (savedImage) {
                const preview = document.getElementById('profile-image-preview');
                preview.innerHTML = `
                    <div class="space-y-4">
                        <img src="${savedImage}" class="w-full h-64 object-cover rounded-xl shadow-lg">
                        <p class="text-sm text-gray-700 font-semibold">✓ صورتك الحالية</p>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>