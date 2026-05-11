<?php
/////////////////////////////////////
// Project: LexChat                 /
// Author: R.I.Moskalenko (Lex0013) /
// License: MIT                     /
// Copyright (c) 2026               /
/////////////////////////////////////

// ========== НАСТРОЙКИ СЕССИИ НА 1 ГОД ==========
ini_set('session.gc_maxlifetime', 31536000); // 365 дней
ini_set('session.cookie_lifetime', 31536000); // 365 дней
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 1000);

session_set_cookie_params([
    'lifetime' => 31536000, // 365 дней
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=utf-8');

// ========== НАСТРОЙКИ БД ==========
// LexChat.rf.gd
$host = 'localhost';
$dbname = 'lexchat_db';
$user = 'root';
$pass = '';

function getDB() {
    global $host, $dbname, $user, $pass;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->exec("SET NAMES utf8");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(Exception $e) {
        error_log("DB connection error: " . $e->getMessage());
        return null;
    }
}

// ========== ЗАГРУЗКА НАСТРОЕК ДЛЯ ПРОВЕРКИ ГРУПП ==========
$settings_file = 'admin_settings.json';
$disable_groups = 0;
if (file_exists($settings_file)) {
    $content = file_get_contents($settings_file);
    $settings = json_decode($content, true);
    if (is_array($settings) && isset($settings['disable_groups'])) {
        $disable_groups = $settings['disable_groups'];
    }
}
define('DISABLE_GROUPS', $disable_groups);

// Создание необходимых таблиц
$pdo = getDB();
if ($pdo) {
    try {
        // ===== ОСНОВНЫЕ ТАБЛИЦЫ =====
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            avatar VARCHAR(500),
            last_seen TIMESTAMP NULL,
            last_active TIMESTAMP NULL,
            is_online TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            email VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_phone VARCHAR(20) NOT NULL,
            to_phone VARCHAR(20) NOT NULL,
            text TEXT,
            file_path VARCHAR(500),
            file_name VARCHAR(255),
            file_type VARCHAR(100),
            file_size INT,
            audio_path VARCHAR(500),
            audio_duration INT,
            video_path VARCHAR(500),
            video_thumbnail VARCHAR(500),
            time INT NOT NULL,
            status VARCHAR(20) DEFAULT 'sent',
            is_read TINYINT DEFAULT 0,
            deleted_at TIMESTAMP NULL,
            INDEX idx_from (from_phone),
            INDEX idx_to (to_phone),
            INDEX idx_time (time),
            INDEX idx_from_to (from_phone, to_phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS broadcast_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            from_phone VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_broadcast_read (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_phone VARCHAR(20) NOT NULL,
            broadcast_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_read (user_phone, broadcast_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // ===== ТАБЛИЦЫ ДЛЯ ГРУПП (только если не отключены) =====
        // Таблицы создаются всегда, но могут быть скрыты на клиенте
        $pdo->exec("CREATE TABLE IF NOT EXISTS `groups` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            avatar VARCHAR(500),
            created_by VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT DEFAULT 1,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS group_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_phone VARCHAR(20) NOT NULL,
            role ENUM('admin', 'member') DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_member (group_id, user_phone),
            INDEX idx_group (group_id),
            INDEX idx_user (user_phone),
            FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS group_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            from_phone VARCHAR(20) NOT NULL,
            text TEXT,
            file_path VARCHAR(500),
            file_name VARCHAR(255),
            file_type VARCHAR(100),
            file_size INT,
            video_path VARCHAR(500),
            audio_path VARCHAR(500),
            audio_duration INT,
            time INT NOT NULL,
            status VARCHAR(20) DEFAULT 'sent',
            is_read TINYINT DEFAULT 0,
            deleted_at TIMESTAMP NULL,
            INDEX idx_group (group_id),
            INDEX idx_time (time),
            INDEX idx_group_time (group_id, time),
            FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // ===== ДОБАВЛЯЕМ НЕДОСТАЮЩИЕ КОЛОНКИ =====
        // Для таблицы users
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
        } catch(Exception $e) {}
        
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_active TIMESTAMP NULL");
        } catch(Exception $e) {}
        
        // Для таблицы messages
        try {
            $pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT DEFAULT 0");
        } catch(Exception $e) {}
        
        try {
            $pdo->exec("ALTER TABLE messages ADD COLUMN deleted_at TIMESTAMP NULL");
        } catch(Exception $e) {}
        
        // Для таблицы group_messages
        try {
            $pdo->exec("ALTER TABLE group_messages ADD COLUMN is_read TINYINT DEFAULT 0");
        } catch(Exception $e) {}
        
        try {
            $pdo->exec("ALTER TABLE group_messages ADD COLUMN deleted_at TIMESTAMP NULL");
        } catch(Exception $e) {}
        
        // ===== ОПТИМИЗАЦИЯ ИНДЕКСОВ =====
        try {
            $pdo->exec("ALTER TABLE messages ADD INDEX idx_from_to_time (from_phone, to_phone, time)");
        } catch(Exception $e) {}
        
        try {
            $pdo->exec("ALTER TABLE group_messages ADD INDEX idx_group_time (group_id, time)");
        } catch(Exception $e) {}
        
        // ===== СОЗДАЕМ ДИРЕКТОРИИ =====
        $dirs = [
            'uploads/',
            'uploads/avatars/',
            'uploads/group_avatars/',
            'uploads/photo/',
            'uploads/files/',
            'uploads/records/'
        ];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
        
        // ===== СОЗДАЕМ ФАЙЛ НАСТРОЕК ЕСЛИ ЕГО НЕТ =====
        $settings_file = 'admin_settings.json';
        if (!file_exists($settings_file)) {
            $default_settings = [
                'chats_poll_interval' => 60,
                'broadcast_poll_interval' => 120,
                'messages_poll_interval' => 30,
                'messaging_mode' => 'mysql_only',
                'fallback_mode' => false,
                'disable_groups' => 0
            ];
            file_put_contents($settings_file, json_encode($default_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            @chmod($settings_file, 0666);
        }
        
        // ===== СОЗДАЕМ ФАЙЛ ЦВЕТОВ ЕСЛИ ЕГО НЕТ =====
        $colors_file = 'colors.json';
        if (!file_exists($colors_file)) {
            $default_colors = [
                'dark_bg' => '#0a0f12',
                'dark_sidebar_bg' => '#111b21',
                'dark_header_bg' => '#202c33',
                'dark_text' => '#e9edef',
                'dark_message_in_bg' => '#202c33',
                'dark_message_out_bg' => '#005c4b',
                'dark_input_bg' => '#2a3942',
                'light_bg' => '#ffffff',
                'light_sidebar_bg' => '#ffffff',
                'light_header_bg' => '#e9edef',
                'light_text' => '#111b21',
                'light_message_in_bg' => '#ffffff',
                'light_message_out_bg' => '#d9fdd3',
                'light_input_bg' => '#ffffff',
                'chat_background' => 'fonDefault.png'
            ];
            file_put_contents($colors_file, json_encode($default_colors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            @chmod($colors_file, 0666);
        }
        
        // ===== КОПИРУЕМ ДЕФОЛТНЫЕ АВАТАРКИ =====
        $default_avatar = 'uploads/avatars/default.png';
        if (!file_exists($default_avatar)) {
            // Создаем простую заглушку если нет файла
            $img = imagecreatetruecolor(200, 200);
            $bg = imagecolorallocate($img, 32, 44, 51);
            $text_color = imagecolorallocate($img, 233, 237, 239);
            imagefill($img, 0, 0, $bg);
            imagestring($img, 5, 70, 90, 'User', $text_color);
            imagepng($img, $default_avatar);
            imagedestroy($img);
        }
        
        $default_group_avatar = 'uploads/group_avatars/default.png';
        if (!file_exists($default_group_avatar) && file_exists($default_avatar)) {
            copy($default_avatar, $default_group_avatar);
        }
        
    } catch(Exception $e) {
        error_log("DB setup error: " . $e->getMessage());
    }
}

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

// Функция для очистки номера телефона
function cleanPhoneNumber($phone) {
    $cleaned = preg_replace('/\D/', '', $phone);
    if (strlen($cleaned) === 10) {
        $cleaned = '7' . $cleaned;
    } elseif (strlen($cleaned) === 11 && $cleaned[0] === '8') {
        $cleaned = '7' . substr($cleaned, 1);
    }
    return $cleaned;
}

// Функция для форматирования номера телефона для отображения
function formatPhoneNumber($phone) {
    if (!$phone) return '';
    $cleaned = preg_replace('/\D/', '', $phone);
    if (strlen($cleaned) === 11 && $cleaned[0] === '7') {
        $cleaned = substr($cleaned, 1);
    }
    return '+7 (' . substr($cleaned, 0, 3) . ') ' . 
           substr($cleaned, 3, 3) . '-' . 
           substr($cleaned, 6, 2) . '-' . 
           substr($cleaned, 8, 2);
}

// Функция для получения настроек нагрузки
function getLoadSettings() {
    $settings_file = 'admin_settings.json';
    $default_settings = [
        'chats_poll_interval' => 60,
        'broadcast_poll_interval' => 120,
        'messages_poll_interval' => 30,
        'messaging_mode' => 'mysql_only',
        'fallback_mode' => false,
        'disable_groups' => 0
    ];
    
    if (file_exists($settings_file)) {
        $content = file_get_contents($settings_file);
        $settings = json_decode($content, true);
        if (is_array($settings)) {
            foreach ($default_settings as $key => $value) {
                if (isset($settings[$key])) {
                    $default_settings[$key] = $settings[$key];
                }
            }
        }
    }
    
    return $default_settings;
}

// Функция для получения настроек цветов
function getColorSettings() {
    $colors_file = 'colors.json';
    $default_colors = [
        'dark_bg' => '#0a0f12',
        'dark_sidebar_bg' => '#111b21',
        'dark_header_bg' => '#202c33',
        'dark_text' => '#e9edef',
        'dark_message_in_bg' => '#202c33',
        'dark_message_out_bg' => '#005c4b',
        'dark_input_bg' => '#2a3942',
        'light_bg' => '#ffffff',
        'light_sidebar_bg' => '#ffffff',
        'light_header_bg' => '#e9edef',
        'light_text' => '#111b21',
        'light_message_in_bg' => '#ffffff',
        'light_message_out_bg' => '#d9fdd3',
        'light_input_bg' => '#ffffff',
        'chat_background' => 'fonDefault.png'
    ];
    
    if (file_exists($colors_file)) {
        $content = file_get_contents($colors_file);
        $colors = json_decode($content, true);
        if (is_array($colors)) {
            foreach ($default_colors as $key => $value) {
                if (isset($colors[$key])) {
                    $default_colors[$key] = $colors[$key];
                }
            }
        }
    }
    
    return $default_colors;
}

// Функция для проверки существования пользователя
function userExists($phone) {
    $pdo = getDB();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([cleanPhoneNumber($phone)]);
    return $stmt->fetch() !== false;
}

// Функция для обновления онлайн статуса
function updateUserOnlineStatus($phone, $is_online = true) {
    $pdo = getDB();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("UPDATE users SET is_online = ?, last_active = NOW() WHERE phone = ?");
    return $stmt->execute([$is_online ? 1 : 0, cleanPhoneNumber($phone)]);
}

// Функция для получения имени пользователя по номеру
function getUserName($phone) {
    $pdo = getDB();
    if (!$pdo) return $phone;
    $stmt = $pdo->prepare("SELECT name FROM users WHERE phone = ?");
    $stmt->execute([cleanPhoneNumber($phone)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user['name'] : $phone;
}

// ========== ПРОВЕРКА И СОЗДАНИЕ ДЕФОЛТНЫХ ФАЙЛОВ ==========
$fonDefault = 'fonDefault.png';
if (!file_exists($fonDefault)) {
    // Создаем простой фоновый рисунок
    $img = imagecreatetruecolor(1920, 1080);
    $bg1 = imagecolorallocate($img, 10, 15, 18);
    $bg2 = imagecolorallocate($img, 32, 44, 51);
    imagefill($img, 0, 0, $bg1);
    // Градиент
    for ($i = 0; $i < 1080; $i++) {
        $ratio = $i / 1080;
        $r = 10 + ($bg2 >> 16 & 0xFF) * $ratio;
        $g = 15 + ($bg2 >> 8 & 0xFF) * $ratio;
        $b = 18 + ($bg2 & 0xFF) * $ratio;
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $i, 1920, $i, $color);
    }
    imagepng($img, $fonDefault);
    imagedestroy($img);
}

$icon = 'icon.png';
if (!file_exists($icon)) {
    // Создаем простую иконку
    $img = imagecreatetruecolor(512, 512);
    $bg = imagecolorallocate($img, 0, 168, 132);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bg);
    // Рисуем букву "L"
    imagettftext($img, 200, 0, 160, 360, $white, '', 'L');
    imagepng($img, $icon);
    imagedestroy($img);
}

$qr = 'qr.png';
if (!file_exists($qr)) {
    // Создаем простой QR заглушку
    $img = imagecreatetruecolor(300, 300);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);
    // Рисуем квадрат
    imagefilledrectangle($img, 50, 50, 250, 250, $black);
    imagepng($img, $qr);
    imagedestroy($img);
}
?>
