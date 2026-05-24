<?php
/**
 * Setup Test Users for Flix Platform
 * Run this once to populate test data
 * Access: http://localhost:8000/setup-test-users.php
 */

require_once __DIR__ . '/db.php';

$results = [];
$password = 'Test@1234'; // Test password for all users
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    // 1. Create test user (عميل - Customer)
    try {
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, phone, password_hash, city, account_status, user_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'أحمد محمد',                    // name
            'user@flix.com',                 // email
            '01000000001',                   // phone
            $hashedPassword,                 // password_hash
            'السادس من أكتوبر',             // city (6th of October)
            'active',                        // account_status
            'user'                           // user_type
        ]);
        $results['user'] = '✅ User created: user@flix.com / password: ' . $password;
    } catch (Exception $e) {
        $results['user'] = '⚠️ User may already exist: ' . $e->getMessage();
    }

    // 2. Create test worker (فني - Technician)
    try {
        $stmt = $conn->prepare("
            INSERT INTO workers (name, email, phone, password_hash, specialization, city, approved, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'علي الفني',                     // name
            'worker@flix.com',               // email
            '01100000001',                   // phone
            $hashedPassword,                 // password
            'سباكة وكهرباء',                 // specialization
            'السادس من أكتوبر',             // city (6th of October)
            'yes',                           // approved
            'active'                         // status
        ]);
        $results['worker'] = '✅ Worker created: worker@flix.com / password: ' . $password;
    } catch (Exception $e) {
        $results['worker'] = '⚠️ Worker may already exist: ' . $e->getMessage();
    }

    // 3. Create test admin (مدير - Admin)
    try {
        $stmt = $conn->prepare("
            INSERT INTO admins (name, email, password_hash, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            'مدير النظام',                   // name
            'admin@flix.com',                // email
            $hashedPassword,                 // password_hash
            'admin'                          // role
        ]);
        $results['admin'] = '✅ Admin created: admin@flix.com / password: ' . $password;
    } catch (Exception $e) {
        $results['admin'] = '⚠️ Admin may already exist: ' . $e->getMessage();
    }

    // 4. Insert sample service types
    $services = [
        ['سباكة', 'Plumbing', '🚰'],
        ['كهرباء', 'Electrical', '⚡'],
        ['تنظيف', 'Cleaning', '🧹'],
        ['نجارة', 'Carpentry', '🔨'],
        ['دهان', 'Painting', '🎨'],
        ['نقل', 'Moving', '📦'],
    ];

    $results['services'] = [];
    foreach ($services as [$name_ar, $name_en, $icon]) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO service_types (name_ar, name_en, icon)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name_ar, $name_en, $icon]);
            $results['services'][] = "✅ Service added: {$name_ar}";
        } catch (Exception $e) {
            $results['services'][] = "⚠️ {$name_ar} may already exist";
        }
    }

    // 5. Insert sample cities (Updated coverage areas)
    $cities = [
        ['السادس من أكتوبر', '6th of October'],
        ['الشيخ زايد', 'Sheikh Zayed'],
    ];

    $results['cities'] = [];
    foreach ($cities as [$name_ar, $name_en]) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO cities (name_ar, name_en)
                VALUES (?, ?)
            ");
            $stmt->execute([$name_ar, $name_en]);
            $results['cities'][] = "✅ City added: {$name_ar}";
        } catch (Exception $e) {
            $results['cities'][] = "⚠️ {$name_ar} may already exist";
        }
    }

} catch (Exception $e) {
    $results['error'] = '❌ Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت بيانات الاختبار - Flix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: #0ea5e9;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .result-section {
            margin: 20px 0;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            border-right: 4px solid #0ea5e9;
        }
        
        .result-section h3 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .result-item {
            color: #374151;
            font-size: 14px;
            margin: 8px 0;
            padding: 8px;
            background: white;
            border-radius: 4px;
        }
        
        .success {
            color: #10b981;
            font-weight: 500;
        }
        
        .warning {
            color: #f59e0b;
            font-weight: 500;
        }
        
        .error {
            color: #ef4444;
            font-weight: 500;
        }
        
        .test-info {
            background: #e0f2fe;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #0369a1;
            font-size: 14px;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        
        a, button {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #0ea5e9;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0284c7;
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ تثبيت بيانات الاختبار</h1>
        
        <div class="test-info">
            <strong>📝 معلومات المستخدمين للاختبار:</strong><br>
            <br>
            🔐 كلمة المرور الموحدة: <strong><?php echo $password; ?></strong>
        </div>
        
        <?php if (isset($results['user'])): ?>
        <div class="result-section">
            <h3>👤 حساب العميل</h3>
            <div class="result-item">
                <span class="<?php echo strpos($results['user'], '✅') === 0 ? 'success' : 'warning'; ?>">
                    <?php echo $results['user']; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($results['worker'])): ?>
        <div class="result-section">
            <h3>👷 حساب الفني</h3>
            <div class="result-item">
                <span class="<?php echo strpos($results['worker'], '✅') === 0 ? 'success' : 'warning'; ?>">
                    <?php echo $results['worker']; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($results['admin'])): ?>
        <div class="result-section">
            <h3>⚙️ حساب المدير</h3>
            <div class="result-item">
                <span class="<?php echo strpos($results['admin'], '✅') === 0 ? 'success' : 'warning'; ?>">
                    <?php echo $results['admin']; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($results['services']) && !empty($results['services'])): ?>
        <div class="result-section">
            <h3>🔧 الخدمات</h3>
            <?php foreach ($results['services'] as $service): ?>
            <div class="result-item">
                <span class="<?php echo strpos($service, '✅') === 0 ? 'success' : 'warning'; ?>">
                    <?php echo $service; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($results['cities']) && !empty($results['cities'])): ?>
        <div class="result-section">
            <h3>🏙️ المدن</h3>
            <?php foreach ($results['cities'] as $city): ?>
            <div class="result-item">
                <span class="<?php echo strpos($city, '✅') === 0 ? 'success' : 'warning'; ?>">
                    <?php echo $city; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($results['error'])): ?>
        <div class="result-section">
            <div class="result-item">
                <span class="error"><?php echo $results['error']; ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="buttons">
            <a href="login.php" class="btn-primary">🔓 اذهب للدخول</a>
            <a href="landing.html" class="btn-secondary">🏠 العودة للرئيسية</a>
        </div>
    </div>
</body>
</html>
