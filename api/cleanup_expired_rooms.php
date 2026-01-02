<?php
/**
 * تابع پاک کردن اتاق‌های منقضی شده
 * این تابع باید در API‌ها فراخوانی شود
 */

require_once __DIR__ . '/../config/database.php';

/**
 * پاک کردن اتاق‌های منقضی شده و پیام‌های مرتبط
 * @param PDO $pdo اتصال به دیتابیس
 * @return int تعداد اتاق‌های پاک شده
 */
function cleanupExpiredRooms($pdo) {
    try {
        // پیدا کردن اتاق‌های منقضی شده
        $stmt = $pdo->prepare("
            SELECT id 
            FROM rooms 
            WHERE expires_at IS NOT NULL 
            AND expires_at <= NOW()
        ");
        $stmt->execute();
        $expiredRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($expiredRooms)) {
            return 0;
        }
        
        // پاک کردن اتاق‌ها (پیام‌ها به دلیل ON DELETE CASCADE خودکار پاک می‌شوند)
        $placeholders = implode(',', array_fill(0, count($expiredRooms), '?'));
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id IN ($placeholders)");
        $stmt->execute($expiredRooms);
        
        $deletedCount = $stmt->rowCount();
        
        error_log("Cleaned up $deletedCount expired room(s)");
        
        return $deletedCount;
    } catch (PDOException $e) {
        error_log("Error cleaning up expired rooms: " . $e->getMessage());
        return 0;
    }
}
