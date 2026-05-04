<?php
/////////////////////////////////////
// Project: LexChat                 /
// Author: R.I.Moskalenko (Lex0013) /
// License: MIT                     /
// Copyright (c) 2026               /
/////////////////////////////////////
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_active_broadcast') {
    $user_phone = isset($_GET['user_phone']) ? $_GET['user_phone'] : '';
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(array('has_broadcast' => false));
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, message, from_phone FROM broadcast_messages WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $broadcast = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($broadcast) {
            $stmt2 = $pdo->prepare("SELECT id FROM user_broadcast_read WHERE user_phone = ? AND broadcast_id = ?");
            $stmt2->execute(array($user_phone, $broadcast['id']));
            $is_read = $stmt2->fetch();
            
            if (!$is_read) {
                echo json_encode(array(
                    'has_broadcast' => true,
                    'broadcast_id' => $broadcast['id'],
                    'message' => $broadcast['message'],
                    'from_phone' => $broadcast['from_phone']
                ), JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        
        echo json_encode(array('has_broadcast' => false));
    } catch(Exception $e) {
        echo json_encode(array('has_broadcast' => false));
    }
    exit;
}

if ($action === 'mark_broadcast_read') {
    $user_phone = isset($_POST['user_phone']) ? $_POST['user_phone'] : '';
    $broadcast_id = isset($_POST['broadcast_id']) ? (int)$_POST['broadcast_id'] : 0;
    
    if (empty($user_phone) || !$broadcast_id) {
        echo json_encode(array('success' => false));
        exit;
    }
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(array('success' => false));
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_broadcast_read (user_phone, broadcast_id) VALUES (?, ?)");
        $stmt->execute(array($user_phone, $broadcast_id));
        echo json_encode(array('success' => true));
    } catch(Exception $e) {
        echo json_encode(array('success' => false));
    }
    exit;
}

echo json_encode(array('error' => 'Unknown action'));
?>