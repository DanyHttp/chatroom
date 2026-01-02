<?php
/**
 * API ارسال پیام
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

$senderName = trim($input['sender_name'] ?? '');
$message = trim($input['message'] ?? '');
$roomId = isset($input['room_id']) ? (int)$input['room_id'] : 0;

// اعتبارسنجی
$errors = [];

if (empty($senderName)) {
    $errors[] = 'نام فرستنده الزامی است';
} elseif (strlen($senderName) > 100) {
    $errors[] = 'نام فرستنده نباید بیشتر از 100 کاراکتر باشد';
}

if (empty($message)) {
    $errors[] = 'متن پیام الزامی است';
} elseif (strlen($message) > 5000) {
    $errors[] = 'پیام نباید بیشتر از 5000 کاراکتر باشد';
}

if ($roomId <= 0) {
    $errors[] = 'room_id الزامی است';
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
    // بررسی وجود و انقضای اتاق
    $stmt = $pdo->prepare("SELECT id, expires_at FROM rooms WHERE id = :room_id");
    $stmt->execute([':room_id' => $roomId]);
    $room = $stmt->fetch();
    
    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'اتاق یافت نشد']);
        exit;
    }
    
    // بررسی انقضای اتاق
    if ($room['expires_at'] && strtotime($room['expires_at']) <= time()) {
        http_response_code(410);
        echo json_encode(['success' => false, 'message' => 'این اتاق منقضی شده است']);
        exit;
    }
    
    // رمزنگاری پیام
    $messageEncrypted = encryptData($message);
    if ($messageEncrypted === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در رمزنگاری پیام']);
        exit;
    }
    
    // ذخیره پیام در دیتابیس (با رمزنگاری)
    $stmt = $pdo->prepare("INSERT INTO messages (room_id, sender_name, message, message_encrypted) VALUES (:room_id, :sender_name, :message, :message_encrypted)");
    $stmt->execute([
        ':room_id' => $roomId,
        ':sender_name' => $senderName,
        ':message' => $message, // نگه می‌داریم برای backward compatibility
        ':message_encrypted' => $messageEncrypted
    ]);
    
    $messageId = $pdo->lastInsertId();
    
    // دریافت پیام ذخیره شده (پیام اصلی را برمی‌گردانیم)
    $stmt = $pdo->prepare("SELECT id, sender_name, message, created_at FROM messages WHERE id = :id");
    $stmt->execute([':id' => $messageId]);
    $savedMessage = $stmt->fetch();
    
    // تبدیل تاریخ به فرمت خوانا (مشابه get_messages.php)
    if ($savedMessage && isset($savedMessage['created_at'])) {
        $savedMessage['created_at'] = date('Y-m-d H:i:s', strtotime($savedMessage['created_at']));
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'پیام با موفقیت ارسال شد',
        'data' => $savedMessage
    ]);
    
} catch (PDOException $e) {
    error_log("Error sending message: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره پیام']);
}
