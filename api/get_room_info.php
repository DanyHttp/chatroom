<?php
/**
 * API دریافت اطلاعات اتاق
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/cleanup_expired_rooms.php';

// دریافت کد اتاق
$roomCode = isset($_GET['room_code']) ? trim($_GET['room_code']) : '';

// اعتبارسنجی
if (empty($roomCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'room_code الزامی است']);
    exit;
}

if (!preg_match('/^\d{6}$/', $roomCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'کد ورود باید دقیقاً 6 رقم عددی باشد']);
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
    // محاسبه hash برای جستجو
    $roomCodeHash = hashRoomCode($roomCode);
    
    // دریافت اطلاعات اتاق با استفاده از hash
    $stmt = $pdo->prepare("SELECT id, room_code, room_code_encrypted, created_by, created_at, expires_at FROM rooms WHERE room_code_hash = :room_code_hash");
    $stmt->execute([':room_code_hash' => $roomCodeHash]);
    $room = $stmt->fetch();
    
    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'اتاقی با این کد ورود یافت نشد']);
        exit;
    }
    
    // بررسی انقضای اتاق
    if ($room['expires_at'] && strtotime($room['expires_at']) <= time()) {
        http_response_code(410);
        echo json_encode(['success' => false, 'message' => 'این اتاق منقضی شده است']);
        exit;
    }
    
    // room_code اصلی را برمی‌گردانیم (از فیلد room_code که برای backward compatibility نگه داشته شده)
    // یا اگر room_code_encrypted وجود دارد، آن را رمزگشایی می‌کنیم
    if (!empty($room['room_code_encrypted'])) {
        $decryptedCode = decryptData($room['room_code_encrypted']);
        if ($decryptedCode !== false) {
            $room['room_code'] = $decryptedCode;
        }
    }
    
    // حذف فیلدهای حساس از پاسخ
    unset($room['room_code_encrypted']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $room
    ]);
    
} catch (PDOException $e) {
    error_log("Error getting room info: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در دریافت اطلاعات اتاق']);
}
