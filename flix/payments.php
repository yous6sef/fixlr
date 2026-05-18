<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['user_id'];

// جلب بيانات العامل
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_STR);
$stmt->execute();
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

$earnings = $worker['total_earnings'];

$tax = $earnings * (15/100) ;

$net_profit = $earnings - $tax;

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flix | صفحة المدفوعات</title>
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
        /* Modal Slide Up Animation */
        .modal-slide {
            position: fixed;
            bottom: -100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 2rem 2rem 0 0;
            padding: 2rem;
            z-index: 50;
            transition: bottom 0.3s ease-out;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
        }
        .modal-slide.active {
            bottom: 5rem;
        }
        .modal-backdrop-payment {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 45;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .modal-backdrop-payment.active {
            opacity: 1;
            pointer-events: all;
        }
        .success-animation {
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0.5);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
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
                <h1 class="text-2xl font-extrabold text-indigo-800">Flix <span class="text-emerald-500">المدفوعات</span></h1>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <a href="workermain.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition duration-300">
                    العودة
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div id="root" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="space-y-8">
            
            <!-- Total Profit Card -->
            <div class="p-8 glass-card rounded-3xl shadow-xl border border-white/50">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">💰 إجمالي أرباحك</h2>
                <div class="bg-gradient-to-r from-emerald-50 to-blue-50 rounded-2xl p-8">
                    <p class="text-gray-600 text-lg mb-2">المبلغ الإجمالي</p>
                    <div class="text-5xl font-extrabold text-emerald-600 mb-4">
                        <?= htmlspecialchars($earnings) ?> <span class="text-2xl">EGP</span>
                    </div>
                    <p class="text-gray-500 text-sm">هذا هو إجمالي ما كسبته من تنفيذ الطلبات</p>
                </div>
            </div>

            <!-- Fee Breakdown Card -->
            <div class="p-8 glass-card rounded-3xl shadow-xl border border-white/50">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">📊 تفصيل الرسوم</h2>
                <div class="space-y-4">
                    <!-- Subtotal -->
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl">
                        <span class="text-gray-700 font-semibold">الإجمالي قبل الرسوم</span>
                        <span class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($earnings) ?> EGP</span>
                    </div>

                    <!-- Fee Calculation -->
                    <div class="flex justify-between items-center p-4 bg-red-50 rounded-xl border-2 border-red-200">
                        <span class="text-gray-700 font-semibold">رسوم النظام (15%)</span>
                        <span class="text-2xl font-bold text-red-600"><?= htmlspecialchars($tax) ?> EGP</span>
                    </div>

                    <!-- Net Amount -->
                    <div class="flex justify-between items-center p-4 bg-emerald-50 rounded-xl border-2 border-emerald-200">
                        <span class="text-gray-700 font-semibold">المبلغ الصافي</span>
                        <span class="text-2xl font-bold text-emerald-600"><?= htmlspecialchars($net_profit) ?></span>
                    </div>
                </div>
            </div>

            <!-- InstaPay Payment Info Card -->
            <div class="p-8 glass-card rounded-3xl shadow-xl border border-white/50">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">🏦 معلومات الدفع</h2>
                <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-2xl p-8">
                    <p class="text-gray-600 text-sm mb-4">يرجى تحويل المبلغ المستحق إلى حسابك عبر InstaPay:</p>
                    
                    <div class="bg-white rounded-xl p-6 mb-6 border-2 border-indigo-200">
                        <p class="text-gray-500 text-xs mb-2">حساب InstaPay</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-mono font-bold text-indigo-700">test@instapay</span>
                            <button onclick="copyToClipboard('test@instapay')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-300 text-sm font-semibold">
                                نسخ
                            </button>
                        </div>
                    </div>

                    <div class="bg-yellow-50 rounded-xl p-4 mb-6 border-l-4 border-yellow-500">
                        <p class="text-yellow-800 text-sm"><strong>⚠️ تنبيه:</strong> تأكد من إدخال المبلغ الصحيح: <strong>600 EGP</strong></p>
                    </div>

                    <button onclick="openPaymentModal()" class="w-full bg-gradient-to-r from-emerald-500 to-green-600 text-white py-3 rounded-xl text-lg font-bold hover:from-emerald-600 hover:to-green-700 transition duration-300 shadow-lg shadow-emerald-500/50 hover:scale-[1.01]">
                        ✓ تم الدفع - أدخل بيانات التحويل
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Verification Modal -->
    <div id="payment-modal-backdrop" class="modal-backdrop-payment"></div>
    <div id="payment-modal" class="modal-slide">
        <div class="max-w-md mx-auto">
            <h3 class="text-2xl font-bold text-indigo-700 mb-6 border-b pb-3">تأكيد عملية الدفع</h3>
            
            <div class="space-y-6">
                <!-- Amount Due -->
                <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200">
                    <p class="text-gray-600 text-sm mb-1">المبلغ المستحق</p>
                    <p class="text-3xl font-bold text-indigo-700">600 EGP</p>
                </div>

                <!-- Transaction ID Input -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">معرف التحويل</label>
                    <input 
                        type="text" 
                        id="transaction-id" 
                        placeholder="أدخل معرف التحويل من InstaPay" 
                        class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                    />
                    <p class="text-xs text-gray-500 mt-1">يمكنك العثور على هذا المعرف في تفاصيل التحويل</p>
                </div>

                <!-- Screenshot Upload -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">لقطة شاشة الإيصال</label>
                    <div class="border-2 border-dashed border-indigo-300 rounded-xl p-6 text-center cursor-pointer hover:bg-indigo-50 transition duration-300" onclick="document.getElementById('screenshot-input').click()">
                        <input 
                            type="file" 
                            id="screenshot-input" 
                            accept="image/*" 
                            class="hidden"
                            onchange="handleScreenshotUpload(event)"
                        />
                        <div id="screenshot-preview">
                            <p class="text-indigo-600 font-semibold">📸 اضغط لاختيار صورة</p>
                            <p class="text-gray-500 text-xs mt-1">أو اسحب الصورة هنا</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button 
                        onclick="closePaymentModal()" 
                        class="flex-1 px-4 py-3 bg-gray-300 text-gray-800 font-semibold rounded-xl hover:bg-gray-400 transition duration-300"
                    >
                        إلغاء
                    </button>
                    <button 
                        onclick="submitPayment()" 
                        class="flex-1 px-4 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition duration-300 shadow-lg shadow-emerald-500/50"
                    >
                        إرسال التحويل
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/50">
        <div class="bg-white p-8 rounded-3xl shadow-2xl glass-card border border-emerald-200 success-animation max-w-md mx-4">
            <div class="text-center">
                <div class="text-6xl mb-4">✅</div>
                <h3 class="text-2xl font-bold text-emerald-600 mb-2">تم بنجاح!</h3>
                <p class="text-gray-600 mb-2">تم إرسال بيانات التحويل بنجاح</p>
                <p class="text-sm text-gray-500 mb-6">سيتم التحقق من البيانات خلال 24 ساعة</p>
                
                <button 
                    onclick="closeSuccessModal()" 
                    class="w-full bg-emerald-600 text-white py-3 rounded-xl font-bold hover:bg-emerald-700 transition duration-300"
                >
                    حسناً
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
            </a>
            <a class="nav-btn active" data-nav="payments" href="payments.php">
                <span class="text-2xl">💳</span>
                <span>المدفوعات</span>
            </a>
            <a class="nav-btn" data-nav="profile" href="profile.php">
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

        const navigateTo = (page) => {
            const navBtns = document.querySelectorAll('.nav-btn');
            navBtns.forEach(btn => btn.classList.remove('active'));
            
            const activeBtn = document.querySelector(`[data-nav="${page}"]`);
            if (activeBtn) activeBtn.classList.add('active');
            
            if (page === 'tasks') {
                window.location.href = './workermain.html';
            } else if (page === 'profile') {
                window.location.href = './profile.html';
            }
        };

        const copyToClipboard = (text) => {
            navigator.clipboard.writeText(text).then(() => {
                showCtaMessage('تم نسخ البيانات');
            });
        };

        const openPaymentModal = () => {
            const modal = document.getElementById('payment-modal');
            const backdrop = document.getElementById('payment-modal-backdrop');
            
            modal.classList.add('active');
            backdrop.classList.add('active');
        };

        const closePaymentModal = () => {
            const modal = document.getElementById('payment-modal');
            const backdrop = document.getElementById('payment-modal-backdrop');
            
            modal.classList.remove('active');
            backdrop.classList.remove('active');
            
            document.getElementById('transaction-id').value = '';
            document.getElementById('screenshot-preview').innerHTML = '<p class="text-indigo-600 font-semibold">📸 اضغط لاختيار صورة</p><p class="text-gray-500 text-xs mt-1">أو اسحب الصورة هنا</p>';
        };

        const handleScreenshotUpload = (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('screenshot-preview').innerHTML = `
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-700 mb-2">✅ تم اختيار الصورة</p>
                            <img src="${e.target.result}" class="w-full h-40 object-cover rounded-lg mb-2">
                            <p class="text-xs text-gray-500">${file.name}</p>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        };

        const submitPayment = () => {
            const transactionId = document.getElementById('transaction-id').value.trim();
            const screenshotInput = document.getElementById('screenshot-input');
            
            if (!transactionId) {
                showCtaMessage('الرجاء إدخال معرف التحويل', true);
                return;
            }
            
            if (!screenshotInput.files.length) {
                showCtaMessage('الرجاء اختيار لقطة شاشة الإيصال', true);
                return;
            }

            closePaymentModal();
            
            const successModal = document.getElementById('success-modal');
            successModal.classList.remove('hidden');
            
            setTimeout(() => {
                closeSuccessModal();
            }, 4000);
        };

        const closeSuccessModal = () => {
            const successModal = document.getElementById('success-modal');
            successModal.classList.add('hidden');
            showCtaMessage('شكراً لك! سيتم التحقق من بياناتك قريباً');
        };

        // Close modal when clicking backdrop
        document.getElementById('payment-modal-backdrop').addEventListener('click', closePaymentModal);
    </script>
</body>
</html>