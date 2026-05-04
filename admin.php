<?php
/////////////////////////////////////
// Project: LexChat                 /
// Author: R.I.Moskalenko (Lex0013) /
// License: MIT                     /
// Copyright (c) 2026               /
/////////////////////////////////////

header('Content-Type: text/html; charset=utf-8');
session_start();

require_once 'connect.php';

// ========== ФУНКЦИЯ ДЛЯ ОТПРАВКИ WS УВЕДОМЛЕНИЙ (SOCKET.IO) ==========
function sendWsNotification($endpoint, $data) {
    $ws_url = 'https://lexchat-websocket.onrender.com';
    
    $ch = curl_init($ws_url . $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("WS Notification to {$endpoint}: " . ($error ? "Error: {$error}" : "Success"));
    return !$error;
}

// ========== АВТОРИЗАЦИЯ АДМИНКИ (SHA256) ==========
// Хеш пароля 'admin123' – для демонстрации. В продакшене смените.
$admin_password_hash = '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9';
$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

if (!$is_authenticated && isset($_POST['password'])) {
    $input_hash = hash('sha256', $_POST['password']);
    if ($input_hash === $admin_password_hash) {
        $_SESSION['admin_authenticated'] = true;
        $is_authenticated = true;
    }
}



// ========== API ДЛЯ ПРОВЕРКИ СТАТУСА WS ==========
if ($is_authenticated && isset($_GET['action']) && $_GET['action'] === 'check_ws_status') {
    header('Content-Type: application/json; charset=utf-8');
    
    $ws_url = 'https://lexchat-websocket.onrender.com/healthz';
    
    $ch = curl_init($ws_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        echo json_encode([
            'success' => true, 
            'online' => true,
            'connections' => $data['connections'] ?? 0,
            'online_users' => $data['online'] ?? 0
        ]);
    } else {
        echo json_encode(['success' => false, 'online' => false, 'error' => $error]);
    }
    exit;
}



if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ========== AJAX ПЕРЕКЛЮЧЕНИЕ ГРУПП (МГНОВЕННОЕ) ==========
if ($is_authenticated && isset($_GET['toggle_groups']) && isset($_GET['enabled'])) {
    header('Content-Type: application/json');
    $enabled = (int)$_GET['enabled'];
    
    $settings_file = 'admin_settings.json';
    $settings = array();
    if (file_exists($settings_file)) {
        $content = file_get_contents($settings_file);
        $settings = json_decode($content, true);
        if (!is_array($settings)) $settings = array();
    }
    $settings['disable_groups'] = $enabled ? 0 : 1;
    file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    sendWsNotification('/api/groups_toggle', array('enabled' => ($enabled == 1)));
    
    echo json_encode(['success' => true]);
    exit;
}

// ========== AJAX ЗАГРУЗКА ФОНА ==========
if ($is_authenticated && isset($_GET['upload_bg']) && isset($_FILES['chat_background_file'])) {
    header('Content-Type: application/json');
    
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $ext = strtolower(pathinfo($_FILES['chat_background_file']['name'], PATHINFO_EXTENSION));
    if ($ext == 'jpeg') $ext = 'jpg';
    
    if (!in_array($ext, ['jpg', 'png', 'gif', 'webp'])) {
        echo json_encode(['success' => false, 'error' => 'Разрешены только JPG, PNG, GIF, WEBP']);
        exit;
    }
    
    $filename = 'chat_bg_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['chat_background_file']['tmp_name'], $filepath)) {
        $colors_file = 'colors.json';
        $full_colors = array();
        if (file_exists($colors_file)) {
            $content = file_get_contents($colors_file);
            $full_colors = json_decode($content, true);
            if (!is_array($full_colors)) $full_colors = array();
        }
        $full_colors['chat_background'] = $filepath;
        file_put_contents($colors_file, json_encode($full_colors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        sendWsNotification('/api/colors', array('colors' => $full_colors));
        
        echo json_encode(['success' => true, 'path' => $filepath]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Upload failed']);
    exit;
}

// ========== ЗАГРУЗКА НАСТРОЕК НАГРУЗКИ ==========
$load_settings_file = 'admin_settings.json';
$load_settings = array(
    'chats_poll_interval' => 60,
    'broadcast_poll_interval' => 120,
    'messages_poll_interval' => 0,
    'messages_poll_interval_fallback' => 15,
    'messaging_mode' => 'websocket',
    'fallback_mode' => true,
    'disable_groups' => 0,
    'ws_url' => 'wss://lexchat-websocket.onrender.com'
);

if (file_exists($load_settings_file)) {
    $content = file_get_contents($load_settings_file);
    $saved_settings = json_decode($content, true);
    if (is_array($saved_settings)) {
        foreach ($load_settings as $key => $value) {
            if (isset($saved_settings[$key])) $load_settings[$key] = $saved_settings[$key];
        }
    }
}

// ========== СОХРАНЕНИЕ НАСТРОЕК НАГРУЗКИ ==========
$load_settings_saved = false;
if ($is_authenticated && isset($_POST['save_load_settings'])) {
    $messaging_mode = isset($_POST['messaging_mode']) ? $_POST['messaging_mode'] : 'websocket';
    if ($messaging_mode != 'websocket' && $messaging_mode != 'mysql_only') {
        $messaging_mode = 'websocket';
    }
    
    $load_settings = array(
        'chats_poll_interval' => (int)$_POST['chats_poll_interval'],
        'broadcast_poll_interval' => (int)$_POST['broadcast_poll_interval'],
        'messages_poll_interval' => (int)$_POST['messages_poll_interval'],
        'messages_poll_interval_fallback' => (int)$_POST['messages_poll_interval_fallback'],
        'messaging_mode' => $messaging_mode,
        'fallback_mode' => isset($_POST['fallback_mode']) ? 1 : 0,
        'disable_groups' => isset($_POST['disable_groups']) ? 1 : 0,
        'ws_url' => isset($_POST['ws_url']) ? trim($_POST['ws_url']) : 'wss://lexchat-websocket.onrender.com'
    );
    
    if ($load_settings['messages_poll_interval'] == 0 && $load_settings['messaging_mode'] == 'websocket') {
        $load_settings['fallback_mode'] = 1;
    }
    
    $json_data = json_encode($load_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_data !== false) {
        file_put_contents($load_settings_file, $json_data);
        $load_settings_saved = true;
        
        sendWsNotification('/api/polling_settings', array(
            'chats_poll_interval' => $load_settings['chats_poll_interval'],
            'broadcast_poll_interval' => $load_settings['broadcast_poll_interval'],
            'messages_poll_interval_fallback' => $load_settings['messages_poll_interval_fallback'],
            'disable_groups' => $load_settings['disable_groups']
        ));
    }
}

// ========== СБРОС НАСТРОЕК НАГРУЗКИ ==========
if ($is_authenticated && isset($_GET['reset_load_settings'])) {
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
    file_put_contents($load_settings_file, json_encode($default_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: admin.php?tab=load');
    exit;
}

// ========== ОБРАБОТКА УДАЛЕНИЯ ПОЛЬЗОВАТЕЛЯ ==========
$delete_user_message = '';
$delete_user_error = '';

if ($is_authenticated && isset($_POST['delete_user'])) {
    $user_phone = isset($_POST['user_phone']) ? $_POST['user_phone'] : '';
    
    if (empty($user_phone)) {
        $delete_user_error = "Номер пользователя не указан";
    } else {
        $pdo = getDB();
        if (!$pdo) {
            $delete_user_error = "Ошибка подключения к БД";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$user_phone]);
                if ($stmt->fetch()) {
                    $pdo->prepare("UPDATE messages SET deleted_at = NOW() WHERE from_phone = ? OR to_phone = ?")->execute([$user_phone, $user_phone]);
                    $pdo->prepare("DELETE FROM group_members WHERE user_phone = ?")->execute([$user_phone]);
                    $pdo->prepare("DELETE FROM user_broadcast_read WHERE user_phone = ?")->execute([$user_phone]);
                    $pdo->prepare("DELETE FROM users WHERE phone = ?")->execute([$user_phone]);
                    $delete_user_message = "✅ Пользователь $user_phone успешно удалён!";
                } else {
                    $delete_user_error = "❌ Пользователь $user_phone не найден";
                }
            } catch(Exception $e) {
                $delete_user_error = "Ошибка: " . $e->getMessage();
            }
        }
    }
}

// ========== ОБРАБОТКА ТЕСТОВЫХ ПОЛЬЗОВАТЕЛЕЙ ==========
$test_message = '';
$test_message_type = '';

if ($is_authenticated && isset($_POST['test_action'])) {
    $action = $_POST['test_action'];
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : 'Тест Пользователь';
    $email = isset($_POST['email']) ? trim($_POST['email']) : 'test@lexchat.rf.gd';
    
    function cleanPhoneForDB($phone) {
        $cleaned = preg_replace('/\D/', '', $phone);
        if (strlen($cleaned) === 10) $cleaned = '7' . $cleaned;
        if (strlen($cleaned) === 11 && $cleaned[0] === '8') $cleaned = '7' . substr($cleaned, 1);
        return $cleaned;
    }
    
    if ($action === 'add') {
        if (empty($phone)) {
            $test_message = '❌ Введите номер телефона';
            $test_message_type = 'error';
        } else {
            $cleanPhone = cleanPhoneForDB($phone);
            if (strlen($cleanPhone) !== 11 || $cleanPhone[0] !== '7') {
                $test_message = '❌ Неверный формат номера. Используйте 10 цифр после +7';
                $test_message_type = 'error';
            } else {
                $pdo = getDB();
                if (!$pdo) {
                    $test_message = '❌ Ошибка подключения к БД';
                    $test_message_type = 'error';
                } else {
                    try {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                        $stmt->execute([$cleanPhone]);
                        if ($stmt->fetch()) {
                            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE phone = ?");
                            $stmt->execute([$name, $email, $cleanPhone]);
                            $test_message = "✅ Пользователь обновлён! Номер: $cleanPhone, Код: 131313";
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO users (phone, name, email, last_seen, is_online) VALUES (?, ?, ?, NOW(), 0)");
                            $stmt->execute([$cleanPhone, $name, $email]);
                            $test_message = "✅ Пользователь создан! Номер: $cleanPhone, Код: 131313";
                        }
                        $test_message_type = 'success';
                    } catch (Exception $e) {
                        $test_message = '❌ Ошибка БД: ' . $e->getMessage();
                        $test_message_type = 'error';
                    }
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $cleanPhone = cleanPhoneForDB($phone);
        $pdo = getDB();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$cleanPhone]);
                if ($stmt->fetch()) {
                    $pdo->prepare("DELETE FROM users WHERE phone = ?")->execute([$cleanPhone]);
                    $test_message = "✅ Пользователь $cleanPhone удалён";
                    $test_message_type = 'success';
                } else {
                    $test_message = "❌ Пользователь $cleanPhone не найден";
                    $test_message_type = 'error';
                }
            } catch (Exception $e) {
                $test_message = '❌ Ошибка БД: ' . $e->getMessage();
                $test_message_type = 'error';
            }
        }
    }
}

// ========== СОХРАНЕНИЕ НАСТРОЕК ЦВЕТОВ ==========
$colors_file = 'colors.json';
$colors_saved = false;
$colors = array();

if (file_exists($colors_file)) {
    $content = file_get_contents($colors_file);
    $colors = json_decode($content, true);
    if (!is_array($colors)) $colors = array();
}

$default_colors = array(
    'dark_bg' => '#0a0f12', 'dark_sidebar_bg' => '#111b21', 'dark_header_bg' => '#202c33',
    'dark_text' => '#e9edef', 'dark_message_in_bg' => '#202c33', 'dark_message_out_bg' => '#005c4b',
    'dark_input_bg' => '#2a3942', 'light_bg' => '#ffffff', 'light_sidebar_bg' => '#ffffff',
    'light_header_bg' => '#e9edef', 'light_text' => '#111b21', 'light_message_in_bg' => '#ffffff',
    'light_message_out_bg' => '#d9fdd3', 'light_input_bg' => '#ffffff',
    'chat_background' => 'fonDefault.png'
);

foreach ($default_colors as $key => $value) {
    if (!isset($colors[$key])) $colors[$key] = $value;
}

if ($is_authenticated && isset($_POST['save_colors'])) {
    $colors = array(
        'dark_bg' => $_POST['dark_bg'], 'dark_sidebar_bg' => $_POST['dark_sidebar_bg'],
        'dark_header_bg' => $_POST['dark_header_bg'], 'dark_text' => $_POST['dark_text'],
        'dark_message_in_bg' => $_POST['dark_message_in_bg'], 'dark_message_out_bg' => $_POST['dark_message_out_bg'],
        'dark_input_bg' => $_POST['dark_input_bg'], 'light_bg' => $_POST['light_bg'],
        'light_sidebar_bg' => $_POST['light_sidebar_bg'], 'light_header_bg' => $_POST['light_header_bg'],
        'light_text' => $_POST['light_text'], 'light_message_in_bg' => $_POST['light_message_in_bg'],
        'light_message_out_bg' => $_POST['light_message_out_bg'], 'light_input_bg' => $_POST['light_input_bg'],
        'chat_background' => isset($_POST['chat_background']) ? $_POST['chat_background'] : $colors['chat_background']
    );
    $json_data = json_encode($colors);
    if ($json_data !== false) {
        file_put_contents($colors_file, $json_data);
        $colors_saved = true;
        
        sendWsNotification('/api/colors', array('colors' => $colors));
    }
}

// ========== ЗАГРУЗКА НОВОГО ФОНА ==========
if ($is_authenticated && isset($_FILES['chat_background_file']) && $_FILES['chat_background_file']['error'] == 0) {
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $ext = pathinfo($_FILES['chat_background_file']['name'], PATHINFO_EXTENSION);
    $filename = 'chat_bg_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['chat_background_file']['tmp_name'], $filepath)) {
        $colors_file = 'colors.json';
        $full_colors = array();
        if (file_exists($colors_file)) {
            $content = file_get_contents($colors_file);
            $full_colors = json_decode($content, true);
            if (!is_array($full_colors)) $full_colors = array();
        }
        $full_colors['chat_background'] = $filepath;
        file_put_contents($colors_file, json_encode($full_colors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        sendWsNotification('/api/colors', array('colors' => $full_colors));
        
        header('Location: admin.php?tab=design&bg_uploaded=1');
        exit;
    }
}

// ========== СБРОС ЦВЕТОВ ==========
if ($is_authenticated && isset($_GET['reset_colors'])) {
    file_put_contents($colors_file, json_encode($default_colors));
    header('Location: admin.php?tab=design');
    exit;
}

if ($colors_saved && isset($_GET['tab']) && $_GET['tab'] == 'design') {
    echo '<script>console.log("Colors saved");</script>';
}

// ========== ОБРАБОТКА ОЧИСТКИ БАЗЫ ДАННЫХ ==========
$clean_message = '';
$clean_error = '';

if ($is_authenticated && isset($_POST['confirm_clean'])) {
    $pdo = getDB();
    if (!$pdo) {
        $clean_error = "❌ Ошибка подключения к БД";
    } else {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $tables_to_truncate = [
                'group_messages', 'group_members', 'groups', 'messages',
                'user_broadcast_read', 'broadcast_messages', 'users'
            ];
            foreach ($tables_to_truncate as $table) {
                $pdo->exec("TRUNCATE TABLE `$table`");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $clean_message = "✅ ВСЕ ДАННЫЕ УСПЕШНО ОЧИЩЕНЫ!";
        } catch(Exception $e) {
            try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch(Exception $ex) {}
            $clean_error = "❌ Ошибка очистки: " . $e->getMessage();
        }
    }
}

// ========== СКАЧИВАНИЕ ДАМПА ==========
if ($is_authenticated && isset($_GET['download_db'])) {
    $pdo = getDB();
    if (!$pdo) die("Ошибка подключения к БД");
    
    $filename = 'lexchat_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');
    
    $tables = ['users', 'messages', 'groups', 'group_members', 'group_messages', 'broadcast_messages', 'user_broadcast_read'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "-- Структура таблицы `$table`\n";
        echo $row['Create Table'] . ";\n\n";
        
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            echo "-- Данные таблицы `$table`\n";
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function($val) {
                    if ($val === null) return 'NULL';
                    return "'" . addslashes($val) . "'";
                }, array_values($row));
                echo "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            echo "\n";
        }
    }
    exit;
}

// ========== API ДЛЯ РАССЫЛКИ ==========
if (isset($_GET['action']) && $_GET['action'] === 'get_active_broadcast') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    
    $user_phone = isset($_GET['user_phone']) ? $_GET['user_phone'] : '';
    if (empty($user_phone)) { echo json_encode(array('has_broadcast' => false)); exit; }
    
    $pdo = getDB();
    if (!$pdo) { echo json_encode(array('has_broadcast' => false)); exit; }
    
    try {
        $stmt = $pdo->prepare("SELECT id, message, from_phone FROM broadcast_messages WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $broadcast = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($broadcast) {
            $stmt2 = $pdo->prepare("SELECT id FROM user_broadcast_read WHERE user_phone = ? AND broadcast_id = ?");
            $stmt2->execute(array($user_phone, $broadcast['id']));
            if (!$stmt2->fetch()) {
                echo json_encode(array('has_broadcast' => true, 'broadcast_id' => $broadcast['id'], 'message' => $broadcast['message'], 'from_phone' => $broadcast['from_phone']), JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        echo json_encode(array('has_broadcast' => false));
    } catch(Exception $e) { echo json_encode(array('has_broadcast' => false)); }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'mark_broadcast_read') {
    header('Content-Type: application/json; charset=utf-8');
    $user_phone = isset($_POST['user_phone']) ? $_POST['user_phone'] : '';
    $broadcast_id = isset($_POST['broadcast_id']) ? (int)$_POST['broadcast_id'] : 0;
    if (empty($user_phone) || !$broadcast_id) { echo json_encode(array('success' => false)); exit; }
    $pdo = getDB();
    if (!$pdo) { echo json_encode(array('success' => false)); exit; }
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_broadcast_read (user_phone, broadcast_id) VALUES (?, ?)");
        $stmt->execute(array($user_phone, $broadcast_id));
        echo json_encode(array('success' => true));
    } catch(Exception $e) { echo json_encode(array('success' => false)); }
    exit;
}

// ========== API ДЛЯ НАСТРОЕК ==========
if (isset($_GET['action']) && $_GET['action'] === 'get_colors') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');
    echo json_encode($colors, JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_load_settings') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');
    echo json_encode($load_settings, JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== API ДЛЯ ПРОСМОТРА ПЕРЕПИСОК ==========
if (isset($_GET['action']) && $_GET['action'] === 'get_user_contacts') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_authenticated) { echo json_encode(['error' => 'Unauthorized']); exit; }
    $user_phone = isset($_GET['user_phone']) ? $_GET['user_phone'] : '';
    if (empty($user_phone)) { echo json_encode(['error' => 'User phone required']); exit; }
    $pdo = getDB();
    if (!$pdo) { echo json_encode(['error' => 'DB connection failed']); exit; }
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT CASE WHEN from_phone = ? THEN to_phone ELSE from_phone END as contact_phone FROM messages WHERE (from_phone = ? OR to_phone = ?) AND deleted_at IS NULL");
        $stmt->execute([$user_phone, $user_phone, $user_phone]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($contacts as $c) {
            $contact_phone = $c['contact_phone'];
            $stmt2 = $pdo->prepare("SELECT name, avatar FROM users WHERE phone = ?");
            $stmt2->execute([$contact_phone]);
            $user = $stmt2->fetch(PDO::FETCH_ASSOC);
            $result[] = [
                'phone' => $contact_phone,
                'name' => $user ? $user['name'] : $contact_phone,
                'avatar' => ($user && $user['avatar']) ? $user['avatar'] : 'uploads/avatars/default.png'
            ];
        }
        $stmt3 = $pdo->prepare("SELECT g.id, g.name, g.avatar FROM `groups` g JOIN group_members gm ON gm.group_id = g.id WHERE gm.user_phone = ?");
        $stmt3->execute([$user_phone]);
        $groups = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        foreach ($groups as $group) {
            $result[] = [
                'id' => $group['id'],
                'phone' => 'group_' . $group['id'],
                'name' => '👥 ' . $group['name'],
                'avatar' => !empty($group['avatar']) ? $group['avatar'] : 'uploads/group_avatars/default.png',
                'is_group' => true
            ];
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch(Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'get_conversation') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_authenticated) { echo json_encode(['error' => 'Unauthorized']); exit; }
    $user1 = isset($_GET['user1']) ? $_GET['user1'] : '';
    $user2 = isset($_GET['user2']) ? $_GET['user2'] : '';
    
    // ✅ РАЗРЕШАЕМ ПУСТОЙ user1 ДЛЯ ГРУПП
    if (empty($user2)) { 
        echo json_encode(['error' => 'Chat target required']); 
        exit; 
    }
    
    $pdo = getDB();
    if (!$pdo) { echo json_encode(['error' => 'DB connection failed']); exit; }
    
    try {
        // ✅ ЕСЛИ ГРУППА
        if (strpos($user2, 'group_') === 0) {
            $groupId = str_replace('group_', '', $user2);
            $stmt = $pdo->prepare("SELECT id, from_phone, text, file_path, file_name, file_type, file_size, audio_path, audio_duration, time, status FROM group_messages WHERE group_id = ? AND deleted_at IS NULL ORDER BY time ASC");
            $stmt->execute([$groupId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($messages as &$msg) {
                $stmt2 = $pdo->prepare("SELECT name FROM users WHERE phone = ?");
                $stmt2->execute([$msg['from_phone']]);
                $user = $stmt2->fetch(PDO::FETCH_ASSOC);
                $msg['from_name'] = $user ? $user['name'] : $msg['from_phone'];
            }
            echo json_encode(['type' => 'group', 'messages' => $messages], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // ✅ ЛИЧНАЯ ПЕРЕПИСКА - ТРЕБУЕТ ОБА НОМЕРА
        if (empty($user1)) {
            echo json_encode(['error' => 'User1 required for private chat']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id, from_phone, to_phone, text, file_path, file_name, file_type, file_size, audio_path, audio_duration, time, status FROM messages WHERE ((from_phone = ? AND to_phone = ?) OR (from_phone = ? AND to_phone = ?)) AND deleted_at IS NULL ORDER BY time ASC");
        $stmt->execute([$user1, $user2, $user2, $user1]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['type' => 'private', 'messages' => $messages], JSON_UNESCAPED_UNICODE);
    } catch(Exception $e) { 
        echo json_encode(['error' => $e->getMessage()]); 
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_users') {
    if (!$is_authenticated) { header('Content-Type: application/json'); echo json_encode(array('error' => 'Unauthorized')); exit; }
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDB();
    if (!$pdo) { echo json_encode(array('error' => 'DB connection failed')); exit; }
    try {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        if (!empty($search)) {
            $cleanSearch = preg_replace('/\D/', '', $search);
            $stmt = $pdo->prepare("SELECT id, phone, name, avatar, last_seen, is_online FROM users WHERE phone LIKE ? OR name LIKE ? ORDER BY name");
            $stmt->execute(["%$cleanSearch%", "%$search%"]);
        } else {
            $stmt = $pdo->query("SELECT id, phone, name, avatar, last_seen, is_online FROM users ORDER BY name");
        }
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users, JSON_UNESCAPED_UNICODE);
    } catch(Exception $e) { echo json_encode(array('error' => $e->getMessage())); }
    exit;
}

// ========== API ДЛЯ ГРУПП (АДМИНКА) ==========
if (isset($_GET['action']) && $_GET['action'] === 'get_all_groups') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_authenticated) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $pdo = getDB();
    if (!$pdo) { echo json_encode(['error' => 'DB connection failed']); exit; }
    
    try {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        if (!empty($search)) {
            $cleanSearch = preg_replace('/\D/', '', $search);
            $stmt = $pdo->prepare("SELECT g.id, g.name, g.avatar, g.created_by, g.created_at, 
                                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
                                   FROM `groups` g 
                                   WHERE g.name LIKE ? 
                                   OR EXISTS (SELECT 1 FROM group_members gm WHERE gm.group_id = g.id AND gm.user_phone LIKE ?)
                                   OR EXISTS (SELECT 1 FROM group_members gm JOIN users u ON u.phone = gm.user_phone WHERE gm.group_id = g.id AND u.name LIKE ?)
                                   ORDER BY g.created_at DESC");
            $stmt->execute(["%$search%", "%$cleanSearch%", "%$search%"]);
        } else {
            $stmt = $pdo->query("SELECT g.id, g.name, g.avatar, g.created_by, g.created_at, 
                                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
                                FROM `groups` g ORDER BY g.created_at DESC");
        }
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($groups as &$group) {
            $group['avatar'] = !empty($group['avatar']) ? $group['avatar'] : 'uploads/group_avatars/default.png';
        }
        echo json_encode($groups, JSON_UNESCAPED_UNICODE);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_group_members_admin') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_authenticated) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    if (!$groupId) { echo json_encode(['error' => 'Group ID required']); exit; }
    
    $pdo = getDB();
    if (!$pdo) { echo json_encode(['error' => 'DB connection failed']); exit; }
    
    try {
        $stmt = $pdo->prepare("SELECT gm.user_phone, u.name, u.avatar, gm.role, gm.joined_at
                               FROM group_members gm
                               JOIN users u ON u.phone = gm.user_phone
                               WHERE gm.group_id = ?
                               ORDER BY gm.role = 'admin' DESC, u.name");
        $stmt->execute([$groupId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($members as &$member) {
            $member['avatar'] = !empty($member['avatar']) ? $member['avatar'] : 'uploads/avatars/default.png';
        }
        echo json_encode($members, JSON_UNESCAPED_UNICODE);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_group_admin') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_authenticated) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    if (!$groupId) { echo json_encode(['error' => 'Group ID required']); exit; }
    
    $pdo = getDB();
    if (!$pdo) { echo json_encode(['error' => 'DB connection failed']); exit; }
    
    try {
        $pdo->prepare("DELETE FROM group_messages WHERE group_id = ?")->execute([$groupId]);
        $pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$groupId]);
        $pdo->prepare("DELETE FROM `groups` WHERE id = ?")->execute([$groupId]);
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'remove_member_admin') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_authenticated) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $userPhone = isset($_POST['user_phone']) ? $_POST['user_phone'] : '';
    if (!$groupId || empty($userPhone)) { echo json_encode(['error' => 'Missing params']); exit; }
    
    $pdo = getDB();
    if (!$pdo) { echo json_encode(['error' => 'DB connection failed']); exit; }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND role = 'admin'");
        $stmt->execute([$groupId]);
        $adminCount = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_phone = ?");
        $stmt->execute([$groupId, $userPhone]);
        $member = $stmt->fetch();
        
        if ($member && $member['role'] === 'admin' && $adminCount <= 1) {
            echo json_encode(['error' => 'Нельзя удалить единственного администратора']);
            exit;
        }
        
        $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_phone = ?")->execute([$groupId, $userPhone]);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
        $stmt->execute([$groupId]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("DELETE FROM group_messages WHERE group_id = ?")->execute([$groupId]);
            $pdo->prepare("DELETE FROM `groups` WHERE id = ?")->execute([$groupId]);
            echo json_encode(['success' => true, 'group_deleted' => true]);
        } else {
            echo json_encode(['success' => true]);
        }
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ========== ПОЛУЧЕНИЕ СТАТИСТИКИ ==========
$db_stats = [];
$pdo_db = getDB();
if ($pdo_db) {
    try {
        $tables = ['users', 'messages', 'groups', 'group_members', 'group_messages', 'broadcast_messages', 'user_broadcast_read'];
        foreach ($tables as $table) {
            $stmt = $pdo_db->query("SELECT COUNT(*) as cnt FROM `$table`");
            $db_stats[$table] = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        }
    } catch(Exception $e) {}
}

$testUsers = [];
if ($is_authenticated && $pdo_db) {
    try {
        $result = $pdo_db->query("SELECT phone, name, email, is_online FROM users WHERE email LIKE '%@lexchat.rf.gd%' OR email LIKE '%test%' ORDER BY id DESC");
        if ($result) $testUsers = $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// ========== ОБРАБОТКА ОТПРАВКИ РАССЫЛКИ ==========
$broadcast_success = false;
$broadcast_error = '';
$broadcast_message_result = '';

if ($is_authenticated && isset($_POST['broadcast_submit'])) {
    $message = trim($_POST['broadcast_message']);
    $from_phone = isset($_POST['from_phone']) ? trim($_POST['from_phone']) : 'admin';
    if (!empty($message)) {
        $pdo = getDB();
        if (!$pdo) {
            $broadcast_error = "Ошибка подключения к БД";
        } else {
            try {
                $pdo->exec("UPDATE broadcast_messages SET is_active = 0");
                $stmt = $pdo->prepare("INSERT INTO broadcast_messages (message, from_phone, is_active) VALUES (?, ?, 1)");
                $stmt->execute(array($message, $from_phone));
                $broadcast_id = $pdo->lastInsertId();
                $broadcast_success = true;
                $broadcast_message_result = "✅ Информационное сообщение успешно отправлено!";
                
                sendWsNotification('/api/broadcast', array(
                    'broadcast_id' => $broadcast_id,
                    'message' => $message,
                    'from_phone' => $from_phone
                ));
                
            } catch(Exception $e) {
                $broadcast_error = "Ошибка БД: " . $e->getMessage();
            }
        }
    } else {
        $broadcast_error = "Введите текст сообщения";
    }
}

// ========== АКТИВНАЯ ВКЛАДКА (СОХРАНЕНИЕ В COOKIE) ==========
$active_tab = isset($_COOKIE['admin_active_tab']) ? $_COOKIE['admin_active_tab'] : 'broadcast';
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
    setcookie('admin_active_tab', $active_tab, time() + 31536000, '/');
}

$users_count = 0;
$online_count = 0;
if ($is_authenticated) {
    $pdo = getDB();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $users_count = $row['cnt'];
            $stmt2 = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE is_online = 1");
            $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            $online_count = $row2['cnt'];
        } catch(Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon.png">
    <link rel="apple-touch-icon" href="icon.png">
    <title>Админ панель - LexChat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #111b21; color: #e9edef; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #00a884; margin-bottom: 20px; font-size: 24px; }
        .card { background: #202c33; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .card h2 { margin-bottom: 15px; font-size: 18px; color: #00a884; }
        .color-row { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .color-row label { width: 180px; font-size: 14px; }
        .color-row input[type="color"] { width: 50px; height: 40px; background: #2a3942; border: none; border-radius: 8px; cursor: pointer; }
        .color-row input[type="text"] { width: 100px; padding: 8px 12px; background: #2a3942; border: none; border-radius: 8px; color: #e9edef; font-size: 14px; }
        .range-row { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .range-row label { width: 220px; font-size: 14px; }
        .range-row input[type="range"] { flex: 1; min-width: 200px; height: 6px; border-radius: 3px; background: #2a3942; }
        .range-row span { width: 60px; text-align: right; font-family: monospace; font-size: 14px; color: #00a884; }
        .range-desc { font-size: 11px; color: #8696a0; margin-left: 235px; margin-top: -10px; margin-bottom: 10px; }
        button, .button { background: #00a884; border: none; padding: 12px 24px; border-radius: 8px; color: white; font-size: 16px; cursor: pointer; margin-right: 10px; display: inline-block; text-decoration: none; }
        button.reset { background: #c33; }
        button.logout { background: #555; }
        button.download { background: #00a884; }
        button.download:hover { background: #008f6e; }
        button.clean { background: #c33; }
        .success { background: #00a884; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .error { background: #c33; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .warning { background: #ff9800; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: bold; color: #333; }
        .info { background: #2a3942; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
        .login-form { background: #202c33; border-radius: 12px; padding: 30px; text-align: center; }
        .login-form input { padding: 12px; background: #2a3942; border: none; border-radius: 8px; color: #e9edef; font-size: 16px; width: 100%; max-width: 300px; margin-bottom: 15px; }
        hr { border-color: #2a3942; margin: 20px 0; }
        
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 1px solid #2a3942; padding-bottom: 10px; flex-wrap: wrap; }
        .tab { background: none; border: none; padding: 10px 20px; color: #8696a0; cursor: pointer; font-size: 16px; border-radius: 8px 8px 0 0; transition: all 0.2s; }
        .tab.active { color: #00a884; border-bottom: 2px solid #00a884; background: rgba(0,168,132,0.1); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .checkbox-label { display: flex; align-items: center; gap: 12px; margin: 20px 0; cursor: pointer; }
        .checkbox-label input { width: 20px; height: 20px; cursor: pointer; }
        .button-group { display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; }
        
        .stats { background: #2a3942; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
        .stats span { color: #00a884; font-weight: bold; }
        .footer { position: fixed; bottom: 20px; right: 20px; background: #202c33; padding: 8px 15px; border-radius: 8px; font-size: 12px; color: #8696a0; }
        
        .bg-preview { display: flex; align-items: center; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
        .bg-preview img { max-width: 200px; max-height: 150px; border-radius: 12px; border: 2px solid #00a884; background: #2a3942; }
        .file-input-label { background: #2a3942; padding: 12px 20px; border-radius: 8px; cursor: pointer; display: inline-block; }
        .file-input-label:hover { background: #3b4a54; }
        
        .traffic-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        .traffic-table th, .traffic-table td { padding: 10px; text-align: center; border-bottom: 1px solid #2a3942; }
        .traffic-table th { color: #00a884; font-weight: 500; }
        .traffic-table td:first-child { text-align: left; }
        .traffic-table .warning { background: rgba(255,152,0,0.2); color: #ff9800; }
        .traffic-table .success { background: rgba(0,168,132,0.2); color: #00a884; }
        
        .users-list, .contacts-list, .messages-list, .groups-list { max-height: 500px; overflow-y: auto; }
        .user-item, .contact-item, .message-item, .group-item { padding: 12px 16px; border-bottom: 1px solid #2a3942; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: background 0.2s; }
        .user-item:hover, .contact-item:hover, .group-item:hover { background: #2a3942; }
        .user-avatar, .contact-avatar, .group-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .user-info, .contact-info, .group-info { flex: 1; }
        .user-name, .contact-name, .group-name { font-weight: 500; margin-bottom: 4px; }
        .user-phone, .contact-phone, .group-phone { font-size: 12px; color: #8696a0; }
        .user-status { font-size: 12px; padding: 2px 8px; border-radius: 12px; }
        .status-online { background: #00a884; color: white; }
        .status-offline { background: #2a3942; color: #8696a0; }
        .delete-user-btn { background: #c33; border: none; padding: 6px 12px; border-radius: 6px; color: white; cursor: pointer; font-size: 12px; }
        .delete-user-btn:hover { background: #a00; }
        
        .db-stats-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .db-stats-table td { padding: 10px 0; border-bottom: 1px solid #2a3942; }
        .db-stats-table td:first-child { font-weight: bold; }
        .db-stats-table td:last-child { text-align: right; font-family: monospace; font-size: 18px; }
        
        .search-input { width: 100%; padding: 10px 16px; background: #2a3942; border: none; border-radius: 8px; color: #e9edef; font-size: 14px; margin-bottom: 15px; }
        .test-users-list { margin-top: 16px; max-height: 400px; overflow-y: auto; }
        .test-user-item { padding: 12px; border-bottom: 1px solid #2a3942; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .badge { background: #00a884; padding: 2px 8px; border-radius: 12px; font-size: 11px; color: white; }
        .btn-small { background: #2a3942; padding: 6px 12px; font-size: 12px; border: none; border-radius: 6px; color: #e9edef; cursor: pointer; }
        .btn-small.delete { background: #c33; color: white; }
        .flex { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        
        .group-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 15000; display: none; align-items: center; justify-content: center; }
        .group-modal-overlay.open { display: flex; }
        .group-modal { background: #202c33; border-radius: 20px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; }
        .group-modal-header { padding: 16px; border-bottom: 1px solid #2a3942; display: flex; justify-content: space-between; align-items: center; }
        .group-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #00a884; }
        .group-modal-footer { padding: 16px; border-top: 1px solid #2a3942; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-danger { background: #c33; border: none; padding: 10px 20px; border-radius: 8px; color: white; cursor: pointer; }
        .btn-danger:hover { background: #a00; }
        
        .ws-status { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-left: 8px; }
        .ws-status.online { background: #00a884; box-shadow: 0 0 5px #00a884; }
        .ws-status.offline { background: #c33; }
        
        body.light-theme .card { background: #e9edef; }
        body.light-theme .tabs { border-bottom-color: #e0e0e0; }
        body.light-theme .tab.active { background: rgba(0,168,132,0.1); }
        body.light-theme .user-item, body.light-theme .contact-item, body.light-theme .group-item { border-bottom-color: #e0e0e0; }
        body.light-theme .user-item:hover, body.light-theme .contact-item:hover, body.light-theme .group-item:hover { background: #f0f2f5; }
        body.light-theme .info { background: #f0f2f5; color: #111b21; }
        body.light-theme .traffic-table td { border-bottom-color: #e0e0e0; }
        body.light-theme .stats { background: #f0f2f5; color: #111b21; }
   
   
   
   
   /* ========== СТИЛИ ДЛЯ ИЗОБРАЖЕНИЙ В МОДАЛЬНЫХ ОКНАХ ========== */
#conversationList .message-image {
    max-width: 150px;
    max-height: 120px;
    width: auto;
    height: auto;
    border-radius: 8px;
    cursor: pointer;
    object-fit: cover;
}

#conversationList .message-file {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #2a3942;
    border-radius: 8px;
    margin-top: 5px;
    cursor: pointer;
}

#conversationList .message-file .file-icon {
    font-size: 24px;
}

#conversationList .message-file .file-info {
    flex: 1;
}

#conversationList .message-file .file-name {
    font-size: 12px;
    font-weight: 500;
    word-break: break-all;
}

#conversationList .message-file .file-size {
    font-size: 10px;
    color: #8696a0;
}

#conversationList .message-audio {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #2a3942;
    border-radius: 18px;
    margin-top: 5px;
    min-width: 200px;
}

#conversationList .message-audio .audio-play {
    width: 28px;
    height: 28px;
    background: #00a884;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
}

#conversationList .message-audio .audio-duration {
    font-size: 11px;
    color: #8696a0;
}

#conversationList .message-text {
    word-break: break-word;
    white-space: pre-wrap;
    line-height: 1.4;
}

#conversationList .message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 12px;
}

#conversationList .message-sender {
    font-weight: bold;
    color: #00a884;
}

#conversationList .message-time {
    font-size: 10px;
    color: #8696a0;
}

#conversationList .message-item {
    padding: 12px;
    border-bottom: 1px solid #2a3942;
    background: #111b21;
    border-radius: 8px;
    margin-bottom: 8px;
}

body.light-theme #conversationList .message-item {
    background: #fff;
    border-bottom-color: #e0e0e0;
}

body.light-theme #conversationList .message-file {
    background: #f0f2f5;
}

body.light-theme #conversationList .message-audio {
    background: #f0f2f5;
}

   </style>
</head>
<body>
<div class="container">
    <h1>Админ панель - LexChat</h1>
    
    <?php if (!$is_authenticated): ?>
        <div class="login-form">
            <h2>Вход в админ панель</h2>
            <form method="POST">
                <input type="password" name="password" placeholder="Пароль" required>
                <br>
                <button type="submit">Войти</button>
            </form>
        </div>
    <?php else: ?>
        
        <?php if ($load_settings_saved): ?>
            <div class="success">✅ Настройки нагрузки сохранены!</div>
        <?php endif; ?>
        
        <?php if ($colors_saved): ?>
            <div class="success">✅ Настройки оформления сохранены!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['bg_uploaded'])): ?>
            <div class="success">✅ Фоновое изображение обновлено!</div>
        <?php endif; ?>
        
        <?php if ($broadcast_success): ?>
            <div class="success"><?php echo $broadcast_message_result; ?></div>
        <?php endif; ?>
        <?php if (!empty($broadcast_error)): ?>
            <div class="error"><?php echo $broadcast_error; ?></div>
        <?php endif; ?>
        
        <?php if ($clean_message): ?>
            <div class="success"><?php echo $clean_message; ?></div>
        <?php endif; ?>
        <?php if ($clean_error): ?>
            <div class="error"><?php echo $clean_error; ?></div>
        <?php endif; ?>
        
        <?php if ($delete_user_message): ?>
            <div class="success"><?php echo $delete_user_message; ?></div>
        <?php endif; ?>
        <?php if ($delete_user_error): ?>
            <div class="error"><?php echo $delete_user_error; ?></div>
        <?php endif; ?>
        
        <?php if ($test_message): ?>
            <div class="<?php echo $test_message_type === 'success' ? 'success' : 'error'; ?>"><?php echo $test_message; ?></div>
        <?php endif; ?>
        
        <div class="stats">
            📊 Статистика: <strong><?php echo $users_count; ?></strong> пользователей | 
            🟢 Онлайн: <span><?php echo $online_count; ?></span> | 
            ⚫ Оффлайн: <span><?php echo $users_count - $online_count; ?></span>
        </div>
        
        <div class="tabs">
            <button class="tab <?php echo $active_tab == 'broadcast' ? 'active' : ''; ?>" onclick="showTab('broadcast')">📢 Рассылка</button>
            <button class="tab <?php echo $active_tab == 'design' ? 'active' : ''; ?>" onclick="showTab('design')">🎨 Оформление</button>
            <button class="tab <?php echo $active_tab == 'users' ? 'active' : ''; ?>" onclick="showTab('users')">👥 Пользователи</button>
            <button class="tab <?php echo $active_tab == 'groups' ? 'active' : ''; ?>" onclick="showTab('groups')">👥 Группы</button>
            <button class="tab <?php echo $active_tab == 'test_users' ? 'active' : ''; ?>" onclick="showTab('test_users')">🧪 Тестовые</button>
            <button class="tab <?php echo $active_tab == 'load' ? 'active' : ''; ?>" onclick="showTab('load')">⚡ Нагрузка</button>
            <button class="tab <?php echo $active_tab == 'database' ? 'active' : ''; ?>" onclick="showTab('database')">🗄️ База данных</button>
        </div>
        
        <!-- Вкладка 1: Рассылка -->
        <div id="tab-broadcast" class="tab-content <?php echo $active_tab == 'broadcast' ? 'active' : ''; ?>">
            <div class="card">
                <h2>📨 Информационная рассылка</h2>
                <div class="info">
                    <p>ℹ️ Это сообщение увидят <strong>ВСЕ пользователи</strong> при следующем входе в чат.</p>
                    <p>📌 Сообщение появится в виде модального окна.</p>
                    <p>✅ Каждый пользователь увидит его <strong>только один раз</strong>.</p>
                </div>
                <form method="POST">
                    <div class="broadcast-area">
                        <input type="text" name="from_phone" placeholder="От кого" value="Администратор" style="width:100%; padding:12px; background:#2a3942; border:none; border-radius:8px; color:#e9edef; margin-bottom:10px;">
                        <textarea name="broadcast_message" rows="5" placeholder="Введите текст сообщения..." required style="width:100%; padding:12px; background:#2a3942; border:none; border-radius:8px; color:#e9edef; resize:vertical;"></textarea>
                    </div>
                    <button type="submit" name="broadcast_submit">📢 Отправить рассылку</button>
                </form>
            </div>
        </div>
        
        <!-- Вкладка 2: Оформление (цвета + фон чата) -->
        <div id="tab-design" class="tab-content <?php echo $active_tab == 'design' ? 'active' : ''; ?>">
            <div class="card">
                <h2>🎨 Фоновое изображение чата</h2>
                <div class="bg-preview">
                    <?php if (isset($colors['chat_background']) && file_exists($colors['chat_background'])): ?>
                        <img src="<?php echo $colors['chat_background']; ?>?t=<?php echo time(); ?>" id="bgPreview" alt="Текущий фон">
                    <?php else: ?>
                        <img src="fonDefault.png" id="bgPreview" alt="Фон по умолчанию">
                    <?php endif; ?>
                    <div>
                        <label class="file-input-label" style="background:#2a3942; padding:12px 20px; border-radius:8px; cursor:pointer; display:inline-block;">
                            📁 Выбрать изображение
                            <input type="file" id="bgFileInput" accept="image/*" style="display:none;">
                        </label>
                        <p style="font-size:12px; color:#8696a0; margin-top:10px;">Рекомендуемый размер: 1920x1080px</p>
                        <div id="bgUploadStatus" style="font-size:12px; margin-top:8px;"></div>
                    </div>
                </div>
            </div>
            
            <form method="POST" id="colorsForm">
                <div class="card">
                    <h2>🌑 Темная тема</h2>
                    <?php foreach(['dark_bg','dark_sidebar_bg','dark_header_bg','dark_text','dark_message_in_bg','dark_message_out_bg','dark_input_bg'] as $key): ?>
                    <div class="color-row">
                        <label><?php echo str_replace('_',' ',ucfirst($key)); ?></label>
                        <input type="color" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($colors[$key]); ?>">
                        <input type="text" value="<?php echo htmlspecialchars($colors[$key]); ?>" readonly>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card">
                    <h2>☀️ Светлая тема</h2>
                    <?php foreach(['light_bg','light_sidebar_bg','light_header_bg','light_text','light_message_in_bg','light_message_out_bg','light_input_bg'] as $key): ?>
                    <div class="color-row">
                        <label><?php echo str_replace('_',' ',ucfirst($key)); ?></label>
                        <input type="color" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($colors[$key]); ?>">
                        <input type="text" value="<?php echo htmlspecialchars($colors[$key]); ?>" readonly>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="save_colors" value="1">
                <button type="submit">💾 Сохранить настройки оформления</button>
                <a href="?reset_colors=1&tab=design"><button type="button" class="reset" onclick="return confirm('Сбросить все цвета к стандартным?')">🔄 Сбросить к стандарту</button></a>
            </form>
        </div>
        
        <!-- Вкладка 3: Пользователи -->
        <div id="tab-users" class="tab-content <?php echo $active_tab == 'users' ? 'active' : ''; ?>">
            <div class="card">
                <h2>👥 Список пользователей</h2>
                <input type="tel" id="userSearchInput" class="search-input" placeholder="🔍 Поиск по номеру телефона или имени..." autocomplete="off" oninput="searchUsersDelayed()">
                <div id="usersListContainer">
                    <div id="usersList" class="users-list">Загрузка...</div>
                </div>
            </div>
        </div>
        
        <!-- Вкладка 4: Группы -->
        <div id="tab-groups" class="tab-content <?php echo $active_tab == 'groups' ? 'active' : ''; ?>">
            <div class="card">
                <h2>👥 Управление группами</h2>
                <input type="text" id="groupsSearchInput" class="search-input" placeholder="🔍 Поиск по названию группы, номеру телефона или имени..." autocomplete="off" oninput="searchGroupsDelayed()">
                <div id="groupsListContainer">
                    <div id="groupsList" class="users-list">Загрузка...</div>
                </div>
            </div>
        </div>
        
        <!-- Вкладка 5: Тестовые пользователи -->
        <div id="tab-test_users" class="tab-content <?php echo $active_tab == 'test_users' ? 'active' : ''; ?>">
            <div class="card">
                <h2>🧪 Тестовые пользователи</h2>
                <div class="info">
                    🔐 <strong>Код подтверждения для тестовых пользователей:</strong> 
                    <code style="background: #00a884; padding: 4px 10px; border-radius: 6px; color: white; font-size: 16px;">131313</code>
                </div>
                <form method="POST">
                    <input type="hidden" name="test_action" value="add">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #8696a0; font-size: 13px;">📞 Номер телефона *</label>
                        <input type="tel" id="testPhoneInput" name="phone" placeholder="+7 (___) ___-__-__" required autocomplete="off" style="width:100%; padding:12px; background:#2a3942; border:none; border-radius:8px; color:#e9edef; font-size:16px;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #8696a0; font-size: 13px;">👤 Имя</label>
                        <input type="text" name="name" placeholder="Имя (например: Тест Пользователь)" style="width:100%; padding:12px; background:#2a3942; border:none; border-radius:8px; color:#e9edef;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #8696a0; font-size: 13px;">📧 Email</label>
                        <input type="email" name="email" value="test@lexchat.rf.gd" style="width:100%; padding:12px; background:#2a3942; border:none; border-radius:8px; color:#e9edef;">
                    </div>
                    <button type="submit" style="background:#00a884; border:none; padding:12px 24px; border-radius:8px; color:white; cursor:pointer; width:100%;">➕ Добавить / Обновить тестового пользователя</button>
                </form>
                <hr style="border-color:#2a3942; margin:24px 0;">
                <h3 style="font-size:16px; margin-bottom:16px; color:#00a884;">📋 Существующие тестовые пользователи</h3>
                <?php if (empty($testUsers)): ?>
                    <div class="empty-message" style="text-align:center; padding:40px; color:#8696a0;">🧪 Нет тестовых пользователей</div>
                <?php else: ?>
                    <div class="test-users-list">
                        <?php foreach ($testUsers as $user): ?>
                            <div class="test-user-item">
                                <div>
                                    <div class="test-user-phone" style="font-weight:500; color:#00a884; font-family:monospace;"><?php echo htmlspecialchars($user['phone']); ?></div>
                                    <div class="test-user-name" style="color:#e9edef;"><?php echo htmlspecialchars($user['name'] ?: 'Без имени'); ?></div>
                                    <div class="test-user-email" style="font-size:12px; color:#8696a0;"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <div class="flex">
                                    <span class="user-status <?php echo $user['is_online'] ? 'status-online' : 'status-offline'; ?>"><?php echo $user['is_online'] ? '🟢 Онлайн' : '⚫ Оффлайн'; ?></span>
                                    <span class="badge">код: 131313</span>
                                    <button class="btn-small" onclick="copyPhone('<?php echo htmlspecialchars($user['phone']); ?>')">📋 Копировать</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($user['phone']); ?>')">
                                        <input type="hidden" name="test_action" value="delete">
                                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        <button type="submit" class="btn-small delete">🗑 Удалить</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="info" style="margin-top:20px;">
                    <strong>💡 Как использовать:</strong><br>
                    1. Добавьте тестового пользователя с номером <strong>77777777777</strong><br>
                    2. При входе в чат используйте тот же номер и код <strong>131313</strong><br>
                    3. Email должен быть <strong>test@lexchat.rf.gd</strong>
                </div>
            </div>
        </div>
        
        <!-- Вкладка 6: Нагрузка -->
 <!-- Вкладка 5: Нагрузка -->
<div id="tab-load" class="tab-content <?php echo $active_tab == 'load' ? 'active' : ''; ?>">
    <div class="card">
        <h2>⚡ Настройки нагрузки и трафика</h2>
        <form method="POST" id="loadSettingsForm">
            <div class="info" style="margin-bottom: 20px; padding: 15px; background: #2a3942; border-radius: 12px;">
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="font-weight: bold; min-width: 160px;">⚡ Режим работы:</div>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="messaging_mode" value="websocket" <?php echo ($load_settings['messaging_mode'] == 'websocket') ? 'checked' : ''; ?> onchange="toggleMessagingMode()" style="width: 18px; height: 18px; cursor: pointer;">
                        <span>✅ WebSocket + MySQL</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="messaging_mode" value="mysql_only" <?php echo ($load_settings['messaging_mode'] == 'mysql_only') ? 'checked' : ''; ?> onchange="toggleMessagingMode()" style="width: 18px; height: 18px; cursor: pointer;">
                        <span>🗄️ Только MySQL</span>
                    </label>
                </div>
                <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px; margin-left: 180px;" id="wsUrlRow">
                    <span>🌐 WebSocket сервер:</span>
                    <input type="text" name="ws_url" value="<?php echo htmlspecialchars($load_settings['ws_url']); ?>" style="background:#2a3942; border:none; padding:4px 8px; border-radius:6px; width:250px; font-size:12px;">
                    <span id="wsStatusIndicator" style="display: flex; align-items: center; gap: 5px;">
                        <span class="ws-status offline" id="wsStatusDot"></span>
                        <span id="wsStatusText" style="font-size: 11px;">Проверка...</span>
                    </span>
                </div>
            </div>
            
            <div class="range-row">
                <label>📋 Обновление списка чатов</label>
                <input type="range" name="chats_poll_interval" min="1" max="600" step="1" value="<?php echo $load_settings['chats_poll_interval']; ?>" oninput="this.nextElementSibling.innerText = formatIntervalValue(this.value); updateTrafficCalc();" id="chatsRange">
                <span id="chatsSpan"><?php echo $load_settings['chats_poll_interval']; ?> сек</span>
            </div>
            <div class="range-desc">Как часто обновляется список чатов и контактов (затрагивает: users, messages, groups, group_members, group_messages)</div>
            
            <div class="range-row">
                <label>📢 Проверка рассылок</label>
                <input type="range" name="broadcast_poll_interval" min="1" max="600" step="1" value="<?php echo $load_settings['broadcast_poll_interval']; ?>" oninput="this.nextElementSibling.innerText = formatIntervalValue(this.value); updateTrafficCalc();" id="broadcastRange">
                <span id="broadcastSpan"><?php echo $load_settings['broadcast_poll_interval']; ?> сек</span>
            </div>
            <div class="range-desc">Как часто проверяется наличие новой рассылки (затрагивает: broadcast_messages, user_broadcast_read)</div>
            
            <div class="range-row">
                <label>💬 Получение сообщений (основной режим)</label>
                <input type="range" name="messages_poll_interval" min="0" max="600" step="1" value="<?php echo $load_settings['messages_poll_interval']; ?>" oninput="this.nextElementSibling.innerText = (this.value == 0 ? 'отключен' : formatIntervalValue(this.value)); updateTrafficCalc();" id="messagesRange">
                <span id="messagesSpan"><?php echo $load_settings['messages_poll_interval'] == 0 ? 'отключен' : $load_settings['messages_poll_interval'] . ' сек'; ?></span>
            </div>
            <div class="range-desc">В режиме WebSocket рекомендуется 0 (отключен). В режиме Только MySQL - интервал получения сообщений</div>
            
            <div class="range-row" id="fallbackRow" style="<?php echo ($load_settings['messaging_mode'] == 'websocket') ? '' : 'display:none;'; ?>">
                <label>⚠️ Аварийный режим (Fallback)</label>
                <input type="range" name="messages_poll_interval_fallback" min="5" max="300" step="5" value="<?php echo $load_settings['messages_poll_interval_fallback']; ?>" oninput="this.nextElementSibling.innerText = formatIntervalValue(this.value);" id="fallbackRange">
                <span id="fallbackSpan"><?php echo $load_settings['messages_poll_interval_fallback']; ?> сек</span>
            </div>
            <div class="range-desc" id="fallbackDesc" style="<?php echo ($load_settings['messaging_mode'] == 'websocket') ? '' : 'display:none;'; ?>">
                🔄 Используется когда WebSocket сервер недоступен. Клиент автоматически переключится на polling
            </div>
            
            <div class="checkbox-label">
                <input type="checkbox" name="disable_groups" value="1" <?php echo $load_settings['disable_groups'] ? 'checked' : ''; ?> onchange="toggleGroupsInstant(this)">
                <span>🚫 Отключить групповые чаты</span>
            </div>
            
            <div class="info" style="margin: 20px 0;">
                <strong>📊 Расчет трафика (для 1 пользователя в час)</strong>
                <table class="traffic-table" id="trafficTable">
                    <thead>
                        <tr><th>Параметр</th><th>Интервал</th><th>Запросов/час</th></tr>
                    </thead>
                    <tbody>
                        <tr id="chatsRow">
                            <td>📋 Обновление чатов</td>
                            <td id="chatsInterval"><?php echo $load_settings['chats_poll_interval']; ?> сек</td>
                            <td id="chatsPerHour"><?php echo $load_settings['chats_poll_interval'] > 0 ? round(3600 / $load_settings['chats_poll_interval']) : 0; ?></td>
                        </tr>
                        <tr id="broadcastRow">
                            <td>📢 Проверка рассылок</td>
                            <td id="broadcastInterval"><?php echo $load_settings['broadcast_poll_interval']; ?> сек</td>
                            <td id="broadcastPerHour"><?php echo round(3600 / $load_settings['broadcast_poll_interval']); ?></td>
                        </tr>
                        <tr id="messagesRow" style="<?php echo ($load_settings['messaging_mode'] == 'websocket' && $load_settings['messages_poll_interval'] == 0) ? 'display:none;' : ''; ?>">
                            <td>💬 Получение сообщений</td>
                            <td id="messagesInterval"><?php echo $load_settings['messages_poll_interval'] == 0 ? 'отключен' : $load_settings['messages_poll_interval'] . ' сек'; ?></td>
                            <td id="messagesPerHour"><?php echo $load_settings['messages_poll_interval'] > 0 ? round(3600 / $load_settings['messages_poll_interval']) : 0; ?></td>
                        </tr>
                        <tr style="border-top:2px solid #00a884;" id="totalRow">
                            <td><strong>📊 ИТОГО запросов/час</strong></td>
                            <td></td>
                            <td id="totalPerHour"><strong><?php 
                                $total = 0;
                                if ($load_settings['chats_poll_interval'] > 0) $total += round(3600 / $load_settings['chats_poll_interval']);
                                $total += round(3600 / $load_settings['broadcast_poll_interval']);
                                if ($load_settings['messages_poll_interval'] > 0) $total += round(3600 / $load_settings['messages_poll_interval']);
                                echo $total;
                            ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="info" id="forecastInfo">
                <strong>📈 Прогноз трафика (Лимит infinityfree: 800 000 запросов/месяц)</strong>
                <table class="traffic-table" id="forecastTable">
                    <thead>
                        <tr><th>Пользователей</th><th>Запросов/час</th><th>Запросов/день</th><th>Запросов/месяц</th><th>Статус</th></tr>
                    </thead>
                    <tbody id="forecastBody"></tbody>
                </table>
            </div>
            
            <div class="button-group">
                <button type="submit" name="save_load_settings">💾 Сохранить настройки нагрузки</button>
                <a href="?reset_load_settings=1&tab=load"><button type="button" class="reset" onclick="return confirm('Сбросить все настройки нагрузки к стандартным?')">🔄 Сбросить к стандарту</button></a>
            </div>
        </form>
    </div>
</div>
	   
        <!-- Вкладка 7: База данных -->
        <div id="tab-database" class="tab-content <?php echo $active_tab == 'database' ? 'active' : ''; ?>">
            <div class="card">
                <h2>🗄️ Управление базой данных</h2>
                <div class="warning" style="background:#c33; padding:15px; border-radius:12px; margin-bottom:20px;">
                    ⚠️ ВНИМАНИЕ! Очистка базы НЕОБРАТИМА!
                </div>
                <div class="info">
                    <strong>📊 Статистика таблиц:</strong>
                    <table class="db-stats-table">
                        <tr><td>👥 Пользователи (users):</td><td><?php echo $db_stats['users'] ?? 0; ?></td></tr>
                        <tr><td>💬 Личные сообщения (messages):</td><td><?php echo $db_stats['messages'] ?? 0; ?></td></tr>
                        <tr><td>👥 Группы (groups):</td><td><?php echo $db_stats['groups'] ?? 0; ?></td></tr>
                        <tr><td>👤 Участники групп (group_members):</td><td><?php echo $db_stats['group_members'] ?? 0; ?></td></tr>
                        <tr><td>📝 Групповые сообщения (group_messages):</td><td><?php echo $db_stats['group_messages'] ?? 0; ?></td></tr>
                        <tr><td>📢 Рассылки (broadcast_messages):</td><td><?php echo $db_stats['broadcast_messages'] ?? 0; ?></td></tr>
                        <tr><td>✅ Прочитанные рассылки (user_broadcast_read):</td><td><?php echo $db_stats['user_broadcast_read'] ?? 0; ?></td></tr>
                    </table>
                </div>
                <div class="button-group">
                    <a href="?download_db=1"><button type="button" class="download">💾 Скачать базу данных (SQL дамп)</button></a>
                </div>
                <hr>
                <div class="info">
                    <strong>🗑️ Что будет удалено при очистке:</strong>
                    <ul style="margin-top:10px; margin-left:20px; line-height:1.6;">
                        <li>Все пользователи</li><li>Все личные сообщения</li>
                        <li>Все группы и участники групп</li><li>Все групповые сообщения</li>
                        <li>Все рассылки и отметки прочтения</li>
                    </ul>
                    <p style="margin-top:15px; color:#ff9800;"><strong>📁 Файлы (аватарки, фото, аудио) НЕ удаляются!</strong></p>
                </div>
                <form method="POST" onsubmit="return confirmClean()">
                    <label class="checkbox-label">
                        <input type="checkbox" id="confirmCheck" required>
                        <span>Я понимаю, что все данные будут удалены безвозвратно</span>
                    </label>
                    <button type="submit" name="confirm_clean" value="1" class="clean" style="background:#c33;">🗑️ Очистить всё</button>
                </form>
            </div>
        </div>
        
        <!-- Модальные окна -->
        <div id="contactsModal" class="group-modal-overlay" onclick="closeContactsModal()">
            <div class="group-modal" onclick="event.stopPropagation()">
                <div class="group-modal-header"><h3 id="contactsModalTitle">👥 Контакты пользователя</h3><button class="group-modal-close" onclick="closeContactsModal()">✕</button></div>
                <div class="group-modal-body"><div id="contactsListContainer"><div id="contactsList" class="contacts-list">Загрузка...</div></div></div>
                <div class="group-modal-footer"><button onclick="closeContactsModal()" class="btn-danger">Закрыть</button></div>
            </div>
        </div>
        
        <div id="conversationModal" class="group-modal-overlay" onclick="closeConversationModal()">
            <div class="group-modal" onclick="event.stopPropagation()">
                <div class="group-modal-header"><h3 id="conversationModalTitle">💬 Переписка</h3><button class="group-modal-close" onclick="closeConversationModal()">✕</button></div>
                <div class="group-modal-body"><div id="conversationListContainer"><div id="conversationList" class="messages-list">Загрузка...</div></div></div>
                <div class="group-modal-footer"><button onclick="closeConversationModal()" class="btn-danger">Закрыть</button></div>
            </div>
        </div>
        
        <hr>
        <div class="icon-info" style="background:#2a3942; padding:10px; border-radius:8px; text-align:center; margin-top:20px;">
            <p>Иконка приложения: <strong>icon.png</strong> | Фоновое изображение: <strong>fonDefault.png</strong></p>
        </div>
        <div style="margin-top: 20px; text-align: center;">
            <a href="?logout=1"><button class="logout">Выйти из админки</button></a>
        </div>
        <div class="footer">LexChat Admin Panel</div>
        
    <?php endif; ?>
</div>

<script>
// ========== ФУНКЦИЯ ФОРМАТИРОВАНИЯ ИНТЕРВАЛА ==========
function formatIntervalValue(seconds) {
    if (seconds < 60) return seconds + ' сек';
    var mins = Math.floor(seconds / 60);
    var secs = seconds % 60;
    if (secs === 0) return mins + ' мин';
    return mins + ' мин ' + secs + ' сек';
}

// ========== ПРОВЕРКА СТАТУСА WEBSOCKET ==========
function checkWebSocketStatus() {
    var statusDot = document.getElementById('wsStatusDot');
    var statusText = document.getElementById('wsStatusText');
    
    if (!statusDot || !statusText) return;
    
    statusDot.className = 'ws-status offline';
    statusText.innerHTML = 'Проверка...';
    
    fetch('admin.php?action=check_ws_status', {
        method: 'GET',
        cache: 'no-store'
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.online) {
            statusDot.className = 'ws-status online';
            statusText.innerHTML = '✅ Онлайн (подключений: ' + data.connections + ', пользователей: ' + data.online_users + ')';
        } else {
            statusDot.className = 'ws-status offline';
            statusText.innerHTML = '❌ Оффлайн';
        }
    })
    .catch(function() {
        statusDot.className = 'ws-status offline';
        statusText.innerHTML = '❌ Ошибка проверки';
    });
}


// Вместо 30 секунд - проверяйте раз в 5 минут
setInterval(checkWebSocketStatus, 300000); // 5 минут
setTimeout(checkWebSocketStatus, 2000);

// ========== ПЕРЕМЕННЫЕ ==========
var searchTimeout = null;
var groupsSearchTimeout = null;
var currentSelectedUser = null;
var currentSelectedContact = null;

// ========== ФУНКЦИИ ВКЛАДОК ==========
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(function(tab) { tab.classList.remove('active'); });
    document.querySelectorAll('.tab').forEach(function(btn) { btn.classList.remove('active'); });
    document.getElementById('tab-' + tabName).classList.add('active');
    if (event && event.target) event.target.classList.add('active');
    document.cookie = 'admin_active_tab=' + tabName + '; path=/; max-age=31536000';
    if (tabName === 'users') loadUsers();
    if (tabName === 'groups') loadGroups();
}

// ========== МГНОВЕННОЕ ПЕРЕКЛЮЧЕНИЕ ГРУПП ==========
function toggleGroupsInstant(checkbox) {
    var enabled = !checkbox.checked;
    fetch('admin.php?toggle_groups=1&enabled=' + (enabled ? 1 : 0))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                console.log('✅ Groups toggled, WS notification sent');
            }
        })
        .catch(function(e) {
            console.log('❌ Error sending groups toggle:', e);
        });
}

// ========== AJAX ЗАГРУЗКА ФОНА ==========
document.addEventListener('DOMContentLoaded', function() {
    var bgFileInput = document.getElementById('bgFileInput');
    if (bgFileInput) {
        bgFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var file = this.files[0];
                var formData = new FormData();
                formData.append('chat_background_file', file);
                
                var statusDiv = document.getElementById('bgUploadStatus');
                statusDiv.innerHTML = '⏳ Загрузка...';
                statusDiv.style.color = '#00a884';
                
                fetch('admin.php?upload_bg=1', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        var preview = document.getElementById('bgPreview');
                        if (preview) {
                            preview.src = data.path + '?v=' + Date.now();
                            console.log('✅ Превью обновлено:', preview.src);
                        }
                        statusDiv.innerHTML = '✅ Фон успешно обновлён!';
                        statusDiv.style.color = '#00a884';
                        setTimeout(function() { statusDiv.innerHTML = ''; }, 3000);
                    } else {
                        statusDiv.innerHTML = '❌ Ошибка: ' + (data.error || 'Неизвестная');
                        statusDiv.style.color = '#c33';
                    }
                })
                .catch(function(error) {
                    statusDiv.innerHTML = '❌ Ошибка загрузки: ' + error.message;
                    statusDiv.style.color = '#c33';
                });
            }
        });
    }
});

// ========== ТРАФИК КАЛЬКУЛЯТОР ==========
function updateTrafficCalc() {
    var chatsSlider = document.querySelector('input[name="chats_poll_interval"]');
    var broadcastSlider = document.querySelector('input[name="broadcast_poll_interval"]');
    var messagesSlider = document.querySelector('input[name="messages_poll_interval"]');
    
    if (!chatsSlider) return;
    
    var chatsInterval = parseInt(chatsSlider.value);
    var broadcastInterval = parseInt(broadcastSlider.value);
    var messagesInterval = parseInt(messagesSlider.value);
    
    var chatsPerHour = chatsInterval > 0 ? Math.round(3600 / chatsInterval) : 0;
    var broadcastPerHour = Math.round(3600 / broadcastInterval);
    var messagesPerHour = messagesInterval > 0 ? Math.round(3600 / messagesInterval) : 0;
    var totalPerHour = chatsPerHour + broadcastPerHour + messagesPerHour;
    
    var chatsEl = document.getElementById('chatsInterval');
    var broadcastEl = document.getElementById('broadcastInterval');
    var messagesEl = document.getElementById('messagesInterval');
    var chatsPerHourEl = document.getElementById('chatsPerHour');
    var broadcastPerHourEl = document.getElementById('broadcastPerHour');
    var messagesPerHourEl = document.getElementById('messagesPerHour');
    var totalEl = document.getElementById('totalPerHour');
    
    if (chatsEl) chatsEl.innerText = formatIntervalValue(chatsInterval);
    if (broadcastEl) broadcastEl.innerText = formatIntervalValue(broadcastInterval);
    if (messagesEl) messagesEl.innerText = messagesInterval > 0 ? formatIntervalValue(messagesInterval) : 'отключен';
    if (chatsPerHourEl) chatsPerHourEl.innerText = chatsPerHour;
    if (broadcastPerHourEl) broadcastPerHourEl.innerText = broadcastPerHour;
    if (messagesPerHourEl) messagesPerHourEl.innerText = messagesPerHour;
    if (totalEl) totalEl.innerHTML = '<strong>' + totalPerHour + '</strong>';
    
    var usersCounts = [1, 5, 10, 20, 30, 40, 50];
    var tbody = document.getElementById('forecastBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    for (var i = 0; i < usersCounts.length; i++) {
        var users = usersCounts[i];
        var perHour = totalPerHour * users;
        var perDay = perHour * 24;
        var perMonth = perDay * 30;
        var status = '';
        var statusClass = '';
        if (perMonth <= 800000) {
            status = '✅ В лимите';
            statusClass = 'success';
        } else {
            status = '⚠️ ПРЕВЫШЕНИЕ';
            statusClass = 'warning';
        }
        var row = '<tr class="' + statusClass + '">' +
            '<td>' + users + '</td>' +
            '<td>' + perHour.toLocaleString() + '</td>' +
            '<td>' + perDay.toLocaleString() + '</td>' +
            '<td>' + perMonth.toLocaleString() + '</td>' +
            '<td>' + status + '</td>' +
            '</tr>';
        tbody.innerHTML += row;
    }
}

// ========== ПОИСК ПОЛЬЗОВАТЕЛЕЙ ==========
function searchUsersDelayed() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() { loadUsers(); }, 500);
}

function loadUsers() {
    var searchInput = document.getElementById('userSearchInput');
    var searchValue = searchInput ? searchInput.value : '';
    var url = 'admin.php?action=get_users';
    if (searchValue) url += '&search=' + encodeURIComponent(searchValue);
    
    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(users) {
            var container = document.getElementById('usersList');
            if (users.error) { container.innerHTML = '<div class="empty-message">Ошибка загрузки</div>'; return; }
            if (users.length === 0) { container.innerHTML = '<div class="empty-message">👥 Нет пользователей</div>'; return; }
            
            var html = '';
            for (var i = 0; i < users.length; i++) {
                var user = users[i];
                var statusClass = user.is_online ? 'status-online' : 'status-offline';
                var statusText = user.is_online ? '🟢 Онлайн' : '⚫ Оффлайн';
                var displayPhone = formatPhoneMask(user.phone);
                var avatarUrl = (user.avatar && user.avatar !== 'uploads/avatars/default.png') 
                    ? user.avatar + '?t=' + Date.now() 
                    : 'uploads/avatars/default.png';
                html += '<div class="user-item">';
                html += '<img class="user-avatar" src="' + avatarUrl + '" onerror="this.src=\'uploads/avatars/default.png\'">';
                html += '<div class="user-info" onclick="showUserContacts(\'' + escapeHtml(user.phone) + '\', \'' + escapeHtml(user.name) + '\')">';
                html += '<div class="user-name">' + escapeHtml(user.name) + '</div>';
                html += '<div class="user-phone">' + displayPhone + '</div>';
                html += '</div>';
                html += '<div class="user-status ' + statusClass + '">' + statusText + '</div>';
                html += '<form method="POST" onsubmit="return confirm(\'Удалить пользователя ' + escapeHtml(user.name) + ' (' + displayPhone + ')? Все его сообщения также будут удалены.\')">';
                html += '<input type="hidden" name="delete_user" value="1">';
                html += '<input type="hidden" name="user_phone" value="' + escapeHtml(user.phone) + '">';
                html += '<button type="submit" class="delete-user-btn">🗑 Удалить</button>';
                html += '</form></div>';
            }
            container.innerHTML = html;
        })
        .catch(function(e) { document.getElementById('usersList').innerHTML = '<div class="empty-message">Ошибка: ' + e.message + '</div>'; });
}

// ========== ГРУППЫ (АДМИНКА) ==========
function searchGroupsDelayed() {
    if (groupsSearchTimeout) clearTimeout(groupsSearchTimeout);
    groupsSearchTimeout = setTimeout(function() { loadGroups(); }, 500);
}

function loadGroups() {
    var searchInput = document.getElementById('groupsSearchInput');
    var searchValue = searchInput ? searchInput.value : '';
    var url = 'admin.php?action=get_all_groups';
    if (searchValue) url += '&search=' + encodeURIComponent(searchValue);
    
    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(groups) {
            var container = document.getElementById('groupsList');
            if (groups.error) { container.innerHTML = '<div class="empty-message">Ошибка загрузки</div>'; return; }
            if (groups.length === 0) { container.innerHTML = '<div class="empty-message">👥 Нет групп</div>'; return; }
            
            var html = '';
            for (var i = 0; i < groups.length; i++) {
                var group = groups[i];
                var createdDate = new Date(group.created_at).toLocaleDateString();
                var avatarUrl = group.avatar + '?t=' + Date.now();
                html += '<div class="user-item" data-group-id="' + group.id + '">';
                html += '<img class="user-avatar" src="' + avatarUrl + '" onerror="this.src=\'uploads/group_avatars/default.png\'">';
                html += '<div class="user-info" onclick="showGroupConversationModal(' + group.id + ', \'' + escapeHtml(group.name) + '\')">';
                html += '<div class="user-name">👥 ' + escapeHtml(group.name) + '</div>';
                html += '<div class="user-phone">📅 Создана: ' + createdDate + ' | 👤 Участников: ' + group.member_count + '</div>';
                html += '</div>';
                html += '<button class="delete-user-btn" style="background:#2196f3; margin-right:5px;" onclick="event.stopPropagation(); showGroupMembersModal(' + group.id + ', \'' + escapeHtml(group.name) + '\')">👥 Участники</button>';
                html += '<button class="delete-user-btn" style="background:#c33;" onclick="event.stopPropagation(); deleteGroupAdmin(' + group.id + ', \'' + escapeHtml(group.name) + '\')">🗑 Удалить</button>';
                html += '</div>';
            }
            container.innerHTML = html;
        })
        .catch(function(e) { document.getElementById('groupsList').innerHTML = '<div class="empty-message">Ошибка: ' + e.message + '</div>'; });
}

function showGroupConversationModal(groupId, groupName) {
    document.getElementById('conversationModalTitle').innerHTML = '💬 Группа: ' + escapeHtml(groupName);
    document.getElementById('conversationModal').classList.add('open');
    document.getElementById('conversationList').innerHTML = '<div class="empty-message">Загрузка...</div>';
    
    // ✅ ИСПРАВЛЕНО: передаём group_ prefix во второй параметр, первый оставляем пустой строкой
var url = 'admin.php?action=get_conversation&user1=0&user2=group_' + groupId;
    
    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.error) { 
                document.getElementById('conversationList').innerHTML = '<div class="empty-message">Ошибка: ' + data.error + '</div>'; 
                return; 
            }
            var messages = data.messages;
            if (!messages || messages.length === 0) { 
                document.getElementById('conversationList').innerHTML = '<div class="empty-message">💬 Нет сообщений в группе</div>'; 
                return; 
            }
            
            var html = '';
            for (var i = 0; i < messages.length; i++) {
                var msg = messages[i];
                var timeStr = new Date(msg.time * 1000).toLocaleString();
                var sender = msg.from_name || msg.from_phone;
                html += '<div class="message-item" style="cursor:default;">';
                html += '<div class="message-header"><span class="message-sender"><strong>' + escapeHtml(sender) + '</strong></span><span class="message-time">' + timeStr + '</span></div>';
                if (msg.audio_path) {
                    var duration = msg.audio_duration || 0;
                    var durationStr = Math.floor(duration / 60) + ':' + (duration % 60 < 10 ? '0' : '') + (duration % 60);
                    html += '<div class="message-audio"><span>🎤</span><span>Голосовое сообщение (' + durationStr + ')</span></div>';
                } else if (msg.file_path) {
                    var isImage = msg.file_type && msg.file_type.startsWith('image/');
                    var fileName = msg.file_name || 'Файл';
                    if (isImage) {
                        html += '<img class="message-image" src="' + msg.file_path + '?t=' + Date.now() + '">';
                    } else {
                        html += '<div class="message-file"><span>' + getFileIcon(fileName) + '</span><span>' + escapeHtml(fileName) + '</span></div>';
                    }
                } else if (msg.text) {
                    html += '<div class="message-text">' + escapeHtml(msg.text).replace(/\n/g, '<br>') + '</div>';
                }
                html += '</div>';
            }
            document.getElementById('conversationList').innerHTML = html;
        })
        .catch(function(e) { 
            document.getElementById('conversationList').innerHTML = '<div class="empty-message">Ошибка: ' + e.message + '</div>'; 
        });
}

function showGroupMembersModal(groupId, groupName) {
    document.getElementById('contactsModalTitle').innerHTML = '👥 Участники группы: ' + escapeHtml(groupName);
    document.getElementById('contactsModal').classList.add('open');
    document.getElementById('contactsList').innerHTML = '<div class="empty-message">Загрузка...</div>';
    
    fetch('admin.php?action=get_group_members_admin&group_id=' + groupId)
        .then(function(res) { return res.json(); })
        .then(function(members) {
            if (members.error) { document.getElementById('contactsList').innerHTML = '<div class="empty-message">Ошибка: ' + members.error + '</div>'; return; }
            if (members.length === 0) { document.getElementById('contactsList').innerHTML = '<div class="empty-message">👥 Нет участников</div>'; return; }
            
            var html = '';
            for (var i = 0; i < members.length; i++) {
                var member = members[i];
                var avatarUrl = member.avatar + '?t=' + Date.now();
                var roleIcon = member.role === 'admin' ? ' 👑' : '';
                html += '<div class="user-item" style="cursor:default;">';
                html += '<img class="user-avatar" src="' + avatarUrl + '" onerror="this.src=\'uploads/avatars/default.png\'">';
                html += '<div class="user-info">';
                html += '<div class="user-name">' + escapeHtml(member.name) + roleIcon + '</div>';
                html += '<div class="user-phone">' + formatPhoneMask(member.user_phone) + '</div>';
                html += '</div>';
                if (member.role !== 'admin') {
                    html += '<button class="delete-user-btn" onclick="event.stopPropagation(); removeMemberFromGroupAdmin(' + groupId + ', \'' + member.user_phone + '\', \'' + escapeHtml(member.name) + '\')">❌ Исключить</button>';
                } else {
                    html += '<span style="color:#00a884; font-size:12px;">Администратор</span>';
                }
                html += '</div>';
            }
            document.getElementById('contactsList').innerHTML = html;
        })
        .catch(function(e) { document.getElementById('contactsList').innerHTML = '<div class="empty-message">Ошибка: ' + e.message + '</div>'; });
}

function deleteGroupAdmin(groupId, groupName) {
    if (!confirm('Удалить группу "' + groupName + '" для всех пользователей? Сообщения будут удалены.')) return;
    
    fetch('admin.php?action=delete_group_admin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'group_id=' + groupId
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert('✅ Группа удалена');
            loadGroups();
            fetch('https://lexchat-websocket.onrender.com/api/chats_update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reason: 'group_deleted', group_id: groupId })
            }).catch(function(e) {});
        } else {
            alert('❌ Ошибка: ' + (data.error || 'Неизвестная'));
        }
    })
    .catch(function(e) { alert('Ошибка: ' + e.message); });
}

function removeMemberFromGroupAdmin(groupId, userPhone, userName) {
    if (!confirm('Исключить пользователя ' + userName + ' из группы?')) return;
    
    fetch('admin.php?action=remove_member_admin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'group_id=' + groupId + '&user_phone=' + encodeURIComponent(userPhone)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            if (data.group_deleted) {
                alert('Группа удалена, так как не осталось участников');
                loadGroups();
            } else {
                alert('✅ Пользователь исключён');
                showGroupMembersModal(groupId, '');
                loadGroups();
            }
            fetch('https://lexchat-websocket.onrender.com/api/chats_update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ to: userPhone, reason: 'removed_from_group', group_id: groupId })
            }).catch(function(e) {});
        } else {
            alert('❌ Ошибка: ' + (data.error || 'Неизвестная'));
        }
    })
    .catch(function(e) { alert('Ошибка: ' + e.message); });
}

// ========== КОНТАКТЫ И ПЕРЕПИСКИ ==========
function showUserContacts(userPhone, userName) {
    currentSelectedUser = { phone: userPhone, name: userName };
    document.getElementById('contactsModalTitle').innerHTML = '👥 Контакты: ' + escapeHtml(userName);
    document.getElementById('contactsModal').classList.add('open');
    document.getElementById('contactsList').innerHTML = '<div class="empty-message">Загрузка...</div>';
    
    fetch('admin.php?action=get_user_contacts&user_phone=' + encodeURIComponent(userPhone))
        .then(function(res) { return res.json(); })
        .then(function(contacts) {
            if (contacts.error) { document.getElementById('contactsList').innerHTML = '<div class="empty-message">Ошибка: ' + contacts.error + '</div>'; return; }
            if (contacts.length === 0) { document.getElementById('contactsList').innerHTML = '<div class="empty-message">💬 У пользователя нет переписок</div>'; return; }
            
            var html = '';
            for (var i = 0; i < contacts.length; i++) {
                var contact = contacts[i];
                if (contact.is_group) {
                    html += '<div class="contact-item" onclick="showGroupConversation(\'' + escapeHtml(contact.id) + '\', \'' + escapeHtml(contact.name) + '\')">';
                } else {
                    html += '<div class="contact-item" onclick="showConversation(\'' + escapeHtml(currentSelectedUser.phone) + '\', \'' + escapeHtml(contact.phone) + '\', \'' + escapeHtml(contact.name) + '\')">';
                }
                html += '<img class="contact-avatar" src="' + escapeHtml(contact.avatar) + '?t=' + Date.now() + '" onerror="this.src=\'uploads/avatars/default.png\'">';
                html += '<div class="contact-info"><div class="contact-name">' + escapeHtml(contact.name) + '</div>';
                if (!contact.is_group) html += '<div class="contact-phone">' + formatPhoneMask(contact.phone) + '</div>';
                html += '</div><div style="color:#00a884;">➡️</div></div>';
            }
            document.getElementById('contactsList').innerHTML = html;
        })
        .catch(function(e) { document.getElementById('contactsList').innerHTML = '<div class="empty-message">Ошибка: ' + e.message + '</div>'; });
}

function showConversation(user1, user2, contactName) {
    currentSelectedContact = { phone: user2, name: contactName };
    document.getElementById('conversationModalTitle').innerHTML = '💬 Переписка: ' + escapeHtml(currentSelectedUser.name) + ' ⇄ ' + escapeHtml(contactName);
    document.getElementById('conversationModal').classList.add('open');
    document.getElementById('conversationList').innerHTML = '<div class="empty-message">Загрузка...</div>';
    
    fetch('admin.php?action=get_conversation&user1=' + encodeURIComponent(user1) + '&user2=' + encodeURIComponent(user2))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.error) { document.getElementById('conversationList').innerHTML = '<div class="empty-message">Ошибка: ' + data.error + '</div>'; return; }
            var messages = data.messages;
            if (!messages || messages.length === 0) { document.getElementById('conversationList').innerHTML = '<div class="empty-message">💬 Нет сообщений</div>'; return; }
            
            var html = '';
            for (var i = 0; i < messages.length; i++) {
                var msg = messages[i];
                var isOut = (msg.from_phone === user1);
                var sender = isOut ? currentSelectedUser.name : contactName;
                var timeStr = new Date(msg.time * 1000).toLocaleString();
                html += '<div class="message-item">';
                html += '<div class="message-header"><span class="message-sender">' + escapeHtml(sender) + '</span><span class="message-time">' + timeStr + '</span></div>';
                if (msg.audio_path) {
                    var duration = msg.audio_duration || 0;
                    var durationStr = Math.floor(duration / 60) + ':' + (duration % 60 < 10 ? '0' : '') + (duration % 60);
                    html += '<div class="message-audio"><span>🎤</span><span>Голосовое сообщение (' + durationStr + ')</span></div>';
                } else if (msg.file_path) {
                    var isImage = msg.file_type && msg.file_type.startsWith('image/');
                    var fileName = msg.file_name || 'Файл';
                    if (isImage) {
                        html += '<img class="message-image" src="' + msg.file_path + '?t=' + Date.now() + '" onclick="event.stopPropagation()">';
                    } else {
                        html += '<div class="message-file"><span>' + getFileIcon(fileName) + '</span><span>' + escapeHtml(fileName) + '</span></div>';
                    }
                } else if (msg.text) {
                    html += '<div class="message-text">' + escapeHtml(msg.text).replace(/\n/g, '<br>') + '</div>';
                }
                html += '</div>';
            }
            document.getElementById('conversationList').innerHTML = html;
        })
        .catch(function(e) { document.getElementById('conversationList').innerHTML = '<div class="empty-message">Ошибка: ' + e.message + '</div>'; });
}

function showGroupConversation(groupId, groupName) {
    document.getElementById('conversationModalTitle').innerHTML = '💬 Группа: ' + escapeHtml(groupName);
    document.getElementById('conversationModal').classList.add('open');
    document.getElementById('conversationList').innerHTML = '<div class="empty-message">Загрузка...</div>';
    
    fetch('admin.php?action=get_conversation&user1=' + encodeURIComponent(currentSelectedUser.phone) + '&user2=group_' + groupId)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.error) { document.getElementById('conversationList').innerHTML = '<div class="empty-message">Ошибка: ' + data.error + '</div>'; return; }
            var messages = data.messages;
            if (!messages || messages.length === 0) { document.getElementById('conversationList').innerHTML = '<div class="empty-message">💬 Нет сообщений в группе</div>'; return; }
            
            var html = '';
            for (var i = 0; i < messages.length; i++) {
                var msg = messages[i];
                var isOut = (msg.from_phone === currentSelectedUser.phone);
                var sender = msg.from_name || msg.from_phone;
                var timeStr = new Date(msg.time * 1000).toLocaleString();
                html += '<div class="message-item">';
                html += '<div class="message-header"><span class="message-sender">' + escapeHtml(sender) + (isOut ? ' (выбранный)' : '') + '</span><span class="message-time">' + timeStr + '</span></div>';
                if (msg.audio_path) {
                    var duration = msg.audio_duration || 0;
                    var durationStr = Math.floor(duration / 60) + ':' + (duration % 60 < 10 ? '0' : '') + (duration % 60);
                    html += '<div class="message-audio"><span>🎤</span><span>Голосовое сообщение (' + durationStr + ')</span></div>';
                } else if (msg.file_path) {
                    var isImage = msg.file_type && msg.file_type.startsWith('image/');
                    var fileName = msg.file_name || 'Файл';
                    if (isImage) {
                        html += '<img class="message-image" src="' + msg.file_path + '?t=' + Date.now() + '" onclick="event.stopPropagation()">';
                    } else {
                        html += '<div class="message-file"><span>' + getFileIcon(fileName) + '</span><span>' + escapeHtml(fileName) + '</span></div>';
                    }
                } else if (msg.text) {
                    html += '<div class="message-text">' + escapeHtml(msg.text).replace(/\n/g, '<br>') + '</div>';
                }
                html += '</div>';
            }
            document.getElementById('conversationList').innerHTML = html;
        })
        .catch(function(e) { document.getElementById('conversationList').innerHTML = '<div class="empty-message">Ошибка: ' + e.message + '</div>'; });
}

function closeContactsModal() { document.getElementById('contactsModal').classList.remove('open'); }
function closeConversationModal() { document.getElementById('conversationModal').classList.remove('open'); currentSelectedContact = null; }
function confirmClean() { var cb = document.getElementById('confirmCheck'); if (!cb || !cb.checked) { alert('Подтвердите, что вы понимаете последствия'); return false; } return confirm('ВЫ УВЕРЕНЫ? Это действие нельзя отменить!'); }

function formatPhoneMask(phone) {
    if (!phone) return '';
    var cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 0) return '';
    if (cleaned.length === 11 && cleaned.charAt(0) === '7') cleaned = cleaned.substring(1);
    var formatted = '+7';
    if (cleaned.length > 0) formatted += ' (' + cleaned.substring(0, 3);
    if (cleaned.length >= 3) formatted += ') ' + cleaned.substring(3, 6);
    if (cleaned.length >= 6) formatted += '-' + cleaned.substring(6, 8);
    if (cleaned.length >= 8) formatted += '-' + cleaned.substring(8, 10);
    return formatted;
}

function escapeHtml(str) { if (!str) return ''; return String(str).replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }

function getFileIcon(filename) { var ext = filename.split('.').pop().toLowerCase(); if (['jpg','jpeg','png','gif','webp','bmp'].includes(ext)) return '🖼️'; if (['pdf'].includes(ext)) return '📄'; if (['doc','docx'].includes(ext)) return '📝'; if (['txt'].includes(ext)) return '📃'; return '📎'; }

function copyPhone(phone) { navigator.clipboard.writeText(phone).then(function() { alert('✅ Номер скопирован: ' + phone); }); }
function confirmDelete(phone) { return confirm('Удалить тестового пользователя ' + phone + '?'); }

function phoneMask(input) {
    if (!input) return;
    var value = input.value.replace(/\D/g, '');
    var formatted = '+7';
    if (value.length > 1) formatted += ' (' + value.substring(1, 4);
    if (value.length >= 4) formatted += ') ' + value.substring(4, 7);
    if (value.length >= 7) formatted += '-' + value.substring(7, 9);
    if (value.length >= 9) formatted += '-' + value.substring(9, 11);
    input.value = formatted;
}

// ========== ИНИЦИАЛИЗАЦИЯ ==========
document.addEventListener('DOMContentLoaded', function() {
    var testPhoneInput = document.getElementById('testPhoneInput');
    if (testPhoneInput) testPhoneInput.addEventListener('input', function() { phoneMask(this); });
    updateTrafficCalc();
    checkWebSocketStatus();
    if (document.getElementById('groupsSearchInput')) loadGroups();
});

// ========== ПЕРЕКЛЮЧЕНИЕ РЕЖИМА РАБОТЫ ==========
function toggleMessagingMode() {
    var mode = document.querySelector('input[name="messaging_mode"]:checked').value;
    var wsUrlRow = document.getElementById('wsUrlRow');
    var fallbackRow = document.getElementById('fallbackRow');
    var fallbackDesc = document.getElementById('fallbackDesc');
    var messagesRow = document.getElementById('messagesRow');
    var messagesRange = document.getElementById('messagesRange');
    var messagesSpan = document.getElementById('messagesSpan');
    
    if (mode === 'websocket') {
        if (wsUrlRow) wsUrlRow.style.display = 'flex';
        if (fallbackRow) fallbackRow.style.display = 'flex';
        if (fallbackDesc) fallbackDesc.style.display = 'block';
        
        if (messagesRange && parseInt(messagesRange.value) > 0) {
            messagesRange.value = 0;
            if (messagesSpan) messagesSpan.innerText = 'отключен';
        }
        if (messagesRow) messagesRow.style.display = 'none';
        
    } else {
        if (wsUrlRow) wsUrlRow.style.display = 'none';
        if (fallbackRow) fallbackRow.style.display = 'none';
        if (fallbackDesc) fallbackDesc.style.display = 'none';
        
        if (messagesRange && parseInt(messagesRange.value) === 0) {
            messagesRange.value = 30;
            if (messagesSpan) messagesSpan.innerText = formatIntervalValue(30);
        }
        if (messagesRow) messagesRow.style.display = 'table-row';
    }
    
    updateTrafficCalc();
}

var originalUpdateTrafficCalc = updateTrafficCalc;
updateTrafficCalc = function() {
    var mode = document.querySelector('input[name="messaging_mode"]:checked').value;
    var chatsSlider = document.querySelector('input[name="chats_poll_interval"]');
    var broadcastSlider = document.querySelector('input[name="broadcast_poll_interval"]');
    var messagesSlider = document.querySelector('input[name="messages_poll_interval"]');
    
    if (!chatsSlider) return;
    
    var chatsInterval = parseInt(chatsSlider.value);
    var broadcastInterval = parseInt(broadcastSlider.value);
    var messagesInterval = (mode === 'websocket') ? 0 : parseInt(messagesSlider.value);
    
    var chatsPerHour = chatsInterval > 0 ? Math.round(3600 / chatsInterval) : 0;
    var broadcastPerHour = Math.round(3600 / broadcastInterval);
    var messagesPerHour = messagesInterval > 0 ? Math.round(3600 / messagesInterval) : 0;
    var totalPerHour = chatsPerHour + broadcastPerHour + messagesPerHour;
    
    var chatsIntervalEl = document.getElementById('chatsInterval');
    var broadcastIntervalEl = document.getElementById('broadcastInterval');
    var messagesIntervalEl = document.getElementById('messagesInterval');
    var chatsPerHourEl = document.getElementById('chatsPerHour');
    var broadcastPerHourEl = document.getElementById('broadcastPerHour');
    var messagesPerHourEl = document.getElementById('messagesPerHour');
    var totalEl = document.getElementById('totalPerHour');
    
    if (chatsIntervalEl) chatsIntervalEl.innerText = formatIntervalValue(chatsInterval);
    if (broadcastIntervalEl) broadcastIntervalEl.innerText = formatIntervalValue(broadcastInterval);
    if (messagesIntervalEl) messagesIntervalEl.innerText = messagesInterval > 0 ? formatIntervalValue(messagesInterval) : 'отключен';
    if (chatsPerHourEl) chatsPerHourEl.innerText = chatsPerHour;
    if (broadcastPerHourEl) broadcastPerHourEl.innerText = broadcastPerHour;
    if (messagesPerHourEl) messagesPerHourEl.innerText = messagesPerHour;
    if (totalEl) totalEl.innerHTML = '<strong>' + totalPerHour + '</strong>';
    
    var usersCounts = [1, 5, 10, 20, 30, 40, 50];
    var tbody = document.getElementById('forecastBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    for (var i = 0; i < usersCounts.length; i++) {
        var users = usersCounts[i];
        var perHour = totalPerHour * users;
        var perDay = perHour * 24;
        var perMonth = perDay * 30;
        var status = '';
        var statusClass = '';
        if (perMonth <= 800000) {
            status = '✅ В лимите';
            statusClass = 'success';
        } else {
            status = '⚠️ ПРЕВЫШЕНИЕ';
            statusClass = 'warning';
        }
        var row = '<tr class="' + statusClass + '">' +
            '<td>' + users + '</td>' +
            '<td>' + perHour.toLocaleString() + '</td>' +
            '<td>' + perDay.toLocaleString() + '</td>' +
            '<td>' + perMonth.toLocaleString() + '</td>' +
            '<td>' + status + '</td>' +
            '</tr>';
        tbody.innerHTML += row;
    }
};

document.addEventListener('DOMContentLoaded', function() {
    toggleMessagingMode();
});

</script>
</body>
</html>