<?php
/**
 * API ساخت اتاق جدید
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/cleanup_expired_rooms.php';

// فقط درخواست‌های POST را قبول می‌کنیم
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// دریافت داده‌ها
$input = json_decode(file_get_contents('php://input'), true);

// اگر JSON نبود، از POST معمولی استفاده کن
if (!$input) {
    $input = $_POST;
}

$roomCode = trim($input['room_code'] ?? '');
$creatorName = trim($input['creator_name'] ?? '');
$autoGenerate = isset($input['auto_generate']) ? (bool)$input['auto_generate'] : false;
$expiryHours = isset($input['expiry_hours']) ? (int)$input['expiry_hours'] : 0;

// اعتبارسنجی
$errors = [];

if (empty($creatorName)) {
    $errors[] = 'نام سازنده الزامی است';
} elseif (strlen($creatorName) > 100) {
    $errors[] = 'نام سازنده نباید بیشتر از 100 کاراکتر باشد';
}

// اگر auto_generate فعال است، کد را تولید کن
if ($autoGenerate) {
    $roomCode = '';
}

// اگر کد خالی است، یک کد 6 رقمی رندوم تولید کن
if (empty($roomCode)) {
    $roomCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
} else {
    // اعتبارسنجی کد دستی
    if (!preg_match('/^\d{6}$/', $roomCode)) {
        $errors[] = 'کد ورود باید دقیقاً 6 رقم عددی باشد';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// اتصال به دیتابیس
$pdo = getDBConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به پایگاه داده']);
    exit;
}

// پاک کردن اتاق‌های منقضی شده
cleanupExpiredRooms($pdo);

try {
    // اعتبارسنجی expiry_hours
    if ($expiryHours < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'زمان انقضا نمی‌تواند منفی باشد']);
        exit;
    }
    
    if ($expiryHours > 8760) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'زمان انقضا نمی‌تواند بیشتر از 8760 ساعت (1 سال) باشد']);
        exit;
    }
    
    // محاسبه expires_at
    $expiresAt = null;
    if ($expiryHours > 0) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));
    }
    
    // رمزنگاری room_code
    $roomCodeEncrypted = encryptData($roomCode);
    if ($roomCodeEncrypted === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در رمزنگاری کد اتاق']);
        exit;
    }
    
    // محاسبه hash برای جستجو
    $roomCodeHash = hashRoomCode($roomCode);
    
    // بررسی یکتایی کد (با استفاده از hash)
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code_hash = :room_code_hash");
    $stmt->execute([':room_code_hash' => $roomCodeHash]);
    $existingRoom = $stmt->fetch();
    
    if ($existingRoom) {
        // اگر کد تکراری بود و خودکار تولید شده، دوباره تلاش کن (حداکثر 10 بار)
        if ($autoGenerate || empty($input['room_code'])) {
            $attempts = 0;
            while ($existingRoom && $attempts < 10) {
                $roomCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $roomCodeHash = hashRoomCode($roomCode);
                $roomCodeEncrypted = encryptData($roomCode);
                if ($roomCodeEncrypted === false) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'خطا در رمزنگاری کد اتاق']);
                    exit;
                }
                $stmt->execute([':room_code_hash' => $roomCodeHash]);
                $existingRoom = $stmt->fetch();
                $attempts++;
            }
            
            if ($existingRoom) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'خطا در تولید کد یکتا']);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'این کد ورود قبلاً استفاده شده است']);
            exit;
        }
    }
    
    // ایجاد اتاق در دیتابیس (با رمزنگاری)
    if ($expiresAt) {
        $stmt = $pdo->prepare("INSERT INTO rooms (room_code, room_code_hash, room_code_encrypted, created_by, expires_at) VALUES (:room_code, :room_code_hash, :room_code_encrypted, :created_by, :expires_at)");
        $stmt->execute([
            ':room_code' => $roomCode, // نگه می‌داریم برای backward compatibility
            ':room_code_hash' => $roomCodeHash,
            ':room_code_encrypted' => $roomCodeEncrypted,
            ':created_by' => $creatorName,
            ':expires_at' => $expiresAt
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO rooms (room_code, room_code_hash, room_code_encrypted, created_by) VALUES (:room_code, :room_code_hash, :room_code_encrypted, :created_by)");
        $stmt->execute([
            ':room_code' => $roomCode, // نگه می‌داریم برای backward compatibility
            ':room_code_hash' => $roomCodeHash,
            ':room_code_encrypted' => $roomCodeEncrypted,
            ':created_by' => $creatorName
        ]);
    }
    
    $roomId = $pdo->lastInsertId();
    
    // دریافت اطلاعات اتاق ایجاد شده (room_code اصلی را برمی‌گردانیم)
    $stmt = $pdo->prepare("SELECT id, room_code, created_by, created_at, expires_at FROM rooms WHERE id = :id");
    $stmt->execute([':id' => $roomId]);
    $room = $stmt->fetch();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'اتاق با موفقیت ایجاد شد',
        'data' => $room
    ]);
    
} catch (PDOException $e) {
    error_log("Error creating room: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ایجاد اتاق']);
}
