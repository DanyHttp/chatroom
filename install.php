<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم پیام‌رسانی</title>
    <style>
        :root {
            --bg-gradient-start: #0A0F22;
            --bg-gradient-end: #131A31;
            --surface-bg: #1B2538;
            --text-primary: #E8EAED;
            --text-secondary: #A0A8B7;
            --accent: #03DAC6;
            --success: #4CAF50;
            --warning: #FFC107;
            --error: #F44336;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-primary);
        }
        .install-container {
            background: var(--surface-bg);
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: var(--text-primary);
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .step {
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(27, 37, 56, 0.5);
            border-radius: 10px;
            border-right: 4px solid var(--accent);
        }
        .step-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .step-content {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        .success {
            color: var(--success);
            font-weight: bold;
        }
        .error {
            color: var(--error);
            font-weight: bold;
        }
        .warning {
            color: var(--warning);
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(160, 168, 183, 0.3);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: var(--surface-bg);
            color: var(--text-primary);
            font-family: 'Vazir', inherit;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(3, 218, 198, 0.15);
        }
        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: var(--text-secondary);
        }
        .btn {
            background: var(--accent);
            color: var(--bg-gradient-start);
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s ease;
            font-family: 'Vazir', inherit;
        }
        .btn:hover {
            background: #04C7B3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(3, 218, 198, 0.4);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .info-box {
            background: rgba(3, 218, 198, 0.15);
            border-right: 4px solid var(--accent);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        .check-item {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }
        .check-item::before {
            content: "✓";
            color: var(--success);
            font-weight: bold;
            font-size: 18px;
        }
        .check-item.error::before {
            content: "✗";
            color: var(--error);
        }
        code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--accent);
            font-family: 'Courier New', monospace;
        }
        a {
            color: var(--accent);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        a:hover {
            color: #04C7B3;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h1>🚀 نصب سیستم پیام‌رسانی</h1>
        
        <?php
        // بررسی اینکه آیا نصب قبلاً انجام شده است
        $configFile = __DIR__ . '/config/database.php';
        $installed = file_exists($configFile);
        
        if ($installed && !isset($_POST['reinstall'])) {
            echo '<div class="step">';
            echo '<div class="step-title">⚠️ سیستم قبلاً نصب شده است</div>';
            echo '<div class="step-content">';
            echo '<p>برای نصب مجدد، فایل <code>config/database.php</code> را حذف کنید یا دکمه زیر را کلیک کنید.</p>';
            echo '<form method="post" style="margin-top: 15px;">';
            echo '<button type="submit" name="reinstall" class="btn">نصب مجدد</button>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            exit;
        }
        
        // بررسی نیازمندی‌ها
        $requirements = [];
        $allOk = true;
        
        // بررسی نسخه PHP
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.4.0', '>=');
        $requirements['PHP >= 7.4'] = $phpOk;
        if (!$phpOk) $allOk = false;
        
        // بررسی Extension PDO
        $pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
        $requirements['PDO MySQL'] = $pdoOk;
        if (!$pdoOk) $allOk = false;
        
        // بررسی Extension OpenSSL
        $opensslOk = extension_loaded('openssl');
        $requirements['OpenSSL'] = $opensslOk;
        if (!$opensslOk) $allOk = false;
        
        // بررسی دسترسی نوشتن در پوشه config
        $configWritable = is_writable(__DIR__ . '/config') || (!file_exists(__DIR__ . '/config') && is_writable(__DIR__));
        $requirements['دسترسی نوشتن در config'] = $configWritable;
        if (!$configWritable) $allOk = false;
        
        // نمایش نتایج بررسی
        echo '<div class="step">';
        echo '<div class="step-title">بررسی نیازمندی‌ها</div>';
        echo '<div class="step-content">';
        foreach ($requirements as $req => $status) {
            $class = $status ? 'success' : 'error';
            $icon = $status ? '✓' : '✗';
            echo "<div class='check-item $class'>$req: " . ($status ? 'موجود' : 'نیست') . "</div>";
        }
        if (!$allOk) {
            echo '<p class="error" style="margin-top: 15px;">لطفاً نیازمندی‌های بالا را نصب کنید و دوباره تلاش کنید.</p>';
        }
        echo '</div>';
        echo '</div>';
        
        // اگر نیازمندی‌ها برقرار نبود، فرم را نمایش نده
        if (!$allOk) {
            exit;
        }
        
        // اگر فرم ارسال شده است
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbName = $_POST['db_name'] ?? 'chat_db';
            $dbUser = $_POST['db_user'] ?? 'root';
            $dbPass = $_POST['db_pass'] ?? '';
            
            $errors = [];
            $success = [];
            
            // ایجاد فایل database.php
            try {
                $template = file_get_contents(__DIR__ . '/config/database.php.example');
                $config = str_replace(
                    ["'localhost'", "'chat_db'", "'root'", "''"],
                    ["'$dbHost'", "'$dbName'", "'$dbUser'", "'$dbPass'"],
                    $template
                );
                
                if (file_put_contents($configFile, $config)) {
                    $success[] = "فایل config/database.php ایجاد شد";
                } else {
                    $errors[] = "خطا در ایجاد فایل config/database.php";
                }
            } catch (Exception $e) {
                $errors[] = "خطا در ایجاد فایل config: " . $e->getMessage();
            }
            
            // اتصال به دیتابیس و ایجاد ساختار
            if (empty($errors)) {
                require_once $configFile;
                
                // ابتدا بدون dbname متصل می‌شویم تا بتوانیم دیتابیس را ایجاد کنیم
                try {
                    $dsn = "mysql:host=" . $dbHost . ";charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ];
                    
                    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                    $success[] = "اتصال به سرور MySQL برقرار شد";
                } catch (PDOException $e) {
                    $errorMsg = $e->getMessage();
                    // ترجمه خطاهای رایج
                    if (strpos($errorMsg, 'Access denied') !== false) {
                        $errors[] = "خطا در احراز هویت: نام کاربری یا رمز عبور اشتباه است";
                    } elseif (strpos($errorMsg, 'Unknown MySQL server host') !== false) {
                        $errors[] = "خطا: سرور MySQL در آدرس '$dbHost' یافت نشد. مطمئن شوید MySQL در حال اجرا است";
                    } elseif (strpos($errorMsg, 'Connection refused') !== false) {
                        $errors[] = "خطا: اتصال رد شد. مطمئن شوید MySQL در حال اجرا است و پورت 3306 باز است";
                    } else {
                        $errors[] = "خطا در اتصال به MySQL: " . htmlspecialchars($errorMsg);
                    }
                }
                
                if (empty($errors) && isset($pdo)) {
                    
                    // ایجاد ساختار پایگاه داده (مستقیماً در کد)
                    try {
                        // ایجاد دیتابیس
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $pdo->exec("USE {$dbName}");
                        
                        // ایجاد جدول rooms
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS rooms (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                room_code VARCHAR(20) NOT NULL UNIQUE,
                                room_code_hash VARCHAR(64) NULL,
                                room_code_encrypted TEXT NULL,
                                created_by VARCHAR(100) NOT NULL,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                expires_at DATETIME NULL DEFAULT NULL,
                                INDEX idx_room_code (room_code),
                                INDEX idx_room_code_hash (room_code_hash),
                                INDEX idx_expires_at (expires_at)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                        ");
                        
                        // ایجاد جدول messages
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS messages (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                room_id INT NOT NULL,
                                sender_name VARCHAR(100) NOT NULL,
                                message TEXT NOT NULL,
                                message_encrypted TEXT NULL,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                INDEX idx_created_at (created_at),
                                INDEX idx_room_id (room_id),
                                FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                        ");
                        
                        $success[] = "ساختار پایگاه داده ایجاد شد";
                    } catch (PDOException $e) {
                        // اگر دیتابیس یا جدول از قبل وجود دارد، خطا نده
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            $errors[] = "خطا در ایجاد ساختار دیتابیس: " . $e->getMessage();
                        } else {
                            $success[] = "ساختار پایگاه داده از قبل وجود دارد";
                        }
                    }
                }
            }
            
            // نمایش نتایج
            echo '<div class="step">';
            echo '<div class="step-title">نتیجه نصب</div>';
            echo '<div class="step-content">';
            
            if (!empty($success)) {
                foreach ($success as $msg) {
                    echo "<div class='success'>✓ $msg</div>";
                }
            }
            
            if (!empty($errors)) {
                foreach ($errors as $msg) {
                    echo "<div class='error'>✗ $msg</div>";
                }
            }
            
            if (empty($errors)) {
                echo '<div style="margin-top: 20px; padding: 15px; background: rgba(76, 175, 80, 0.15); border-radius: 8px; border-right: 4px solid var(--success);">';
                echo '<strong class="success">✅ نصب با موفقیت انجام شد!</strong>';
                echo '<p style="margin-top: 10px; color: var(--text-primary);">اکنون می‌توانید به <a href="index.php" style="color: var(--accent); font-weight: bold;">صفحه اصلی</a> بروید.</p>';
                echo '</div>';
            } else {
                echo '<div style="margin-top: 20px; padding: 15px; background: rgba(244, 67, 54, 0.15); border-radius: 8px; border-right: 4px solid var(--error);">';
                echo '<strong class="error">❌ خطا در نصب</strong>';
                echo '<p style="margin-top: 10px; color: var(--text-primary);">لطفاً خطاهای بالا را برطرف کنید و دوباره تلاش کنید.</p>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
            
        } else {
            // نمایش فرم نصب
            ?>
            <div class="info-box">
                <strong>راهنمای نصب:</strong>
                <p style="margin-top: 8px;">لطفاً اطلاعات اتصال به پایگاه داده MySQL را وارد کنید.</p>
                <p style="margin-top: 8px; font-size: 13px; color: var(--text-secondary);">
                    <strong>نکات مهم:</strong><br>
                    • مطمئن شوید MySQL/MariaDB در حال اجرا است<br>
                    • در XAMPP: از Control Panel مطمئن شوید MySQL Start شده است<br>
                    • در Laragon: دکمه Start را بزنید<br>
                    • اگر خطای "Access denied" می‌گیرید، رمز عبور را بررسی کنید
                </p>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="db_host">آدرس سرور دیتابیس:</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">نام پایگاه داده:</label>
                    <input type="text" id="db_name" name="db_name" value="chat_db" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">نام کاربری:</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">رمز عبور:</label>
                    <input type="password" id="db_pass" name="db_pass" value="" placeholder="قوی باشد بهتر است">
                </div>
                
                <button type="submit" name="install" class="btn">شروع نصب</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>
