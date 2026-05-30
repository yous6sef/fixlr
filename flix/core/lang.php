<?php
// ========================================
// FLIX Language & Localization System
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language to Arabic, allow English
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar');

// Validate language
if (!in_array($lang, ['ar', 'en'])) {
    $lang = 'ar';
}

// Store in session
$_SESSION['lang'] = $lang;

// Determine direction (RTL for Arabic, LTR for English)
$dir = $lang === 'ar' ? 'rtl' : 'ltr';

// ========================================
// Translations
// ========================================

$translations = [
    'ar' => [
        // Navigation & General
        'flix' => 'FLIX',
        'home' => 'الرئيسية',
        'services' => 'الخدمات',
        'about' => 'عننا',
        'contact' => 'اتصل',
        'login' => 'دخول',
        'signup' => 'إنشاء حساب',
        'logout' => 'تسجيل خروج',
        'profile' => 'الملف الشخصي',
        'dashboard' => 'لوحة التحكم',
        'language' => 'اللغة',
        'arabic' => 'العربية',
        'english' => 'English',
        
        // Auth Pages
        'welcome_to_flix' => 'مرحباً بك في FLIX',
        'sign_in' => 'تسجيل الدخول',
        'sign_up' => 'إنشاء حساب جديد',
        'i_have_account' => 'لدي حساب بالفعل',
        'create_account' => 'إنشاء حساب',
        'email_or_phone' => 'البريد الإلكتروني أو رقم الهاتف',
        'password' => 'كلمة المرور',
        'confirm_password' => 'تأكيد كلمة المرور',
        'full_name' => 'الاسم الكامل',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'city' => 'المدينة',
        'select_city' => 'اختر المدينة',
        'user_type' => 'نوع المستخدم',
        'i_need_service' => 'أحتاج لخدمة',
        'i_provide_service' => 'أنا متخصص في الخدمات',
        'specialization' => 'التخصص',
        'select_specialization' => 'اختر التخصص',
        'documents' => 'المستندات',
        'id_front' => 'صورة الهوية الأمامية',
        'id_back' => 'صورة الهوية الخلفية',
        'certificate' => 'الشهادات المهنية',
        'cv' => 'السيرة الذاتية',
        'forgot_password' => 'هل نسيت كلمة المرور؟',
        'remember_me' => 'تذكرني',
        'terms_agree' => 'أوافق على الشروط والأحكام',
        'i_agree' => 'أوافق',
        
        // Errors & Messages
        'invalid_credentials' => 'بيانات دخول غير صحيحة',
        'account_not_approved' => 'حسابك قيد الانتظار الموافقة من الإدارة',
        'account_inactive' => 'حسابك معطل حالياً',
        'error_creating_account' => 'خطأ في إنشاء الحساب',
        'success_signup' => 'تم إنشاء الحساب بنجاح! يرجى تسجيل الدخول.',
        'required_field' => 'هذا الحقل مطلوب',
        'invalid_email' => 'البريد الإلكتروني غير صحيح',
        'password_mismatch' => 'كلمات المرور غير متطابقة',
        'weak_password' => 'كلمة المرور ضعيفة جداً',
        'account_exists' => 'الحساب موجود بالفعل',
        'error' => 'خطأ',
        'success' => 'نجاح',
        'warning' => 'تحذير',
        'info' => 'معلومة',
        
        // User Dashboard
        'total_requests' => 'إجمالي الطلبات',
        'completed_tasks' => 'المهام المكتملة',
        'average_rating' => 'متوسط التقييم',
        'total_spent' => 'إجمالي الإنفاق',
        'active_requests' => 'الطلبات النشطة',
        'recent_professionals' => 'المتخصصون الأخيرون',
        'browse_services' => 'استعرض الخدمات',
        'new_request' => '+ طلب جديد',
        'view_all' => 'عرض الكل',
        'view_profile' => 'عرض الملف الشخصي',
        'hire' => 'التوظيف',
        'in_progress' => 'قيد التنفيذ',
        'pending' => 'قيد الانتظار',
        'completed' => 'مكتمل',
        'status' => 'الحالة',
        
        // Order/Request Pages
        'create_service_request' => 'إنشاء طلب خدمة جديد',
        'select_service' => 'اختر الخدمة',
        'select_device' => 'اختر الجهاز (اختياري)',
        'describe_problem' => 'وصف المشكلة',
        'problem_description' => 'وصف تفصيلي للمشكلة',
        'your_location' => 'موقعك',
        'budget' => 'الميزانية المتوقعة (EGP)',
        'estimated_budget' => 'الميزانية المتوقعة',
        'estimated_cost' => 'التكلفة المتوقعة',
        'submit_request' => 'إرسال الطلب',
        'request_submitted' => 'تم إرسال الطلب بنجاح!',
        'waiting_for_offers' => 'في انتظار العروض من المتخصصين',
        'offers' => 'العروض المتلقاة',
        'no_offers_yet' => 'لم تتلقَ أي عروض بعد',
        'accept_offer' => 'قبول العرض',
        'reject_offer' => 'رفض العرض',
        
        // Payment
        'checking_fee' => 'رسوم الكشف الأولي',
        'fixing_fee' => 'رسوم الإصلاح',
        'total_cost' => 'إجمالي التكلفة',
        'currency_egp' => 'جنيه مصري',
        'egp' => 'ج.م',
        'pay_now' => 'ادفع الآن',
        'confirm_payment' => 'تأكيد الدفع',
        'payment_successful' => 'تم الدفع بنجاح!',
        'payment_pending' => 'انتظر تأكيد الدفع',
        'payment_failed' => 'فشل الدفع، حاول مرة أخرى',
        'payment_method' => 'طريقة الدفع',
        'card' => 'بطاقة ائتمان',
        'wallet' => 'المحفظة الرقمية',
        'transfer' => 'تحويل بنكي',
        
        // Tracking
        'track_request' => 'تتبع الطلب',
        'request_status' => 'حالة الطلب',
        'worker_location' => 'موقع المتخصص',
        'estimated_arrival' => 'الوصول المتوقع',
        'task_progress' => 'تقدم المهمة',
        'worker_arrived' => 'وصل المتخصص',
        'checking_in_progress' => 'جاري الكشف',
        'checking_completed' => 'انتهى الكشف',
        'fixing_in_progress' => 'جاري الإصلاح',
        'fixing_completed' => 'انتهى الإصلاح',
        'task_completed' => 'انتهت المهمة',
        'mark_arrived' => 'تم الوصول',
        'start_checking' => 'بدء الكشف',
        'complete_checking' => 'إنهاء الكشف',
        'start_fixing' => 'بدء الإصلاح',
        'complete_fixing' => 'إنهاء الإصلاح',
        
        // Worker Pages
        'worker_dashboard' => 'لوحة عامل الخدمة',
        'available_requests' => 'الطلبات المتاحة',
        'my_tasks' => 'مهامي',
        'my_earnings' => 'أرباحي',
        'accept' => 'قبول',
        'reject' => 'رفض',
        'daily_earnings' => 'الأرباح اليومية',
        'commission' => 'العمولة (20%)',
        'net_earnings' => 'الأرباح الصافية',
        'total_today' => 'إجمالي اليوم',
        'submit_payment_request' => 'طلب الدفع',
        'payment_submitted' => 'تم إرسال طلب الدفع للمراجعة',
        'earnings_history' => 'سجل الأرباح',
        
        // Receipt & Completion
        'service_receipt' => 'إيصال الخدمة',
        'service_completed' => 'اكتملت الخدمة بنجاح',
        'congratulations' => 'مبروك!',
        'date_completed' => 'تاريخ الإكمال',
        'worker_details' => 'تفاصيل المتخصص',
        'service_details' => 'تفاصيل الخدمة',
        'rate_worker' => 'قيّم المتخصص',
        'rating' => 'التقييم',
        'review' => 'التقييم الكتابي',
        'thank_you' => 'شكراً لاستخدامك FLIX',
        'book_again' => 'احجز مرة أخرى',
        'give_rating' => 'إعطاء تقييم',
        'write_review' => 'كتابة تقييم',
        'stars' => 'نجوم',
        'excellent' => 'ممتاز',
        'good' => 'جيد',
        'average' => 'متوسط',
        'poor' => 'سيء',
        
        // General Buttons
        'next' => 'التالي',
        'continue' => 'متابعة',
        'cancel' => 'إلغاء',
        'confirm' => 'تأكيد',
        'delete' => 'حذف',
        'edit' => 'تعديل',
        'save' => 'حفظ',
        'close' => 'إغلاق',
        'back' => 'رجوع',
        'download' => 'تحميل',
        'share' => 'مشاركة',
        'call' => 'اتصل',
        'message' => 'رسالة',
        'rate' => 'قيّم',
        'try_again' => 'حاول مرة أخرى',
    ],
    'en' => [
        // Navigation & General
        'flix' => 'FLIX',
        'home' => 'Home',
        'services' => 'Services',
        'about' => 'About',
        'contact' => 'Contact',
        'login' => 'Sign In',
        'signup' => 'Sign Up',
        'logout' => 'Sign Out',
        'profile' => 'Profile',
        'dashboard' => 'Dashboard',
        'language' => 'Language',
        'arabic' => 'العربية',
        'english' => 'English',
        
        // Auth Pages
        'welcome_to_flix' => 'Welcome to FLIX',
        'sign_in' => 'Sign In',
        'sign_up' => 'Create Account',
        'i_have_account' => 'I already have an account',
        'create_account' => 'Create Account',
        'email_or_phone' => 'Email or Phone',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'full_name' => 'Full Name',
        'phone' => 'Phone Number',
        'email' => 'Email Address',
        'city' => 'City',
        'select_city' => 'Select City',
        'user_type' => 'User Type',
        'i_need_service' => 'I need services',
        'i_provide_service' => 'I provide services',
        'specialization' => 'Specialization',
        'select_specialization' => 'Select Specialization',
        'documents' => 'Documents',
        'id_front' => 'ID Front Side',
        'id_back' => 'ID Back Side',
        'certificate' => 'Professional Certificates',
        'cv' => 'CV/Resume',
        'forgot_password' => 'Forgot Password?',
        'remember_me' => 'Remember Me',
        'terms_agree' => 'I agree to Terms and Conditions',
        'i_agree' => 'I Agree',
        
        // Errors & Messages
        'invalid_credentials' => 'Invalid credentials',
        'account_not_approved' => 'Your account is pending approval',
        'account_inactive' => 'Your account is currently inactive',
        'error_creating_account' => 'Error creating account',
        'success_signup' => 'Account created successfully! Please sign in.',
        'required_field' => 'This field is required',
        'invalid_email' => 'Invalid email address',
        'password_mismatch' => 'Passwords do not match',
        'weak_password' => 'Password is too weak',
        'account_exists' => 'Account already exists',
        'error' => 'Error',
        'success' => 'Success',
        'warning' => 'Warning',
        'info' => 'Info',
        
        // User Dashboard
        'total_requests' => 'Total Requests',
        'completed_tasks' => 'Completed Tasks',
        'average_rating' => 'Average Rating',
        'total_spent' => 'Total Spent',
        'active_requests' => 'Active Requests',
        'recent_professionals' => 'Recent Professionals',
        'browse_services' => 'Browse Services',
        'new_request' => '+ New Request',
        'view_all' => 'View All',
        'view_profile' => 'View Profile',
        'hire' => 'Hire',
        'in_progress' => 'In Progress',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'status' => 'Status',
        
        // Order/Request Pages
        'create_service_request' => 'Create Service Request',
        'select_service' => 'Select Service',
        'select_device' => 'Select Device (Optional)',
        'describe_problem' => 'Describe the Problem',
        'problem_description' => 'Detailed problem description',
        'your_location' => 'Your Location',
        'budget' => 'Expected Budget (EGP)',
        'estimated_budget' => 'Estimated Budget',
        'estimated_cost' => 'Estimated Cost',
        'submit_request' => 'Submit Request',
        'request_submitted' => 'Request submitted successfully!',
        'waiting_for_offers' => 'Waiting for offers from professionals',
        'offers' => 'Offers Received',
        'no_offers_yet' => 'No offers yet',
        'accept_offer' => 'Accept Offer',
        'reject_offer' => 'Reject Offer',
        
        // Payment
        'checking_fee' => 'Checking Fee',
        'fixing_fee' => 'Fixing Fee',
        'total_cost' => 'Total Cost',
        'currency_egp' => 'Egyptian Pound',
        'egp' => 'EGP',
        'pay_now' => 'Pay Now',
        'confirm_payment' => 'Confirm Payment',
        'payment_successful' => 'Payment successful!',
        'payment_pending' => 'Payment pending confirmation',
        'payment_failed' => 'Payment failed, try again',
        'payment_method' => 'Payment Method',
        'card' => 'Credit Card',
        'wallet' => 'Digital Wallet',
        'transfer' => 'Bank Transfer',
        
        // Tracking
        'track_request' => 'Track Request',
        'request_status' => 'Request Status',
        'worker_location' => 'Worker Location',
        'estimated_arrival' => 'Estimated Arrival',
        'task_progress' => 'Task Progress',
        'worker_arrived' => 'Worker Arrived',
        'checking_in_progress' => 'Checking in Progress',
        'checking_completed' => 'Checking Completed',
        'fixing_in_progress' => 'Fixing in Progress',
        'fixing_completed' => 'Fixing Completed',
        'task_completed' => 'Task Completed',
        'mark_arrived' => 'Mark Arrived',
        'start_checking' => 'Start Checking',
        'complete_checking' => 'Complete Checking',
        'start_fixing' => 'Start Fixing',
        'complete_fixing' => 'Complete Fixing',
        
        // Worker Pages
        'worker_dashboard' => 'Worker Dashboard',
        'available_requests' => 'Available Requests',
        'my_tasks' => 'My Tasks',
        'my_earnings' => 'My Earnings',
        'accept' => 'Accept',
        'reject' => 'Reject',
        'daily_earnings' => 'Daily Earnings',
        'commission' => 'Commission (20%)',
        'net_earnings' => 'Net Earnings',
        'total_today' => 'Total Today',
        'submit_payment_request' => 'Submit Payment Request',
        'payment_submitted' => 'Payment request submitted for review',
        'earnings_history' => 'Earnings History',
        
        // Receipt & Completion
        'service_receipt' => 'Service Receipt',
        'service_completed' => 'Service Completed Successfully',
        'congratulations' => 'Congratulations!',
        'date_completed' => 'Completion Date',
        'worker_details' => 'Worker Details',
        'service_details' => 'Service Details',
        'rate_worker' => 'Rate Worker',
        'rating' => 'Rating',
        'review' => 'Review',
        'thank_you' => 'Thank you for using FLIX',
        'book_again' => 'Book Again',
        'give_rating' => 'Give Rating',
        'write_review' => 'Write Review',
        'stars' => 'Stars',
        'excellent' => 'Excellent',
        'good' => 'Good',
        'average' => 'Average',
        'poor' => 'Poor',
        
        // General Buttons
        'next' => 'Next',
        'continue' => 'Continue',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'save' => 'Save',
        'close' => 'Close',
        'back' => 'Back',
        'download' => 'Download',
        'share' => 'Share',
        'call' => 'Call',
        'message' => 'Message',
        'rate' => 'Rate',
        'try_again' => 'Try Again',
    ]
];

// ========================================
// Translation Function
// ========================================

function t($key, $fallback = null) {
    global $translations, $lang;
    
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    
    if ($fallback) {
        return $fallback;
    }
    
    return $key;
}

// ========================================
// Language Switcher URL
// ========================================

function getLangLink($newLang) {
    $url = $_SERVER['REQUEST_URI'];
    
    // Remove existing lang parameter if present
    $url = preg_replace('/([?&])lang=[^&]*/', '', $url);
    
    // Add new lang parameter
    if (strpos($url, '?') === false) {
        $url .= '?lang=' . $newLang;
    } else {
        $url .= '&lang=' . $newLang;
    }
    
    return $url;
}

// ========================================
// Services List (Bilingual)
// ========================================

$services = [
    ['id' => 1, 'name_ar' => 'السباكة', 'name_en' => 'Plumbing', 'icon' => '🚰', 'desc_ar' => 'إصلاحات السباكة والتركيب', 'desc_en' => 'Plumbing repairs and installation'],
    ['id' => 2, 'name_ar' => 'الكهرباء', 'name_en' => 'Electrical', 'icon' => '⚡', 'desc_ar' => 'أعمال كهربائية', 'desc_en' => 'Electrical works'],
    ['id' => 3, 'name_ar' => 'التكييف', 'name_en' => 'AC Service', 'icon' => '❄️', 'desc_ar' => 'صيانة وتنظيف التكييف', 'desc_en' => 'AC maintenance and cleaning'],
    ['id' => 4, 'name_ar' => 'التنظيف', 'name_en' => 'Cleaning', 'icon' => '🧹', 'desc_ar' => 'تنظيف المنازل والمكاتب', 'desc_en' => 'Home and office cleaning'],
    ['id' => 5, 'name_ar' => 'النجارة', 'name_en' => 'Carpentry', 'icon' => '🔨', 'desc_ar' => 'أعمال النجارة والأثاث', 'desc_en' => 'Carpentry works'],
    ['id' => 6, 'name_ar' => 'الدهان', 'name_en' => 'Painting', 'icon' => '🎨', 'desc_ar' => 'الدهان والتجديد', 'desc_en' => 'Painting and renovation'],
];

// ========================================
// Cities List (Bilingual)
// ========================================

$cities = [
    ['id' => 1, 'name_ar' => 'القاهرة', 'name_en' => 'Cairo'],
    ['id' => 2, 'name_ar' => 'الجيزة', 'name_en' => 'Giza'],
    ['id' => 3, 'name_ar' => 'الإسكندرية', 'name_en' => 'Alexandria'],
    ['id' => 4, 'name_ar' => 'المنصورة', 'name_en' => 'Mansoura'],
    ['id' => 5, 'name_ar' => 'طنطا', 'name_en' => 'Tanta'],
    ['id' => 6, 'name_ar' => 'أسيوط', 'name_en' => 'Assiut'],
];

// ========================================
// Get display name based on language
// ========================================

function getName($item, $lang_param = null) {
    global $lang;
    $language = $lang_param ?? $lang;
    
    if ($language === 'ar' && isset($item['name_ar'])) {
        return $item['name_ar'];
    }
    
    if (isset($item['name_en'])) {
        return $item['name_en'];
    }
    
    return $item['name'] ?? '';
}

// ========================================
// Get description based on language
// ========================================

function getDesc($item, $lang_param = null) {
    global $lang;
    $language = $lang_param ?? $lang;
    
    if ($language === 'ar' && isset($item['desc_ar'])) {
        return $item['desc_ar'];
    }
    
    if (isset($item['desc_en'])) {
        return $item['desc_en'];
    }
    
    return '';
}

?>
