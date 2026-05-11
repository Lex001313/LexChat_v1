<?php
/////////////////////////////////////
// Project: LexChat                 /
// Author: R.I.Moskalenko (Lex0013) /
// License: MIT                     /
// Copyright (c) 2026               /
/////////////////////////////////////

// Продлеваем сессию при каждом запросе
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Если сессия активна - продлеваем её
if (isset($_SESSION['user_phone'])) {
    // Обновляем время последней активности
    $_SESSION['last_activity'] = time();
    
    // Продлеваем cookie сессии
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), $_COOKIE[session_name()], time() + 31536000, '/');
    }
}



// ========== РУЧНОЙ ПАРСЕР MULTIPART/FORM-DATA ДЛЯ iOS ==========
function parseMultipart($input, $boundary) {
    $parts = explode('--' . $boundary, $input);
    $data = array();
    $files = array();
    
    foreach ($parts as $part) {
        if (trim($part) === '' || trim($part) === '--') continue;
        
        $separator = strpos($part, "\r\n\r\n");
        if ($separator === false) continue;
        
        $headers = substr($part, 0, $separator);
        $body = substr($part, $separator + 4, -2);
        
        preg_match('/name="([^"]+)"/', $headers, $nameMatch);
        $name = $nameMatch[1] ?? '';
        
        if (preg_match('/filename="([^"]+)"/', $headers, $filenameMatch)) {
            $filename = $filenameMatch[1];
            $tmpPath = tempnam(sys_get_temp_dir(), 'ios_upload_');
            file_put_contents($tmpPath, $body);
            
            $files[$name] = array(
                'name' => $filename,
                'tmp_name' => $tmpPath,
                'type' => 'application/octet-stream',
                'error' => 0,
                'size' => strlen($body)
            );
        } else {
            $data[$name] = $body;
        }
    }
    
    return array('post' => $data, 'files' => $files);
}

// Универсальный приём POST-данных
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST)) {
        $input = file_get_contents('php://input');
        parse_str($input, $postdata);
        if (!empty($postdata)) {
            $_POST = $postdata;
        }
    }
    
    if (empty($_FILES) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        $input = file_get_contents('php://input');
        preg_match('/boundary=(.+)$/', $_SERVER['CONTENT_TYPE'], $boundaryMatch);
        $boundary = $boundaryMatch[1] ?? '';
        
        if ($boundary) {
            $parsed = parseMultipart($input, $boundary);
            $_POST = array_merge($_POST, $parsed['post']);
            $_FILES = array_merge($_FILES, $parsed['files']);
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

error_reporting(0);
ini_set('display_errors', 0);

require_once 'connect.php';

// ========== ПОДКЛЮЧЕНИЕ PHPMailer (ОДИН РАЗ В НАЧАЛЕ) ==========
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pdo = getDB();
if (!$pdo) {
    echo '{"error":"DB connection failed"}';
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ========== АВТОРИЗАЦИЯ ==========
if ($action == 'auth') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : 'User';
    
    if (empty($phone)) {
        echo '{"error":"Phone required"}';
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (phone, name, last_seen, is_online) VALUES (?, ?, NOW(), 1)
                           ON DUPLICATE KEY UPDATE name = ?, last_seen = NOW(), is_online = 1");
    $stmt->execute(array($phone, $name, $name));
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute(array($phone));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_id'] = $user['id'];
    
// Устанавливаем куки на 365 дней
setcookie('user_phone', $phone, [
    'expires' => time() + 31536000, // 365 дней
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

setcookie('user_name', $name, [
    'expires' => time() + 31536000, // 365 дней
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
    
    echo '{"success":true,"phone":"' . addslashes($phone) . '","user_id":"' . $user['id'] . '"}';
    exit;
}

// ========== ПОЛУЧЕНИЕ ИНФОРМАЦИИ О ПОЛЬЗОВАТЕЛЕ ==========
if ($action == 'get_user_info') {
    $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
    
    if (empty($phone)) {
        echo json_encode(array('error' => 'Phone required'));
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT name, avatar, email FROM users WHERE phone = ?");
    $stmt->execute(array($phone));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(array(
            'success' => true,
            'name' => $user['name'],
            'avatar' => $user['avatar'] ?: 'uploads/avatars/default.png',
            'email' => $user['email'] ?: ''
        ), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('success' => false, 'error' => 'User not found'));
    }
    exit;
}

// ========== ОБНОВЛЕНИЕ СТАТУСА ОНЛАЙН ==========
if ($action == 'update_online') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $is_online = isset($_POST['is_online']) ? (int)$_POST['is_online'] : 0;
    
    $stmt = $pdo->prepare("UPDATE users SET is_online = ?, last_active = NOW() WHERE phone = ?");
    $stmt->execute(array($is_online, $phone));
    
    echo '{"success":true}';
    exit;
}

// ========== ОБНОВЛЕНИЕ ИМЕНИ ==========
if ($action == 'update_name') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    
    if (empty($phone) || empty($name)) {
        echo '{"error":"Phone or name required"}';
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE phone = ?");
    $stmt->execute(array($name, $phone));
    
    echo '{"success":true}';
    exit;
}

// ========== ЗАГРУЗКА АВАТАРА ==========
if ($action == 'upload_avatar') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    
    if (empty($phone)) {
        echo '{"error":"Phone required"}';
        exit;
    }
    
    $upload_dir = 'uploads/avatars/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = $phone . '_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        $img = @imagecreatefromstring(file_get_contents($_FILES['avatar']['tmp_name']));
        if ($img) {
            $max_width = 200;
            $width = imagesx($img);
            $height = imagesy($img);
            if ($width > $max_width) {
                $new_width = $max_width;
                $new_height = intval($height * $max_width / $width);
                $new_img = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagedestroy($img);
                $img = $new_img;
            }
            imagejpeg($img, $filepath, 80);
            imagedestroy($img);
        } else {
            move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath);
        }
        
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE phone = ?");
        $stmt->execute(array($phone));
        $old = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($old && $old['avatar'] && file_exists($old['avatar'])) {
            unlink($old['avatar']);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE phone = ?");
        $stmt->execute(array($filepath, $phone));
        
        echo '{"success":true,"avatar_path":"' . $filepath . '"}';
        exit;
    }
    
    echo '{"error":"Upload failed"}';
    exit;
}

// ========== ЗАГРУЗКА ФАЙЛА ЧЕРЕЗ BASE64 (iOS) ==========
if ($action == 'upload_file_base64') {
    $from = isset($_POST['from']) ? $_POST['from'] : '';
    $to = isset($_POST['to']) ? $_POST['to'] : '';
    $file_base64 = isset($_POST['file_base64']) ? $_POST['file_base64'] : '';
    $file_name = isset($_POST['file_name']) ? $_POST['file_name'] : 'file';
    $file_type = isset($_POST['file_type']) ? $_POST['file_type'] : 'application/octet-stream';
    $time = time();
    
    if (empty($from) || empty($to) || empty($file_base64)) {
        echo json_encode(array('error' => 'Phone or file required'));
        exit;
    }
    
    if (strlen($file_base64) > 5 * 1024 * 1024) {
        echo json_encode(array('error' => 'File too large (max 5 MB)'));
        exit;
    }
    
    $upload_dir = 'uploads/';
    $photo_dir = 'uploads/photo/';
    $files_dir = 'uploads/files/';
    
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    if (!file_exists($photo_dir)) mkdir($photo_dir, 0777, true);
    if (!file_exists($files_dir)) mkdir($files_dir, 0777, true);
    
    $file_data = base64_decode($file_base64);
    $file_size = strlen($file_data);
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_filename = $from . '_' . $to . '_' . time() . '.' . $ext;
    
    $is_image = strpos($file_type, 'image/') === 0;
    
    if ($is_image) {
        $dest_path = $photo_dir . $new_filename;
        $img = @imagecreatefromstring($file_data);
        if ($img) {
            $max_width = 800;
            $width = imagesx($img);
            $height = imagesy($img);
            if ($width > $max_width) {
                $new_width = $max_width;
                $new_height = intval($height * $max_width / $width);
                $new_img = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagedestroy($img);
                $img = $new_img;
            }
            imagejpeg($img, $dest_path, 80);
            imagedestroy($img);
        } else {
            file_put_contents($dest_path, $file_data);
        }
    } else {
        $dest_path = $files_dir . $new_filename;
        file_put_contents($dest_path, $file_data);
    }
    
    $stmt = $pdo->prepare("INSERT INTO messages (from_phone, to_phone, file_path, file_name, file_type, file_size, time, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'sent')");
    $stmt->execute(array($from, $to, $dest_path, $file_name, $file_type, $file_size, $time));
    
    echo json_encode(array('success' => true));
    exit;
}

// ========== ЗАГРУЗКА АУДИО ЧЕРЕЗ BASE64 (iOS) ==========
if ($action == 'upload_audio_base64') {
    $from = isset($_POST['from']) ? $_POST['from'] : '';
    $to = isset($_POST['to']) ? $_POST['to'] : '';
    $audio_base64 = isset($_POST['audio_base64']) ? $_POST['audio_base64'] : '';
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
    $time = time();
    
    if (empty($from) || empty($to) || empty($audio_base64)) {
        echo json_encode(array('error' => 'Phone or audio required'));
        exit;
    }
    
    $upload_dir = 'uploads/records/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $audio_data = base64_decode($audio_base64);
    $filename = $from . '_' . $to . '_' . $time . '.webm';
    $filepath = $upload_dir . $filename;
    file_put_contents($filepath, $audio_data);
    
    $stmt = $pdo->prepare("INSERT INTO messages (from_phone, to_phone, audio_path, audio_duration, time, status) VALUES (?, ?, ?, ?, ?, 'sent')");
    $stmt->execute(array($from, $to, $filepath, $duration, $time));
    
    echo json_encode(array('success' => true));
    exit;
}

// ========== ЗАГРУЗКА АУДИО В ГРУППУ ЧЕРЕЗ BASE64 ==========
if ($action == 'upload_group_audio_base64') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $from = isset($_POST['from']) ? $_POST['from'] : '';
    $audio_base64 = isset($_POST['audio_base64']) ? $_POST['audio_base64'] : '';
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
    $time = time();
    
    if (empty($groupId) || empty($from) || empty($audio_base64)) {
        echo json_encode(['error' => 'Group ID, from or audio required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_phone = ?");
    $stmt->execute([$groupId, $from]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Not a member of this group']);
        exit;
    }
    
    $upload_dir = 'uploads/records/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $audio_data = base64_decode($audio_base64);
    $filename = 'group_' . $groupId . '_' . $from . '_' . $time . '.webm';
    $filepath = $upload_dir . $filename;
    file_put_contents($filepath, $audio_data);
    
    $stmt = $pdo->prepare("INSERT INTO group_messages (group_id, from_phone, audio_path, audio_duration, time, status) VALUES (?, ?, ?, ?, ?, 'sent')");
    $stmt->execute([$groupId, $from, $filepath, $duration, $time]);
    
    echo json_encode(['success' => true]);
    exit;
}

// ========== ПОИСК ПОЛЬЗОВАТЕЛЯ ==========
if ($action == 'search_user') {
    $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
    $cleanPhone = preg_replace('/\D/', '', $phone);
    
    if (empty($cleanPhone)) {
        echo '{"error":"Phone required"}';
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, phone, name, avatar, is_online FROM users WHERE phone = ?");
    $stmt->execute(array($cleanPhone));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $avatar = !empty($user['avatar']) ? $user['avatar'] : 'uploads/avatars/default.png';
        echo '{"success":true,"user":{"id":"' . $user['id'] . '","phone":"' . addslashes($user['phone']) . '","name":"' . addslashes($user['name']) . '","avatar":"' . addslashes($avatar) . '","is_online":' . ($user['is_online'] ? 'true' : 'false') . '}}';
    } else {
        echo '{"success":false,"message":"Пользователь не зарегистрирован"}';
    }
    exit;
}

// ========== ПРОВЕРКА НАЗВАНИЯ ГРУППЫ (ДЛЯ ПОДСКАЗКИ) ==========
if ($action == 'check_group_name') {
    $groupName = isset($_GET['name']) ? $_GET['name'] : '';
    $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;
    
    if (empty($groupName)) {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    if ($excludeId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE name = ? AND id != ?");
        $stmt->execute([$groupName, $excludeId]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE name = ?");
        $stmt->execute([$groupName]);
    }
    
    $exists = $stmt->fetch() ? true : false;
    echo json_encode(['exists' => $exists]);
    exit;
}

// ========== СПИСОК ЧАТОВ (ЛИЧНЫЕ + ГРУППЫ) ==========
if ($action == 'get_chats') {
    $myPhone = isset($_GET['my_phone']) ? $_GET['my_phone'] : '';
    
    if (empty($myPhone)) {
        echo json_encode(['error' => 'Phone required']);
        exit;
    }
    
    $chats = [];
    
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT 
            CASE 
                WHEN from_phone = ? THEN to_phone 
                ELSE from_phone 
            END as contact_phone
            FROM messages 
            WHERE (from_phone = ? OR to_phone = ?) AND deleted_at IS NULL");
        $stmt->execute([$myPhone, $myPhone, $myPhone]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contacts as $c) {
            $contactPhone = $c['contact_phone'];
            
            $stmt2 = $pdo->prepare("SELECT name, avatar, is_online FROM users WHERE phone = ?");
            $stmt2->execute([$contactPhone]);
            $user = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) continue;
            
            $stmt3 = $pdo->prepare("SELECT text, file_path, audio_path, time, from_phone, status 
                                   FROM messages 
                                   WHERE ((from_phone = ? AND to_phone = ?) OR (from_phone = ? AND to_phone = ?))
                                   AND deleted_at IS NULL
                                   ORDER BY time DESC LIMIT 1");
            $stmt3->execute([$myPhone, $contactPhone, $contactPhone, $myPhone]);
            $lastMsg = $stmt3->fetch(PDO::FETCH_ASSOC);
            
            $lastMsgText = '';
            $lastTime = 0;
            $unread = false;
            
            if ($lastMsg) {
                if ($lastMsg['audio_path']) {
                    $lastMsgText = '🎤 Голосовое';
                } elseif ($lastMsg['file_path']) {
                    $lastMsgText = '📎 Файл';
                } else {
                    $lastMsgText = mb_substr($lastMsg['text'], 0, 30);
                }
                $lastTime = (int)$lastMsg['time'];
                $unread = ($lastMsg['from_phone'] != $myPhone && $lastMsg['status'] != 'read');
            }
            
            $avatar = !empty($user['avatar']) ? $user['avatar'] : 'uploads/avatars/default.png';
            $chats[] = [
                'id' => $contactPhone,
                'type' => 'user',
                'name' => $user['name'],
                'avatar' => $avatar,
                'last_message' => $lastMsgText,
                'last_time' => $lastTime,
                'unread' => $unread,
                'is_online' => (bool)$user['is_online']
            ];
        }
    } catch (Exception $e) {
        error_log("Chats error: " . $e->getMessage());
    }
    
    try {
        $settings_file = 'admin_settings.json';
        $disable_groups = 0;
        if (file_exists($settings_file)) {
            $settings = json_decode(file_get_contents($settings_file), true);
            $disable_groups = isset($settings['disable_groups']) ? $settings['disable_groups'] : 0;
        }
        
        if (!$disable_groups) {
            $stmt = $pdo->prepare("SELECT group_id FROM group_members WHERE user_phone = ?");
            $stmt->execute([$myPhone]);
            $myGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($myGroups as $mg) {
                $groupId = $mg['group_id'];
                
                $stmt2 = $pdo->prepare("SELECT id, name, avatar FROM `groups` WHERE id = ?");
                $stmt2->execute([$groupId]);
                $group = $stmt2->fetch(PDO::FETCH_ASSOC);
                
                if (!$group) continue;
                
                $stmt3 = $pdo->prepare("SELECT text, file_path, audio_path, time, from_phone 
                                       FROM group_messages 
                                       WHERE group_id = ? AND deleted_at IS NULL 
                                       ORDER BY time DESC LIMIT 1");
                $stmt3->execute([$groupId]);
                $lastMsg = $stmt3->fetch(PDO::FETCH_ASSOC);
                
                $lastMsgText = '';
                $lastTime = 0;
                if ($lastMsg) {
                    if ($lastMsg['audio_path']) {
                        $lastMsgText = '🎤 Голосовое';
                    } elseif ($lastMsg['file_path']) {
                        $lastMsgText = '📎 Файл';
                    } else {
                        $lastMsgText = mb_substr($lastMsg['text'], 0, 30);
                    }
                    $lastTime = (int)$lastMsg['time'];
                } else {
                    $lastMsgText = 'Группа создана';
                }
                
                $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM group_messages 
                                       WHERE group_id = ? AND from_phone != ? AND is_read = 0");
                $stmt4->execute([$groupId, $myPhone]);
                $unreadCount = $stmt4->fetchColumn();
                
                $chats[] = [
                    'id' => $groupId,
                    'type' => 'group',
                    'name' => $group['name'],
                    'avatar' => !empty($group['avatar']) ? $group['avatar'] : 'uploads/group_avatars/default.png',
                    'last_message' => $lastMsgText,
                    'last_time' => $lastTime,
                    'unread' => $unreadCount > 0,
                    'is_online' => false
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Groups error: " . $e->getMessage());
    }
    
    usort($chats, function($a, $b) {
        return $b['last_time'] - $a['last_time'];
    });
    
    echo json_encode($chats, JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== ОТПРАВКА СООБЩЕНИЯ (ЛИЧНОЕ) ==========
if ($action == 'send') {
    $from = isset($_POST['from']) ? $_POST['from'] : '';
    $to = isset($_POST['to']) ? $_POST['to'] : '';
    $text = isset($_POST['text']) ? $_POST['text'] : '';
    $time = time();
    
    $stmt = $pdo->prepare("INSERT INTO messages (from_phone, to_phone, text, time, status) VALUES (?, ?, ?, ?, 'sent')");
    $stmt->execute(array($from, $to, $text, $time));
    $msgId = $pdo->lastInsertId();
    
    echo '{"success":true,"msg_id":' . $msgId . '}';
    exit;
}

// ========== ПОЛУЧЕНИЕ СООБЩЕНИЙ (ЛИЧНОЕ) ==========
if ($action == 'messages') {
    $myPhone = isset($_GET['my_phone']) ? $_GET['my_phone'] : '';
    $contactPhone = isset($_GET['contact_phone']) ? $_GET['contact_phone'] : '';
    
    $stmt = $pdo->prepare("SELECT id, from_phone, text, file_path, file_name, file_type, file_size, audio_path, audio_duration, time, status FROM messages 
                           WHERE ((from_phone = ? AND to_phone = ?) OR (from_phone = ? AND to_phone = ?))
                           AND deleted_at IS NULL
                           ORDER BY time ASC");
    $stmt->execute(array($myPhone, $contactPhone, $contactPhone, $myPhone));
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $items = array();
    foreach ($messages as $m) {
        $item = array(
            'id' => $m['id'],
            'from_phone' => $m['from_phone'],
            'text' => $m['text'],
            'time' => $m['time'],
            'status' => $m['status']
        );
        if ($m['file_path']) {
            $item['file_path'] = $m['file_path'];
            $item['file_name'] = $m['file_name'];
            $item['file_type'] = $m['file_type'];
            $item['file_size'] = $m['file_size'];
        }
        if ($m['audio_path']) {
            $item['audio_path'] = $m['audio_path'];
            $item['audio_duration'] = $m['audio_duration'];
        }
        $items[] = $item;
    }
    echo json_encode($items, JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== ОБНОВЛЕНИЕ СТАТУСА СООБЩЕНИЯ ==========
if ($action == 'update_status') {
    $msgId = isset($_POST['msg_id']) ? (int)$_POST['msg_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    if ($msgId && $status) {
        $stmt = $pdo->prepare("UPDATE messages SET status = ? WHERE id = ?");
        $stmt->execute(array($status, $msgId));
    }
    
    echo '{"success":true}';
    exit;
}

// ========== ПОМЕТИТЬ КАК ПРОЧИТАННЫЕ (ЛИЧНОЕ) ==========
if ($action == 'mark_read') {
    $myPhone = isset($_POST['my_phone']) ? $_POST['my_phone'] : '';
    $contactPhone = isset($_POST['contact_phone']) ? $_POST['contact_phone'] : '';
    
    if (empty($myPhone) || empty($contactPhone)) {
        echo '{"error":"Phone required"}';
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE messages SET status = 'read', is_read = 1 
                           WHERE from_phone = ? AND to_phone = ? AND (status != 'read' OR is_read = 0)");
    $stmt->execute(array($contactPhone, $myPhone));
    
    echo '{"success":true}';
    exit;
}

// ========== УДАЛЕНИЕ СООБЩЕНИЯ ==========
if ($action == 'delete_message') {
    $msgId = isset($_POST['msg_id']) ? (int)$_POST['msg_id'] : 0;
    $myPhone = isset($_POST['my_phone']) ? $_POST['my_phone'] : '';
    
    if ($msgId && $myPhone) {
        $stmt = $pdo->prepare("UPDATE messages SET deleted_at = NOW() WHERE id = ? AND from_phone = ?");
        $stmt->execute(array($msgId, $myPhone));
    }
    
    echo '{"success":true}';
    exit;
}

// ========== УДАЛЕНИЕ ЧАТА (ЛИЧНОГО) ==========
if ($action == 'delete_chat') {
    $myPhone = isset($_POST['my_phone']) ? $_POST['my_phone'] : '';
    $contactPhone = isset($_POST['contact_phone']) ? $_POST['contact_phone'] : '';
    
    if ($myPhone && $contactPhone) {
        $stmt = $pdo->prepare("UPDATE messages SET deleted_at = NOW() 
                               WHERE ((from_phone = ? AND to_phone = ?) OR (from_phone = ? AND to_phone = ?))
                               AND deleted_at IS NULL");
        $stmt->execute(array($myPhone, $contactPhone, $contactPhone, $myPhone));
    }
    
    echo '{"success":true}';
    exit;
}

// ========== ГРУППОВЫЕ ФУНКЦИИ ==========

// 1. Создание группы
if ($action == 'create_group') {
    $creatorPhone = isset($_POST['creator_phone']) ? $_POST['creator_phone'] : '';
    $groupName = isset($_POST['group_name']) ? $_POST['group_name'] : '';
    $membersJson = isset($_POST['members']) ? $_POST['members'] : '[]';
    $avatarBase64 = isset($_POST['avatar_base64']) ? $_POST['avatar_base64'] : '';
    
    if (empty($creatorPhone) || empty($groupName)) {
        echo json_encode(['error' => 'Creator phone and group name required']);
        exit;
    }
    
    $checkStmt = $pdo->prepare("SELECT id FROM `groups` WHERE name = ?");
    $checkStmt->execute([$groupName]);
    if ($checkStmt->fetch()) {
        echo json_encode(['error' => 'Группа с таким названием уже существует']);
        exit;
    }
    
    $members = json_decode($membersJson, true);
    if (!is_array($members)) $members = [];
    if (!in_array($creatorPhone, $members)) $members[] = $creatorPhone;
    
    try {
        $pdo->beginTransaction();
        
        $avatarPath = null;
        if (!empty($avatarBase64)) {
            $upload_dir = 'uploads/group_avatars/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $avatarData = base64_decode($avatarBase64);
            $avatarName = 'group_' . time() . '.jpg';
            $avatarPath = $upload_dir . $avatarName;
            file_put_contents($avatarPath, $avatarData);
        }
        
        $stmt = $pdo->prepare("INSERT INTO `groups` (name, avatar, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$groupName, $avatarPath, $creatorPhone]);
        $groupId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_phone, role) VALUES (?, ?, ?)");
        foreach ($members as $phone) {
            $role = ($phone == $creatorPhone) ? 'admin' : 'member';
            $stmt->execute([$groupId, $phone, $role]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'group_id' => $groupId]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// 2. Информация о группе
if ($action == 'get_group_info') {
    $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    $myPhone = isset($_GET['my_phone']) ? $_GET['my_phone'] : '';
    if (!$groupId) {
        echo json_encode(['error' => 'Group ID required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, name, avatar, created_by FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group) {
        echo json_encode(['error' => 'Group not found']);
        exit;
    }
    $group['avatar'] = !empty($group['avatar']) ? $group['avatar'] : 'uploads/group_avatars/default.png';
    
    $stmt = $pdo->prepare("SELECT gm.user_phone, u.name, u.avatar, gm.role, gm.joined_at
                           FROM group_members gm
                           JOIN users u ON u.phone = gm.user_phone
                           WHERE gm.group_id = ?
                           ORDER BY gm.role = 'admin' DESC, u.name");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $myRole = 'member';
    foreach ($members as $m) {
        if ($m['user_phone'] == $myPhone) {
            $myRole = $m['role'];
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'group' => $group,
        'members' => $members,
        'my_role' => $myRole
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Отправка сообщения в группу
if ($action == 'send_group_message') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $from = isset($_POST['from']) ? $_POST['from'] : '';
    $text = isset($_POST['text']) ? $_POST['text'] : '';
    $time = time();
    
    if (!$groupId || empty($from)) {
        echo json_encode(['error' => 'Group ID and from required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_phone = ?");
    $stmt->execute([$groupId, $from]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Not a member']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO group_messages (group_id, from_phone, text, time, status) VALUES (?, ?, ?, ?, 'sent')");
    $stmt->execute([$groupId, $from, $text, $time]);
    echo json_encode(['success' => true, 'msg_id' => $pdo->lastInsertId()]);
    exit;
}
// 4. Получение сообщений группы
if ($action == 'get_group_messages') {
    $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    $myPhone = isset($_GET['my_phone']) ? $_GET['my_phone'] : '';
    if (!$groupId) {
        echo json_encode(['error' => 'Group ID required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, from_phone, text, file_path, file_name, file_type, file_size, audio_path, audio_duration, time, status, is_read
                           FROM group_messages
                           WHERE group_id = ? AND deleted_at IS NULL
                           ORDER BY time ASC");
    $stmt->execute([$groupId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ДОБАВЬТЕ ЭТОТ БЛОК - получаем имена отправителей
    foreach ($messages as &$msg) {
        $stmt2 = $pdo->prepare("SELECT name FROM users WHERE phone = ?");
        $stmt2->execute([$msg['from_phone']]);
        $user = $stmt2->fetch(PDO::FETCH_ASSOC);
        $msg['from_name'] = $user ? $user['name'] : $msg['from_phone'];
    }
    
    $stmt2 = $pdo->prepare("UPDATE group_messages SET is_read = 1 WHERE group_id = ? AND from_phone != ? AND is_read = 0");
    $stmt2->execute([$groupId, $myPhone]);
    
    echo json_encode($messages, JSON_UNESCAPED_UNICODE);
    exit;
}




// 5. Добавить участника
if ($action == 'add_group_member') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $adminPhone = isset($_POST['admin_phone']) ? $_POST['admin_phone'] : '';
    $newPhone = isset($_POST['new_phone']) ? $_POST['new_phone'] : '';
    
    if (!$groupId || empty($adminPhone) || empty($newPhone)) {
        echo json_encode(['error' => 'Missing params']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_phone = ?");
    $stmt->execute([$groupId, $adminPhone]);
    $admin = $stmt->fetch();
    if (!$admin || $admin['role'] != 'admin') {
        echo json_encode(['error' => 'Only admin can add members']);
        exit;
    }
    
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $userStmt->execute([$newPhone]);
    if (!$userStmt->fetch()) {
        echo json_encode(['error' => 'Пользователь не зарегистрирован']);
        exit;
    }
    
    $checkStmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_phone = ?");
    $checkStmt->execute([$groupId, $newPhone]);
    if ($checkStmt->fetch()) {
        echo json_encode(['error' => 'Пользователь уже в группе']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_phone, role) VALUES (?, ?, 'member')");
        $stmt->execute([$groupId, $newPhone]);
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// 6. Удалить участника / выйти из группы
if ($action == 'remove_group_member') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $actorPhone = isset($_POST['actor_phone']) ? $_POST['actor_phone'] : '';
    $targetPhone = isset($_POST['target_phone']) ? $_POST['target_phone'] : '';
    
    if (!$groupId || empty($actorPhone) || empty($targetPhone)) {
        echo json_encode(['error' => 'Missing params']);
        exit;
    }
    
    $isSelf = ($actorPhone == $targetPhone);
    
    if (!$isSelf) {
        $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_phone = ?");
        $stmt->execute([$groupId, $actorPhone]);
        $actor = $stmt->fetch();
        if (!$actor || $actor['role'] != 'admin') {
            echo json_encode(['error' => 'Only admin can remove others']);
            exit;
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_phone = ?");
    $stmt->execute([$groupId, $targetPhone]);
    
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
    $stmt2->execute([$groupId]);
    if ($stmt2->fetchColumn() == 0) {
        $pdo->prepare("DELETE FROM `groups` WHERE id = ?")->execute([$groupId]);
        echo json_encode(['success' => true, 'group_deleted' => true]);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}

// 7. Удалить группу полностью
if ($action == 'delete_group') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $creatorPhone = isset($_POST['creator_phone']) ? $_POST['creator_phone'] : '';
    
    $stmt = $pdo->prepare("SELECT created_by FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    if (!$group || $group['created_by'] != $creatorPhone) {
        echo json_encode(['error' => 'Only creator can delete group']);
        exit;
    }
    
    $pdo->prepare("DELETE FROM `groups` WHERE id = ?")->execute([$groupId]);
    echo json_encode(['success' => true]);
    exit;
}

// 8. Обновить группу
if ($action == 'update_group') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $adminPhone = isset($_POST['admin_phone']) ? $_POST['admin_phone'] : '';
    $newName = isset($_POST['group_name']) ? $_POST['group_name'] : '';
    $avatarBase64 = isset($_POST['avatar_base64']) ? $_POST['avatar_base64'] : null;
    
    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_phone = ?");
    $stmt->execute([$groupId, $adminPhone]);
    $adminRow = $stmt->fetch();
    if (!$adminRow || $adminRow['role'] != 'admin') {
        echo json_encode(['error' => 'Only admin can edit group']);
        exit;
    }
    
    if (!empty($newName)) {
        $checkStmt = $pdo->prepare("SELECT id FROM `groups` WHERE name = ? AND id != ?");
        $checkStmt->execute([$newName, $groupId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['error' => 'Группа с таким названием уже существует']);
            exit;
        }
    }
    
    $updates = [];
    $params = [];
    if (!empty($newName)) {
        $updates[] = "name = ?";
        $params[] = $newName;
    }
    if ($avatarBase64 !== null && $avatarBase64 !== '') {
        $upload_dir = 'uploads/group_avatars/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $avatarData = base64_decode($avatarBase64);
        $avatarName = 'group_' . time() . '.jpg';
        $avatarPath = $upload_dir . $avatarName;
        file_put_contents($avatarPath, $avatarData);
        $updates[] = "avatar = ?";
        $params[] = $avatarPath;
    }
    if (empty($updates)) {
        echo json_encode(['error' => 'Nothing to update']);
        exit;
    }
    $params[] = $groupId;
    $sql = "UPDATE `groups` SET " . implode(', ', $updates) . " WHERE id = ?";
    $pdo->prepare($sql)->execute($params);
    echo json_encode(['success' => true]);
    exit;
}

// ========== ЗАГРУЗКА ФАЙЛА В ГРУППУ ЧЕРЕЗ BASE64 ==========
if ($action == 'upload_group_file_base64') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $from = isset($_POST['from']) ? $_POST['from'] : '';
    $file_base64 = isset($_POST['file_base64']) ? $_POST['file_base64'] : '';
    $file_name = isset($_POST['file_name']) ? $_POST['file_name'] : 'file';
    $file_type = isset($_POST['file_type']) ? $_POST['file_type'] : 'application/octet-stream';
    $time = time();
    
    if (empty($groupId) || empty($from) || empty($file_base64)) {
        echo json_encode(['error' => 'Group ID, from or file required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_phone = ?");
    $stmt->execute([$groupId, $from]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Not a member of this group']);
        exit;
    }
    
    if (strlen($file_base64) > 5 * 1024 * 1024) {
        echo json_encode(['error' => 'File too large (max 5 MB)']);
        exit;
    }
    
    $upload_dir = 'uploads/';
    $photo_dir = 'uploads/photo/';
    $files_dir = 'uploads/files/';
    
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    if (!file_exists($photo_dir)) mkdir($photo_dir, 0777, true);
    if (!file_exists($files_dir)) mkdir($files_dir, 0777, true);
    
    $file_data = base64_decode($file_base64);
    $file_size = strlen($file_data);
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_filename = 'group_' . $groupId . '_' . $from . '_' . time() . '.' . $ext;
    
    $is_image = strpos($file_type, 'image/') === 0;
    
    if ($is_image) {
        $dest_path = $photo_dir . $new_filename;
        $img = @imagecreatefromstring($file_data);
        if ($img) {
            $max_width = 800;
            $width = imagesx($img);
            $height = imagesy($img);
            if ($width > $max_width) {
                $new_width = $max_width;
                $new_height = intval($height * $max_width / $width);
                $new_img = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagedestroy($img);
                $img = $new_img;
            }
            imagejpeg($img, $dest_path, 80);
            imagedestroy($img);
        } else {
            file_put_contents($dest_path, $file_data);
        }
    } else {
        $dest_path = $files_dir . $new_filename;
        file_put_contents($dest_path, $file_data);
    }
    
    $stmt = $pdo->prepare("INSERT INTO group_messages (group_id, from_phone, file_path, file_name, file_type, file_size, time, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'sent')");
    $stmt->execute([$groupId, $from, $dest_path, $file_name, $file_type, $file_size, $time]);
    
    echo json_encode(['success' => true]);
    exit;
}

// ========== УДАЛЕНИЕ СООБЩЕНИЯ В ГРУППЕ ==========
if ($action == 'delete_group_message') {
    $msgId = isset($_POST['msg_id']) ? (int)$_POST['msg_id'] : 0;
    $myPhone = isset($_POST['my_phone']) ? $_POST['my_phone'] : '';
    
    if (!$msgId || empty($myPhone)) {
        echo json_encode(['error' => 'Missing params']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT group_id, from_phone FROM group_messages WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$msgId]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$msg) {
        echo json_encode(['error' => 'Message not found']);
        exit;
    }
    
    $groupId = $msg['group_id'];
    $fromPhone = $msg['from_phone'];
    
    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_phone = ?");
    $stmt->execute([$groupId, $myPhone]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        echo json_encode(['error' => 'Not a member']);
        exit;
    }
    
    $isAdmin = ($member['role'] === 'admin');
    $isSender = ($myPhone === $fromPhone);
    
    if (!$isAdmin && !$isSender) {
        echo json_encode(['error' => 'No permission to delete this message']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE group_messages SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$msgId]);
    
    echo json_encode(['success' => true]);
    exit;
}

// ========== ОТПРАВКА КОДА ПОДТВЕРЖДЕНИЯ ==========
// ========== ОТПРАВКА КОДА ПОДТВЕРЖДЕНИЯ ==========
if ($action == 'send_verification') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    
    if (empty($phone) || empty($email)) {
        echo json_encode(['error' => 'Phone and email required']);
        exit;
    }
    
    // Приводим номер к единому формату
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 10) {
        $phone = '7' . $phone;
    }
    if (strlen($phone) === 11 && $phone[0] === '8') {
        $phone = '7' . substr($phone, 1);
    }
    
    $pdo_local = getDB();
    if (!$pdo_local) {
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    // Проверяем, есть ли уже пользователь с таким номером и другим email
    $stmt = $pdo_local->prepare("SELECT email FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing && !empty($existing['email']) && $existing['email'] !== $email) {
        echo json_encode(['error' => 'Этот номер телефона уже зарегистрирован на другой email']);
        exit;
    }
    
    // ТЕСТОВЫЙ РЕЖИМ: для email @lexchat.rf.gd используем код 131313
    if (strpos($email, '@lexchat.rf.gd') !== false) {
        $code = '131313';
    } else {
        $code = sprintf("%06d", mt_rand(0, 999999));
    }
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['temp_phone'] = $phone;
    $_SESSION['temp_name'] = $name;
    $_SESSION['temp_email'] = $email;
    $_SESSION['temp_code'] = $code;
    $_SESSION['temp_code_expires'] = time() + 600;
    
    // Отправляем письмо (только если не тестовый email или всегда)
    // Для тестовых пользователей письмо не отправляем, но код сохраняем
    if (strpos($email, '@lexchat.rf.gd') === false) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
			$mail->Username = 'your_email@gmail.com';
			$mail->Password   = 'your_app_password';  // Замените на свои данные при установке
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            
            $mail->setFrom($mail->Username, 'LexChat');
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = '=?UTF-8?B?'.base64_encode('Код подтверждения LexChat').'?=';
            $mail->Body    = '
                <div style="font-family: Arial; text-align: center; padding: 20px;">
                    <h2 style="color: #00a884;">Код подтверждения LexChat</h2>
                    <div style="font-size: 32px; font-weight: bold; color: #00a884; padding: 15px; background: #f0f2f5; border-radius: 8px;">
                        ' . $code . '
                    </div>
                    <p>Номер телефона: <strong>' . htmlspecialchars($phone) . '</strong></p>
                    <p>Код действителен 10 минут.</p>
                </div>
            ';
            $mail->AltBody = 'Ваш код: ' . $code . ' для номера ' . $phone;
            $mail->send();
        } catch (Exception $e) {
            echo json_encode(['error' => $mail->ErrorInfo]);
            exit;
        }
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// ========== ПРОВЕРКА КОДА И ЗАВЕРШЕНИЕ ВХОДА ==========
if ($action == 'verify_code') {
    $code = isset($_POST['code']) ? $_POST['code'] : '';
    
    if (empty($code)) {
        echo json_encode(['error' => 'Code required']);
        exit;
    }
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (!isset($_SESSION['temp_code']) || $_SESSION['temp_code'] != $code) {
        echo json_encode(['error' => 'Неверный код']);
        exit;
    }
    
    if ($_SESSION['temp_code_expires'] < time()) {
        echo json_encode(['error' => 'Код истёк. Запросите новый']);
        exit;
    }
    
    $phone = $_SESSION['temp_phone'];
    $name = $_SESSION['temp_name'] ?: 'User';
    $email = $_SESSION['temp_email'];
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }
    
    // Дополнительная проверка: номер не привязан к другому email
    $stmt = $pdo->prepare("SELECT email FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing && !empty($existing['email']) && $existing['email'] !== $email) {
        echo json_encode(['error' => 'Этот номер телефона уже зарегистрирован на другой email']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (phone, name, email, last_seen, is_online) 
                           VALUES (?, ?, ?, NOW(), 1)
                           ON DUPLICATE KEY UPDATE name = ?, email = ?, last_seen = NOW(), is_online = 1");
    $stmt->execute([$phone, $name, $email, $name, $email]);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    unset($_SESSION['temp_code'], $_SESSION['temp_phone'], $_SESSION['temp_name'], $_SESSION['temp_email'], $_SESSION['temp_code_expires']);
    
    session_regenerate_id(true);
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_id'] = $user['id'];
    
    setcookie('user_phone', $phone, [
        'expires' => time() + 60*60*24*30,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    setcookie('user_name', $name, [
        'expires' => time() + 60*60*24*30,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    echo json_encode(['success' => true, 'phone' => $phone, 'user_id' => $user['id']]);
    exit;
}

// ========== СМЕНА EMAIL ПОЛЬЗОВАТЕЛЯ ==========
if ($action == 'change_email') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $myPhone = isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '';
    $newEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($myPhone)) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }
    
    // Проверяем, не занят ли email другим пользователем
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE email = ? AND phone != ?");
    $stmt->execute([$newEmail, $myPhone]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Этот email уже используется другим пользователем']);
        exit;
    }
    
    // Обновляем email
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE phone = ?");
    $stmt->execute([$newEmail, $myPhone]);
    
    echo json_encode(['success' => true, 'email' => $newEmail]);
    exit;
}


// ВРЕМЕННОЕ API ДЛЯ ОТЛАДКИ (удалить потом)
if ($action == 'debug_users') {
    $stmt = $pdo->query("SELECT phone, name, email FROM users ORDER BY id DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
    exit;
}


// ========== ПОЛУЧЕНИЕ НАСТРОЕК НАГРУЗКИ ДЛЯ КЛИЕНТА ==========
if (isset($_GET['action']) && $_GET['action'] === 'get_load_settings') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    $settings_file = 'admin_settings.json';
    $default_settings = array(
        'chats_poll_interval' => 60,
        'broadcast_poll_interval' => 120,
        'messages_poll_interval' => 0,
        'messages_poll_interval_fallback' => 15,
        'messaging_mode' => 'websocket',
        'fallback_mode' => true,
        'disable_groups' => 0,
        'ws_url' => 'wss://lexchat-websocket.onrender.com'
    );
    
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
    
    echo json_encode($default_settings, JSON_UNESCAPED_UNICODE);
    exit;
}



if ($action == 'keep_alive') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_phone'])) {
        $_SESSION['last_activity'] = time();
        $phone = $_SESSION['user_phone'];
        $pdo = getDB();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE phone = ?");
            $stmt->execute([$phone]);
        }
        echo json_encode(['success' => true, 'phone' => $_SESSION['user_phone']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No session']);
    }
    exit;
}

echo '{"error":"unknown action"}';
?>
