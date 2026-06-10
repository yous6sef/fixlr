<?php
/**
 * ====================================================================
 * FLIX Unique Title Generator for SEO
 * ====================================================================
 * Generates contextual, unique page titles based on URL, service type,
 * worker/user profiles, and other identifiers to eliminate duplicates
 * and improve CTR in search results.
 * 
 * USAGE:
 * Instead of hardcoding $pageTitle, use:
 * $pageTitle = seoGenerateUniqueTitle($lang, $conn, $requestPath, $queryParams);
 * ====================================================================
 */

if (!function_exists('seoGenerateUniqueTitle')) {
    /**
     * Generate a unique, contextual page title based on the current page
     * 
     * @param string $lang 'en' or 'ar'
     * @param PDO $conn Database connection for lookups
     * @param string $requestPath The current request path (e.g., /pages/user/user_dashboard.php)
     * @param array $queryParams Query parameters from URL
     * @return string SEO-optimized unique title
     */
    function seoGenerateUniqueTitle($lang = 'en', $conn = null, $requestPath = '', $queryParams = []) {
        // Normalize path
        $path = str_replace('//', '/', $requestPath);
        $pathLower = strtolower($path);
        
        // Extract filename
        $filename = basename($path);
        
        // Base branding
        $brandSuffix = ($lang === 'ar') ? ' | فليكس' : ' | FLIX';
        
        // ========== USER PAGES ==========
        if (strpos($pathLower, 'user_dashboard') !== false) {
            return ($lang === 'ar') 
                ? 'لوحة التحكم - إدارة طلبات الخدمة المنزلية' . $brandSuffix
                : 'User Dashboard - Manage Home Service Requests' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'user_new_request') !== false) {
            return ($lang === 'ar')
                ? 'طلب خدمة منزلية جديدة - احجز فنياً موثوقاً' . $brandSuffix
                : 'New Home Service Request - Book a Professional' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'user_requests') !== false) {
            return ($lang === 'ar')
                ? 'طلباتي - سجل الخدمات المنزلية' . $brandSuffix
                : 'My Requests - Service History' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'user_profile') !== false) {
            if (!empty($queryParams['id']) && $conn instanceof PDO) {
                try {
                    $stmt = $conn->prepare("SELECT fullName FROM users WHERE id = ?");
                    $stmt->execute([$queryParams['id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        $name = htmlspecialchars($user['fullName']);
                        return ($lang === 'ar')
                            ? "ملف المستخدم - {$name}" . $brandSuffix
                            : "User Profile - {$name}" . $brandSuffix;
                    }
                } catch (Exception $e) {}
            }
            return ($lang === 'ar')
                ? 'ملف المستخدم' . $brandSuffix
                : 'User Profile' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'request_detail') !== false) {
            if (!empty($queryParams['id'])) {
                return ($lang === 'ar')
                    ? 'تفاصيل الطلب #' . intval($queryParams['id']) . $brandSuffix
                    : 'Request Details #' . intval($queryParams['id']) . $brandSuffix;
            }
        }
        
        if (strpos($pathLower, 'order') !== false) {
            return ($lang === 'ar')
                ? 'الطلب والدفع - فليكس' . $brandSuffix
                : 'Order & Payment' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'payments') !== false) {
            return ($lang === 'ar')
                ? 'إيصالات الدفع' . $brandSuffix
                : 'Receipts & Payments' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'track') !== false) {
            return ($lang === 'ar')
                ? 'تتبع الخدمة في الوقت الفعلي' . $brandSuffix
                : 'Real-time Service Tracking' . $brandSuffix;
        }
        
        // ========== WORKER PAGES ==========
        if (strpos($pathLower, 'worker_dashboard') !== false) {
            return ($lang === 'ar')
                ? 'لوحة تحكم الفني - إدارة الطلبات والأرباح' . $brandSuffix
                : 'Professional Dashboard - Manage Jobs & Earnings' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'worker_orders') !== false) {
            return ($lang === 'ar')
                ? 'طلبات الخدمة المتاحة والمكتملة' . $brandSuffix
                : 'Active & Completed Jobs' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'worker_payments') !== false) {
            return ($lang === 'ar')
                ? 'سحب الأرباح والحسابات البنكية' . $brandSuffix
                : 'Earnings & Withdrawal' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'worker_profile') !== false) {
            if (!empty($queryParams['id']) && $conn instanceof PDO) {
                try {
                    $stmt = $conn->prepare("SELECT u.fullName, w.specializations FROM workers w JOIN users u ON w.userId = u.id WHERE w.id = ?");
                    $stmt->execute([$queryParams['id']]);
                    $worker = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($worker) {
                        $name = htmlspecialchars($worker['fullName']);
                        $specs = json_decode($worker['specializations'] ?? '[]', true);
                        $specStr = is_array($specs) && !empty($specs) ? implode(', ', array_slice($specs, 0, 2)) : '';
                        
                        if ($specStr) {
                            return ($lang === 'ar')
                                ? "ملف الفني - {$name} ({$specStr})" . $brandSuffix
                                : "Professional Profile - {$name} ({$specStr})" . $brandSuffix;
                        }
                        return ($lang === 'ar')
                            ? "ملف الفني - {$name}" . $brandSuffix
                            : "Professional Profile - {$name}" . $brandSuffix;
                    }
                } catch (Exception $e) {}
            }
            return ($lang === 'ar')
                ? 'ملف الفني' . $brandSuffix
                : 'Professional Profile' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'update_price') !== false) {
            return ($lang === 'ar')
                ? 'تعديل السعر - تحديث عرض الخدمة' . $brandSuffix
                : 'Update Service Price' . $brandSuffix;
        }
        
        // ========== ADMIN PAGES ==========
        if (strpos($pathLower, 'admin_dashboard') !== false) {
            return ($lang === 'ar')
                ? 'لوحة الإدارة - إحصائيات وإدارة النظام' . $brandSuffix
                : 'Admin Dashboard - System Management & Analytics' . $brandSuffix;
        }
        
        if (strpos($pathLower, 'admin_chat') !== false) {
            if (!empty($queryParams['request_id'])) {
                return ($lang === 'ar')
                    ? 'دردشة دعم الطلب #' . intval($queryParams['request_id']) . $brandSuffix
                    : 'Support Chat - Request #' . intval($queryParams['request_id']) . $brandSuffix;
            }
        }
        
        if (strpos($pathLower, 'worker_details') !== false || strpos($pathLower, 'admin/worker') !== false) {
            if (!empty($queryParams['id']) && $conn instanceof PDO) {
                try {
                    $stmt = $conn->prepare("SELECT u.fullName FROM workers w JOIN users u ON w.userId = u.id WHERE w.id = ?");
                    $stmt->execute([$queryParams['id']]);
                    $worker = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($worker) {
                        $name = htmlspecialchars($worker['fullName']);
                        return ($lang === 'ar')
                            ? "تفاصيل الفني - {$name}" . $brandSuffix
                            : "Worker Details - {$name}" . $brandSuffix;
                    }
                } catch (Exception $e) {}
            }
            return ($lang === 'ar')
                ? 'تفاصيل الفني' . $brandSuffix
                : 'Worker Details' . $brandSuffix;
        }
        
        // ========== FALLBACK ==========
        return ($lang === 'ar')
            ? 'فليكس - منصة الخدمات المنزلية الموثوقة'
            : 'FLIX - Trusted Home Services Platform';
    }
}

?>
