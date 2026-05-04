<?php
/////////////////////////////////////
// Project: LexChat                 /
// Author: R.I.Moskalenko (Lex0013) /
// License: MIT                     /
// Copyright (c) 2026               /
/////////////////////////////////////

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Проверяем статус WebSocket-сервера
$ws_url = 'https://lexchat-websocket.onrender.com/healthz';
$ch = curl_init($ws_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$ws_online = ($httpCode === 200);
$timestamp = date('Y-m-d H:i:s');

// Логируем (опционально)
@file_put_contents('keepalive.log', "$timestamp - WS: " . ($ws_online ? 'ONLINE' : 'OFFLINE') . "\n", FILE_APPEND);

echo json_encode([
    'timestamp' => $timestamp,
    'ws_status' => $ws_online ? 'online' : 'offline',
    'ws_url' => $ws_url
]);
?>