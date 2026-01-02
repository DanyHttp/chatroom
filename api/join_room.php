<?php
/**
 * API پیوستن به اتاق
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

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

// اعتبارسنجی
if (empty($roomCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'کد ورود الزامی است']);
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

try {
    // محاسبه hash برای جستجو
    $roomCodeHash = hashRoomCode($roomCode);
    
    // بررسی وجود اتاق با استفاده از hash
    $stmt = $pdo->prepare("SELECT id, room_code, created_by, created_at FROM rooms WHERE room_code_hash = :room_code_hash");
    $stmt->execute([':room_code_hash' => $roomCodeHash]);
    $room = $stmt->fetch();
    
    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'اتاقی با این کد ورود یافت نشد']);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'اتاق یافت شد',
        'data' => $room
    ]);
    
} catch (PDOException $e) {
    error_log("Error joining room: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در بررسی اتاق']);
}
