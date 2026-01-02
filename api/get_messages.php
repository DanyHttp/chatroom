<?php
/**
 * API دریافت پیام‌ها
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/cleanup_expired_rooms.php';

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
    // دریافت پارامترهای الزامی و اختیاری
    $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
    
    if ($roomId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'room_id الزامی است']);
        exit;
    }
    
    // بررسی وجود و انقضای اتاق
    $roomStmt = $pdo->prepare("SELECT expires_at FROM rooms WHERE id = :room_id");
    $roomStmt->execute([':room_id' => $roomId]);
    $room = $roomStmt->fetch();
    
    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'اتاق یافت نشد']);
        exit;
    }
    
    if ($room['expires_at'] && strtotime($room['expires_at']) <= time()) {
        http_response_code(410);
        echo json_encode(['success' => false, 'message' => 'این اتاق منقضی شده است']);
        exit;
    }
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $limit = max(1, min($limit, 200)); // محدودیت بین 1 تا 200
    
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    // بررسی وجود فیلد room_id در جدول
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM messages LIKE 'room_id'");
        $columnExists = $checkStmt->rowCount() > 0;
        
        if (!$columnExists) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'فیلد room_id در جدول messages وجود ندارد. لطفاً schema را اجرا کنید.'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error checking column: " . $e->getMessage());
    }
    
    // اگر last_id مشخص شده، فقط پیام‌های جدیدتر را بگیر
    if ($lastId > 0) {
        $stmt = $pdo->prepare("
            SELECT id, sender_name, message, message_encrypted, created_at 
            FROM messages 
            WHERE room_id = :room_id AND id > :last_id 
            ORDER BY created_at ASC 
            LIMIT :limit
        ");
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    } else {
        // در غیر این صورت آخرین N پیام را بگیر
        $stmt = $pdo->prepare("
            SELECT id, sender_name, message, message_encrypted, created_at 
            FROM messages 
            WHERE room_id = :room_id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $messages = $stmt->fetchAll();
    
    // اگر last_id مشخص نشده، ترتیب را معکوس کن تا از قدیمی به جدید باشد
    if ($lastId == 0) {
        $messages = array_reverse($messages);
    }
    
    // رمزگشایی پیام‌ها و تبدیل تاریخ به فرمت خوانا
    foreach ($messages as &$msg) {
        // اگر message_encrypted وجود دارد، از آن استفاده کن
        if (!empty($msg['message_encrypted'])) {
            $decrypted = decryptData($msg['message_encrypted']);
            if ($decrypted !== false) {
                $msg['message'] = $decrypted;
            }
        }
        // در غیر این صورت از message اصلی استفاده می‌کنیم (backward compatibility)
        
        // حذف فیلد حساس
        unset($msg['message_encrypted']);
        
        // تبدیل تاریخ به فرمت خوانا
        $msg['created_at'] = date('Y-m-d H:i:s', strtotime($msg['created_at']));
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $messages,
        'count' => count($messages)
    ]);
    
} catch (PDOException $e) {
    error_log("Error getting messages: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    error_log("SQL State: " . $e->errorInfo[0] ?? 'N/A');
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'خطا در دریافت پیام‌ها',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'خطای عمومی: ' . $e->getMessage()
    ]);
}
