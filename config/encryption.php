<?php
/**
 * ماژول رمزنگاری پیشرفته با AES-256-GCM
 * 
 * این ماژول از رمزنگاری AES-256-GCM استفاده می‌کند که:
 * - غیرقابل شکستن است (با کلید مخفی)
 * - احراز هویت و یکپارچگی داده را تضمین می‌کند
 * - از IV (Initialization Vector) یکتا برای هر encryption استفاده می‌کند
 */

// مسیر فایل کلید مخفی
define('ENCRYPTION_KEY_FILE', __DIR__ . '/.encryption_key');

// الگوریتم رمزنگاری
define('ENCRYPTION_METHOD', 'aes-256-gcm');

// طول کلید (256 بیت = 32 بایت)
define('ENCRYPTION_KEY_LENGTH', 32);

// طول IV (96 بیت برای GCM = 12 بایت)
define('ENCRYPTION_IV_LENGTH', 12);

// طول tag برای GCM (128 بیت = 16 بایت)
define('ENCRYPTION_TAG_LENGTH', 16);

/**
 * تولید یا خواندن کلید رمزنگاری
 * @return string کلید رمزنگاری
 */
function getEncryptionKey() {
    $keyFile = ENCRYPTION_KEY_FILE;
    
    // اگر فایل کلید وجود دارد، آن را بخوان
    if (file_exists($keyFile)) {
        $key = trim(file_get_contents($keyFile));
        if (strlen($key) >= ENCRYPTION_KEY_LENGTH) {
            return substr($key, 0, ENCRYPTION_KEY_LENGTH);
        }
    }
    
    // اگر فایل کلید وجود ندارد، یک کلید جدید تولید کن
    $key = random_bytes(ENCRYPTION_KEY_LENGTH);
    
    // ذخیره کلید در فایل (با دسترسی محدود)
    $dir = dirname($keyFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    
    file_put_contents($keyFile, $key);
    chmod($keyFile, 0600); // فقط مالک می‌تواند بخواند/بنویسد
    
    return $key;
}

/**
 * رمزنگاری داده با AES-256-GCM
 * @param string $data داده برای رمزنگاری
 * @return string|false داده رمزنگاری شده (base64) یا false در صورت خطا
 */
function encryptData($data) {
    if (empty($data)) {
        return $data;
    }
    
    try {
        $key = getEncryptionKey();
        $iv = random_bytes(ENCRYPTION_IV_LENGTH);
        
        // رمزنگاری با GCM
        $encrypted = openssl_encrypt(
            $data,
            ENCRYPTION_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            error_log("Encryption failed: " . openssl_error_string());
            return false;
        }
        
        // ترکیب IV + tag + encrypted data و تبدیل به base64
        $combined = $iv . $tag . $encrypted;
        return base64_encode($combined);
        
    } catch (Exception $e) {
        error_log("Encryption error: " . $e->getMessage());
        return false;
    }
}

/**
 * رمزگشایی داده با AES-256-GCM
 * @param string $encryptedData داده رمزنگاری شده (base64)
 * @return string|false داده رمزگشایی شده یا false در صورت خطا
 */
function decryptData($encryptedData) {
    if (empty($encryptedData)) {
        return $encryptedData;
    }
    
    try {
        $key = getEncryptionKey();
        
        // تبدیل از base64
        $combined = base64_decode($encryptedData, true);
        if ($combined === false) {
            error_log("Failed to decode base64 encrypted data");
            return false;
        }
        
        // استخراج IV, tag, و encrypted data
        $iv = substr($combined, 0, ENCRYPTION_IV_LENGTH);
        $tag = substr($combined, ENCRYPTION_IV_LENGTH, ENCRYPTION_TAG_LENGTH);
        $encrypted = substr($combined, ENCRYPTION_IV_LENGTH + ENCRYPTION_TAG_LENGTH);
        
        if (strlen($iv) !== ENCRYPTION_IV_LENGTH || strlen($tag) !== ENCRYPTION_TAG_LENGTH) {
            error_log("Invalid encrypted data format");
            return false;
        }
        
        // رمزگشایی
        $decrypted = openssl_decrypt(
            $encrypted,
            ENCRYPTION_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            error_log("Decryption failed: " . openssl_error_string());
            return false;
        }
        
        return $decrypted;
        
    } catch (Exception $e) {
        error_log("Decryption error: " . $e->getMessage());
        return false;
    }
}

/**
 * Hash کردن room_code برای جستجو (SHA-256)
 * @param string $roomCode کد اتاق
 * @return string hash شده
 */
function hashRoomCode($roomCode) {
    return hash('sha256', $roomCode . getEncryptionKey());
}
