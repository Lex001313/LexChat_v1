<?php
/////////////////////////////////////
// Project: LexChat                 /
// Author: R.I.Moskalenko (Lex0013) /
// License: MIT                     /
// Copyright (c) 2026               /
/////////////////////////////////////



/*if (strpos($_SERVER['HTTP_USER_AGENT'], 'WebToAPK') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'rerebot') !== false) {
    header('Location: https://lexchat.free.nf/');
    exit;
}*/

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
//require_once 'connect.php';

session_start();
// ========== ВОССТАНОВЛЕНИЕ СЕССИИ ИЗ КУКИ ==========
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_phone'])) {
    $pdo_local = getDB();
    if ($pdo_local) {
        $stmt = $pdo_local->prepare("SELECT id, name FROM users WHERE phone = ?");
        $stmt->execute([$_COOKIE['user_phone']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_phone'] = $_COOKIE['user_phone'];
            $_SESSION['user_name'] = $user['name'];
        }
    }
}


?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#111b21">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LexChat">
    <link rel="apple-touch-icon" href="icon.png">
    <link rel="icon" type="image/png" href="icon.png">
    <link rel="manifest" href="manifest.json">
    <title>LexChat</title>
    <style>
        /* Стили для подсказок */
        .input-hint {
            font-size: 12px;
            margin-top: 5px;
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            display: none;
        }
        .input-hint.warning {
            display: block;
            background: #ff9800;
            color: #333;
        }
        .input-hint.error {
            display: block;
            background: #c33;
            color: white;
        }
        .input-hint.success {
            display: block;
            background: #00a884;
            color: white;
        }
        .input-hint.info {
            display: block;
            background: #2a3942;
            color: #e9edef;
        }
        body.light-theme .input-hint.info {
            background: #e9edef;
            color: #111b21;
        }
        .btn-primary.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
		
	* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, sans-serif;
    background: #111b21;
    height: 100vh;
    overflow: hidden;
}

#loginScreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100;
    background: #0a0f12;
}

.login-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100%;
    padding: 20px;
    background: #0a0f12;
    overflow-y: auto;
}

.logo {
    font-size: 48px;
    margin-bottom: 40px;
    color: #00a884;
}

.login-container input {
    width: 100%;
    max-width: 300px;
    padding: 12px 16px;
    margin: 8px 0;
    background: #2a3942;
    border: none;
    border-radius: 8px;
    color: #e9edef;
    font-size: 16px;
}

.login-container button {
    background: #00a884;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 16px;
}

.copyright-login {
    text-align: center;
    margin-top: 30px;
    color: #8696a0;
    font-size: 12px;
}

.os-icons {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin: 20px 0 15px 0;
}

.os-icon {
    text-align: center;
    cursor: pointer;
}

.os-icon-circle {
    width: 60px;
    height: 60px;
    background: #2a3942;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 8px;
    transition: transform 0.2s, background 0.2s;
}

.os-icon-circle:hover {
    background: #3b4a54;
    transform: scale(1.05);
}

.os-icon span {
    font-size: 12px;
    color: #8696a0;
}

.share-link {
    text-align: center;
    color: #00a884;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    margin-top: 5px;
}

.share-link:hover {
    text-decoration: underline;
}

#mainScreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10;
    display: flex;
}

#mainScreen.hidden {
    display: none !important;
}

.sidebar {
    width: 320px;
    background: #111b21;
    border-right: 1px solid #2a3942;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    transition: transform 0.3s ease;
    position: relative;
    z-index: 10;
    height: 100%;
}

.sidebar-header {
    padding: 16px;
    background: #202c33;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#myName {
    color: #e9edef;
    font-weight: 500;
    font-size: 18px;
    cursor: pointer;
}

.create-group-btn {
    background: #00a884;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 20px;
    color: white;
    transition: transform 0.2s, background 0.2s;
}

.create-group-btn:hover {
    background: #008f6e;
    transform: scale(1.05);
}

.search-container {
    padding: 12px;
    background: #111b21;
    border-bottom: 1px solid #2a3942;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 10px 16px;
    background: #2a3942;
    border: none;
    border-radius: 8px;
    color: #e9edef;
    font-size: 14px;
    box-sizing: border-box;
}

.search-input::placeholder {
    color: #8696a0;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #111b21;
    border-radius: 8px;
    z-index: 100;
    max-height: 300px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    margin-top: 4px;
}

.search-results.hidden {
    display: none;
}

.search-result-item {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    border-bottom: 1px solid #2a3942;
    transition: all 0.2s;
}

.search-result-item:hover {
    background: #2a3942;
    opacity: 0.9;
}

.start-chat-btn {
    background: #00a884;
    border: none;
    padding: 6px 12px;
    border-radius: 16px;
    color: white;
    cursor: pointer;
    font-size: 12px;
}

.start-chat-btn:hover {
    background: #008f6e;
}

.contacts {
    flex: 1;
    overflow-y: auto;
}

.contact-item {
    padding: 12px 16px;
    border-bottom: 1px solid #2a3942;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
}

.contact-item:hover {
    background: #2a3942;
}

.contact-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.contact-info {
    flex: 1;
    min-width: 0;
}

.contact-name {
    color: #e9edef;
    font-weight: 500;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.contact-last {
    color: #8696a0;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-time {
    font-size: 11px;
    color: #8696a0;
    flex-shrink: 0;
}

.unread-badge {
    display: inline-block;
    width: 10px;
    height: 10px;
    background: #00a884;
    border-radius: 50%;
}

.online-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #00a884;
}

.delete-chat-icon {
    color: #c33;
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: background 0.2s;
    flex-shrink: 0;
}

.delete-chat-icon:hover {
    background: #2a3942;
}

.empty-contacts {
    padding: 40px 20px;
    text-align: center;
    color: #8696a0;
}

.bottom-menu {
    border-top: 1px solid #2a3942;
    background: #111b21;
    padding: 8px 0;
    display: flex;
    justify-content: space-around;
}

.bottom-menu-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 0;
    cursor: pointer;
    border-radius: 8px;
}

.bottom-menu-item:hover {
    background: #2a3942;
}

.bottom-menu-icon {
    font-size: 24px;
    margin-bottom: 4px;
}

.bottom-menu-name {
    font-size: 12px;
    color: #8696a0;
}

.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #0a0f12;
    min-width: 0;
    position: relative;
    z-index: 10;
    height: 100%;
}

.chat-header {
    padding: 16px;
    background: #202c33;
    color: #e9edef;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.chat-header-info {
    flex: 1;
}

.chat-header-name {
    font-size: 16px;
    font-weight: 500;
}

.chat-header-type {
    font-size: 11px;
    color: #8696a0;
    margin-top: 2px;
}

.back-button {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #00a884;
    padding: 0;
    margin: 0;
    width: 40px;
    height: 40px;
    display: none;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.menu-button {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #00a884;
    padding: 0;
    margin: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.menu-button:hover {
    background: rgba(0, 168, 132, 0.1);
}

.messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #0a0f12;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.message {
    max-width: 70%;
    padding: 8px 12px;
    border-radius: 18px;
    font-size: 14px;
    word-wrap: break-word;
    position: relative;
    cursor: pointer;
}

.message.out {
    background: #005c4b;
    color: #e9edef;
    align-self: flex-end;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.message.in {
    background: #202c33;
    color: #e9edef;
    align-self: flex-start;
    margin-right: auto;
    border-bottom-left-radius: 4px;
}

.message-content {
    word-wrap: break-word;
}

.message-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 4px;
}

.message-status {
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.status-icon {
    font-size: 12px;
    color: #8696a0;
}

.status-icon.delivered {
    color: #00a884;
}

.status-icon.read {
    color: #00a884;
}

.message-time {
    font-size: 10px;
    color: #8696a0;
}

.message-file {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 5px;
    border-radius: 8px;
    background: rgba(0,0,0,0.1);
}

.message-file:hover {
    background: rgba(0,0,0,0.2);
}

.file-icon {
    font-size: 32px;
}

.file-info {
    flex: 1;
}

.file-name {
    font-size: 12px;
    font-weight: 500;
}

.file-size {
    font-size: 10px;
    color: #8696a0;
}

.message-image {
    max-width: 200px;
    max-height: 150px;
    border-radius: 12px;
    cursor: pointer;
    margin-top: 5px;
    display: block;
}

.message-image:hover {
    opacity: 0.9;
}

.message-audio {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 18px;
    background: rgba(0,0,0,0.1);
    min-width: 200px;
}

.message.out .message-audio {
    background: rgba(255,255,255,0.1);
}

.audio-play {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #00a884;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.audio-wave {
    flex: 1;
    height: 32px;
    display: flex;
    align-items: center;
    gap: 2px;
}

.audio-wave span {
    width: 3px;
    background: #8696a0;
    border-radius: 2px;
    animation: wave 0.5s infinite ease alternate;
}

.audio-wave span:nth-child(1) {
    height: 8px;
    animation-delay: 0s;
}

.audio-wave span:nth-child(2) {
    height: 16px;
    animation-delay: 0.1s;
}

.audio-wave span:nth-child(3) {
    height: 12px;
    animation-delay: 0.2s;
}

.audio-wave span:nth-child(4) {
    height: 20px;
    animation-delay: 0.3s;
}

.audio-wave span:nth-child(5) {
    height: 10px;
    animation-delay: 0.4s;
}

@keyframes wave {
    from { height: 4px; }
    to { height: 20px; }
}

.audio-duration {
    font-size: 11px;
    color: #8696a0;
    min-width: 35px;
}

/* ========== КОНТЕКСТНОЕ МЕНЮ ДЛЯ МОБИЛЬНЫХ УСТРОЙСТВ ========== */
.message-context-menu {
    position: fixed;
    bottom: 80px;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 40px);
    max-width: 300px;
    background: #202c33;
    border-radius: 16px;
    z-index: 10000;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: slideUp 0.2s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

.message-context-menu-item {
    padding: 14px 20px;
    cursor: pointer;
    color: #e9edef;
    transition: background 0.2s;
    font-size: 16px;
    text-align: center;
    border-bottom: 1px solid #2a3942;
}

.message-context-menu-item:last-child {
    border-bottom: none;
}

.message-context-menu-item.delete {
    color: #ff6b6b;
    font-weight: 500;
}

.message-context-menu-item:hover {
    background: #2a3942;
}

.message-context-menu-item:active {
    background: #3b4a54;
}

/* Светлая тема */
body.light-theme .message-context-menu {
    background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

body.light-theme .message-context-menu-item {
    color: #111b21;
    border-bottom-color: #e0e0e0;
}

body.light-theme .message-context-menu-item.delete {
    color: #c33;
}

body.light-theme .message-context-menu-item:hover {
    background: #f0f2f5;
}

/* Для десктопа - обычное позиционирование */
@media (min-width: 769px) {
    .message-context-menu {
        position: fixed;
        bottom: auto;
        top: auto;
        left: auto;
        transform: none;
        width: auto;
        min-width: 180px;
        background: #202c33;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    
    .message-context-menu-item {
        text-align: left;
        padding: 10px 16px;
        font-size: 14px;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}

/* Затемнение фона при открытом меню */
.message-context-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: none;
}

.message-context-menu-overlay.open {
    display: block;
}

.input-area {
    padding: 16px;
    background: #202c33;
    display: flex;
    gap: 8px;
    align-items: flex-end;
    flex-shrink: 0;
}

.attach-button {
    background: #2a3942;
    border: none;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    cursor: pointer;
    color: #e9edef;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.2s;
}

.attach-button:hover {
    background: #3b4a54;
}

.input-area textarea {
    flex: 1;
    background: #2a3942;
    margin: 0;
    padding: 12px 16px;
    border: none;
    border-radius: 24px;
    color: #e9edef;
    font-size: 16px;
    font-family: inherit;
    resize: none;
    overflow-y: auto;
    line-height: 1.4;
    height: 48px;
    min-height: 48px;
}

.input-area textarea:focus {
    outline: none;
}

.mic-button {
    background: #2a3942;
    border: none;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    cursor: pointer;
    color: #e9edef;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}

.mic-button.recording {
    background: #c33;
    animation: pulse 0.5s infinite;
    color: white;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

.send-button {
    background: #00a884;
    border: none;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    cursor: pointer;
    color: white;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.recording-timer {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    font-size: 16px;
    z-index: 10000;
    display: none;
    gap: 10px;
    align-items: center;
}

.recording-timer .red-dot {
    width: 12px;
    height: 12px;
    background: #c33;
    border-radius: 50%;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.image-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 12000;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.image-modal.open {
    display: flex;
}

.image-modal img {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 11000;
    display: none;
    align-items: center;
    justify-content: center;
}

.modal-overlay.open {
    display: flex;
}

.modal-content {
    background: #fff;
    border-radius: 20px;
    padding: 20px;
    text-align: center;
    width: 85%;
    max-width: 320px;
}

.modal-content h3 {
    color: #111b21;
    margin-bottom: 15px;
    font-size: 18px;
}

.modal-content img {
    width: 100%;
    max-width: 180px;
    margin: 10px auto;
}

.instruction-text {
    text-align: left;
    margin: 15px 0;
    padding: 12px;
    background: #f0f2f5;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.5;
    color: #333;
}

.instruction-text strong {
    display: block;
    margin-bottom: 8px;
    color: #111;
}

.step-icon {
    display: inline-block;
    background: #00a884;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    text-align: center;
    line-height: 20px;
    font-size: 12px;
    margin-right: 8px;
}

.share-icon, .menu-icon {
    display: inline-block;
    background: #ddd;
    padding: 2px 8px;
    border-radius: 6px;
}

.close-btn {
    background: #00a884;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    margin-top: 15px;
    width: 100%;
}

.close-btn:hover {
    background: #008f6e;
}

.group-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 15000;
    display: none;
    align-items: center;
    justify-content: center;
}

.group-modal-overlay.open {
    display: flex;
}

.group-modal {
    background: #202c33;
    border-radius: 20px;
    width: 90%;
    max-width: 400px;
    max-height: 80vh;
    overflow-y: auto;
}

.group-modal-header {
    padding: 16px;
    border-bottom: 1px solid #2a3942;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.group-modal-header h3 {
    color: #e9edef;
    margin: 0;
}

.group-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #00a884;
}

.group-modal-body {
    padding: 20px;
}

.group-modal-footer {
    padding: 16px;
    border-top: 1px solid #2a3942;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.group-avatar-wrapper {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto 15px;
}

.group-avatar-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    background: #2a3942;
}

.group-avatar-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 32px;
    height: 32px;
    background: #00a884;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    transition: transform 0.2s;
}

.group-avatar-overlay:hover {
    transform: scale(1.1);
}

.group-input {
    width: 100%;
    padding: 12px;
    background: #2a3942;
    border: none;
    border-radius: 8px;
    color: #e9edef;
    font-size: 14px;
    margin-bottom: 15px;
}

.group-input:focus {
    outline: none;
}

.group-members-list {
    max-height: 200px;
    overflow-y: auto;
}

.member-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #2a3942;
}

.member-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.member-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.member-name {
    color: #e9edef;
}

.member-role {
    font-size: 11px;
    color: #00a884;
}

.remove-member {
    color: #c33;
    cursor: pointer;
    padding: 5px;
}

.add-member-input {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.add-member-input input {
    flex: 1;
    padding: 8px 12px;
    background: #2a3942;
    border: none;
    border-radius: 8px;
    color: #e9edef;
}

.add-member-input input:focus {
    outline: none;
}

.add-member-input button {
    background: #00a884;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
}

.btn-primary {
    background: #00a884;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
}

.btn-primary:hover {
    background: #008f6e;
}

.btn-danger {
    background: #c33;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
}

.btn-danger:hover {
    background: #a00;
}

.btn-warning {
    background: #ff9800;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
}

.btn-warning:hover {
    background: #e68900;
}

.settings-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
    display: none;
}

.settings-modal-overlay.open {
    display: block;
}

.settings-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 400px;
    height: 100%;
    background: #111b21;
    z-index: 9999;
    display: none;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0,0,0,0.3);
}

.settings-modal.open {
    display: block;
}

.settings-modal .modal-header {
    padding: 16px;
    background: #202c33;
    display: flex;
    align-items: center;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 10;
}

.settings-modal .modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #00a884;
    cursor: pointer;
}

.settings-modal .modal-body {
    padding: 20px;
}

.settings-avatar {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.avatar-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    position: relative;
    cursor: pointer;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    background: #00a884;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.settings-item {
    margin-bottom: 20px;
}

.settings-item label {
    display: block;
    color: #8696a0;
    margin-bottom: 8px;
    font-size: 14px;
}

.settings-input {
    width: 100%;
    padding: 12px;
    background: #2a3942;
    border: none;
    border-radius: 8px;
    color: #e9edef;
    font-size: 16px;
    margin-bottom: 10px;
}

.save-btn {
    background: #00a884;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    font-size: 14px;
}

.save-btn:hover {
    background: #008f6e;
}

.share-btn {
    width: 100%;
    padding: 12px;
    background: #00a884 !important;
    border: none !important;
    border-radius: 8px !important;
    color: white !important;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    text-align: center;
    display: inline-block;
}

.share-btn:hover {
    background: #008f6e !important;
    transform: scale(1.02);
}

.theme-selector {
    display: flex;
    gap: 10px;
}

.theme-btn {
    flex: 1;
    padding: 10px;
    background: #2a3942;
    border: none;
    border-radius: 8px;
    color: #e9edef;
    cursor: pointer;
}

.theme-btn.active {
    background: #00a884;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.checkbox-item input {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-item label {
    margin: 0;
    cursor: pointer;
}

.logout-settings-btn {
    width: 100%;
    padding: 14px;
    background: #c33;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    margin-top: 20px;
}

.logout-settings-btn:hover {
    background: #a00;
}

.copyright-settings {
    text-align: center;
    padding: 20px;
    color: #8696a0;
    font-size: 12px;
    border-top: 1px solid #2a3942;
    margin-top: 20px;
}

body.light-theme .sidebar {
    background: #fff;
    border-right: 1px solid #e0e0e0;
}

body.light-theme .sidebar-header {
    background: #e9edef;
}

body.light-theme #myName {
    color: #111b21;
}

body.light-theme .search-container {
    background: #fff;
}

body.light-theme .search-input {
    background: #f0f2f5;
    color: #111b21;
}

body.light-theme .contact-item {
    border-bottom: 1px solid #e0e0e0;
}

body.light-theme .contact-name {
    color: #111b21;
}

body.light-theme .chat-area {
    background: #fff;
}

body.light-theme .chat-header {
    background: #e9edef;
}

body.light-theme .chat-header-name {
    color: #111b21;
}

body.light-theme .input-area {
    background: #e9edef;
}

body.light-theme .input-area textarea {
    background: #fff;
    color: #111b21;
}

body.light-theme .message.in {
    background: #fff;
    color: #111b21;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

body.light-theme .message.out {
    background: #d9fdd3;
    color: #111b21;
}

body.light-theme .bottom-menu {
    background: #111b21;
    border-top: 1px solid #2a3942;
}

body.light-theme .bottom-menu-name {
    color: #8696a0;
}

body.light-theme .settings-modal {
    background: #fff;
}

body.light-theme .settings-modal .modal-header {
    background: #e9edef;
}

body.light-theme .settings-input {
    background: #f0f2f5;
    color: #111b21;
}

body.light-theme .theme-btn {
    background: #f0f2f5;
    color: #111b21;
}

body.light-theme .settings-item label {
    color: #666;
}

body.light-theme .copyright-settings {
    color: #666;
    border-top-color: #e0e0e0;
}

body.light-theme .copyright-login {
    color: #666;
}

body.light-theme .modal-content {
    background: #fff;
}

body.light-theme .modal-content h3 {
    color: #111b21;
}

body.light-theme .instruction-text {
    background: #e8e8e8;
    color: #333;
}

body.light-theme .instruction-text strong {
    color: #111;
}

body.light-theme .group-modal {
    background: #fff;
}

body.light-theme .group-modal-header {
    border-bottom-color: #e0e0e0;
}

body.light-theme .group-modal-header h3 {
    color: #111b21;
}

body.light-theme .group-input {
    background: #f0f2f5;
    color: #111b21;
}

body.light-theme .member-item {
    border-bottom-color: #e0e0e0;
}

body.light-theme .member-name {
    color: #111b21;
}

body.light-theme .add-member-input input {
    background: #f0f2f5;
    color: #111b21;
}

@media (max-width: 768px) {
    #loginScreen {
        z-index: 200;
    }
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        z-index: 2000;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        background: #111b21;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .chat-area {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .back-button {
        display: flex;
    }
    .message {
        max-width: 85%;
    }
    .settings-modal {
        width: 100%;
        left: 0;
    }
    .chat-header {
        padding-top: calc(16px + env(safe-area-inset-top, 0px));
    }
    .sidebar-header {
        padding-top: calc(16px + env(safe-area-inset-top, 0px));
    }
    .settings-modal .modal-header {
        padding-top: calc(16px + env(safe-area-inset-top, 0px));
    }
    .messages {
        padding: 20px;
        padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        flex: 1;
        overflow-y: auto;
    }
    .input-area {
        padding: 12px 16px;
        padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        background: #202c33;
        flex-shrink: 0;
    }
    .input-area textarea {
        min-height: 60px;
        height: auto;
        max-height: 100px;
    }
    .message-image {
        max-width: 150px;
        max-height: 120px;
    }
    .message-audio {
        min-width: 160px;
    }
}

@media (min-width: 769px) {
    .sidebar {
        display: flex !important;
        transform: translateX(0) !important;
        width: 320px !important;
    }
    .back-button {
        display: none !important;
    }
    .chat-area {
        width: calc(100% - 320px) !important;
    }
}


/* Плавное обновление без мигания */
.contact-item {
    transition: background-color 0.2s ease;
}

.contact-item.updating {
    background-color: #2a3942;
    transition: background-color 0.1s ease;
}

body.light-theme .contact-item.updating {
    background-color: #e0e0e0;
}

/* Анимация для нового сообщения */
@keyframes subtlePulse {
    0% { background-color: transparent; }
    50% { background-color: rgba(0, 168, 132, 0.2); }
    100% { background-color: transparent; }
}

.contact-item.new-message {
    animation: subtlePulse 0.5s ease;
}


/* Плавная загрузка изображений без мерцания */
.message-image {
    max-width: 200px;
    max-height: 150px;
    border-radius: 12px;
    cursor: pointer;
    margin-top: 5px;
    display: block;
    background: #2a3942;
    transition: opacity 0.2s ease;
}

.message-image[loading="lazy"] {
    opacity: 0;
    animation: fadeIn 0.3s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Стили для подсказок с крестиком */
.input-hint {
    font-size: 12px;
    margin-top: 5px;
    margin-bottom: 10px;
    padding: 8px 12px;
    border-radius: 8px;
    display: none;
    position: relative;
}

.input-hint.warning,
.input-hint.error {
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.input-hint.warning:hover,
.input-hint.error:hover {
    opacity: 0.8;
}

.input-hint span {
    font-size: 14px;
    padding: 0 5px;
    border-radius: 50%;
}

.input-hint span:hover {
    background: rgba(255,255,255,0.2);
}	

.messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #0a0f12;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    -webkit-overflow-scrolling: touch; /* Для плавного скролла на iOS */
    scroll-behavior: smooth;
}

/* Для WebKit браузеров (Chrome, Safari) */
.messages::-webkit-scrollbar {
    width: 6px;
}

.messages::-webkit-scrollbar-track {
    background: #2a3942;
    border-radius: 3px;
}

.messages::-webkit-scrollbar-thumb {
    background: #00a884;
    border-radius: 3px;
}


/* ========== СТИЛИ ДЛЯ МОДАЛЬНОГО ОКНА ЗВОНКА ========== */
.call-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); z-index: 30000; display: none;
    align-items: center; justify-content: center;
}
.call-modal-overlay.open { display: flex; }
.call-modal {
    background: #fff; border-radius: 32px; width: 90%; max-width: 400px;
    padding: 20px; text-align: center; color: #111b21;
}
.call-videos { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.call-videos video { width: 100%; background: #000; border-radius: 16px; max-height: 250px; object-fit: cover; }
.call-controls { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
.call-controls button { background: #e9edef; border: none; width: 56px; height: 56px; border-radius: 50%; font-size: 24px; cursor: pointer; transition: 0.2s; }
.call-controls button.active { background: #00a884; color: white; }
.call-controls button.danger { background: #c33; color: white; }
.call-avatar { width: 100px; height: 100px; border-radius: 50%; margin: 10px auto; }
body.dark-theme .call-modal { background: #202c33; color: #e9edef; }
@media (min-width: 768px) { .call-videos { flex-direction: row; } .call-videos video { width: 50%; } }

/* Стили для модального окна звонка с двумя видео */
.call-videos-container {
    position: relative;
    width: 100%;
    background: #000;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 20px;
}
.remote-video {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    background: #000;
}
.local-video {
    position: absolute;
    bottom: 16px;
    right: 16px;
    width: 120px;
    height: 160px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid #00a884;
    background: #1e2a32;
    cursor: pointer;
    z-index: 10;
}
@media (max-width: 768px) {
    .local-video {
        width: 80px;
        height: 120px;
        bottom: 8px;
        right: 8px;
    }
}



.temp-call-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    transition: 0.2s;
    border: none;
}
.temp-call-btn:hover {
    transform: scale(1.05);
}
.temp-call-btn.active {
    background: #00a884 !important;
}
.temp-call-btn.danger {
    background: #c33 !important;
}
    </style>
</head>
<body>
<!--<script src="https://cdn.jsdelivr.net/npm/eruda"></script>
<script>eruda.init();</script>  -->
<!-- Добавьте сразу после <body> -->
<div id="offlineBanner" style="display:none; position:fixed; top:0; left:0; right:0; background:#ff9800; color:#333; text-align:center; padding:10px; font-size:14px; z-index:20000; box-shadow:0 2px 5px rgba(0,0,0,0.2);">
    ⚠️ <span id="offlineBannerMessage">Потеряно соединение</span>
    <button id="offlineBannerReload" style="margin-left:10px; background:#333; color:white; border:none; border-radius:4px; padding:4px 12px; cursor:pointer;">Перезагрузить</button>
</div>
    <div id="app">
        <div id="loginScreen">
            <div class="login-container">
                <div class="logo">💬 LexChat</div>
                <input type="tel" id="phoneInput" placeholder="+7 (___) ___-__-__" autocomplete="off" maxlength="18" oninput="phoneMask(this)">
                <input type="email" id="emailInput" placeholder="Email" autocomplete="off">
                <input type="text" id="nameInput" placeholder="Ваше имя" autocomplete="off">
                <button onclick="login()">Войти</button>
                <div class="copyright-login">© R.I.Moskalenko</div>
                <div class="os-icons">
                    <div class="os-icon" onclick="showIOSModal()"><div class="os-icon-circle">🍏</div><span>iOS</span></div>
                    <div class="os-icon" onclick="showAndroidModal()"><div class="os-icon-circle">🤖</div><span>Android</span></div>
                </div>
                <div class="share-link" onclick="showQRModal()">📲 Поделиться</div>
            </div>
        </div>

        <div id="mainScreen" class="hidden">
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <div id="myName" onclick="openChats()">Чаты</div>
                    <button class="create-group-btn" onclick="showCreateGroupModal()" id="createGroupBtn">👥</button>
                </div>
                <div class="search-container">
                    <input type="tel" id="searchInput" class="search-input" placeholder="+7 (___) ___-__-__" autocomplete="off" maxlength="18" oninput="searchPhoneMask(this)">
                    <div id="searchResults" class="search-results hidden"></div>
                </div>
                <div id="contactsList" class="contacts"></div>
                <div id="bottomMenu" class="bottom-menu"></div>
            </div>
            <div class="chat-area" id="chatArea">
                <div class="chat-header">
                    <button id="backButton" class="back-button" onclick="toggleSidebar()">←</button>
                    <img id="chatHeaderAvatar" class="chat-avatar" src="uploads/avatars/default.png" style="display:none;">
                    <div class="chat-header-info">
                        <div id="contactName" class="chat-header-name">Выберите чат</div>
                        <div id="chatHeaderType" class="chat-header-type"></div>
                    </div>
                    <button id="groupMenuBtn" class="menu-button" style="display:none;" onclick="showGroupInfoModal()">👥</button>
                </div>
                <div id="messagesContainer" class="messages"></div>
                <div class="input-area">
                    <button class="attach-button" onclick="document.getElementById('fileInput').click()">📎</button>
                    <textarea id="messageInput" placeholder="Сообщение" autocomplete="off" rows="1"></textarea>
                    <button id="micButton" class="mic-button">🎤</button>
                    <button class="send-button" onclick="sendMessage()">→</button>
                </div>
                <input type="file" id="fileInput" style="display:none" accept="image/*, .pdf, .doc, .txt, .zip" onchange="uploadFile(this)">
            </div>
            <div id="settingsOverlay" class="settings-modal-overlay" onclick="closeSettings()"></div>
            <div id="settingsModal" class="settings-modal"></div>
        </div>
        
        <div id="recordingTimer" class="recording-timer"><div class="red-dot"></div><span id="recordingTime">0:00</span><span>Запись...</span></div>
        <div id="imageModal" class="image-modal" onclick="closeImageModal()"><img id="modalImage" src=""></div>
        <div id="qrModal" class="modal-overlay" onclick="closeQRModal()"><div class="modal-content" onclick="event.stopPropagation()"><h3>Установите приложение</h3><img src="qr.png" alt="QR-код"><p style="font-size:12px; color:#666; margin-top:5px;">Отсканируйте QR-код камерой телефона</p><button class="close-btn" onclick="closeQRModal()">Закрыть</button></div></div>
        <div id="iosModal" class="modal-overlay" onclick="closeIOSModal()"><div class="modal-content" onclick="event.stopPropagation()">
		
		
		<h3>🍏 Установка на iOS</h3><div class="instruction-text">
		<strong>Инструкция:</strong>
		<br><br><span class="step-icon">1</span> Нажмите кнопку внизу <span class="share-icon">(...)->«Поделиться»</span><br><br><span class="step-icon">2</span> Прокрутите вниз и выберите <strong>«На экран «Домой»</strong><br><br><span class="step-icon">3</span> Нажмите <strong>«Добавить»</strong> в правом верхнем углу</div><button class="close-btn" onclick="closeIOSModal()">Закрыть</button></div></div>
      





	  
        <div id="androidModal" class="modal-overlay" onclick="closeAndroidModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <h3>🤖 Установка на Android</h3>
                <div class="instruction-text">
                   <!-- <strong>Способ 1 (через браузер Chrome):</strong><br><br>
                    <span class="step-icon">1</span> Откройте сайт в браузере Chrome<br>
                    <span class="step-icon">2</span> Нажмите на три точки <span class="menu-icon">⋮</span> в правом верхнем углу<br>
                    <span class="step-icon">3</span> Выберите <strong>«Установить приложение»</strong> или <strong>«Добавить на главный экран»</strong><br>
                    <span class="step-icon">4</span> Нажмите <strong>«Установить»</strong><br><br>
                    -->
                    <strong>Способ (через APK файл):</strong><br><br>
                    <span class="step-icon">1</span> Скачайте файл <strong>LexChat.apk</strong> по ссылке ниже<br>
                    <span class="step-icon">2</span> Откройте скачанный файл<br>
                    <span class="step-icon">3</span> Разрешите установку из неизвестных источников<br>
                    <span class="step-icon">4</span> Нажмите <strong>«Установить»</strong><br><br>
                    
                    <a href="https://github.com/Lex001313/LexChat/releases/download/v1.0.0/LexChat_rf.apk" download style="display:inline-block; background:#00a884; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; margin-top:5px;">
                        📥 Скачать LexChat.apk
                    </a>
                </div>
                <button class="close-btn" onclick="closeAndroidModal()">Закрыть</button>
            </div>
        </div>
		
		
<!-- Модальное окно подтверждения -->
<div id="codeModal" class="modal-overlay" style="display:none;">
    <div class="modal-content" style="max-width: 300px; width: 85%; margin: 0 auto; border-radius: 20px; overflow: hidden;">
        <h3 style="font-size: 18px; margin-bottom: 15px;">📨 Подтверждение</h3>
        <p style="font-size: 14px; margin-bottom: 15px; color: #666;">Введите код из письма</p>
        <input type="text" id="codeInput" placeholder="000000" maxlength="6" 
               style="text-align: center; font-size: 24px; letter-spacing: 8px; 
                      width: 100%; padding: 15px 10px; 
                      border: 1px solid #ddd; border-radius: 12px;
                      background: #f5f5f5; box-sizing: border-box;">
        <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
            <button onclick="verifyCode()" class="btn-primary" style="padding: 12px 25px; font-size: 16px;">Подтвердить</button>
            <button onclick="closeCodeModal()" class="btn-danger" style="padding: 12px 25px; font-size: 16px;">Отмена</button>
        </div>
        <p style="font-size: 12px; margin-top: 15px; color: #999;" id="codeTimer"></p>
    </div>
</div>
		
		
        
        <!-- Модальное окно создания группы -->
        <div id="createGroupModal" class="group-modal-overlay" onclick="closeCreateGroupModal()">
            <div class="group-modal" onclick="event.stopPropagation()">
                <div class="group-modal-header">
                    <h3>👥 Создать группу</h3>
                    <button class="group-modal-close" onclick="closeCreateGroupModal()">✕</button>
                </div>
                <div class="group-modal-body">
                    <div class="group-avatar-wrapper">
                        <img id="createGroupAvatar" class="group-avatar-preview" src="uploads/group_avatars/default.png">
                        <div class="group-avatar-overlay" onclick="document.getElementById('createGroupAvatarInput').click()">📷</div>
                        <input type="file" id="createGroupAvatarInput" style="display:none" accept="image/*">
                    </div>
                    <input type="text" id="createGroupName" class="group-input" placeholder="Название группы" oninput="checkGroupName()">
                    <div id="groupNameHint" class="input-hint"></div>
                    <div id="createGroupMembersList" class="group-members-list"><div style="padding:10px; color:#8696a0;">Участники:</div></div>
                    <div class="add-member-input">
                        <input type="tel" id="addMemberPhone" placeholder="+7 (___) ___-__-__" oninput="phoneMask(this); checkMemberForCreate()" autocomplete="off" maxlength="18">
                        <button id="addMemberBtn" onclick="addMemberToCreateGroup()" style="opacity:0.5; pointer-events:none;">➕ Пригласить</button>
                    </div>
                    <div id="addMemberHint" class="input-hint"></div>
                    <div id="createGroupSearchResults"></div>
                </div>
                <div class="group-modal-footer">
                    <button id="createGroupSubmitBtn" class="btn-primary" onclick="createGroup()">Создать</button>
                    <button onclick="closeCreateGroupModal()" class="btn-danger">Отмена</button>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно информации о группе -->
        <div id="groupInfoModal" class="group-modal-overlay" onclick="closeGroupInfoModal()">
            <div class="group-modal" onclick="event.stopPropagation()">
                <div class="group-modal-header">
                    <h3 id="groupInfoTitle">👥 Информация о группе</h3>
                    <button class="group-modal-close" onclick="closeGroupInfoModal()">✕</button>
                </div>
                <div class="group-modal-body" id="groupInfoBody">
                    <div class="group-avatar-wrapper">
                        <img id="groupInfoAvatar" class="group-avatar-preview" src="uploads/group_avatars/default.png">
                        <div class="group-avatar-overlay" onclick="document.getElementById('groupInfoAvatarInput').click()">📷</div>
                        <input type="file" id="groupInfoAvatarInput" style="display:none" accept="image/*" onchange="updateGroupAvatar(this)">
                    </div>
                    <input type="text" id="groupInfoName" class="group-input" placeholder="Название группы" onblur="updateGroupName()">
                    <div id="groupMembersList" class="group-members-list"><div style="text-align:center; padding:20px; color:#8696a0;">Загрузка...</div></div>
                    <div id="groupAddMemberSection" style="display:none; margin-top:15px;">
                        <div class="add-member-input">
                            <input type="tel" id="groupAddMemberPhone" placeholder="+7 (___) ___-__-__" oninput="phoneMask(this); checkMemberForGroup()" autocomplete="off" maxlength="18">
                            <button id="groupAddMemberBtn" onclick="addMemberToGroup()" style="opacity:0.5; pointer-events:none;">➕ Пригласить</button>
                        </div>
                        <div id="groupAddMemberHint" class="input-hint"></div>
                        <div id="groupSearchResults"></div>
                    </div>
                </div>
                <div class="group-modal-footer">
                    <button id="leaveGroupBtn" class="btn-warning" onclick="leaveOrDeleteGroup()">🚪 Выйти из группы</button>
                    <button onclick="closeGroupInfoModal()" class="btn-danger">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
	
<!-- Модальное окно звонка -->
<div id="callModal" class="call-modal-overlay">
    <div class="call-modal">
        <div class="call-videos-container">
            <video id="remoteVideo" autoplay playsinline class="remote-video"></video>
            <video id="localVideo" autoplay muted playsinline class="local-video"></video>
        </div>
        <div id="callAvatar" style="display: none; text-align: center;">
            <img id="callerAvatar" class="call-avatar" src="uploads/avatars/default.png">
            <div id="callerName" style="font-size: 20px; margin-top: 8px;"></div>
            <div id="callStatus">Соединение...</div>
        </div>
        
        <!-- Временные кнопки для входящего звонка (показываются только при входящем) -->
        <div id="tempCallControls" class="call-controls" style="display: none;">
            <button id="tempAnswerBtn" class="temp-call-btn active" style="background:#00a884;">📞</button>
            <button id="tempRejectBtn" class="temp-call-btn danger" style="background:#c33;">📞</button>
        </div>
        
        <!-- Постоянные кнопки управления звонком (показываются после ответа) -->
        <div id="permCallControls" class="call-controls" style="display: none;">
            <button id="callMicBtn" class="active" title="Микрофон">🎤</button>
            <button id="callSpeakerBtn" class="active" title="Громкая связь">🔊</button>
            <button id="callVideoBtn" class="" title="Видео">📷</button>
            <button id="callHangupBtn" class="danger" title="Завершить">📞</button>
        </div>
        
        <div style="margin-top: 16px; font-size: 12px;" id="callTimer">00:00</div>
    </div>
</div>
	
	
<script src="socket.io.min.js"></script>
<script>
// ========== ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ ==========
var myPhone = null;
var myUserId = null;
var currentContact = null;
var allChats = [];
var isModalShowing = false;
var lastMessageIds = {};
var isLoadingMessages = false;
var lastIncomingCount = {};


// Переменные для звонков (WebRTC)
var callSettings = { video_calls_enabled: false, audio_calls_enabled: false, ice_servers: [] };
var localStream = null;
var peerConnection = null;
var callStartTime = null;
var callTimerInterval = null;
var currentCallType = null;       // 'audio' or 'video'
var currentCallPeerId = null;     // номер вызываемого/вызвавшего
var isCallActive = false;
var pendingOffer = null;
var pendingIceCandidates = []; // буфер для ICE-кандидатов, пришедших до создания peerConnection
// ========== ТАЙМЕР ЗВОНКА ==========
function startCallTimer() {
    console.log('⏱️ startCallTimer() вызвана');
    // Сбрасываем предыдущий таймер, если он был
    if (callTimerInterval) {
        clearInterval(callTimerInterval);
        callTimerInterval = null;
    }
    if (callStartTime) callStartTime = null;
    
    callStartTime = Date.now();
    callTimerInterval = setInterval(() => {
        if (!callStartTime) return;
        var elapsed = Math.floor((Date.now() - callStartTime) / 1000);
        var mins = Math.floor(elapsed / 60);
        var secs = elapsed % 60;
        var timerStr = mins + ':' + (secs < 10 ? '0' : '') + secs;
        var timerElement = document.getElementById('callTimer');
        if (timerElement) {
            timerElement.innerText = timerStr;
        } else {
            console.error('❌ Элемент #callTimer не найден в DOM');
        }
    }, 1000);
    console.log('⏱️ Таймер запущен');
}





// Переменные для звуков звонков
var ringtoneInterval = null;      // для входящего звонка
var dialtoneInterval = null;      // для исходящих гудков
var callConnectedSoundPlayed = false; // чтобы не повторять звук соединения
var audioCtxForBeep = null;       // AudioContext для звуков



// НОВЫЕ ПЕРЕМЕННЫЕ ДЛЯ ПИНГА И СТАБИЛЬНОСТИ
var wsPingTimer = null;
var wsPongTimeout = null;
var reconnectAttempt = 0;
var maxReconnectAttempts = 10;
var lastUserActivity = Date.now();
var offlineBannerHideTimer = null;

// НОВЫЕ ПЕРЕМЕННЫЕ ДЛЯ НАСТРОЕК НАГРУЗКИ
var loadSettings = {
    chats_poll_interval: 60,
    broadcast_poll_interval: 120,
    messages_poll_interval: 0,
    messages_poll_interval_fallback: 15,
    messaging_mode: 'websocket',
    fallback_mode: true,
    disable_groups: 0,
    ws_url: 'wss://lexchat-websocket.onrender.com'
};

// Таймеры
var chatsIntervalTimer = null;
var broadcastIntervalTimer = null;
var messagesIntervalTimer = null;

// WebSocket переменные
var socket = null;
var wsConnected = false;
var wsFallbackMode = false;
var wsReconnectTimer = null;
var wsReconnectAttempts = 0;
var wsMaxReconnectAttempts = 20;

// Групповые переменные
var currentGroupId = null;
var currentGroupName = null;
var currentGroupAvatar = null;
var currentGroupRole = null;
var currentGroupMembers = [];
var lastGroupMessageIds = {};
var lastMessageKeys = {};
var lastGroupMessageKeys = {};
var lastGroupIncomingCount = {};
var codeTimerInterval = null;

// Звук
var audioCtx = null;
var soundActivated = false;
var lastSoundTime = 0;
var soundDebounce = 800;

// Аудио запись
var mediaRecorder = null, audioChunks = [], recordingStartTime = null, recordingInterval = null, audioStream = null, isRecording = false;
var currentAudio = null;

// Таймеры для долгого нажатия
var pressTimer = null;
var currentMsgIdForPress = null;
var currentElementForPress = null;
var groupMembersToAdd = [];

// ========== УДАЛЕНА СТАТИЧЕСКАЯ КОНФИГУРАЦИЯ window.rtcConfiguration ==========
// Теперь настройки ICE грузятся с сервера через loadCallSettingsFromServer()



// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

// ========== ГЕНЕРАЦИЯ ЗВУКОВ ДЛЯ ЗВОНКОВ (БЕЗ АУДИОФАЙЛОВ) ==========
async function generateBeep(frequency, duration, volume = 0.3) {
    return new Promise((resolve) => {
        try {
            if (!audioCtxForBeep || audioCtxForBeep.state === 'closed') {
                audioCtxForBeep = new (window.AudioContext || window.webkitAudioContext)();
            }
            var ctx = audioCtxForBeep;
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = frequency;
            gain.gain.value = volume;
            osc.start();
            gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration / 1000);
            setTimeout(() => {
                try { osc.stop(); } catch(e) {}
                resolve();
            }, duration);
        } catch(e) {
            console.log('⚠️ Ошибка генерации звука:', e);
            resolve();
        }
    });
}

function startDialtone() {
    stopRingtone();
    if (dialtoneInterval) {
        clearInterval(dialtoneInterval);
        dialtoneInterval = null;
    }
    
    const playOneBeep = async () => {
        await generateBeep(425, 1000, 0.4);
        await new Promise(r => setTimeout(r, 1000));
    };
    
    const loopBeeps = async () => {
        while (dialtoneInterval !== null) {
            await playOneBeep();
        }
    };
    dialtoneInterval = setInterval(() => {}, 1);
    loopBeeps();
    console.log('📞 Исходящие гудки запущены');
}

function stopDialtone() {
    if (dialtoneInterval) {
        clearInterval(dialtoneInterval);
        dialtoneInterval = null;
        console.log('📞 Исходящие гудки остановлены');
    }
}

function startRingtone() {
    stopDialtone();
    if (ringtoneInterval) {
        clearInterval(ringtoneInterval);
        ringtoneInterval = null;
    }
    
    const playRing = async () => {
        await generateBeep(550, 300, 0.35);
        await new Promise(r => setTimeout(r, 200));
        await generateBeep(550, 300, 0.35);
    };
    
    const loopRing = async () => {
        while (ringtoneInterval !== null) {
            await playRing();
            await new Promise(r => setTimeout(r, 2500));
        }
    };
    ringtoneInterval = setInterval(() => {}, 1);
    loopRing();
    console.log('📞 Входящий рингтон запущен');
}

function stopRingtone() {
    if (ringtoneInterval) {
        clearInterval(ringtoneInterval);
        ringtoneInterval = null;
        console.log('📞 Рингтон остановлен');
    }
}

function playConnectedSound() {
    if (callConnectedSoundPlayed) return;
    callConnectedSoundPlayed = true;
    generateBeep(880, 150, 0.4);
    setTimeout(() => {
        generateBeep(880, 150, 0.4);
    }, 200);
    console.log('✅ Звук соединения');
}




function formatPhoneMask(phone) {
    if (!phone) return '';
    var cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 0) return '';
    if (cleaned.length === 11 && cleaned.charAt(0) === '7') cleaned = cleaned.substring(1);
    var formatted = '+7';
    if (cleaned.length > 1) formatted += ' (' + cleaned.substring(1, 4);
    if (cleaned.length >= 4) formatted += ') ' + cleaned.substring(4, 7);
    if (cleaned.length >= 7) formatted += '-' + cleaned.substring(7, 9);
    if (cleaned.length >= 9) formatted += '-' + cleaned.substring(9, 11);
    return formatted;
}

function cleanPhone(phone) {
    return phone.replace(/\D/g, '');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Б';
    var k = 1024, sizes = ['Б', 'КБ', 'МБ', 'ГБ'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function getFileIcon(filename) {
    var ext = filename.split('.').pop().toLowerCase();
    if (['jpg','jpeg','png','gif','webp','bmp'].includes(ext)) return '🖼️';
    if (['pdf'].includes(ext)) return '📄';
    if (['doc','docx'].includes(ext)) return '📝';
    if (['xls','xlsx'].includes(ext)) return '📊';
    if (['ppt','pptx'].includes(ext)) return '📽️';
    if (['txt'].includes(ext)) return '📃';
    if (['zip','rar','7z'].includes(ext)) return '📦';
    if (['mp3','wav','ogg'].includes(ext)) return '🎵';
    return '📎';
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function formatTimerTime(seconds) {
    var mins = Math.floor(seconds / 60);
    var secs = seconds % 60;
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}

// ========== ЗВУК ==========
function playBeep() {
    var now = Date.now();
    if (now - lastSoundTime < soundDebounce) { return; }
    lastSoundTime = now;
    
    try {
        if (!audioCtx || audioCtx.state === 'closed') {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().then(function() { actuallyPlayBeep(); }).catch(function(e) {});
        } else {
            actuallyPlayBeep();
        }
    } catch(e) {
        playFallbackBeep();
    }
}

function actuallyPlayBeep() {
    try {
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        gain.gain.value = 0.35;
        osc.frequency.value = 880;
        osc.start();
        gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.2);
        setTimeout(function() { try { osc.stop(); } catch(e) {} }, 200);
    } catch(e) {
        playFallbackBeep();
    }
}

function playFallbackBeep() {
    var audio = new Audio();
    audio.src = 'data:audio/wav;base64,U3RlYWx0aCBzb3VuZA==';
    audio.volume = 0.35;
    audio.play().catch(function(e) {});
}

function activateSound() {
    if (soundActivated) return;
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    audioCtx.resume().then(function() { soundActivated = true; console.log('🔊 Звук активирован'); }).catch(function(e) {});
}

function playNotificationSound() {
    if (!soundActivated) {
        if (audioCtx && audioCtx.state === 'suspended') { audioCtx.resume().catch(function(e) {}); }
        return;
    }
    if (audioCtx && audioCtx.state === 'suspended') {
        audioCtx.resume().then(function() { playBeep(); }).catch(function(e) {});
    } else {
        playBeep();
    }
}

document.addEventListener('click', activateSound);
document.addEventListener('touchstart', activateSound);

// ========== МАСКИ ВВОДА ==========
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

function searchPhoneMask(input) { phoneMask(input); }

// ========== WEBSOCKET ==========
function connectWebSocket() {
    if (loadSettings.messaging_mode !== 'websocket') return;
    if (socket && socket.connected) return;
    
    var wsUrl = loadSettings.ws_url || 'wss://lexchat-websocket.onrender.com';
    console.log('🔌 Подключение к WebSocket...');
    
    socket = io(wsUrl, {
        transports: ['websocket', 'polling'],
        reconnection: false,
        timeout: 10000
    });
    
    socket.on('connect', function() {
        console.log('✅ WebSocket connected');
        wsConnected = true;
        wsFallbackMode = false;
        wsReconnectAttempts = 0;
        reconnectAttempt = 0;
        hideOfflineBanner();
        
        // ✅ ЗАПУСК ПИНГ-ПОНГА
        if (wsPingTimer) clearInterval(wsPingTimer);
        wsPingTimer = setInterval(function() {
            if (socket && socket.connected) {
                console.log('📡 Sending ping...');
                socket.emit('ping');
                if (wsPongTimeout) clearTimeout(wsPongTimeout);
                wsPongTimeout = setTimeout(function() {
                    console.log('⚠️ No pong response, WebSocket dead');
                    if (socket) socket.disconnect();
                    wsConnected = false;
                    if (loadSettings.fallback_mode) {
                        enableFallbackMode();
                    }
                    reconnectAttempt++;
                    if (reconnectAttempt <= maxReconnectAttempts) {
                        setTimeout(connectWebSocket, 5000);
                    }
                }, 10000);
            }
        }, 25000);
        
        if (myPhone) {
            socket.emit('register', { phone: myPhone, name: localStorage.getItem('chat_name') || myPhone });
        }
        disableFallbackTimers();
    });
    
    socket.on('pong', function() {
        console.log('🏓 Pong received');
        if (wsPongTimeout) clearTimeout(wsPongTimeout);
    });
    
    socket.on('registered', function(data) {
        console.log('📝 Зарегистрирован на WS сервере:', data);
    });
    
    socket.on('new_message', function(data) {
        console.log('📨 Новое сообщение:', data);
        if (data.type === 'private') {
            if (currentContact && currentContact.phone === data.from) {
                loadMessages();
            }
        } else if (data.type === 'group') {
            var groupId = parseInt(data.group_id);
            if (currentGroupId == groupId) {
                loadGroupMessages();
            }
        } else {
            if (data.group_id) {
                if (currentGroupId == data.group_id) loadGroupMessages();
            } else if (data.from) {
                if (currentContact && currentContact.phone === data.from) loadMessages();
            }
        }
        loadChats();
        playNotificationSound();
    });
    
    socket.on('new_group_message', function(data) {
        console.log('👥 Новое групповое сообщение:', data);
        if (currentGroupId == data.group_id) loadGroupMessages();
        loadChats();
        playNotificationSound();
    });
    
    socket.on('delete_message', function(data) {
        console.log('🗑 Удалено сообщение:', data);
        if (currentGroupId) {
            loadGroupMessages();
        } else if (currentContact && currentContact.phone === data.from) {
            loadMessages();
        }
        loadChats();
    });
    
    socket.on('user_typing', function(data) {
        if (currentContact && currentContact.phone === data.from) {
            showTypingIndicator(' печатает...');
            setTimeout(hideTypingIndicator, 2000);
        }
    });
    
    socket.on('group_typing', function(data) {
        if (currentGroupId == data.group_id) {
            showTypingIndicator(data.from_name + ' печатает...');
            setTimeout(hideTypingIndicator, 2000);
        }
    });
    
    socket.on('user_status', function(data) {
        updateUserStatusInUI(data.phone, data.is_online);
    });
    
    socket.on('new_broadcast', function(data) {
        showBroadcastModal(data.broadcast_id, data.message, data.from_phone);
    });
    
    socket.on('new_polling_settings', function(data) {
        loadSettings.chats_poll_interval = data.chats_poll_interval;
        loadSettings.broadcast_poll_interval = data.broadcast_poll_interval;
        loadSettings.messages_poll_interval_fallback = data.messages_poll_interval_fallback;
        loadSettings.disable_groups = data.disable_groups;
        updateGroupButtonVisibility();
    });
    
    socket.on('new_colors', function(data) {
        if (data && data.colors) applyColorsDirect(data.colors);
    });
    
    socket.on('new_avatar', function(data) {
        updateAvatarInUI(data.phone, data.avatar);
    });
    
    socket.on('groups_toggle', function(data) {
        var createBtn = document.getElementById('createGroupBtn');
        if (createBtn) createBtn.style.display = data.enabled ? 'block' : 'none';
        loadSettings.disable_groups = data.enabled ? 0 : 1;
        fetch('api.php?action=get_chats&my_phone=' + encodeURIComponent(myPhone) + '&_t=' + Date.now(), { cache: 'no-store' })
            .then(function(res) { return res.json(); })
            .then(function(chats) {
                if (loadSettings.disable_groups === 1) {
                    chats = chats.filter(function(chat) { return chat.type !== 'group'; });
                }
                renderChats(chats);
            });
        if (!data.enabled && currentGroupId) {
            currentGroupId = null;
            document.getElementById('contactName').innerHTML = 'Выберите чат';
            document.getElementById('messagesContainer').innerHTML = '';
            document.getElementById('groupMenuBtn').style.display = 'none';
        }
    });
    
    socket.on('new_chats', function(data) {
        loadChats();
    });
    
    socket.on('message_sent', function(data) {
        console.log('✅ Сообщение отправлено:', data);
    });
    
    socket.on('disconnect', function(reason) {
        console.log('❌ WebSocket disconnected:', reason);
        wsConnected = false;
        if (loadSettings.fallback_mode) enableFallbackMode();
        if (wsReconnectAttempts < wsMaxReconnectAttempts) {
            wsReconnectAttempts++;
            wsReconnectTimer = setTimeout(connectWebSocket, 5000);
        }
    });
    
    socket.on('connect_error', function(error) {
        console.log('⚠️ WebSocket connection error:', error);
        wsConnected = false;
      //  showOfflineBanner('⚠️ Ошибка подключения к WebSocket, используется резервный режим');
    });
	
	
// ========== СОБЫТИЯ ЗВОНКОВ ==========
socket.on('calls_settings_updated', function(settings) {
    console.log('📞 Настройки звонков обновлены через WS:', settings);
    callSettings = settings;
    // Обновляем конфигурацию ICE
    if (callSettings.ice_servers && Array.isArray(callSettings.ice_servers)) {
        window.rtcConfiguration = { iceServers: callSettings.ice_servers };
    } else {
        window.rtcConfiguration = { iceServers: [] };
    }
    updateCallButtonsUI();
    
    // Если звонки отключены и есть активный звонок – завершаем его
    if (!callSettings.audio_calls_enabled && !callSettings.video_calls_enabled && peerConnection) {
        console.log('📞 Звонки отключены администратором, завершаем текущий звонок');
        endCall();
        alert('Звонки отключены администратором');
    }
});

socket.on('incoming_call', function(data) {
    console.log('📞 [WS] incoming_call получен:', data);
    if (!callSettings.audio_calls_enabled && !callSettings.video_calls_enabled) {
        console.warn('⚠️ Звонки отключены в настройках');
        return;
    }
    currentCallPeerId = data.from;
    currentCallType = data.type;
    
    if (!currentContact) {
        currentContact = { phone: data.from, name: data.from_name, avatar: null };
    }
    
    console.log('📞 [incoming] Установлен currentCallPeerId:', currentCallPeerId, 'currentCallType:', currentCallType);
    
    document.getElementById('callerName').innerText = data.from_name;
    var callerAvatar = document.getElementById('callerAvatar');
    if (callerAvatar) {
        fetch('api.php?action=get_user_info&phone=' + encodeURIComponent(data.from))
            .then(res => res.json())
            .then(info => {
                if (info.success && info.avatar) {
                    callerAvatar.src = info.avatar + '?t=' + Date.now();
                } else {
                    callerAvatar.src = 'uploads/avatars/default.png';
                }
            })
            .catch(() => { callerAvatar.src = 'uploads/avatars/default.png'; });
    }
    
    // Запускаем рингтон
    startRingtone();
    
    // Принудительно сбрасываем видимость панелей перед показом
    var tempControls = document.getElementById('tempCallControls');
    var permControls = document.getElementById('permCallControls');
    if (tempControls) tempControls.style.display = 'flex';
    if (permControls) permControls.style.display = 'none';
    
    showCallModal(false);
    
    // Создаём временные кнопки заново
    if (tempControls) {
        tempControls.innerHTML = '';
        var answerBtn = document.createElement('button');
        answerBtn.id = 'tempAnswerBtn';
        answerBtn.className = 'temp-call-btn active';
        answerBtn.innerHTML = '📞';
        answerBtn.title = 'Ответить';
        answerBtn.style.background = '#00a884';
        answerBtn.style.color = 'white';
        answerBtn.onclick = function() {
            console.log('🔘 Ответить');
            acceptCall();
        };
        
        var rejectBtn = document.createElement('button');
        rejectBtn.id = 'tempRejectBtn';
        rejectBtn.className = 'temp-call-btn danger';
        rejectBtn.innerHTML = '📞';
        rejectBtn.title = 'Отклонить';
        rejectBtn.style.background = '#c33';
        rejectBtn.style.color = 'white';
        rejectBtn.onclick = function() {
            console.log('🔘 Отклонить');
            endCall();
        };
        
        tempControls.appendChild(answerBtn);
        tempControls.appendChild(rejectBtn);
        tempControls.style.display = 'flex';
    }
    
    console.log('📞 [incoming] Кнопки ответа/отклонения готовы, рингтон запущен');
});

// ========== ОБРАБОТКА ПРЕДЛОЖЕНИЯ ЗВОНКА ==========
socket.on('call_offer', function(data) {
    console.log('📞 [WS] call_offer получен от', data.from);
    pendingOffer = data;
    console.log('📞 [WS] pendingOffer сохранён:', pendingOffer ? 'да' : 'нет');
});

socket.on('call_answer', function(data) {
    if (peerConnection) peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
});



socket.on('call_ice', function(data) {
    console.log('📞 [WS] Получен ICE кандидат от', data.to || data.from, 'кандидат:', data.candidate ? data.candidate.candidate : 'null');
    if (peerConnection) {
        peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate))
            .then(() => console.log('✅ ICE кандидат добавлен'))
            .catch(e => console.error('❌ Ошибка добавления ICE кандидата:', e));
    } else {
        if (data.candidate) {
            console.log('📦 Сохраняем ICE кандидат в буфер, так как peerConnection ещё нет');
            pendingIceCandidates.push(data.candidate);
        }
    }
});

socket.on('call_hangup', function() {
	  console.log('📞 Получен call_hangup от удалённой стороны');
    endCall();
});	



socket.on('call_toggle_video', function(data) {
    console.log('📹 Собеседник переключил видео:', data.enabled);
    // Здесь можно добавить логику для отображения иконки у собеседника
    // (например, показать/скрыть индикатор видео у удалённого пользователя)
});

socket.on('call_start_video', function(data) {
    console.log('📹 Собеседник включил видео');
    // Собеседник добавил видео-трек, он автоматически придёт через ontrack
    // Ничего дополнительно делать не нужно, ontrack сработает сам
});
	
}

function enableFallbackMode() {
    if (wsFallbackMode) return;
    wsFallbackMode = true;
    console.log('⚠️ Включен аварийный режим (polling)');
   // showOfflineBanner('⚠️ WebSocket отключён, используется резервный режим');
    
    if (chatsIntervalTimer) clearInterval(chatsIntervalTimer);
    if (broadcastIntervalTimer) clearInterval(broadcastIntervalTimer);
    if (messagesIntervalTimer) clearInterval(messagesIntervalTimer);
    
    if (loadSettings.chats_poll_interval > 0) {
        chatsIntervalTimer = setInterval(function() { loadChats(); }, loadSettings.chats_poll_interval * 1000);
    }
    if (loadSettings.broadcast_poll_interval > 0) {
        broadcastIntervalTimer = setInterval(function() { checkBroadcastMessage(); }, loadSettings.broadcast_poll_interval * 1000);
    }
    if (loadSettings.messages_poll_interval_fallback > 0) {
        messagesIntervalTimer = setInterval(function() {
            if (currentContact && !isLoadingMessages) loadMessages();
            if (currentGroupId) loadGroupMessages();
        }, loadSettings.messages_poll_interval_fallback * 1000);
    }
}

function disableFallbackTimers() {
    wsFallbackMode = false;
    if (chatsIntervalTimer) { clearInterval(chatsIntervalTimer); chatsIntervalTimer = null; }
    if (broadcastIntervalTimer) { clearInterval(broadcastIntervalTimer); broadcastIntervalTimer = null; }
    if (messagesIntervalTimer) { clearInterval(messagesIntervalTimer); messagesIntervalTimer = null; }
}

function sendMessageViaWS(type, data) {
    if (wsConnected && socket) {
        socket.emit(type, data);
        return true;
    }
    return false;
}


function showTypingIndicator(text) {
    console.log('🔧 showTypingIndicator вызвана с текстом:', text);
    
    // Находим элемент с именем в шапке чата
    var chatHeaderName = document.getElementById('contactName');
    if (!chatHeaderName) return;
    
    // Удаляем старый индикатор если есть
    var existing = document.getElementById('typingIndicator');
    if (existing) {
        existing.remove();
    }
    
    // Создаём индикатор
    var indicator = document.createElement('span');
    indicator.id = 'typingIndicator';
    indicator.style.cssText = 'font-size:11px; color:#8696a0; margin-left:8px; font-weight:normal;';
    indicator.innerText = '✏️ ' + text;
    
    // Добавляем индикатор после имени
    chatHeaderName.appendChild(indicator);
}

function hideTypingIndicator() {
    var indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.remove();
    }
}


function updateUserStatusInUI(phone, isOnline) {
    var chatItem = document.querySelector('.contact-item[data-chat-id="' + phone + '"]');
    if (chatItem) {
        var onlineDot = chatItem.querySelector('.online-dot');
        if (isOnline && !onlineDot) {
            var nameDiv = chatItem.querySelector('.contact-name');
            if (nameDiv) {
                var dot = document.createElement('span');
                dot.className = 'online-dot';
                nameDiv.appendChild(dot);
            }
        } else if (!isOnline && onlineDot) {
            onlineDot.remove();
        }
    }
    if (currentContact && currentContact.phone === phone) {
        var headerType = document.getElementById('chatHeaderType');
        if (headerType) headerType.innerHTML = isOnline ? '🟢 в сети' : '⚫ не в сети';
    }
}

function applyColorsDirect(colors) {
    console.log('🎨 Applying colors directly:', colors);
    var theme = localStorage.getItem('theme') || 'dark';
    
    if (theme === 'dark') {
        if (colors.dark_bg) document.querySelector('.chat-area').style.background = colors.dark_bg;
        if (colors.dark_sidebar_bg) document.querySelector('.sidebar').style.background = colors.dark_sidebar_bg;
        if (colors.dark_header_bg) document.querySelectorAll('.sidebar-header, .chat-header').forEach(function(h) { h.style.background = colors.dark_header_bg; });
        if (colors.dark_text) document.querySelectorAll('.contact-name, #myName, .chat-header-name').forEach(function(t) { t.style.color = colors.dark_text; });
        if (colors.dark_message_in_bg) document.querySelectorAll('.message.in').forEach(function(m) { m.style.background = colors.dark_message_in_bg; });
        if (colors.dark_message_out_bg) document.querySelectorAll('.message.out').forEach(function(m) { m.style.background = colors.dark_message_out_bg; });
        if (colors.dark_input_bg) document.querySelectorAll('.input-area textarea, .attach-button, .mic-button').forEach(function(i) { i.style.background = colors.dark_input_bg; });
    } else {
        if (colors.light_bg) document.querySelector('.chat-area').style.background = colors.light_bg;
        if (colors.light_sidebar_bg) document.querySelector('.sidebar').style.background = colors.light_sidebar_bg;
        if (colors.light_header_bg) document.querySelectorAll('.sidebar-header, .chat-header').forEach(function(h) { h.style.background = colors.light_header_bg; });
        if (colors.light_text) document.querySelectorAll('.contact-name, #myName, .chat-header-name').forEach(function(t) { t.style.color = colors.light_text; });
        if (colors.light_message_in_bg) document.querySelectorAll('.message.in').forEach(function(m) { m.style.background = colors.light_message_in_bg; });
        if (colors.light_message_out_bg) document.querySelectorAll('.message.out').forEach(function(m) { m.style.background = colors.light_message_out_bg; });
        if (colors.light_input_bg) document.querySelectorAll('.input-area textarea, .attach-button, .mic-button').forEach(function(i) { i.style.background = colors.light_input_bg; });
    }
    
    // ФОН - ПРИМЕНЯЕМ С ПРИНУДИТЕЛЬНЫМ ОБНОВЛЕНИЕМ
var showBg = localStorage.getItem('show_chat_bg') !== 'false';
var messagesContainer = document.getElementById('messagesContainer');

if (showBg && messagesContainer && colors.chat_background) {
    var bgImage = colors.chat_background;
    // УНИЧТОЖАЕМ КЭШ - уникальный параметр + удаляем старый стиль
    var uniqueParam = Date.now() + '_' + Math.random().toString(36).substr(2, 5);
    var newUrl = bgImage + '?v=' + uniqueParam;
    
    console.log('🖼️ Устанавливаем фон:', newUrl);
    
    // Полностью сбрасываем background
    messagesContainer.style.backgroundImage = '';
    
    // Используем requestAnimationFrame для принудительной перерисовки
    requestAnimationFrame(function() {
        messagesContainer.style.backgroundImage = "url('" + newUrl + "')";
        messagesContainer.style.backgroundSize = "cover";
        messagesContainer.style.backgroundPosition = "center";
        messagesContainer.style.backgroundRepeat = "no-repeat";
        
        // Принудительная перерисовка элемента
        void messagesContainer.offsetHeight;
    });
}
}

function updateAvatarInUI(phone, avatar) {
    var avatarImg = document.querySelector('.contact-item[data-chat-id="' + phone + '"] .contact-avatar');
    if (avatarImg) avatarImg.src = avatar + '?t=' + Date.now();
    if (currentContact && currentContact.phone === phone) {
        var headerAvatar = document.getElementById('chatHeaderAvatar');
        if (headerAvatar) headerAvatar.src = avatar + '?t=' + Date.now();
    }
}

function updateGroupButtonVisibility() {
    var createBtn = document.getElementById('createGroupBtn');
    if (createBtn) createBtn.style.display = (loadSettings.disable_groups === 1) ? 'none' : 'block';
}

// ========== АУДИОЗАПИСЬ ==========
function formatTime(seconds) {
    var mins = Math.floor(seconds / 60);
    var secs = seconds % 60;
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}

function updateRecordingTimer() {
    if (recordingStartTime) document.getElementById('recordingTime').innerText = formatTime(Math.floor((Date.now() - recordingStartTime) / 1000));
}


// ========== НАСТРОЙКИ ЗВОНКОВ (ЗАГРУЗКА С СЕРВЕРА) ==========
function loadCallSettingsFromServer() {
    console.log('📞 Загрузка настроек звонков с сервера...');
    fetch('admin.php?action=get_call_settings&t=' + Date.now(), { cache: 'no-store' })
        .then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(settings => {
            console.log('Raw settings from server:', settings);
            callSettings = {
                video_calls_enabled: !!settings.video_calls_enabled,
                audio_calls_enabled: !!settings.audio_calls_enabled,
                ice_servers: Array.isArray(settings.ice_servers) ? settings.ice_servers : []
            };
            window.rtcConfiguration = { iceServers: callSettings.ice_servers };
            console.log('Parsed callSettings:', callSettings);
            updateCallButtonsUI();
        })
        .catch(e => {
            console.error('❌ Ошибка загрузки настроек звонков, звонки будут отключены', e);
            callSettings = { video_calls_enabled: false, audio_calls_enabled: false, ice_servers: [] };
            window.rtcConfiguration = { iceServers: [] };
            updateCallButtonsUI();
        });
}

function updateCallButtonsUI() {
    var menuBtn = document.getElementById('groupMenuBtn');
    var header = document.querySelector('.chat-header');
    if (!header) return;
    var existing = document.querySelector('.call-icons-wrapper');
    if (existing) existing.remove();
    if (!currentContact || !currentContact.phone) return;
    if (!callSettings.audio_calls_enabled && !callSettings.video_calls_enabled) return;
    
    var wrapper = document.createElement('div');
    wrapper.className = 'call-icons-wrapper';
    wrapper.style.cssText = 'display:flex; gap:8px; margin-left:auto; order:2;';
    
    // Кнопка аудиозвонка (всегда показываем, если включено)
    if (callSettings.audio_calls_enabled) {
        var audioBtn = document.createElement('button');
        audioBtn.className = 'call-icon menu-button';
        audioBtn.innerHTML = '📞';
        audioBtn.title = 'Аудиозвонок';
        audioBtn.style.cssText = 'background:none; border:none; font-size:22px; cursor:pointer; color:#00a884; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center;';
        audioBtn.onclick = (e) => { e.stopPropagation(); startCall('audio'); };
        wrapper.appendChild(audioBtn);
    }
    
    // Кнопка видеозвонка – НЕ показываем для аудио, только если это отдельная кнопка вызова
    // (она нужна только для инициирования видеозвонка, не во время аудио)
    if (callSettings.video_calls_enabled) {
        var videoBtn = document.createElement('button');
        videoBtn.className = 'call-icon menu-button';
        videoBtn.innerHTML = '🎥';
        videoBtn.title = 'Видеозвонок';
        videoBtn.style.cssText = 'background:none; border:none; font-size:22px; cursor:pointer; color:#00a884; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center;';
        videoBtn.onclick = (e) => { e.stopPropagation(); startCall('video'); };
        wrapper.appendChild(videoBtn);
    }
    
    if (menuBtn && menuBtn.parentNode) menuBtn.parentNode.insertBefore(wrapper, menuBtn);
    else header.appendChild(wrapper);
}


function showCallModal(isOutgoing) {
    var modal = document.getElementById('callModal');
    if (!modal) return;
    
    var callVideosContainer = document.querySelector('.call-videos-container');
    var callAvatar = document.getElementById('callAvatar');
    var callStatus = document.getElementById('callStatus');
    var callTimer = document.getElementById('callTimer');
    var callerNameElem = document.getElementById('callerName');
    var callerAvatarImg = document.getElementById('callerAvatar');
    
    var tempControls = document.getElementById('tempCallControls');
    var permControls = document.getElementById('permCallControls');
    var callVideoBtn = document.getElementById('callVideoBtn'); // кнопка видео
    
    if (callTimer) callTimer.innerText = '00:00';
    
    if (currentCallPeerId) {
        if (callerNameElem) {
            if (currentContact && currentContact.name) {
                callerNameElem.innerText = currentContact.name;
            } else if (currentGroupId) {
                callerNameElem.innerText = currentGroupName || 'Групповой звонок';
            } else {
                callerNameElem.innerText = formatPhoneMask(currentCallPeerId);
            }
        }
        if (callerAvatarImg && currentContact && currentContact.avatar) {
            callerAvatarImg.src = currentContact.avatar + '?t=' + Date.now();
        } else if (callerAvatarImg) {
            callerAvatarImg.src = 'uploads/avatars/default.png';
        }
    }
    
    if (callStatus) {
        callStatus.innerHTML = isOutgoing ? 'Звоним...' : 'Входящий звонок...';
    }
    
    // Управление видимостью кнопок
    if (isOutgoing) {
        if (tempControls) tempControls.style.display = 'none';
        if (permControls) permControls.style.display = 'flex';
    } else {
        if (tempControls) tempControls.style.display = 'flex';
        if (permControls) permControls.style.display = 'none';
    }
    
    // ✅ Управляем видимостью кнопки ВИДЕО
    if (currentCallType === 'video') {
        if (callVideoBtn) callVideoBtn.style.display = 'flex';
    } else {
        if (callVideoBtn) callVideoBtn.style.display = 'none';
    }
    
    // Для аудиозвонков – показываем аватар, скрываем видео
    if (currentCallType === 'audio') {
        if (callVideosContainer) callVideosContainer.style.display = 'none';
        if (callAvatar) callAvatar.style.display = 'block';
        var remoteVideo = document.getElementById('remoteVideo');
        var localVideo = document.getElementById('localVideo');
        if (remoteVideo) remoteVideo.style.display = 'none';
        if (localVideo) localVideo.style.display = 'none';
    } else if (currentCallType === 'video') {
        if (callVideosContainer) callVideosContainer.style.display = 'block';
        if (callAvatar) callAvatar.style.display = 'none';
        var remoteVideo = document.getElementById('remoteVideo');
        var localVideo = document.getElementById('localVideo');
        if (remoteVideo) remoteVideo.style.display = 'block';
        if (localVideo) localVideo.style.display = 'block';
    } else {
        if (callVideosContainer) callVideosContainer.style.display = 'none';
        if (callAvatar) callAvatar.style.display = 'block';
    }
    
    modal.classList.add('open');
}

async function startCall(type) {
    console.log('📞 startCall() вызвана, тип:', type);
    if (!currentContact || !currentContact.phone) {
        console.error('❌ Нет выбранного контакта');
        return;
    }
    if ((type === 'audio' && !callSettings.audio_calls_enabled) || (type === 'video' && !callSettings.video_calls_enabled)) {
        alert('Звонки этого типа отключены администратором');
        return;
    }
    if (!callSettings.ice_servers || callSettings.ice_servers.length === 0) {
        console.error('❌ Нет настроек ICE серверов');
        alert('Звонки временно недоступны (нет ICE серверов)');
        return;
    }
    
    // Сбрасываем флаг звука соединения
    callConnectedSoundPlayed = false;
    
    currentCallType = type;
    currentCallPeerId = currentContact.phone;
    var constraints = { audio: true, video: (type === 'video') };
    console.log('📞 startCall: запрос медиа constraints:', constraints);
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        console.log('✅ startCall: медиа получены, треки:', stream.getTracks().map(t => t.kind));
        localStream = stream;
        document.getElementById('localVideo').srcObject = stream;
        
        var tempControls = document.getElementById('tempCallControls');
        var permControls = document.getElementById('permCallControls');
        if (tempControls) tempControls.style.display = 'none';
        if (permControls) permControls.style.display = 'flex';
        
        var callVideoBtn = document.getElementById('callVideoBtn');
        if (callVideoBtn) {
            callVideoBtn.style.display = (type === 'video') ? 'flex' : 'none';
        }
        
        const iceConfig = { iceServers: callSettings.ice_servers };
        console.log('📞 startCall: создаём PeerConnection с ICE:', iceConfig);
        peerConnection = new RTCPeerConnection(iceConfig);
        
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
            console.log('📞 startCall: добавлен трек', track.kind);
        });
        
        peerConnection.oniceconnectionstatechange = () => {
            console.log('🔄 [startCall] ICE connection state:', peerConnection.iceConnectionState);
            if (peerConnection.iceConnectionState === 'connected') {
                console.log('✅ [startCall] ICE соединение установлено!');
                stopDialtone();
                playConnectedSound();
                document.getElementById('callStatus').innerHTML = 'Соединено';
            } else if (peerConnection.iceConnectionState === 'failed') {
                console.error('❌ [startCall] ICE failed');
                alert('Не удалось установить соединение (ICE failed)');
                endCall();
            } else if (peerConnection.iceConnectionState === 'disconnected') {
                console.warn('⚠️ [startCall] ICE соединение разорвано');
            }
        };
        peerConnection.onicegatheringstatechange = () => {
            console.log('🔄 [startCall] ICE gathering state:', peerConnection.iceGatheringState);
        };
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                console.log('📡 [startCall] Новый ICE кандидат:', event.candidate.candidate, 'тип:', event.candidate.type);
                if (wsConnected && socket) {
                    socket.emit('call_ice', { to: currentCallPeerId, candidate: event.candidate });
                }
            } else {
                console.log('📡 [startCall] Сбор ICE кандидатов завершён');
            }
        };
        peerConnection.onicecandidateerror = (event) => {
            console.error('❌ [startCall] Ошибка ICE кандидата:', event.errorCode, event.errorText, event.url);
        };
        
        peerConnection.ontrack = (event) => {
            console.log('📹 [startCall] ontrack, потоков:', event.streams.length);
            if (event.streams && event.streams[0]) {
                const remoteStream = event.streams[0];
                console.log('📹 [startCall] удалённый поток, треки:', remoteStream.getTracks().map(t => t.kind));
                document.getElementById('remoteVideo').srcObject = remoteStream;
                startCallTimer();
            } else {
                console.error('❌ [startCall] ontrack не получил поток');
            }
        };
        
        const offer = await peerConnection.createOffer();
        console.log('📞 [startCall] Offer создан:', offer);
        await peerConnection.setLocalDescription(offer);
        console.log('📞 [startCall] LocalDescription установлен');
        
        if (wsConnected && socket) {
            socket.emit('call_start', { to: currentCallPeerId, type: currentCallType });
            socket.emit('call_offer', { to: currentCallPeerId, offer: peerConnection.localDescription });
            console.log('📤 [startCall] Offer и call_start отправлены');
        } else {
            console.error('❌ [startCall] WebSocket не подключён');
            endCall();
            return;
        }
        
        showCallModal(true);
        document.getElementById('callStatus').innerHTML = 'Звоним...';
        startDialtone(); // Запускаем гудки
    } catch (err) {
        console.error('❌ [startCall] Ошибка:', err);
        alert('Не удалось получить доступ к микрофону/камере: ' + err.message);
        endCall();
    }
}


async function acceptCall() {
    console.log('📞 acceptCall() вызвана');
    if (!pendingOffer) {
        console.error('❌ Нет ожидающего звонка (pendingOffer = null)');
        return;
    }
    console.log('📞 acceptCall: pendingOffer существует, from:', pendingOffer.from);
    
    // Останавливаем рингтон, так как ответили
    stopRingtone();
    callConnectedSoundPlayed = false;
    
    if (!currentCallPeerId) {
        currentCallPeerId = pendingOffer.from;
    }
    if (!currentCallType) {
        currentCallType = 'video';
    }
    console.log('📞 acceptCall: currentCallPeerId =', currentCallPeerId, 'currentCallType =', currentCallType);
    
    if ((currentCallType === 'audio' && !callSettings.audio_calls_enabled) ||
        (currentCallType === 'video' && !callSettings.video_calls_enabled)) {
        alert('Звонки этого типа отключены администратором');
        endCall();
        return;
    }
    if (!callSettings.ice_servers || callSettings.ice_servers.length === 0) {
        console.error('❌ Нет настроек ICE серверов');
        alert('Звонки временно недоступны (нет ICE серверов)');
        endCall();
        return;
    }
    
    const constraints = { audio: true, video: (currentCallType === 'video') };
    console.log('📞 acceptCall: запрос медиа с constraints:', constraints);
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        console.log('✅ acceptCall: медиа получены, треки:', stream.getTracks().map(t => t.kind));
        localStream = stream;
        document.getElementById('localVideo').srcObject = stream;
        
        var tempControls = document.getElementById('tempCallControls');
        var permControls = document.getElementById('permCallControls');
        if (tempControls) tempControls.style.display = 'none';
        if (permControls) permControls.style.display = 'flex';
        
        var callVideoBtn = document.getElementById('callVideoBtn');
        if (callVideoBtn) {
            callVideoBtn.style.display = (currentCallType === 'video') ? 'flex' : 'none';
        }
        
        const controls = document.querySelector('.call-controls');
        const tempBtns = controls.querySelectorAll('.temp-call-btn');
        tempBtns.forEach(btn => btn.remove());
        
        const iceConfig = { iceServers: callSettings.ice_servers };
        console.log('📞 acceptCall: создаём PeerConnection с ICE:', iceConfig);
        peerConnection = new RTCPeerConnection(iceConfig);
        
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
            console.log('📞 acceptCall: добавлен трек', track.kind);
        });
        
        peerConnection.oniceconnectionstatechange = () => {
            console.log('🔄 [acceptCall] ICE connection state:', peerConnection.iceConnectionState);
            if (peerConnection.iceConnectionState === 'connected') {
                console.log('✅ [acceptCall] ICE соединение установлено!');
                playConnectedSound();
                document.getElementById('callStatus').innerHTML = 'Соединено';
            } else if (peerConnection.iceConnectionState === 'failed') {
                console.error('❌ [acceptCall] ICE failed');
                alert('Не удалось установить соединение (ICE failed)');
                endCall();
            }
        };
        peerConnection.onicegatheringstatechange = () => {
            console.log('🔄 [acceptCall] ICE gathering state:', peerConnection.iceGatheringState);
        };
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                console.log('📡 [acceptCall] Новый ICE кандидат:', event.candidate.candidate, 'тип:', event.candidate.type);
                if (wsConnected && socket) {
                    socket.emit('call_ice', { to: currentCallPeerId, candidate: event.candidate });
                }
            }
        };
        peerConnection.onicecandidateerror = (event) => {
            console.error('❌ [acceptCall] Ошибка ICE кандидата:', event.errorCode, event.errorText, event.url);
        };
        
        peerConnection.ontrack = (event) => {
            console.log('📹 [acceptCall] ontrack, потоков:', event.streams.length);
            if (event.streams && event.streams[0]) {
                const remoteStream = event.streams[0];
                console.log('📹 [acceptCall] удалённый поток, треки:', remoteStream.getTracks().map(t => t.kind));
                document.getElementById('remoteVideo').srcObject = remoteStream;
                startCallTimer();
            }
        };
        
        console.log('📞 [acceptCall] Устанавливаем remoteDescription из pendingOffer.offer');
        await peerConnection.setRemoteDescription(new RTCSessionDescription(pendingOffer.offer));
        console.log('✅ [acceptCall] setRemoteDescription успешно');
        
        if (pendingIceCandidates.length > 0) {
            console.log('📦 Добавляем отложенные ICE-кандидаты, количество:', pendingIceCandidates.length);
            for (let candidate of pendingIceCandidates) {
                try {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                    console.log('✅ Отложенный ICE кандидат добавлен');
                } catch (e) {
                    console.error('❌ Ошибка добавления отложенного ICE кандидата:', e);
                }
            }
            pendingIceCandidates = [];
        }
        
        const answer = await peerConnection.createAnswer();
        console.log('✅ [acceptCall] createAnswer получен:', answer);
        await peerConnection.setLocalDescription(answer);
        console.log('✅ [acceptCall] setLocalDescription успешно');
        
        socket.emit('call_answer', { to: currentCallPeerId, answer: peerConnection.localDescription });
        console.log('📤 [acceptCall] Answer отправлен');
        
        document.getElementById('callStatus').innerHTML = 'Соединение...';
        pendingOffer = null;
    } catch (err) {
        console.error('❌ [acceptCall] Ошибка:', err);
        alert('Не удалось получить доступ к медиа: ' + err.message);
        endCall();
    }
}

function endCall() {
    console.trace('📞endCall вызвана');
    
    // Останавливаем все звуки
    stopRingtone();
    stopDialtone();
    callConnectedSoundPlayed = false;
    
    if (wsConnected && socket && currentCallPeerId) {
        socket.emit('call_hangup', { to: currentCallPeerId });
        console.log('📤 Отправлен call_hangup');
    }
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
        console.log('🔇 Локальный поток остановлен');
    }
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
        console.log('🔌 PeerConnection закрыт');
    }
    if (callTimerInterval) {
        clearInterval(callTimerInterval);
        callTimerInterval = null;
    }
    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');
    if (localVideo) localVideo.srcObject = null;
    if (remoteVideo) remoteVideo.srcObject = null;
    const modal = document.getElementById('callModal');
    if (modal) modal.classList.remove('open');
    currentCallPeerId = null;
    currentCallType = null;
    
    var tempControls = document.getElementById('tempCallControls');
    var permControls = document.getElementById('permCallControls');
    if (tempControls) tempControls.style.display = 'none';
    if (permControls) permControls.style.display = 'none';
    
    var remoteVideoElem = document.getElementById('remoteVideo');
    var localVideoElem = document.getElementById('localVideo');
    if (remoteVideoElem) remoteVideoElem.style.display = 'block';
    if (localVideoElem) localVideoElem.style.display = 'block';
    
    pendingIceCandidates = [];
    pendingOffer = null;
    
    console.log('📞 Звонок завершён');
}

function toggleMicrophone() {
    if (localStream) {
        var track = localStream.getAudioTracks()[0];
        if (track) {
            track.enabled = !track.enabled;
            var btn = document.getElementById('callMicBtn');
            btn.classList.toggle('active');
            btn.style.background = track.enabled ? '#00a884' : '#888';
        }
    }
}
function toggleCamera() {
    var videoBtn = document.getElementById('callVideoBtn');
    
    // Если уже есть видео-трек
    if (localStream && localStream.getVideoTracks().length > 0) {
        var videoTrack = localStream.getVideoTracks()[0];
        videoTrack.enabled = !videoTrack.enabled;
        videoBtn.classList.toggle('active');
        videoBtn.style.background = videoTrack.enabled ? '#00a884' : '#888';
        
        // Отправляем сигнал собеседнику о переключении видео
        if (wsConnected && socket && currentCallPeerId) {
            socket.emit('call_toggle_video', { to: currentCallPeerId, enabled: videoTrack.enabled });
        }
        return;
    }
    
    // Видео-трека нет (аудиозвонок) – добавляем камеру
    if (!localStream) {
        console.error('Нет локального потока');
        return;
    }
    
    // Запрашиваем доступ к камере
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(videoStream) {
            // Получаем видео-трек из нового потока
            var videoTrack = videoStream.getVideoTracks()[0];
            
            // Добавляем видео-трек в существующий peerConnection
            if (peerConnection) {
                peerConnection.addTrack(videoTrack, localStream);
            }
            
            // Добавляем видео-трек в локальный поток
            localStream.addTrack(videoTrack);
            
            // Обновляем видео-элемент (показываем свою камеру)
            var localVideo = document.getElementById('localVideo');
            if (localVideo) {
                localVideo.srcObject = localStream;
                localVideo.style.display = 'block';
            }
            
            // Показываем видео-контейнер, скрываем аватар
            var callVideosContainer = document.querySelector('.call-videos-container');
            var callAvatar = document.getElementById('callAvatar');
            if (callVideosContainer) callVideosContainer.style.display = 'block';
            if (callAvatar) callAvatar.style.display = 'none';
            
            // Меняем тип звонка на video (для UI)
            currentCallType = 'video';
            
            // Обновляем кнопку
            videoBtn.classList.add('active');
            videoBtn.style.background = '#00a884';
            
            // Отправляем сигнал собеседнику, что мы включили видео
            if (wsConnected && socket && currentCallPeerId) {
                socket.emit('call_start_video', { to: currentCallPeerId });
            }
            
            console.log('✅ Видео включено во время аудиозвонка');
        })
        .catch(function(err) {
            console.error('❌ Ошибка доступа к камере:', err);
            alert('Не удалось получить доступ к камере: ' + err.message);
        });
}



function toggleSpeaker() {
    var speakerBtn = document.getElementById('callSpeakerBtn');
    var remoteVideo = document.getElementById('remoteVideo');
    var isSpeakerOn = speakerBtn.classList.contains('active');
    
    if (isSpeakerOn) {
        // Выключаем громкую связь
        speakerBtn.classList.remove('active');
        speakerBtn.style.background = '#e9edef';
        speakerBtn.style.color = '#111b21';
        
        // Способ 1: Для iOS Safari
        if (remoteVideo && remoteVideo.webkitSupportsSessionPlayback) {
            remoteVideo.webkitSetPresentationMode('inline');
        }
        
        // Способ 2: Для Android / Chrome – пересоздаём аудио-трек
        if (remoteVideo && remoteVideo.srcObject) {
            var audioTracks = remoteVideo.srcObject.getAudioTracks();
            if (audioTracks.length > 0) {
                audioTracks.forEach(track => {
                    track.enabled = false;
                    setTimeout(() => { track.enabled = true; }, 100);
                });
            }
        }
        
        // Способ 3: Убираем атрибут playsinline
        if (remoteVideo) {
            remoteVideo.removeAttribute('playsinline');
        }
        
        console.log('🔊 Громкая связь выключена (обычный динамик)');
    } else {
        // Включаем громкую связь
        speakerBtn.classList.add('active');
        speakerBtn.style.background = '#00a884';
        speakerBtn.style.color = 'white';
        
        // Способ 1: Для iOS Safari
        if (remoteVideo && remoteVideo.webkitSupportsSessionPlayback) {
            remoteVideo.webkitSetPresentationMode('speaker');
        }
        
        // Способ 2: Для Android / Chrome
        if (remoteVideo && remoteVideo.srcObject) {
            // Создаём новый AudioContext и направляем звук на динамик
            if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
                var AudioCtx = window.AudioContext || window.webkitAudioContext;
                var audioContext = new AudioCtx();
                var source = audioContext.createMediaStreamSource(remoteVideo.srcObject);
                source.connect(audioContext.destination);
                audioContext.resume();
                console.log('🎵 Звук направлен на динамик');
            }
        }
        
        // Способ 3: Добавляем атрибут playsinline
        if (remoteVideo) {
            remoteVideo.setAttribute('playsinline', '');
        }
        
        console.log('🔊 Громкая связь включена');
    }
}


function startRecording() {
    if (isRecording) return;
    if (!currentContact && !currentGroupId) { alert('Выберите чат'); return; }
    var micBtn = document.getElementById('micButton');
    micBtn.classList.add('recording');
    document.getElementById('recordingTimer').style.display = 'flex';
    recordingStartTime = Date.now();
    updateRecordingTimer();
    recordingInterval = setInterval(updateRecordingTimer, 1000);
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function(stream) {
            audioStream = stream;
            mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
            audioChunks = [];
            mediaRecorder.ondataavailable = function(event) { if (event.data.size > 0) audioChunks.push(event.data); };
            mediaRecorder.onstop = function() {
                var audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                var duration = Math.floor((Date.now() - recordingStartTime) / 1000);
                if (currentGroupId) {
                    sendGroupAudio(audioBlob, duration);
                } else if (currentContact) {
                    sendAudio(audioBlob, duration);
                }
                if (audioStream) audioStream.getTracks().forEach(function(track) { track.stop(); });
                recordingStartTime = null;
                clearInterval(recordingInterval);
                document.getElementById('recordingTimer').style.display = 'none';
                micBtn.classList.remove('recording');
                isRecording = false;
            };
            mediaRecorder.start();
            isRecording = true;
        })
        .catch(function(err) {
            clearInterval(recordingInterval);
            document.getElementById('recordingTimer').style.display = 'none';
            micBtn.classList.remove('recording');
            isRecording = false;
            alert('Не удалось получить доступ к микрофону');
        });
}

function stopRecording() {
    if (!isRecording) return;
    if (mediaRecorder && mediaRecorder.state === 'recording') mediaRecorder.stop();
}

function sendAudio(audioBlob, duration) {
    if (!currentContact || !myPhone) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var base64 = e.target.result.split(',')[1];
        var params = new URLSearchParams();
        params.append('from', myPhone);
        params.append('to', currentContact.phone);
        params.append('audio_base64', base64);
        params.append('duration', duration);
        fetch('api.php?action=upload_audio_base64', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        }).then(function(res) { return res.json(); }).then(function(data) {
            if (data.success) { 
                loadChats(); 
                loadMessages();
                
                // ОТПРАВКА WS УВЕДОМЛЕНИЯ
                if (wsConnected && socket) {
                    socket.emit('send_message', {
                        from: myPhone,
                        to: currentContact.phone,
                        text: null,
                        msg_id: Date.now(),
                        time: Math.floor(Date.now() / 1000),
                        data: { audio: true, duration: duration }
                    });
                }
            } else { 
                alert('Ошибка отправки аудио'); 
            }
        }).catch(function() { alert('Ошибка сети'); });
    };
    reader.readAsDataURL(audioBlob);
}

function sendGroupAudio(audioBlob, duration) {
    if (!currentGroupId || !myPhone) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var base64 = e.target.result.split(',')[1];
        var params = new URLSearchParams();
        params.append('group_id', currentGroupId);
        params.append('from', myPhone);
        params.append('audio_base64', base64);
        params.append('duration', duration);
        fetch('api.php?action=upload_group_audio_base64', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        }).then(function(res) { return res.json(); }).then(function(data) {
            if (data.success) { 
                loadGroupMessages(); 
                loadChats();
                
                // ОТПРАВКА WS УВЕДОМЛЕНИЯ
                if (wsConnected && socket) {
                    socket.emit('send_group_message', {
                        from: myPhone,
                        group_id: currentGroupId,
                        text: null,
                        msg_id: Date.now(),
                        time: Math.floor(Date.now() / 1000),
                        data: { audio: true, duration: duration }
                    });
                }
            } else { 
                alert('Ошибка отправки аудио: ' + (data.error || 'Неизвестная ошибка')); 
            }
        }).catch(function() { alert('Ошибка сети'); });
    };
    reader.readAsDataURL(audioBlob);
}


// ========== ВОСПРОИЗВЕДЕНИЕ АУДИО ==========
function playAudio(element, audioPath) {
    if (currentAudio && !currentAudio.paused) {
        currentAudio.pause();
        currentAudio = null;
        document.querySelectorAll('.audio-play').forEach(function(btn) { btn.innerHTML = '▶'; });
    }
    var audio = new Audio(audioPath + '?t=' + Date.now());
    currentAudio = audio;
    var playBtn = element.querySelector('.audio-play');
    if (playBtn) playBtn.innerHTML = '⏸';
    audio.play().catch(function(e) { if (playBtn) playBtn.innerHTML = '▶'; });
    audio.onended = function() { if (playBtn) playBtn.innerHTML = '▶'; currentAudio = null; };
    audio.onerror = function() { if (playBtn) playBtn.innerHTML = '▶'; currentAudio = null; };
}

// ========== СКАЧИВАНИЕ ФАЙЛОВ ==========
function downloadFile(url, filename) {
    if (window.innerWidth <= 768) {
        var modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.background = 'rgba(0,0,0,0.8)';
        modal.style.zIndex = '20000';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        var content = document.createElement('div');
        content.style.background = '#202c33';
        content.style.padding = '20px';
        content.style.borderRadius = '12px';
        content.style.textAlign = 'center';
        content.style.maxWidth = '280px';
        var fileIcon = getFileIcon(filename);
        content.innerHTML = 
            '<div style="font-size: 48px; margin-bottom: 15px;">' + fileIcon + '</div>' +
            '<div style="color: #e9edef; margin-bottom: 15px; word-break: break-all;">' + escapeHtml(filename) + '</div>' +
            '<div style="display: flex; gap: 10px; margin-top: 10px;">' +
            '<button id="downloadFileBtn" style="flex:1; background:#00a884; border:none; padding:12px; border-radius:8px; color:white; cursor:pointer;">⬇️ Скачать</button>' +
            '<button id="cancelFileBtn" style="flex:1; background:#c33; border:none; padding:12px; border-radius:8px; color:white; cursor:pointer;">✖ Отмена</button>' +
            '</div>';
        modal.appendChild(content);
        document.body.appendChild(modal);
        var modalRef = modal;
        document.getElementById('downloadFileBtn').onclick = function() {
            fetch(url + '?t=' + Date.now()).then(function(response) { return response.blob(); }).then(function(blob) {
                var blobUrl = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(function() { document.body.removeChild(a); URL.revokeObjectURL(blobUrl); if (modalRef) modalRef.remove(); }, 100);
            }).catch(function() { alert('Ошибка загрузки файла'); if (modalRef) modalRef.remove(); });
        };
        document.getElementById('cancelFileBtn').onclick = function() { if (modalRef) modalRef.remove(); };
        modal.onclick = function(e) { if (e.target === modal) modal.remove(); };
    } else {
        var a = document.createElement('a');
        a.href = url + '?t=' + Date.now();
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function() { document.body.removeChild(a); }, 100);
    }
}

// ========== МОДАЛЬНЫЕ ОКНА ==========
function showQRModal() { document.getElementById('qrModal').classList.add('open'); }
function closeQRModal() { document.getElementById('qrModal').classList.remove('open'); }
function showIOSModal() { document.getElementById('iosModal').classList.add('open'); }
function closeIOSModal() { document.getElementById('iosModal').classList.remove('open'); }
function showAndroidModal() { document.getElementById('androidModal').classList.add('open'); }
function closeAndroidModal() { document.getElementById('androidModal').classList.remove('open'); }

function openImageModal(src, msgId, fromPhone, groupId = null) {
    var modal = document.getElementById('imageModal');
    var modalImg = document.getElementById('modalImage');
    modalImg.src = src + '?t=' + Date.now();
    modal.classList.add('open');
    var canDelete = false;
    if (groupId) {
        if (fromPhone === myPhone) { canDelete = true; }
        else if (currentGroupRole === 'admin') { canDelete = true; }
    } else {
        if (fromPhone === myPhone) { canDelete = true; }
    }
    var deleteBtn = document.getElementById('imageModalDeleteBtn');
    if (!deleteBtn) {
        deleteBtn = document.createElement('button');
        deleteBtn.id = 'imageModalDeleteBtn';
        deleteBtn.innerHTML = '🗑 Удалить фото';
        deleteBtn.style.cssText = 'position:absolute; bottom:30px; left:50%; transform:translateX(-50%); background:#c33; border:none; padding:12px 24px; border-radius:30px; color:white; font-size:16px; cursor:pointer; z-index:12001;';
        modal.appendChild(deleteBtn);
    }
    if (canDelete) {
        deleteBtn.style.display = 'block';
        deleteBtn.onclick = function(e) { e.stopPropagation(); if (confirm('Удалить это фото?')) { deleteMessage(msgId); closeImageModal(); } };
    } else {
        deleteBtn.style.display = 'none';
    }
}

function closeImageModal() {
    var modal = document.getElementById('imageModal');
    modal.classList.remove('open');
    var deleteBtn = document.getElementById('imageModalDeleteBtn');
    if (deleteBtn) deleteBtn.style.display = 'none';
}

function toggleSidebar() { 
    var sidebar = document.getElementById('sidebar'); 
    if (sidebar) sidebar.classList.contains('open') ? sidebar.classList.remove('open') : sidebar.classList.add('open'); 
}

function hideSidebar() { 
    var sidebar = document.getElementById('sidebar'); 
    if (sidebar) sidebar.classList.remove('open'); 
}

// ========== НАСТРОЙКИ ==========
function loadBottomMenu() {
    var menuHtml = '<div class="bottom-menu-item" onclick="openModule(\'chats\')"><div class="bottom-menu-icon">💬</div><div class="bottom-menu-name">Чаты</div></div>' +
                   '<div class="bottom-menu-item" onclick="openModule(\'settings\')"><div class="bottom-menu-icon">⚙️</div><div class="bottom-menu-name">Настройки</div></div>';
    var bottomMenu = document.getElementById('bottomMenu');
    if (bottomMenu) bottomMenu.innerHTML = menuHtml;
}

function openModule(moduleId) {
    if (moduleId === 'chats') { 
        if (window.innerWidth <= 768) toggleSidebar(); 
    } else if (moduleId === 'settings') {
        openSettings();
    }
}

function openChats() {
    if (window.innerWidth <= 768) toggleSidebar();
}

function applyAdminColors() {
    fetch('admin.php?action=get_colors', { cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(colors) {
            if (colors && !colors.error) {
                var theme = localStorage.getItem('theme') || 'dark';
                if (theme === 'dark') {
                    if (colors.dark_bg) document.querySelector('.chat-area').style.background = colors.dark_bg;
                    if (colors.dark_sidebar_bg) document.querySelector('.sidebar').style.background = colors.dark_sidebar_bg;
                    if (colors.dark_header_bg) document.querySelectorAll('.sidebar-header, .chat-header').forEach(function(h) { h.style.background = colors.dark_header_bg; });
                    if (colors.dark_text) document.querySelectorAll('.contact-name, #myName, .chat-header-name').forEach(function(t) { t.style.color = colors.dark_text; });
                    if (colors.dark_message_in_bg) document.querySelectorAll('.message.in').forEach(function(m) { m.style.background = colors.dark_message_in_bg; });
                    if (colors.dark_message_out_bg) document.querySelectorAll('.message.out').forEach(function(m) { m.style.background = colors.dark_message_out_bg; });
                    if (colors.dark_input_bg) document.querySelectorAll('.input-area textarea, .attach-button, .mic-button').forEach(function(i) { i.style.background = colors.dark_input_bg; });
                } else {
                    if (colors.light_bg) document.querySelector('.chat-area').style.background = colors.light_bg;
                    if (colors.light_sidebar_bg) document.querySelector('.sidebar').style.background = colors.light_sidebar_bg;
                    if (colors.light_header_bg) document.querySelectorAll('.sidebar-header, .chat-header').forEach(function(h) { h.style.background = colors.light_header_bg; });
                    if (colors.light_text) document.querySelectorAll('.contact-name, #myName, .chat-header-name').forEach(function(t) { t.style.color = colors.light_text; });
                    if (colors.light_message_in_bg) document.querySelectorAll('.message.in').forEach(function(m) { m.style.background = colors.light_message_in_bg; });
                    if (colors.light_message_out_bg) document.querySelectorAll('.message.out').forEach(function(m) { m.style.background = colors.light_message_out_bg; });
                    if (colors.light_input_bg) document.querySelectorAll('.input-area textarea, .attach-button, .mic-button').forEach(function(i) { i.style.background = colors.light_input_bg; });
                }
                var bgImage = colors.chat_background || 'fonDefault.png';
                var messagesContainer = document.getElementById('messagesContainer');
                if (messagesContainer && localStorage.getItem('show_chat_bg') !== 'false') {
                    messagesContainer.style.backgroundImage = "url('" + bgImage + "?t=" + Date.now() + "')";
                    messagesContainer.style.backgroundSize = "cover";
                    messagesContainer.style.backgroundPosition = "center";
                }
            }
        });
}

function openSettings() {
    var modal = document.getElementById('settingsModal');
    var overlay = document.getElementById('settingsOverlay');
    var savedName = localStorage.getItem('chat_name') || '';
    var savedTheme = localStorage.getItem('theme') || 'dark';
    var showChatBg = localStorage.getItem('show_chat_bg') !== 'false';
    var savedEmail = localStorage.getItem('user_email') || '';
    
    if (myPhone) {
        fetch('api.php?action=get_user_info&phone=' + encodeURIComponent(myPhone))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.email) {
                    savedEmail = data.email;
                    document.getElementById('settingsEmail').value = savedEmail;
                }
            });
    }
    
    var html = '<div class="modal-header"><button class="modal-close" onclick="closeSettings()">←</button><span>Настройки</span></div><div class="modal-body">';
    html += '<div class="settings-avatar"><div class="avatar-preview" onclick="document.getElementById(\'avatarInput\').click()">';
    html += '<img src="uploads/avatars/default.png" id="avatarImg" onerror="this.src=\'uploads/avatars/default.png\'">';
    html += '<div class="avatar-overlay">📷</div></div><input type="file" id="avatarInput" style="display:none" accept="image/*" onchange="uploadAvatar(this)"></div>';
    html += '<div class="settings-item"><label>📧 Email</label><input type="email" id="settingsEmail" class="settings-input" value="' + escapeHtml(savedEmail) + '" placeholder="Ваш email"><button class="save-btn" onclick="changeEmail()">Сохранить</button></div>';
    html += '<div class="settings-item"><label>Имя</label><input type="text" id="settingsName" class="settings-input" value="' + escapeHtml(savedName) + '" placeholder="Ваше имя"><button class="save-btn" onclick="saveName()">Сохранить</button></div>';
    html += '<div class="settings-item"><label>Тема</label><div class="theme-selector"><button class="theme-btn ' + (savedTheme === 'dark' ? 'active' : '') + '" data-theme="dark" onclick="setTheme(\'dark\')">Темная</button><button class="theme-btn ' + (savedTheme === 'light' ? 'active' : '') + '" data-theme="light" onclick="setTheme(\'light\')">Светлая</button></div></div>';
    html += '<div class="settings-item"><div class="checkbox-item"><input type="checkbox" id="showChatBgCheckbox" ' + (showChatBg ? 'checked' : '') + ' onchange="toggleChatBg()"><label for="showChatBgCheckbox">Показать фон чата</label></div></div>';
    html += '<div class="settings-item"><button class="share-btn" onclick="showQRModal(); closeSettings();">📲 Поделиться</button></div>';
    html += '<div class="settings-item"><button class="logout-settings-btn" onclick="logout()">Выйти из аккаунта</button></div>';
    html += '<div class="copyright-settings">© R.I.Moskalenko</div></div>';
    
    if (modal) modal.innerHTML = html;
    if (modal) modal.classList.add('open');
    if (overlay) overlay.classList.add('open');
    
    if (myPhone) {
        fetch('api.php?action=get_user_info&phone=' + encodeURIComponent(myPhone))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.avatar) {
                    var avatarImg = document.getElementById('avatarImg');
                    if (avatarImg) avatarImg.src = data.avatar + '?t=' + Date.now();
                }
            })
            .catch(function(e) { console.log(e); });
    }
}

function closeSettings() {
    var modal = document.getElementById('settingsModal');
    var overlay = document.getElementById('settingsOverlay');
    if (modal) modal.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    if (modal) modal.innerHTML = '';
}

function changeEmail() {
    var newEmail = document.getElementById('settingsEmail').value.trim();
    if (!newEmail) { alert('Введите email'); return; }
    if (!newEmail.includes('@') || !newEmail.includes('.')) { alert('Введите корректный email'); return; }
    var formData = new FormData();
    formData.append('email', newEmail);
    fetch('api.php?action=change_email', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success) {
            alert('✅ Email успешно изменён на ' + data.email);
            localStorage.setItem('user_email', data.email);
        } else { alert('❌ Ошибка: ' + (data.error || 'Неизвестная ошибка')); }
    }).catch(err => { alert('Ошибка соединения: ' + err.message); });
}

function toggleChatBg() { 
    localStorage.setItem('show_chat_bg', document.getElementById('showChatBgCheckbox').checked); 
    applyChatBg(); 
}

function applyChatBg() {
    var showBg = localStorage.getItem('show_chat_bg') !== 'false';
    var messagesContainer = document.getElementById('messagesContainer');
    if (showBg && messagesContainer) {
        fetch('admin.php?action=get_colors', { cache: 'no-store' })
            .then(res => res.json())
            .then(colors => {
                var bgImage = colors.chat_background || 'fonDefault.png';
                messagesContainer.style.backgroundImage = "url('" + bgImage + "?t=' + Date.now())";
                messagesContainer.style.backgroundSize = "cover";
                messagesContainer.style.backgroundRepeat = "no-repeat";
            });
    } else if (messagesContainer) {
        messagesContainer.style.backgroundImage = "none";
        var theme = localStorage.getItem('theme') || 'dark';
        messagesContainer.style.background = theme === 'light' ? "#fff" : "#0a0f12";
    }
}

function setTheme(theme) {
    localStorage.setItem('theme', theme);
    document.body.classList[theme === 'light' ? 'add' : 'remove']('light-theme');
    applyAdminColors();
    applyChatBg();
    document.querySelectorAll('.theme-btn').forEach(function(btn) { btn.classList.remove('active'); });
    var activeBtn = document.querySelector('.theme-btn[data-theme="' + theme + '"]');
    if (activeBtn) activeBtn.classList.add('active');
}

function saveName() {
    var newName = document.getElementById('settingsName').value;
    if (newName && myPhone) {
        localStorage.setItem('chat_name', newName);
        document.getElementById('nameInput').value = newName;
        fetch('api.php?action=update_name', { method: 'POST', body: 'phone=' + encodeURIComponent(myPhone) + '&name=' + encodeURIComponent(newName), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }).catch(function() {});
    }
}

function uploadAvatar(input) {
    var file = input.files[0];
    if (!file) return;
    if (!myPhone) return;
    if (file.size > 2 * 1024 * 1024) { alert('Файл слишком большой (макс. 2 МБ)'); return; }
    if (!file.type.startsWith('image/')) { alert('Можно загружать только изображения'); return; }
    var formData = new FormData();
    formData.append('phone', myPhone);
    formData.append('avatar', file);
    fetch('api.php?action=upload_avatar', {
        method: 'POST',
        body: formData
    }).then(function(res) { return res.text(); }).then(function(text) {
        try {
            var data = JSON.parse(text);
            if (data.success) {
                var avatarImg = document.getElementById('avatarImg');
                if (avatarImg) avatarImg.src = data.avatar_path + '?t=' + Date.now();
                loadChats();
                alert('Аватарка обновлена!');
                if (wsConnected && socket) {
                    socket.emit('new_avatar', { phone: myPhone, avatar: data.avatar_path });
                }
            } else { alert('Ошибка: ' + (data.error || 'Неизвестная ошибка')); }
        } catch(e) { console.error('Parse error:', text); alert('Ошибка сервера'); }
    }).catch(function(err) { console.error('Fetch error:', err); alert('Ошибка сети: ' + err.message); });
    input.value = '';
}

// ========== ЗАГРУЗКА ФАЙЛА ==========
function uploadFile(input) {
    var file = input.files[0];
    if (!file) { alert('Выберите файл'); return; }
    if (!currentContact && !currentGroupId) { alert('Выберите чат'); input.value = ''; return; }
    if (!myPhone) { alert('Ошибка: не авторизован'); input.value = ''; return; }
    if (file.size > 5 * 1024 * 1024) { alert('Файл слишком большой (макс. 5 МБ)'); input.value = ''; return; }
    
    if (file.type.startsWith('image/')) {
        fixImageOrientationClient(file, function(blob) { sendFileToServer(blob, file.name, file.type); });
    } else {
        var reader = new FileReader();
        reader.onload = function(e) { sendFileToServer(e.target.result.split(',')[1], file.name, file.type); };
        reader.readAsDataURL(file);
    }
    input.value = '';
}

function fixImageOrientationClient(file, callback) {
    var reader = new FileReader();
    reader.onload = function(e) {
        var view = new DataView(e.target.result);
        var orientation = 1;
        if (view.getUint8(0) === 0xFF && view.getUint8(1) === 0xD8) {
            var offset = 2;
            var length = view.byteLength;
            while (offset + 2 < length) {
                var marker = view.getUint8(offset);
                var marker2 = view.getUint8(offset + 1);
                if (marker !== 0xFF) break;
                if (marker2 === 0xDA) break;
                var segmentLength = view.getUint16(offset + 2);
                if (marker2 === 0xE1 && view.getUint32(offset + 4) === 0x45786966) { orientation = findOrientationInExif(view, offset + 4); break; }
                offset += 2 + segmentLength;
            }
        }
        var img = new Image();
        img.onload = function() {
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var width = img.width, height = img.height;
            switch (orientation) {
                case 3: canvas.width = width; canvas.height = height; ctx.rotate(180 * Math.PI / 180); ctx.drawImage(img, -width, -height); break;
                case 6: canvas.width = height; canvas.height = width; ctx.rotate(90 * Math.PI / 180); ctx.drawImage(img, 0, -height); break;
                case 8: canvas.width = height; canvas.height = width; ctx.rotate(-90 * Math.PI / 180); ctx.drawImage(img, -width, 0); break;
                default: canvas.width = width; canvas.height = height; ctx.drawImage(img, 0, 0);
            }
            canvas.toBlob(function(blob) { callback(blob); }, file.type, 0.9);
        };
        img.src = URL.createObjectURL(file);
    };
    reader.readAsArrayBuffer(file);
}

function findOrientationInExif(view, exifStart) {
    var tiffStart = exifStart + 6;
    var isLittleEndian = view.getUint16(tiffStart) === 0x4949;
    var ifdOffset = view.getUint32(tiffStart + 4, isLittleEndian);
    var ifdStart = tiffStart + ifdOffset;
    var numEntries = view.getUint16(ifdStart, isLittleEndian);
    for (var i = 0; i < numEntries; i++) {
        var entryOffset = ifdStart + 2 + i * 12;
        if (view.getUint16(entryOffset, isLittleEndian) === 0x0112) {
            return view.getUint16(entryOffset + 8, isLittleEndian);
        }
    }
    return 1;
}

function sendFileToServer(fileData, fileName, fileType) {
    if (fileData instanceof Blob) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var base64 = e.target.result.split(',')[1];
            sendToServer(base64);
        };
        reader.readAsDataURL(fileData);
    } else {
        sendToServer(fileData);
    }
    
    function sendToServer(base64) {
        var params, url;
        if (currentGroupId) {
            params = new URLSearchParams();
            params.append('group_id', currentGroupId);
            params.append('from', myPhone);
            params.append('file_base64', base64);
            params.append('file_name', fileName);
            params.append('file_type', fileType);
            url = 'api.php?action=upload_group_file_base64';
        } else {
            params = new URLSearchParams();
            params.append('from', myPhone);
            params.append('to', currentContact.phone);
            params.append('file_base64', base64);
            params.append('file_name', fileName);
            params.append('file_type', fileType);
            url = 'api.php?action=upload_file_base64';
        }
        
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (currentGroupId) {
                    loadGroupMessages();
                } else {
                    loadMessages();
                }
                loadChats();
                
                // ОТПРАВКА WS УВЕДОМЛЕНИЯ
                if (wsConnected && socket) {
                    if (currentGroupId) {
                        socket.emit('send_group_message', {
                            from: myPhone,
                            group_id: currentGroupId,
                            text: null,
                            msg_id: Date.now(),
                            time: Math.floor(Date.now() / 1000),
                            data: { file: fileName, file_type: fileType, file_size: fileData.size || null }
                        });
                    } else {
                        socket.emit('send_message', {
                            from: myPhone,
                            to: currentContact.phone,
                            text: null,
                            msg_id: Date.now(),
                            time: Math.floor(Date.now() / 1000),
                            data: { file: fileName, file_type: fileType, file_size: fileData.size || null }
                        });
                    }
                }
            } else {
                alert('Ошибка отправки файла: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(function(err) {
            alert('Ошибка сети: ' + err.message);
        });
    }
}


// ========== СТАТУСЫ СООБЩЕНИЙ ==========
function getMessageStatus(status, isOut) {
    if (!isOut) return '';
    if (status === 'read') return '<span class="message-status"><span class="status-icon read">✓✓</span></span>';
    else if (status === 'delivered') return '<span class="message-status"><span class="status-icon delivered">✓✓</span></span>';
    else return '<span class="message-status"><span class="status-icon">✓</span></span>';
}

function updateMessageStatusToDelivered(msgId) {
    fetch('api.php?action=update_status', { method: 'POST', body: 'msg_id=' + msgId + '&status=delivered', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
}

function markMessagesAsRead() {
    if (!currentContact || !myPhone) return;
    fetch('api.php?action=mark_read', { method: 'POST', body: 'my_phone=' + encodeURIComponent(myPhone) + '&contact_phone=' + encodeURIComponent(currentContact.phone), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
        .then(function() { loadMessages(); });
}

// ========== ПОИСК ПОЛЬЗОВАТЕЛЯ ==========
var searchTimeout = null;

function searchUserDelayed() {
    var searchInput = document.getElementById('searchInput');
    var rawPhone = searchInput.value.replace(/\D/g, '');
    if (searchTimeout) clearTimeout(searchTimeout);
    if (rawPhone.length >= 11) {
        searchTimeout = setTimeout(function() { searchUser(rawPhone); }, 600);
    } else if (rawPhone.length > 0 && rawPhone.length < 11) {
        var resultsDiv = document.getElementById('searchResults');
        resultsDiv.innerHTML = '<div class="search-result-item" style="justify-content:center; background:#ff9800; color:#333; border-radius:20px; margin:5px;" onclick="clearSearchResults()">⚠️ Введите 10 цифр после +7</div>';
        resultsDiv.classList.remove('hidden');
    } else { clearSearchResults(); }
}

function searchUser(cleanPhone) {
    var resultsDiv = document.getElementById('searchResults');
    fetch('api.php?action=search_user&phone=' + encodeURIComponent(cleanPhone))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.user) {
                var isExistingChat = false;
                for (var i = 0; i < allChats.length; i++) {
                    if (allChats[i] && allChats[i].id === data.user.phone && allChats[i].type === 'user') { isExistingChat = true; break; }
                }
                if (isExistingChat) {
                    resultsDiv.innerHTML = '<div class="search-result-item" style="justify-content:center; background:#00a884; color:white; border-radius:20px; margin:5px;" onclick="clearSearchResults()">✅ Уже есть в чатах</div>';
                } else {
                    var avatarUrl = data.user.avatar && data.user.avatar !== 'uploads/avatars/default.png' ? data.user.avatar + '?t=' + Date.now() : 'uploads/avatars/default.png';
                    resultsDiv.innerHTML = '<div class="search-result-item" onclick="startChat(\'' + data.user.phone + '\', \'' + escapeHtml(data.user.name) + '\', \'' + avatarUrl + '\')">' +
                        '<img class="contact-avatar" src="' + avatarUrl + '" onerror="this.src=\'uploads/avatars/default.png\'">' +
                        '<div class="contact-info"><div class="contact-name">' + escapeHtml(data.user.name) + '</div><div class="contact-last">' + formatPhoneMask(data.user.phone) + '</div></div>' +
                        '<button class="start-chat-btn">Написать</button></div>';
                }
                resultsDiv.classList.remove('hidden');
            } else {
                resultsDiv.innerHTML = '<div class="search-result-item" style="justify-content:center; background:#c33; color:white; border-radius:20px; margin:5px;" onclick="clearSearchResults()">❌ Пользователь не зарегистрирован</div>';
                resultsDiv.classList.remove('hidden');
            }
        })
        .catch(function(e) {
            resultsDiv.innerHTML = '<div class="search-result-item" style="justify-content:center; background:#c33; color:white; border-radius:20px; margin:5px;" onclick="clearSearchResults()">❌ Ошибка поиска</div>';
            resultsDiv.classList.remove('hidden');
        });
}

function clearSearchResults() {
    var resultsDiv = document.getElementById('searchResults');
    var searchInput = document.getElementById('searchInput');
    resultsDiv.classList.add('hidden');
    resultsDiv.innerHTML = '';
    searchInput.value = '';
    loadChats();
}

function startChat(phone, name, avatar) { 
    clearSearchResults(); 
    selectContact(phone, name, avatar, 'user'); 
}

// ========== УДАЛЕНИЕ ==========
function deleteMessage(msgId) {
    if (!confirm('Удалить сообщение?')) return;
    
    var formData = new FormData();
    formData.append('msg_id', msgId);
    formData.append('my_phone', myPhone);
    
    var url;
    if (currentGroupId) {
        url = 'api.php?action=delete_group_message';
    } else {
        url = 'api.php?action=delete_message';
    }
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (currentGroupId) {
                loadGroupMessages();
            } else if (currentContact) {
                loadMessages();
            }
            loadChats();
            if (wsConnected && socket) {
                if (currentGroupId) {
                    socket.emit('delete_message', {
                        type: 'group',
                        group_id: currentGroupId,
                        msg_id: msgId,
                        from: myPhone
                    });
                } else if (currentContact) {
                    socket.emit('delete_message', {
                        type: 'private',
                        to: currentContact.phone,
                        msg_id: msgId,
                        from: myPhone
                    });
                }
            }
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось удалить сообщение'));
        }
    })
    .catch(err => {
        alert('Ошибка сети: ' + err.message);
    });
}

function deleteChatFromList(chatId, event, type = 'user') {
    event.stopPropagation();
    if (type === 'group') {
        if (!confirm('Выйти из группы? (Сообщения останутся)')) return;
        fetch('api.php?action=remove_group_member', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'group_id=' + chatId + '&actor_phone=' + encodeURIComponent(myPhone) + '&target_phone=' + encodeURIComponent(myPhone)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (currentGroupId == chatId) {
                    currentGroupId = null;
                    document.getElementById('contactName').innerHTML = 'Выберите чат';
                    document.getElementById('messagesContainer').innerHTML = '';
                    document.getElementById('groupMenuBtn').style.display = 'none';
                }
                loadChats();
                if (wsConnected && socket) {
                    socket.emit('new_chats', { reason: 'deleted', from: myPhone });
                }
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная'));
            }
        });
    } else {
        if (!confirm('Удалить чат? Сообщения будут удалены только у вас.')) return;
        var formData = new FormData();
        formData.append('my_phone', myPhone);
        formData.append('contact_phone', chatId);
        fetch('api.php?action=delete_chat', { method: 'POST', body: formData })
            .then(function() {
                if (currentContact && currentContact.phone === chatId) {
                    currentContact = null;
                    document.getElementById('contactName').innerHTML = 'Выберите чат';
                    document.getElementById('messagesContainer').innerHTML = '';
                }
                loadChats();
                if (wsConnected && socket) {
                    socket.emit('new_chats', { reason: 'deleted', from: myPhone });
                }
            });
    }
}

// ========== ДОЛГОЕ НАЖАТИЕ ДЛЯ УДАЛЕНИЯ ==========
function onTouchStart(event, msgId, element) {
    event.stopPropagation();
    currentMsgIdForPress = msgId;
    currentElementForPress = element;
    pressTimer = setTimeout(function() {
        showMessageContextMenu(event, currentMsgIdForPress);
        pressTimer = null;
    }, 500);
}

function onTouchEnd(event) {
    if (pressTimer) {
        clearTimeout(pressTimer);
        pressTimer = null;
        currentMsgIdForPress = null;
        currentElementForPress = null;
    }
}

function onTouchMove() {
    if (pressTimer) {
        clearTimeout(pressTimer);
        pressTimer = null;
        currentMsgIdForPress = null;
        currentElementForPress = null;
    }
}

// ========== КОНТЕКСТНОЕ МЕНЮ ДЛЯ ГРУППОВЫХ ЧАТОВ ==========
var groupPressTimer = null;
var currentGroupMsgIdForPress = null;
var currentGroupElementForPress = null;

function onGroupTouchStart(event, msgId, element) {
    event.stopPropagation();
    currentGroupMsgIdForPress = msgId;
    currentGroupElementForPress = element;
    groupPressTimer = setTimeout(function() {
        showGroupMessageContextMenu(event, currentGroupMsgIdForPress);
        groupPressTimer = null;
    }, 500);
}

function onGroupTouchEnd(event) {
    if (groupPressTimer) {
        clearTimeout(groupPressTimer);
        groupPressTimer = null;
        currentGroupMsgIdForPress = null;
        currentGroupElementForPress = null;
    }
}

function onGroupTouchMove() {
    if (groupPressTimer) {
        clearTimeout(groupPressTimer);
        groupPressTimer = null;
        currentGroupMsgIdForPress = null;
        currentGroupElementForPress = null;
    }
}


function showGroupMessageContextMenu(event, msgId) {
    event.preventDefault();
    event.stopPropagation();
    
    var existingMenu = document.querySelector('.message-context-menu');
    var existingOverlay = document.querySelector('.message-context-menu-overlay');
    if (existingMenu) existingMenu.remove();
    if (existingOverlay) existingOverlay.remove();
    
    var overlay = document.createElement('div');
    overlay.className = 'message-context-menu-overlay';
    overlay.onclick = function() {
        menu.remove();
        overlay.remove();
    };
    document.body.appendChild(overlay);
    
    var menu = document.createElement('div');
    menu.className = 'message-context-menu';
    
    if (window.innerWidth <= 768) {
        // ✅ ПОЗИЦИОНИРУЕМ НАД ПОЛЕМ ВВОДА
        var inputArea = document.querySelector('.input-area');
        if (inputArea) {
            var rect = inputArea.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
            menu.style.left = '50%';
            menu.style.transform = 'translateX(-50%)';
            menu.style.width = 'calc(100% - 40px)';
            menu.style.maxWidth = '300px';
        } else {
            menu.style.position = 'fixed';
            menu.style.bottom = '80px';
            menu.style.left = '50%';
            menu.style.transform = 'translateX(-50%)';
            menu.style.width = 'calc(100% - 40px)';
            menu.style.maxWidth = '300px';
        }
    } else {
        var clientX = event.touches ? event.touches[0].clientX : event.clientX;
        var clientY = event.touches ? event.touches[0].clientY : event.clientY;
        menu.style.position = 'fixed';
        menu.style.left = (clientX - 90) + 'px';
        menu.style.top = (clientY - 50) + 'px';
    }
    
    var deleteItem = document.createElement('div');
    deleteItem.className = 'message-context-menu-item delete';
    deleteItem.innerHTML = '🗑 Удалить сообщение';
    deleteItem.onclick = function(e) {
        e.stopPropagation();
        if (confirm('Удалить это сообщение?')) {
            deleteGroupMessage(msgId);
        }
        menu.remove();
        overlay.remove();
    };
    menu.appendChild(deleteItem);
	
	
	//////////Всплывпшка

    if (window.innerWidth <= 768) {
        var cancelItem = document.createElement('div');
        cancelItem.className = 'message-context-menu-item';
        cancelItem.innerHTML = 'Отмена';
        cancelItem.onclick = function(e) {
            e.stopPropagation();
            menu.remove();
            overlay.remove();
        };
        menu.appendChild(cancelItem);
    }
 
    document.body.appendChild(menu);
    
    function closeMenu(e) {
        if (menu && !menu.contains(e.target)) {
            menu.remove();
            if (overlay) overlay.remove();
            document.removeEventListener('click', closeMenu);
            document.removeEventListener('touchstart', closeMenu);
        }
    }
    setTimeout(function() {
        document.addEventListener('click', closeMenu);
        document.addEventListener('touchstart', closeMenu);
    }, 100);
}

function deleteGroupMessage(msgId) {
    var formData = new FormData();
    formData.append('msg_id', msgId);
    formData.append('my_phone', myPhone);
    
    fetch('api.php?action=delete_group_message', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadGroupMessages();
            loadChats();
            if (wsConnected && socket && currentGroupId) {
                socket.emit('delete_message', {
                    type: 'group',
                    group_id: currentGroupId,
                    msg_id: msgId,
                    from: myPhone
                });
            }
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось удалить сообщение'));
        }
    })
    .catch(err => {
        alert('Ошибка сети: ' + err.message);
    });
}

function showMessageContextMenu(event, msgId) {
    event.preventDefault();
    event.stopPropagation();
    
    var existingMenu = document.querySelector('.message-context-menu');
    var existingOverlay = document.querySelector('.message-context-menu-overlay');
    if (existingMenu) existingMenu.remove();
    if (existingOverlay) existingOverlay.remove();
    
    var overlay = document.createElement('div');
    overlay.className = 'message-context-menu-overlay';
    overlay.onclick = function() {
        menu.remove();
        overlay.remove();
    };
    document.body.appendChild(overlay);
    
    var menu = document.createElement('div');
    menu.className = 'message-context-menu';
    
    if (window.innerWidth <= 768) {
        // ✅ ПОЗИЦИОНИРУЕМ НАД ПОЛЕМ ВВОДА
        var inputArea = document.querySelector('.input-area');
        if (inputArea) {
            var rect = inputArea.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
            menu.style.left = '50%';
            menu.style.transform = 'translateX(-50%)';
            menu.style.width = 'calc(100% - 40px)';
            menu.style.maxWidth = '300px';
        } else {
            menu.style.position = 'fixed';
            menu.style.bottom = '80px';
            menu.style.left = '50%';
            menu.style.transform = 'translateX(-50%)';
            menu.style.width = 'calc(100% - 40px)';
            menu.style.maxWidth = '300px';
        }
    } else {
        var clientX = event.touches ? event.touches[0].clientX : event.clientX;
        var clientY = event.touches ? event.touches[0].clientY : event.clientY;
        menu.style.position = 'fixed';
        menu.style.left = (clientX - 90) + 'px';
        menu.style.top = (clientY - 50) + 'px';
    }
    
    var deleteItem = document.createElement('div');
    deleteItem.className = 'message-context-menu-item delete';
    deleteItem.innerHTML = '🗑 Удалить сообщение';
    deleteItem.onclick = function(e) {
        e.stopPropagation();
        if (confirm('Удалить это сообщение?')) {
            deleteMessage(msgId);
        }
        menu.remove();
        overlay.remove();
    };
    menu.appendChild(deleteItem);
   /////Всплывашка
    if (window.innerWidth <= 768) {
        var cancelItem = document.createElement('div');
        cancelItem.className = 'message-context-menu-item';
        cancelItem.innerHTML = 'Отмена';
        cancelItem.onclick = function(e) {
            e.stopPropagation();
            menu.remove();
            overlay.remove();
        };
        menu.appendChild(cancelItem);
    }
    
    document.body.appendChild(menu);
    
    function closeMenu(e) {
        if (menu && !menu.contains(e.target)) {
            menu.remove();
            if (overlay) overlay.remove();
            document.removeEventListener('click', closeMenu);
            document.removeEventListener('touchstart', closeMenu);
        }
    }
    setTimeout(function() {
        document.addEventListener('click', closeMenu);
        document.addEventListener('touchstart', closeMenu);
    }, 100);
}

// ========== ЗАГРУЗКА ЧАТОВ ==========
function loadChats() {
    if (!myPhone) return;
    // Добавляем уникальный параметр для обхода кэша
    var timestamp = Date.now();
    fetch('api.php?action=get_chats&my_phone=' + encodeURIComponent(myPhone) + '&_t=' + timestamp, { cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(chats) {
            // ✅ ФИЛЬТРУЕМ ГРУППЫ, если они отключены
            if (loadSettings.disable_groups === 1) {
                chats = chats.filter(function(chat) {
                    return chat.type !== 'group';
                });
            }
            renderChats(chats);
        })
        .catch(function(e) { console.log('loadChats error:', e); });
}
function renderChats(chats) {
    var container = document.getElementById('contactsList');
    if (!container) return;
    
    var oldChats = allChats;
    allChats = chats;
    
    if (chats.length === 0) {
        container.innerHTML = '<div class="empty-contacts">💬 У вас пока нет чатов<br>Для начала найдите <br>собеседника по номеру телефона.</div>';
        return;
    }
    
    if (oldChats.length === 0 || oldChats.length !== chats.length) {
        renderFullChatsList(chats);
        return;
    }
    
    for (var i = 0; i < chats.length; i++) {
        var newChat = chats[i];
        var oldChat = oldChats[i];
        
        var existingItem = document.querySelector('.contact-item[data-chat-id="' + newChat.id + '"][data-chat-type="' + newChat.type + '"]');
        
        if (!existingItem) {
            renderFullChatsList(chats);
            return;
        }
        
        var needUpdate = false;
        
        if (oldChat.last_message !== newChat.last_message) needUpdate = true;
        if (oldChat.last_time !== newChat.last_time) needUpdate = true;
        if (oldChat.unread !== newChat.unread) needUpdate = true;
        if (oldChat.is_online !== newChat.is_online) needUpdate = true;
        
        if (needUpdate) {
            var timeSpan = existingItem.querySelector('.contact-time');
            if (timeSpan && newChat.last_time) {
                var timeStr = new Date(newChat.last_time * 1000).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                timeSpan.innerHTML = timeStr;
            }
            
            var lastMsgSpan = existingItem.querySelector('.contact-last');
            if (lastMsgSpan) {
                lastMsgSpan.innerHTML = escapeHtml(newChat.last_message);
            }
            
            var badge = existingItem.querySelector('.unread-badge');
            if (newChat.unread && !badge) {
                var nameDiv = existingItem.querySelector('.contact-name');
                if (nameDiv) {
                    var badgeSpan = document.createElement('div');
                    badgeSpan.className = 'unread-badge';
                    nameDiv.appendChild(badgeSpan);
                }
            } else if (!newChat.unread && badge) {
                badge.remove();
            }
            
            var onlineDot = existingItem.querySelector('.online-dot');
            if (newChat.is_online && !onlineDot && newChat.type !== 'group') {
                var nameDiv = existingItem.querySelector('.contact-name');
                if (nameDiv) {
                    var dotSpan = document.createElement('span');
                    dotSpan.className = 'online-dot';
                    nameDiv.appendChild(dotSpan);
                }
            } else if (!newChat.is_online && onlineDot) {
                onlineDot.remove();
            }
            
            if (oldChat.last_message !== newChat.last_message && oldChat.last_message !== '') {
                existingItem.classList.add('new-message');
                setTimeout(function() {
                    existingItem.classList.remove('new-message');
                }, 500);
            }
        }
    }
}

function renderFullChatsList(chats) {
    var container = document.getElementById('contactsList');
    if (!container) return;
    
    var html = '';
    for (var i = 0; i < chats.length; i++) {
        var chat = chats[i];
        var timeStr = chat.last_time ? new Date(chat.last_time * 1000).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
        var badge = chat.unread ? '<div class="unread-badge"></div>' : '';
        var onlineDot = chat.is_online && chat.type !== 'group' ? '<span class="online-dot"></span>' : '';
        var typeIcon = chat.type === 'group' ? '👥 ' : '';
        var avatarUrl = chat.avatar && chat.avatar !== 'uploads/avatars/default.png' ? chat.avatar + '?t=' + Date.now() : (chat.type === 'group' ? 'uploads/group_avatars/default.png' : 'uploads/avatars/default.png');
        
        html += '<div class="contact-item" data-chat-id="' + escapeHtml(chat.id) + '" data-chat-type="' + (chat.type || 'user') + '" onclick="selectContact(\'' + escapeHtml(chat.id) + '\', \'' + escapeHtml(chat.name) + '\', \'' + avatarUrl + '\', \'' + (chat.type || 'user') + '\')">';
        html += '<img class="contact-avatar" src="' + avatarUrl + '" onerror="this.src=\'' + (chat.type === 'group' ? 'uploads/group_avatars/default.png' : 'uploads/avatars/default.png') + '\'">';
        html += '<div class="contact-info"><div class="contact-name">' + typeIcon + escapeHtml(chat.name) + onlineDot + badge + '</div><div class="contact-last">' + escapeHtml(chat.last_message) + '</div></div>';
        if (timeStr) html += '<div class="contact-time">' + timeStr + '</div>';
        html += '<div class="delete-chat-icon" onclick="deleteChatFromList(\'' + escapeHtml(chat.id) + '\', event, \'' + (chat.type || 'user') + '\')">🗑</div></div>';
    }
    container.innerHTML = html;
}

function selectContact(contactId, name, avatar, type = 'user') {
    var messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
    }
    
    isLoadingMessages = false;
    document.getElementById('contactName').innerHTML = escapeHtml(name);
    
    var headerAvatar = document.getElementById('chatHeaderAvatar');
    if (headerAvatar) {
        if (avatar && avatar !== 'uploads/avatars/default.png') {
            headerAvatar.src = avatar + '?t=' + Date.now();
            headerAvatar.style.display = 'block';
        } else {
            headerAvatar.style.display = 'none';
        }
    }
    
    if (type === 'group') {
        // Выход из предыдущей группы (если была)
        if (currentGroupId && wsConnected && socket) {
            console.log('🚪 Выход из предыдущей группы:', currentGroupId);
            socket.emit('leave_group', { group_id: parseInt(currentGroupId) });
        }
        
        currentGroupId = contactId;
        currentGroupName = name;
        currentGroupAvatar = avatar;
        currentContact = null;
        
        // ПРИСОЕДИНЕНИЕ К WS КОМНАТЕ ГРУППЫ
        if (wsConnected && socket) {
            console.log('🏠 Присоединение к группе:', contactId);
            socket.emit('join_group', { group_id: parseInt(contactId) });
        }
        
        fetch('api.php?action=get_group_info&group_id=' + contactId + '&my_phone=' + encodeURIComponent(myPhone))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentGroupRole = data.my_role;
                    console.log('Group role loaded:', currentGroupRole);
                }
            })
            .catch(e => console.log('Error loading group role:', e));
        
        document.getElementById('chatHeaderType').innerHTML = '👥 Группа';
        document.getElementById('groupMenuBtn').style.display = 'flex';
    } else {
        // Выход из группы (если была открыта)
        if (currentGroupId && wsConnected && socket) {
            console.log('🚪 Выход из группы:', currentGroupId);
            socket.emit('leave_group', { group_id: parseInt(currentGroupId) });
        }
        
        currentContact = { phone: contactId, name: name, avatar: avatar };
        currentGroupId = null;
        currentGroupRole = null;
        document.getElementById('chatHeaderType').innerHTML = '';
        document.getElementById('groupMenuBtn').style.display = 'none';
    }
    
    if (window.innerWidth <= 768) {
        hideSidebar();
    }
    
    loadMessagesForCurrentChat();
    
    if (type === 'user') {
        markMessagesAsRead();
    }
	
updateCallButtonsUI();  	
}



function loadMessagesForCurrentChat() {
    if (currentGroupId) {
        loadGroupMessages();
    } else if (currentContact) {
        loadMessages();
    }
}

// ========== ЗАГРУЗКА СООБЩЕНИЙ (ЛИЧНЫЕ) ==========
function loadMessages() {
    if (!currentContact || !myPhone) return;
    
    var container = document.getElementById('messagesContainer');
    if (!container) return;
    
    if (isLoadingMessages) return;
    isLoadingMessages = true;
    
    var wasAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 150;
    
    fetch('api.php?action=messages&my_phone=' + encodeURIComponent(myPhone) + '&contact_phone=' + encodeURIComponent(currentContact.phone), { cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(messages) {
            if (!messages || !Array.isArray(messages)) {
                isLoadingMessages = false;
                return;
            }
            
            if (!currentContact || currentContact.phone !== (currentContact.phone)) {
                isLoadingMessages = false;
                return;
            }
            
            checkNewMessages(messages);
            
            var newMessageIds = messages.map(function(m) { return m.id; }).join(',');
            var currentMessageIds = (lastMessageIds[currentContact.phone] || '').toString();
            
            if (newMessageIds === currentMessageIds && container.innerHTML !== '') {
                isLoadingMessages = false;
                return;
            }
            
            lastMessageIds[currentContact.phone] = newMessageIds;
            
            var hasNewMessage = false;
            if (messages.length > 0) {
                var lastMsgTime = messages[messages.length - 1].time;
                var prevLastMsgTime = lastMessageKeys[currentContact.phone] || 0;
                if (lastMsgTime > prevLastMsgTime) {
                    hasNewMessage = true;
                    lastMessageKeys[currentContact.phone] = lastMsgTime;
                }
            }
            
            var html = '';
            for (var i = 0; i < messages.length; i++) {
                var msg = messages[i];
                var isOut = (msg.from_phone === myPhone);
                var msgClass = isOut ? 'message out' : 'message in';
                var timeStr = new Date(msg.time * 1000).toLocaleTimeString();
                var statusHtml = getMessageStatus(msg.status, isOut);
                var msgId = msg.id;
                var contextAttr = '';
                var touchAttrs = '';
                if (isOut) {
                    contextAttr = ' oncontextmenu="showMessageContextMenu(event, ' + msgId + ')"';
                    touchAttrs = ' ontouchstart="onTouchStart(event, ' + msgId + ', this)" ontouchend="onTouchEnd(event)" ontouchmove="onTouchMove()"';
                }
                html += '<div class="' + msgClass + '" data-msg-id="' + msgId + '"' + contextAttr + touchAttrs + '>';
                
                if (msg.audio_path) {
                    var duration = msg.audio_duration || 0;
                    var durationStr = Math.floor(duration / 60) + ':' + (duration % 60 < 10 ? '0' : '') + (duration % 60);
                    html += '<div class="message-audio" data-audio-path="' + msg.audio_path + '" onclick="playAudio(this, \'' + msg.audio_path + '\')">';
                    html += '<div class="audio-play">▶</div>';
                    html += '<div class="audio-wave"><span></span><span></span><span></span><span></span><span></span></div>';
                    html += '<div class="audio-duration">' + durationStr + '</div>';
                    html += '</div>';
                } else if (msg.file_path) {
                    var isImage = msg.file_type && msg.file_type.startsWith('image/');
                    var fileName = msg.file_name || 'Файл';
                    var fileSize = msg.file_size ? formatFileSize(msg.file_size) : '';
                    if (isImage) {
                        html += '<img class="message-image" src="' + msg.file_path + '?t=' + Date.now() + '" loading="lazy" onclick="openImageModal(\'' + msg.file_path + '\', ' + msgId + ', \'' + msg.from_phone + '\')">';
                    } else {
                        html += '<div class="message-file" onclick="downloadFile(\'' + msg.file_path + '\', \'' + escapeHtml(fileName) + '\')">';
                        html += '<div class="file-icon">' + getFileIcon(fileName) + '</div>';
                        html += '<div class="file-info"><div class="file-name">' + escapeHtml(fileName) + '</div>';
                        if (fileSize) html += '<div class="file-size">' + fileSize + '</div>';
                        html += '</div></div>';
                    }
                } else if (msg.text) {
                    html += '<div class="message-content">' + escapeHtml(msg.text) + '</div>';
                }
                
                html += '<div class="message-footer">';
                html += '<span class="message-time">' + timeStr + '</span>';
                if (statusHtml) html += statusHtml;
                html += '</div></div>';
                
                if (isOut && msg.status !== 'delivered' && msg.status !== 'read') {
                    updateMessageStatusToDelivered(msg.id);
                }
            }
            
            container.innerHTML = html;
            
            // ========== ИСПРАВЛЕННЫЙ БЛОК ПРОКРУТКИ ==========
            function scrollToBottomSmooth() {
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }
            
            var shouldScroll = wasAtBottom || hasNewMessage || messages.length === 0;
            if (shouldScroll) {
                scrollToBottomSmooth();
            }
            
            // Дожидаемся загрузки всех изображений
            var images = container.querySelectorAll('.message-image');
            if (images.length > 0) {
                var pending = images.length;
                var onImageLoad = function() {
                    pending--;
                    if (pending === 0) {
                        scrollToBottomSmooth();
                    }
                };
                for (var i = 0; i < images.length; i++) {
                    if (images[i].complete) {
                        onImageLoad();
                    } else {
                        images[i].addEventListener('load', onImageLoad);
                        images[i].addEventListener('error', onImageLoad);
                    }
                }
            }
            
            // Страховка через 300 мс
            if (shouldScroll) {
                setTimeout(function() {
                    scrollToBottomSmooth();
                }, 300);
            }
            // ===============================================
            
            container.querySelectorAll('.message.out').forEach(function(msg) {
                if (!msg.hasAttribute('data-has-touch')) {
                    msg.setAttribute('data-has-touch', 'true');
                    var msgId = msg.getAttribute('data-msg-id');
                    if (msgId) {
                        if (msg._touchStartHandler) {
                            msg.removeEventListener('touchstart', msg._touchStartHandler);
                            msg.removeEventListener('touchend', msg._touchEndHandler);
                            msg.removeEventListener('touchmove', msg._touchMoveHandler);
                        }
                        msg._touchStartHandler = function(e) { onTouchStart(e, msgId, this); };
                        msg._touchEndHandler = onTouchEnd;
                        msg._touchMoveHandler = onTouchMove;
                        msg.addEventListener('touchstart', msg._touchStartHandler);
                        msg.addEventListener('touchend', msg._touchEndHandler);
                        msg.addEventListener('touchmove', msg._touchMoveHandler);
                    }
                }
            });
            
            markMessagesAsRead();
            isLoadingMessages = false;
        })
        .catch(function(e) {
            console.log('Ошибка загрузки сообщений:', e);
            isLoadingMessages = false;
        });
}

// ========== ЗАГРУЗКА СООБЩЕНИЙ ГРУППЫ ==========
function loadGroupMessages() {
    if (!currentGroupId || !myPhone) return;
    
    var container = document.getElementById('messagesContainer');
    if (!container) return;
    
    var wasAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 150;
    
    fetch('api.php?action=get_group_messages&group_id=' + currentGroupId + '&my_phone=' + encodeURIComponent(myPhone), { cache: 'no-store' })
        .then(res => res.json())
        .then(messages => {
            if (!messages || !Array.isArray(messages)) return;
            if (!currentGroupId) return;
            
            checkNewGroupMessages(messages);
            
            var newMessageIds = messages.map(function(m) { return m.id; }).join(',');
            var currentMessageIds = lastGroupMessageIds[currentGroupId] || '';
            
            if (newMessageIds === currentMessageIds && container.innerHTML !== '') {
                return;
            }
            
            var hasNewMessage = false;
            if (messages.length > 0) {
                var lastMsgTime = messages[messages.length - 1].time;
                var prevLastMsgTime = lastGroupMessageKeys[currentGroupId] || 0;
                if (lastMsgTime > prevLastMsgTime) {
                    hasNewMessage = true;
                    lastGroupMessageKeys[currentGroupId] = lastMsgTime;
                }
            }
            
            lastGroupMessageIds[currentGroupId] = newMessageIds;
            
            var html = '';
            for (var msg of messages) {
                var isOut = (msg.from_phone === myPhone);
                var msgClass = isOut ? 'message out' : 'message in';
                var timeStr = new Date(msg.time * 1000).toLocaleTimeString();
                var statusHtml = isOut ? (msg.status === 'read' ? '✓✓' : '✓') : '';
                var msgId = msg.id;
                var fromPhone = msg.from_phone;
                
                var canDelete = (currentGroupRole === 'admin') || (fromPhone === myPhone);
                var contextAttr = canDelete ? ' oncontextmenu="showGroupMessageContextMenu(event, ' + msgId + ')"' : '';
                var touchAttrs = canDelete ? ' ontouchstart="onGroupTouchStart(event, ' + msgId + ', this)" ontouchend="onGroupTouchEnd(event)" ontouchmove="onGroupTouchMove()"' : '';
                
                html += '<div class="' + msgClass + '" data-msg-id="' + msgId + '"' + contextAttr + touchAttrs + '>';
                if (!isOut) {
                    var senderName = msg.from_name || msg.from_phone;
                    html += '<div class="message-sender" style="font-size:11px; color:#8696a0; margin-bottom:4px;">' + escapeHtml(senderName) + '</div>';
                }
                if (msg.audio_path) {
                    var duration = msg.audio_duration || 0;
                    var durationStr = Math.floor(duration / 60) + ':' + (duration % 60 < 10 ? '0' : '') + (duration % 60);
                    html += '<div class="message-audio" data-audio-path="' + msg.audio_path + '" onclick="playAudio(this, \'' + msg.audio_path + '\')">';
                    html += '<div class="audio-play">▶</div>';
                    html += '<div class="audio-wave"><span></span><span></span><span></span><span></span><span></span></div>';
                    html += '<div class="audio-duration">' + durationStr + '</div>';
                    html += '</div>';
                } else if (msg.file_path) {
                    var isImage = msg.file_type && msg.file_type.startsWith('image/');
                    var fileName = msg.file_name || 'Файл';
                    var fileSize = msg.file_size ? formatFileSize(msg.file_size) : '';
                    if (isImage) {
                        html += '<img class="message-image" src="' + msg.file_path + '?t=' + Date.now() + '" loading="lazy" onclick="openImageModal(\'' + msg.file_path + '\', ' + msgId + ', \'' + msg.from_phone + '\', ' + currentGroupId + ')">';
                    } else {
                        html += '<div class="message-file" onclick="downloadFile(\'' + msg.file_path + '\', \'' + escapeHtml(fileName) + '\')">';
                        html += '<div class="file-icon">' + getFileIcon(fileName) + '</div>';
                        html += '<div class="file-info"><div class="file-name">' + escapeHtml(fileName) + '</div>';
                        if (fileSize) html += '<div class="file-size">' + fileSize + '</div>';
                        html += '</div></div>';
                    }
                } else if (msg.text) {
                    html += '<div class="message-content">' + escapeHtml(msg.text) + '</div>';
                }
                html += '<div class="message-footer">';
                html += '<span class="message-time">' + timeStr + '</span>';
                if (statusHtml) html += '<span class="message-status">' + statusHtml + '</span>';
                html += '</div></div>';
            }
            
            container.innerHTML = html;
            
            // ========== ИСПРАВЛЕННЫЙ БЛОК ПРОКРУТКИ (полностью такой же) ==========
            function scrollToBottomSmooth() {
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }
            
            var shouldScroll = wasAtBottom || hasNewMessage || messages.length === 0;
            if (shouldScroll) {
                scrollToBottomSmooth();
            }
            
            var images = container.querySelectorAll('.message-image');
            if (images.length > 0) {
                var pending = images.length;
                var onImageLoad = function() {
                    pending--;
                    if (pending === 0) {
                        scrollToBottomSmooth();
                    }
                };
                for (var i = 0; i < images.length; i++) {
                    if (images[i].complete) {
                        onImageLoad();
                    } else {
                        images[i].addEventListener('load', onImageLoad);
                        images[i].addEventListener('error', onImageLoad);
                    }
                }
            }
            
            if (shouldScroll) {
                setTimeout(function() {
                    scrollToBottomSmooth();
                }, 300);
            }
            // ================================================================
        })
        .catch(function(e) {
            console.log('Ошибка загрузки сообщений группы:', e);
        });
}

function checkNewMessages(messages) {
    if (!currentContact || !myPhone) return false;
    var incomingCount = 0;
    for (var i = 0; i < messages.length; i++) {
        if (messages[i] && messages[i].from_phone !== myPhone) incomingCount++;
    }
    var prevIncomingCount = lastIncomingCount[currentContact.phone] || 0;
    if (incomingCount > prevIncomingCount) {
        playNotificationSound();
    }
    lastIncomingCount[currentContact.phone] = incomingCount;
    return incomingCount > prevIncomingCount;
}

function checkNewGroupMessages(messages) {
    if (!currentGroupId || !myPhone) return false;
    
    var incomingCount = 0;
    for (var i = 0; i < messages.length; i++) {
        if (messages[i] && messages[i].from_phone !== myPhone) incomingCount++;
    }
    
    var prevIncomingCount = lastGroupIncomingCount[currentGroupId] || 0;
    
    if (incomingCount > prevIncomingCount) {
        playNotificationSound();
    }
    
    lastGroupIncomingCount[currentGroupId] = incomingCount;
    return incomingCount > prevIncomingCount;
}

// ========== ОТПРАВКА СООБЩЕНИЯ ==========
function sendMessage() {
    var input = document.getElementById('messageInput');
    var text = input ? input.value.trim() : '';
    if (!text) return;
    
    if (currentGroupId) {
        sendGroupMessage(text);
        input.value = '';
    } else if (currentContact) {
        sendPrivateMessage(text);
        input.value = '';
    }
}

function sendPrivateMessage(text) {
    if (!currentContact || !myPhone) return;
    
    // Сначала сохраняем в БД через API
    fetch('api.php?action=send', { method: 'POST', body: 'from=' + encodeURIComponent(myPhone) + '&to=' + encodeURIComponent(currentContact.phone) + '&text=' + encodeURIComponent(text), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                loadMessages();
                loadChats();
                // Отправляем WS уведомление
                if (wsConnected && socket) {
                    socket.emit('send_message', {
                        from: myPhone,
                        to: currentContact.phone,
                        text: text,
                        msg_id: data.msg_id,
                        time: Math.floor(Date.now() / 1000)
                    });
                }
            }
        });
}

function sendGroupMessage(text) {
    if (!currentGroupId || !myPhone) return;
    var formData = new FormData();
    formData.append('group_id', currentGroupId);
    formData.append('from', myPhone);
    formData.append('text', text);
    fetch('api.php?action=send_group_message', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadGroupMessages();
                loadChats();
                if (wsConnected && socket) {
                    socket.emit('send_group_message', {
                        from: myPhone,
                        group_id: currentGroupId,
                        text: text,
                        msg_id: data.msg_id,
                        time: Math.floor(Date.now() / 1000)
                    });
                }
            } else {
                alert('Ошибка отправки');
            }
        });
}

// ========== ГРУППОВЫЕ ФУНКЦИИ ==========
function showCreateGroupModal() {
    if (loadSettings.disable_groups === 1) {
        alert('❌ Создание групповых чатов отключено администратором');
        return;
    }
    
    groupMembersToAdd = [];
    document.getElementById('createGroupName').value = '';
    
    // Сбрасываем аватарку группы на default
    var avatarImg = document.getElementById('createGroupAvatar');
    if (avatarImg) {
        avatarImg.src = 'uploads/group_avatars/default.png';
    }
    
    var listHtml = '<div style="padding:10px; color:#8696a0;">Участники:</div>';
    listHtml += '<div id="creatorItem" class="member-item">' +
        '<div class="member-info">' +
        '<img id="creatorAvatar" class="member-avatar" src="uploads/avatars/default.png">' +
        '<span class="member-name">' + escapeHtml(localStorage.getItem('chat_name') || 'Вы') + ' (создатель)</span>' +
        '<span class="member-role">👑</span>' +
        '</div>' +
        '</div>';
    document.getElementById('createGroupMembersList').innerHTML = listHtml;
    document.getElementById('createGroupModal').classList.add('open');
    
    // ✅ Загружаем реальную аватарку создателя
    if (myPhone) {
        fetch('api.php?action=get_user_info&phone=' + encodeURIComponent(myPhone), { cache: 'no-store' })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.avatar) {
                    var creatorAvatar = document.getElementById('creatorAvatar');
                    if (creatorAvatar) {
                        creatorAvatar.src = data.avatar + '?t=' + Date.now();
                    }
                }
            })
            .catch(e => console.log('Error loading creator avatar:', e));
    }
    
    // Добавляем обработчик предпросмотра аватарки группы
    var avatarInput = document.getElementById('createGroupAvatarInput');
    if (avatarInput) {
        avatarInput.onchange = function(e) {
            previewGroupAvatar(e, 'createGroupAvatar');
        };
    }
}
// ========== ПРЕДПРОСМОТР АВАТАРКИ ГРУППЫ ==========
function previewGroupAvatar(event, targetImgId) {
    var file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        alert('Можно загружать только изображения');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        alert('Файл слишком большой (макс. 2 МБ)');
        return;
    }
    
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = document.getElementById(targetImgId);
        if (img) {
            img.src = e.target.result;
        }
    };
    reader.readAsDataURL(file);
}

function closeCreateGroupModal() {
    document.getElementById('createGroupModal').classList.remove('open');
}

function addMemberToCreateGroup() {
    var phoneInput = document.getElementById('addMemberPhone');
    var rawPhone = cleanPhone(phoneInput.value);
    
    if (rawPhone.length !== 11 || !rawPhone.startsWith('7')) {
        alert('⚠️ Введите корректный номер телефона (10 цифр после +7)');
        return;
    }
    
    var phone = rawPhone;
    
    if (groupMembersToAdd.includes(phone)) {
        alert('❌ Пользователь уже добавлен');
        return;
    }
    
    if (phone === myPhone) {
        alert('Вы не можете добавить сами себя');
        return;
    }
    
    fetch('api.php?action=search_user&phone=' + encodeURIComponent(phone))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.user) {
                groupMembersToAdd.push(phone);
                var list = document.getElementById('createGroupMembersList');
                var div = document.createElement('div');
                div.className = 'member-item';
                div.innerHTML = '<div class="member-info"><img class="member-avatar" src="' + (data.user.avatar || 'uploads/avatars/default.png') + '"><span class="member-name">' + escapeHtml(data.user.name) + '</span></div><span class="remove-member" onclick="this.parentElement.remove(); groupMembersToAdd = groupMembersToAdd.filter(p => p !== \'' + phone + '\')">✖</span>';
                list.appendChild(div);
                phoneInput.value = '';
            } else {
                alert('❌ Пользователь не зарегистрирован');
            }
        })
        .catch(() => {
            alert('❌ Ошибка при поиске пользователя');
        });
}

function createGroup() {
    var groupName = document.getElementById('createGroupName').value.trim();
    if (!groupName) {
        alert('Введите название группы');
        return;
    }
    
    var avatarFile = document.getElementById('createGroupAvatarInput').files[0];
    
    var finishCreation = function(avatarBase64) {
        var formData = new FormData();
        formData.append('creator_phone', myPhone);
        formData.append('group_name', groupName);
        formData.append('members', JSON.stringify(groupMembersToAdd));
        if (avatarBase64) formData.append('avatar_base64', avatarBase64);
        
        fetch('api.php?action=create_group', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeCreateGroupModal();
                loadChats();
                if (wsConnected && socket && groupMembersToAdd.length > 0) {
                    // Оповещаем добавленных участников
                    groupMembersToAdd.forEach(function(phone) {
                        socket.emit('new_chats', { to: phone, reason: 'new_group' });
                    });
                }
            } else {
                alert('❌ Ошибка: ' + (data.error || 'Неизвестная'));
            }
        })
        .catch(err => {
            alert('❌ Ошибка соединения: ' + err.message);
        });
    };
    
    if (avatarFile) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var base64 = e.target.result.split(',')[1];
            finishCreation(base64);
        };
        reader.readAsDataURL(avatarFile);
    } else {
        finishCreation(null);
    }
}

function showGroupInfoModal() {
    if (!currentGroupId) {
        alert('Группа не выбрана');
        return;
    }
    
    if (wsConnected && socket) {
        socket.emit('join_group', { group_id: parseInt(currentGroupId) });
    }
    
    fetch('api.php?action=get_group_info&group_id=' + currentGroupId + '&my_phone=' + encodeURIComponent(myPhone))
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Ошибка загрузки группы');
                return;
            }
            currentGroupRole = data.my_role;
            currentGroupMembers = data.members;
            document.getElementById('groupInfoTitle').innerHTML = '👥 ' + escapeHtml(data.group.name);
            document.getElementById('groupInfoAvatar').src = data.group.avatar + '?t=' + Date.now();
            document.getElementById('groupInfoName').value = data.group.name;
            
            // ✅ Добавляем обработчик предпросмотра аватарки при редактировании
            var avatarInput = document.getElementById('groupInfoAvatarInput');
            if (avatarInput) {
                avatarInput.onchange = function(e) {
                    previewGroupAvatar(e, 'groupInfoAvatar');
                };
            }
            
            var isAdmin = (currentGroupRole === 'admin');
            var nameInput = document.getElementById('groupInfoName');
            nameInput.readOnly = !isAdmin;
            nameInput.style.backgroundColor = isAdmin ? '#2a3942' : '#1e2a32';
            
            var avatarOverlay = document.querySelector('#groupInfoModal .group-avatar-overlay');
            if (avatarOverlay) {
                if (isAdmin) {
                    avatarOverlay.style.display = 'flex';
                    avatarOverlay.onclick = function() { document.getElementById('groupInfoAvatarInput').click(); };
                } else {
                    avatarOverlay.style.display = 'none';
                }
            }
            
            var membersHtml = '';
            for (var m of data.members) {
                membersHtml += '<div class="member-item">' +
                    '<div class="member-info">' +
                    '<img class="member-avatar" src="' + (m.avatar || 'uploads/avatars/default.png') + '">' +
                    '<span class="member-name">' + escapeHtml(m.name) + '</span>' +
                    (m.role === 'admin' ? '<span class="member-role">👑</span>' : '') +
                    '</div>';
                if (currentGroupRole === 'admin' && m.user_phone !== myPhone) {
                    membersHtml += '<span class="remove-member" onclick="removeMemberFromGroup(\'' + m.user_phone + '\')">✖</span>';
                }
                membersHtml += '</div>';
            }
            document.getElementById('groupMembersList').innerHTML = membersHtml;
            
            var addSection = document.getElementById('groupAddMemberSection');
            if (currentGroupRole === 'admin') {
                addSection.style.display = 'block';
                document.getElementById('leaveGroupBtn').innerHTML = '🔨 Удалить группу';
            } else {
                addSection.style.display = 'none';
                document.getElementById('leaveGroupBtn').innerHTML = '🚪 Выйти из группы';
            }
            document.getElementById('groupInfoModal').classList.add('open');
        });
}



function closeGroupInfoModal() {
    document.getElementById('groupInfoModal').classList.remove('open');
}

function addMemberToGroup() {
    var phoneInput = document.getElementById('groupAddMemberPhone');
    var rawPhone = cleanPhone(phoneInput.value);
    
    if (rawPhone.length !== 11 || !rawPhone.startsWith('7')) {
        alert('⚠️ Введите корректный номер телефона (10 цифр после +7)');
        return;
    }
    
    var phone = rawPhone;
    
    if (phone === myPhone) {
        alert('Вы не можете добавить сами себя');
        return;
    }
    
    fetch('api.php?action=add_group_member', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'group_id=' + currentGroupId + '&admin_phone=' + encodeURIComponent(myPhone) + '&new_phone=' + encodeURIComponent(phone)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Участник добавлен');
            showGroupInfoModal();
            loadChats();
            if (wsConnected && socket) {
                socket.emit('new_chats', { to: phone, reason: 'new_member', group_id: currentGroupId });
            }
        } else {
            alert('❌ Ошибка: ' + (data.error || 'Неизвестная'));
        }
        document.getElementById('groupAddMemberPhone').value = '';
    });
}

function updateGroupAvatar(input) {
    if (!currentGroupId) return;
    if (currentGroupRole !== 'admin') {
        alert('Только администратор может менять аватар группы');
        return;
    }
    
    var file = input.files[0];
    if (!file) return;
    
    if (file.size > 2 * 1024 * 1024) {
        alert('Файл слишком большой (макс. 2 МБ)');
        return;
    }
    
    if (!file.type.startsWith('image/')) {
        alert('Можно загружать только изображения');
        return;
    }
    
    var newGroupName = document.getElementById('groupInfoName').value.trim();
    
    var reader = new FileReader();
    reader.onload = function(e) {
        var base64 = e.target.result.split(',')[1];
        
        var formData = new FormData();
        formData.append('group_id', currentGroupId);
        formData.append('admin_phone', myPhone);
        formData.append('avatar_base64', base64);
        if (newGroupName) formData.append('group_name', newGroupName);
        
        fetch('api.php?action=update_group', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Аватар группы обновлен');
                showGroupInfoModal();
                loadChats();
                if (currentGroupId) {
                    loadGroupMessages();
                }
                if (wsConnected && socket) {
                    socket.emit('new_avatar', { group_id: currentGroupId, avatar: data.avatar_path });
                }
            } else {
                alert('❌ Ошибка: ' + (data.error || 'Неизвестная'));
            }
        });
    };
    reader.readAsDataURL(file);
    input.value = '';
}

function updateGroupName() {
    if (!currentGroupId) return;
    if (currentGroupRole !== 'admin') {
        alert('Только администратор может менять название группы');
        return;
    }
    
    var newName = document.getElementById('groupInfoName').value.trim();
    if (!newName) {
        alert('Введите название группы');
        return;
    }
    
    var formData = new FormData();
    formData.append('group_id', currentGroupId);
    formData.append('admin_phone', myPhone);
    formData.append('group_name', newName);
    
    fetch('api.php?action=update_group', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Название группы обновлено');
            showGroupInfoModal();
            loadChats();
            if (currentGroupId) {
                document.getElementById('contactName').innerHTML = escapeHtml(newName);
            }
            if (wsConnected && socket) {
                socket.emit('new_chats', { reason: 'group_updated', group_id: currentGroupId });
            }
        } else {
            alert('❌ Ошибка: ' + (data.error || 'Неизвестная'));
            showGroupInfoModal();
        }
    });
}

function removeMemberFromGroup(targetPhone) {
    if (!confirm('Исключить участника?')) return;
    fetch('api.php?action=remove_group_member', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'group_id=' + currentGroupId + '&actor_phone=' + encodeURIComponent(myPhone) + '&target_phone=' + encodeURIComponent(targetPhone)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.group_deleted) {
                alert('Группа удалена, так как не осталось участников');
                closeGroupInfoModal();
                if (currentGroupId) {
                    currentGroupId = null;
                    document.getElementById('contactName').innerHTML = 'Выберите чат';
                    document.getElementById('messagesContainer').innerHTML = '';
                    document.getElementById('groupMenuBtn').style.display = 'none';
                }
                loadChats();
                if (wsConnected && socket) {
                    socket.emit('new_chats', { reason: 'group_deleted', group_id: currentGroupId });
                }
            } else {
                showGroupInfoModal();
                loadChats();
                if (wsConnected && socket) {
                    socket.emit('new_chats', { to: targetPhone, reason: 'removed_from_group', group_id: currentGroupId });
                }
            }
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная'));
        }
    });
}


function leaveOrDeleteGroup() {
    if (currentGroupRole === 'admin') {
        if (!confirm('Удалить группу для всех?')) return;
        
        // Выход из WS комнаты
        if (wsConnected && socket) {
            socket.emit('leave_group', { group_id: parseInt(currentGroupId) });
        }
        
        fetch('api.php?action=delete_group', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'group_id=' + currentGroupId + '&creator_phone=' + encodeURIComponent(myPhone)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Группа удалена');
                closeGroupInfoModal();
                if (currentGroupId) {
                    currentGroupId = null;
                    document.getElementById('contactName').innerHTML = 'Выберите чат';
                    document.getElementById('messagesContainer').innerHTML = '';
                    document.getElementById('groupMenuBtn').style.display = 'none';
                }
                loadChats();
                if (wsConnected && socket) {
                    socket.emit('new_chats', { reason: 'group_deleted', group_id: currentGroupId });
                }
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная'));
            }
        });
    } else {
        if (!confirm('Выйти из группы?')) return;
        
        // Выход из WS комнаты
        if (wsConnected && socket) {
            socket.emit('leave_group', { group_id: parseInt(currentGroupId) });
        }
        
        fetch('api.php?action=remove_group_member', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'group_id=' + currentGroupId + '&actor_phone=' + encodeURIComponent(myPhone) + '&target_phone=' + encodeURIComponent(myPhone)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Вы вышли из группы');
                closeGroupInfoModal();
                if (currentGroupId) {
                    currentGroupId = null;
                    document.getElementById('contactName').innerHTML = 'Выберите чат';
                    document.getElementById('messagesContainer').innerHTML = '';
                    document.getElementById('groupMenuBtn').style.display = 'none';
                }
                loadChats();
                if (wsConnected && socket) {
                    socket.emit('new_chats', { reason: 'left_group', group_id: currentGroupId });
                }
            } else {
                alert('Ошибка');
            }
        });
    }
}




// ========== РАССЫЛКА ==========
function checkBroadcastMessage() {
    if (!myPhone) { return; }
    var timestamp = Date.now();
    fetch('broadcast_api.php?action=get_active_broadcast&user_phone=' + encodeURIComponent(myPhone) + '&_t=' + timestamp, { cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.has_broadcast && data.message && !isModalShowing) {
                showBroadcastModal(data.broadcast_id, data.message, data.from_phone);
            }
        })
        .catch(function(e) {});
}

function showBroadcastModal(broadcastId, message, fromPhone) {
    if (isModalShowing) return;
    isModalShowing = true;
    playNotificationSound();
    var uniqueId = 'broadcastModalOverlay_' + broadcastId + '_' + Date.now();
    var modalHtml = '<div id="' + uniqueId + '" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:20000; display:flex; align-items:center; justify-content:center;">' +
        '<div style="background:#fff; border-radius:20px; padding:25px; text-align:center; width:85%; max-width:320px; color:#111b21;">' +
        '<div style="font-size:48px; margin-bottom:15px;">📢</div>' +
        '<h3 style="margin-bottom:10px; color:#111b21;">Информационное сообщение</h3>' +
        '<div style="font-size:13px; color:#666; margin-bottom:15px;">От: ' + escapeHtml(fromPhone) + '</div>' +
        '<div style="margin-bottom:20px; line-height:1.5; text-align:left; word-break:break-word;">' + escapeHtml(message).replace(/\n/g, '<br>') + '</div>' +
        '<button class="closeBroadcastBtn" style="background:#00a884; border:none; padding:12px 20px; border-radius:10px; color:white; font-size:16px; cursor:pointer; width:100%;">Понятно</button>' +
        '</div></div>';
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    var modalOverlay = document.getElementById(uniqueId);
    function closeModal() {
        if (modalOverlay && modalOverlay.remove) modalOverlay.remove();
        isModalShowing = false;
        fetch('broadcast_api.php?action=mark_broadcast_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'user_phone=' + encodeURIComponent(myPhone) + '&broadcast_id=' + broadcastId
        }).catch(function(e) {});
    }
    if (modalOverlay) {
        var closeBtn = modalOverlay.querySelector('.closeBroadcastBtn');
        if (closeBtn) closeBtn.onclick = function(e) { e.preventDefault(); e.stopPropagation(); closeModal(); };
        modalOverlay.onclick = function(e) { if (e.target === modalOverlay) closeModal(); };
    }
}

// ========== НАСТРОЙКИ ПОЛЛИНГА ==========
function loadLoadSettings() {
    fetch('api.php?action=get_load_settings', { cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(settings) {
            loadSettings = settings;
            // loadCallSettings() был удалён – настройки звонков загружаются отдельно через loadCallSettingsFromServer()
            updateGroupButtonVisibility();
			
			           // ✅ ПРИМЕНЯЕМ ФИЛЬТРАЦИЮ ГРУПП СРАЗУ
            if (loadSettings.disable_groups === 1) {
                loadChats(); // перезагружаем чаты, группы исчезнут
                if (currentGroupId) {
                    currentGroupId = null;
                    document.getElementById('contactName').innerHTML = 'Выберите чат';
                    document.getElementById('messagesContainer').innerHTML = '';
                    document.getElementById('groupMenuBtn').style.display = 'none';
                }
            }
			
            
            // ТОЛЬКО ПОСЛЕ ЗАГРУЗКИ НАСТРОЕК - ПОДКЛЮЧАЕМ WS
            if (loadSettings.messaging_mode === 'websocket' && !socket && !wsConnected) {
                connectWebSocket();
            }
            if (loadSettings.messaging_mode === 'mysql_only' && socket) {
                if (socket.disconnect) socket.disconnect();
                socket = null;
                wsConnected = false;
                enableFallbackMode();
            }
            if (loadSettings.messaging_mode === 'websocket' && wsConnected) {
                disableFallbackTimers();
            }
            
            startPollingIntervals();
        })
        .catch(function(e) { 
            console.log('Error loading load settings:', e);
            startPollingIntervals();
        });
}

function startPollingIntervals() {
    if (chatsIntervalTimer) clearInterval(chatsIntervalTimer);
    if (broadcastIntervalTimer) clearInterval(broadcastIntervalTimer);
    if (messagesIntervalTimer) clearInterval(messagesIntervalTimer);
    
    if (loadSettings.chats_poll_interval > 0) {
        chatsIntervalTimer = setInterval(function() { loadChats(); }, loadSettings.chats_poll_interval * 1000);
    }
    if (loadSettings.broadcast_poll_interval > 0) {
        broadcastIntervalTimer = setInterval(function() { checkBroadcastMessage(); }, loadSettings.broadcast_poll_interval * 1000);
    }
    if (loadSettings.messages_poll_interval > 0) {
        messagesIntervalTimer = setInterval(function() {
            if (currentContact && !isLoadingMessages) { loadMessages(); }
            if (currentGroupId) { loadGroupMessages(); }
        }, loadSettings.messages_poll_interval * 1000);
    }
}

// ========== НАСТРОЙКИ ЦВЕТОВ И ФОНА ==========
function loadColors() {
    fetch('admin.php?action=get_colors', { cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(colors) {
            if (colors && !colors.error) {
                var theme = localStorage.getItem('theme') || 'dark';
                if (theme === 'dark') {
                    if (colors.dark_bg) document.querySelector('.chat-area').style.background = colors.dark_bg;
                    if (colors.dark_sidebar_bg) document.querySelector('.sidebar').style.background = colors.dark_sidebar_bg;
                    if (colors.dark_header_bg) document.querySelectorAll('.sidebar-header, .chat-header').forEach(function(h) { h.style.background = colors.dark_header_bg; });
                    if (colors.dark_text) document.querySelectorAll('.contact-name, #myName, .chat-header-name').forEach(function(t) { t.style.color = colors.dark_text; });
                    if (colors.dark_message_in_bg) document.querySelectorAll('.message.in').forEach(function(m) { m.style.background = colors.dark_message_in_bg; });
                    if (colors.dark_message_out_bg) document.querySelectorAll('.message.out').forEach(function(m) { m.style.background = colors.dark_message_out_bg; });
                    if (colors.dark_input_bg) document.querySelectorAll('.input-area textarea, .attach-button, .mic-button').forEach(function(i) { i.style.background = colors.dark_input_bg; });
                } else {
                    if (colors.light_bg) document.querySelector('.chat-area').style.background = colors.light_bg;
                    if (colors.light_sidebar_bg) document.querySelector('.sidebar').style.background = colors.light_sidebar_bg;
                    if (colors.light_header_bg) document.querySelectorAll('.sidebar-header, .chat-header').forEach(function(h) { h.style.background = colors.light_header_bg; });
                    if (colors.light_text) document.querySelectorAll('.contact-name, #myName, .chat-header-name').forEach(function(t) { t.style.color = colors.light_text; });
                    if (colors.light_message_in_bg) document.querySelectorAll('.message.in').forEach(function(m) { m.style.background = colors.light_message_in_bg; });
                    if (colors.light_message_out_bg) document.querySelectorAll('.message.out').forEach(function(m) { m.style.background = colors.light_message_out_bg; });
                    if (colors.light_input_bg) document.querySelectorAll('.input-area textarea, .attach-button, .mic-button').forEach(function(i) { i.style.background = colors.light_input_bg; });
                }
                var showBg = localStorage.getItem('show_chat_bg') !== 'false';
                if (showBg) {
                    var bgImage = colors.chat_background || 'fonDefault.png';
                    var messagesContainer = document.getElementById('messagesContainer');
                    if (messagesContainer) {
                        messagesContainer.style.backgroundImage = "url('" + bgImage + "?t=' + Date.now())";
                        messagesContainer.style.backgroundSize = "cover";
                        messagesContainer.style.backgroundPosition = "center";
                    }
                }
            }
        });
}

// ========== ВОССТАНОВЛЕНИЕ СЕССИИ ==========
function checkAndRestoreSession() {
    var savedPhone = localStorage.getItem('chat_phone');
    var savedName = localStorage.getItem('chat_name');
    var savedUserId = localStorage.getItem('chat_user_id');
    var savedEmail = localStorage.getItem('user_email');
    
    if (savedPhone && savedName && savedUserId) {
        myPhone = savedPhone;
        myUserId = savedUserId;
        myEmail = savedEmail;
        
        fetch('api.php?action=update_online', {
            method: 'POST',
            body: 'phone=' + encodeURIComponent(myPhone) + '&is_online=1',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).catch(function(e) {});
        
        document.getElementById('phoneInput').value = formatPhoneMask(savedPhone);
        document.getElementById('nameInput').value = savedName;
        if (savedEmail) document.getElementById('emailInput').value = savedEmail;
        
        document.getElementById('loginScreen').style.display = 'none';
        document.getElementById('mainScreen').classList.remove('hidden');
        loadColors();
        loadLoadSettings();
        loadCallSettingsFromServer(); // 📞 Загружаем настройки звонков
        loadChats();
        setTimeout(function() { checkBroadcastMessage(); }, 1000);
        return true;
    }
    
    var cookiePhone = getCookie('user_phone');
    var cookieName = getCookie('user_name');
    
    if (cookiePhone && cookieName) {
        fetch('api.php?action=auth', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'phone=' + encodeURIComponent(cookiePhone) + '&name=' + encodeURIComponent(cookieName)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                myPhone = cookiePhone;
                myUserId = data.user_id;
                localStorage.setItem('chat_phone', cookiePhone);
                localStorage.setItem('chat_name', cookieName);
                localStorage.setItem('chat_user_id', data.user_id);
                
                document.getElementById('loginScreen').style.display = 'none';
                document.getElementById('mainScreen').classList.remove('hidden');
                loadColors();
                loadLoadSettings();
                loadCallSettingsFromServer(); // 📞 Загружаем настройки звонков
                loadChats();
                setTimeout(function() { checkBroadcastMessage(); }, 1000);
                return true;
            } else {
                return false;
            }
        });
        return false;
    }
    
    return false;
}

function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

// ========== НОВАЯ ЛОГИКА ВХОДА ==========
function login() {
    var phoneRaw = document.getElementById('phoneInput').value;
    var phone = cleanPhone(phoneRaw);
    var name = document.getElementById('nameInput').value;
    var email = document.getElementById('emailInput').value;
    
    if (!phone || phone.length < 11) {
        alert('Введите корректный номер телефона (10 цифр после +7)');
        return;
    }
    
    if (!email || !email.includes('@')) {
        alert('Введите корректный email');
        return;
    }
    
    var formData = new FormData();
    formData.append('phone', phone);
    formData.append('name', name);
    formData.append('email', email);
    
    fetch('api.php?action=send_verification', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { 
        if (!res.ok) {
            throw new Error('HTTP error ' + res.status);
        }
        return res.json();
    })
    .then(function(data) {
        if (data.success) {
            showCodeModal();
            startCodeTimer(600);
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось отправить код'));
        }
    })
    .catch(function(e) {
        console.error('Error:', e);
        alert('Ошибка соединения: ' + e.message);
    });
}

function showCodeModal() {
    if (codeTimerInterval) {
        clearInterval(codeTimerInterval);
        codeTimerInterval = null;
    }
    document.getElementById('codeModal').style.display = 'flex';
    document.getElementById('codeInput').value = '';
    document.getElementById('codeInput').focus();
}

function closeCodeModal() {
    if (codeTimerInterval) {
        clearInterval(codeTimerInterval);
        codeTimerInterval = null;
    }
    document.getElementById('codeModal').style.display = 'none';
}

function startCodeTimer(seconds) {
    var timerDiv = document.getElementById('codeTimer');
    if (!timerDiv) return;
    
    if (codeTimerInterval) {
        clearInterval(codeTimerInterval);
        codeTimerInterval = null;
    }
    
    var remaining = seconds;
    timerDiv.innerHTML = 'Код действителен: ' + formatTimerTime(remaining);
    
    codeTimerInterval = setInterval(function() {
        remaining--;
        if (remaining <= 0) {
            clearInterval(codeTimerInterval);
            codeTimerInterval = null;
            timerDiv.innerHTML = '⏰ Код истёк. Запросите новый.';
        } else {
            timerDiv.innerHTML = 'Код действителен: ' + formatTimerTime(remaining);
        }
    }, 1000);
}

function verifyCode() {
    var code = document.getElementById('codeInput').value.trim();
    
    if (!code || code.length !== 6) {
        alert('Введите 6-значный код');
        return;
    }
    
    var formData = new FormData();
    formData.append('code', code);
    
    fetch('api.php?action=verify_code', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            myPhone = data.phone;
            myUserId = data.user_id;
            
            localStorage.setItem('chat_phone', data.phone);
            localStorage.setItem('chat_name', document.getElementById('nameInput').value);
            localStorage.setItem('chat_user_id', data.user_id);
            localStorage.setItem('user_email', document.getElementById('emailInput').value);
            localStorage.setItem('login_saved', 'true');
            
            closeCodeModal();
            document.getElementById('loginScreen').style.display = 'none';
            document.getElementById('mainScreen').classList.remove('hidden');
            loadColors();
            loadLoadSettings();
            loadCallSettingsFromServer(); // 📞 Загружаем настройки звонков
            loadChats();
            setTimeout(function() { checkBroadcastMessage(); }, 1000);
        } else {
            alert('Ошибка: ' + (data.error || 'Неверный код'));
        }
    })
    .catch(e => {
        alert('Ошибка соединения: ' + e.message);
    });
}

function keepAlive() {
    if (myPhone) {
        fetch('api.php?action=update_online', {
            method: 'POST',
            body: 'phone=' + encodeURIComponent(myPhone) + '&is_online=1',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).catch(function(e) {});
        localStorage.setItem('last_active', Date.now().toString());
    }
}

setInterval(keepAlive, 900000);
document.addEventListener('click', keepAlive);
document.addEventListener('touchstart', keepAlive);

function logout() {
    if (chatsIntervalTimer) clearInterval(chatsIntervalTimer);
    if (broadcastIntervalTimer) clearInterval(broadcastIntervalTimer);
    if (messagesIntervalTimer) clearInterval(messagesIntervalTimer);
    
    if (socket && socket.disconnect) socket.disconnect();
    socket = null;
    wsConnected = false;
    
    localStorage.removeItem('chat_phone');
    localStorage.removeItem('chat_name');
    localStorage.removeItem('chat_user_id');
    myPhone = null;
    myUserId = null;
    currentContact = null;
    currentGroupId = null;
    document.getElementById('loginScreen').style.display = 'block';
    document.getElementById('mainScreen').classList.add('hidden');
    document.getElementById('phoneInput').value = '';
    document.getElementById('nameInput').value = '';
    document.getElementById('messageInput').value = '';
    document.getElementById('contactsList').innerHTML = '';
    document.getElementById('messagesContainer').innerHTML = '';
    closeSettings();
    hideSidebar();
}


// ========== БАННЕР ОФЛАЙН-РЕЖИМА ==========
function showOfflineBanner(message) {
    var banner = document.getElementById('offlineBanner');
    var msgSpan = document.getElementById('offlineBannerMessage');
    if (!banner) return;
    if (msgSpan) msgSpan.innerHTML = message || '⚠️ Потеряно соединение, используется резервный режим';
    banner.style.display = 'block';
    
    if (offlineBannerHideTimer) clearTimeout(offlineBannerHideTimer);
    offlineBannerHideTimer = setTimeout(function() {
        if (banner && banner.style.display === 'block') {
            banner.style.opacity = '0.8';
        }
    }, 15000);
}

function hideOfflineBanner() {
    var banner = document.getElementById('offlineBanner');
    if (banner) banner.style.display = 'none';
    if (offlineBannerHideTimer) clearTimeout(offlineBannerHideTimer);
}

// ========== ПРОДЛЕНИЕ PHP-СЕССИИ ==========
function startSessionKeepAlive() {
    setInterval(function() {
        if (myPhone) {
            fetch('api.php?action=keep_alive', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'include'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    console.log('🔄 Session extended for', data.phone);
                } else {
                    console.log('⚠️ Session expired');
                }
            })
            .catch(function(e) { console.log('Keep-alive error:', e); });
        }
    }, 1200000); // 20 минуты
}

// ========== ОТСЛЕЖИВАНИЕ БЕЗДЕЙСТВИЯ ==========
function startInactivityWatch() {
    var INACTIVITY_TIMEOUT = 2 * 60 * 60 * 1000; // 2 часа
    
    function resetActivityTimer() {
        lastUserActivity = Date.now();
    }
    
    document.addEventListener('click', resetActivityTimer);
    document.addEventListener('touchstart', resetActivityTimer);
    document.addEventListener('keydown', resetActivityTimer);
    document.addEventListener('scroll', resetActivityTimer);
    
    setInterval(function() {
        var inactiveTime = Date.now() - lastUserActivity;
        if (inactiveTime > INACTIVITY_TIMEOUT && myPhone) {
            console.log('💤 Долгое бездействие (' + Math.round(inactiveTime/60000) + ' мин), перезагружаем страницу');
            location.reload();
        }
    }, 60000);
}


// ========== ИНИЦИАЛИЗАЦИЯ ==========
window.onload = function() {
    var phoneInput = document.getElementById('phoneInput');
    if (phoneInput) phoneInput.addEventListener('input', function() { phoneMask(this); });
    
    var searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.addEventListener('input', function() { searchPhoneMask(this); searchUserDelayed(); });
    
    var micButton = document.getElementById('micButton');
    if (micButton) {
        micButton.addEventListener('mousedown', function(e) { e.preventDefault(); startRecording(); });
        micButton.addEventListener('mouseup', function() { stopRecording(); });
        micButton.addEventListener('touchstart', function(e) { e.preventDefault(); startRecording(); });
        micButton.addEventListener('touchend', function() { stopRecording(); });
    }
    
var messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('input', function() { 
        if (window.innerWidth <= 768) {
            this.style.height = 'auto'; 
            this.style.height = Math.min(this.scrollHeight, 100) + 'px'; 
        }
    });
    
    // ========== СТАТУС "ПЕЧАТАЕТ" ==========
    var typingTimeout = null;
    messageInput.addEventListener('keydown', function() {
        if (!wsConnected || !socket) return;
        
        if (currentContact) {
            socket.emit('typing_start', { from: myPhone, to: currentContact.phone });
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function() {
                socket.emit('typing_stop', { from: myPhone, to: currentContact.phone });
            }, 1000);
        } else if (currentGroupId) {
            socket.emit('group_typing_start', { from: myPhone, group_id: currentGroupId });
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function() {
                socket.emit('group_typing_stop', { from: myPhone, group_id: currentGroupId });
            }, 1000);
        }
    });
}
    
    var fileInput = document.getElementById('fileInput');
    if (fileInput && !fileInput.onchange) {
        fileInput.onchange = function() { uploadFile(this); };
    }
    
    loadBottomMenu();
    setTheme(localStorage.getItem('theme') || 'dark');
    applyChatBg();
    
    if (!checkAndRestoreSession()) console.log('Нет сохраненной сессии');
    
    setInterval(function() { 
        if (myPhone) fetch('api.php?action=update_online', { method: 'POST', body: 'phone=' + encodeURIComponent(myPhone) + '&is_online=1', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }).catch(function(e) {}); 
    }, 30000);
    
    window.addEventListener('beforeunload', function() { 
        if (myPhone) { 
            var fd = new FormData(); 
            fd.append('phone', myPhone); 
            fd.append('is_online', 0); 
            navigator.sendBeacon('api.php?action=update_online', fd); 
        } 
    });
	
	
// ВНУТРИ window.onload или DOMContentLoaded, после существующего кода:
startSessionKeepAlive();
startInactivityWatch();	

// Инициализация кнопок звонка
var hangupBtn = document.getElementById('callHangupBtn');
if (hangupBtn) hangupBtn.onclick = function() { endCall(); };
var micBtnCall = document.getElementById('callMicBtn');
if (micBtnCall) micBtnCall.onclick = toggleMicrophone;
var speakerBtn = document.getElementById('callSpeakerBtn');
if (speakerBtn) speakerBtn.onclick = toggleSpeaker;
var videoBtn = document.getElementById('callVideoBtn');
if (videoBtn) videoBtn.onclick = toggleCamera;
	
};



document.addEventListener('keydown', function(e) { 
    if (e.key === 'Enter' && !e.shiftKey && document.activeElement.id === 'messageInput') { 
        e.preventDefault(); 
        sendMessage(); 
    } 
});

// ========== ПОДСКАЗКИ ДЛЯ ГРУПП ==========
var groupNameTimeout = null;
var memberCheckTimeout = null;
var memberCheckForGroupTimeout = null;

function checkGroupName() {
    var groupName = document.getElementById('createGroupName').value.trim();
    var hintDiv = document.getElementById('groupNameHint');
    var submitBtn = document.getElementById('createGroupSubmitBtn');
    
    if (groupNameTimeout) clearTimeout(groupNameTimeout);
    
    if (groupName.length === 0) {
        hintDiv.className = 'input-hint';
        hintDiv.innerHTML = '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
        }
        return;
    }
    
    groupNameTimeout = setTimeout(function() {
        fetch('api.php?action=check_group_name&name=' + encodeURIComponent(groupName))
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    hintDiv.className = 'input-hint error';
                    hintDiv.innerHTML = '❌ Группа с таким названием уже существует';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('disabled');
                    }
                } else {
                    hintDiv.className = 'input-hint success';
                    hintDiv.innerHTML = '✅ Название доступно';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('disabled');
                    }
                }
            })
            .catch(() => {
                hintDiv.className = 'input-hint error';
                hintDiv.innerHTML = '❌ Ошибка проверки';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('disabled');
                }
            });
    }, 500);
}

function checkMemberForCreate() {
    var phoneInput = document.getElementById('addMemberPhone');
    var rawPhone = cleanPhone(phoneInput.value);
    var hintDiv = document.getElementById('addMemberHint');
    var addBtn = document.getElementById('addMemberBtn');
    
    if (memberCheckTimeout) clearTimeout(memberCheckTimeout);
    
    if (rawPhone.length > 0 && rawPhone.length < 11) {
        hintDiv.className = 'input-hint warning';
        hintDiv.innerHTML = '⚠️ Введите 10 цифр после +7 <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'addMemberPhone\', \'addMemberHint\')">✖</span>';
        hintDiv.style.cursor = 'pointer';
        hintDiv.onclick = function() { clearPhoneInput('addMemberPhone', 'addMemberHint'); };
        if (addBtn) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        }
        return;
    }
    
    if (rawPhone.length !== 11 || !rawPhone.startsWith('7')) {
        hintDiv.className = 'input-hint';
        hintDiv.innerHTML = '';
        hintDiv.onclick = null;
        if (addBtn) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        }
        return;
    }
    
    if (rawPhone === myPhone) {
        hintDiv.className = 'input-hint error';
        hintDiv.innerHTML = '❌ Вы не можете добавить сами себя <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'addMemberPhone\', \'addMemberHint\')">✖</span>';
        hintDiv.style.cursor = 'pointer';
        hintDiv.onclick = function() { clearPhoneInput('addMemberPhone', 'addMemberHint'); };
        if (addBtn) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        }
        return;
    }
    
    if (groupMembersToAdd.includes(rawPhone)) {
        hintDiv.className = 'input-hint error';
        hintDiv.innerHTML = '❌ Пользователь уже добавлен <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'addMemberPhone\', \'addMemberHint\')">✖</span>';
        hintDiv.style.cursor = 'pointer';
        hintDiv.onclick = function() { clearPhoneInput('addMemberPhone', 'addMemberHint'); };
        if (addBtn) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        }
        return;
    }
    
    memberCheckTimeout = setTimeout(function() {
        fetch('api.php?action=search_user&phone=' + encodeURIComponent(rawPhone))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.user) {
                    hintDiv.className = 'input-hint success';
                    hintDiv.innerHTML = '✅ ' + escapeHtml(data.user.name) + ' - можно добавить';
                    hintDiv.onclick = null;
                    if (addBtn) {
                        addBtn.style.opacity = '1';
                        addBtn.style.pointerEvents = 'auto';
                    }
                } else {
                    hintDiv.className = 'input-hint error';
                    hintDiv.innerHTML = '❌ Пользователь не зарегистрирован <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'addMemberPhone\', \'addMemberHint\')">✖</span>';
                    hintDiv.style.cursor = 'pointer';
                    hintDiv.onclick = function() { clearPhoneInput('addMemberPhone', 'addMemberHint'); };
                    if (addBtn) {
                        addBtn.style.opacity = '0.5';
                        addBtn.style.pointerEvents = 'none';
                    }
                }
            })
            .catch(() => {
                hintDiv.className = 'input-hint error';
                hintDiv.innerHTML = '❌ Ошибка проверки <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'addMemberPhone\', \'addMemberHint\')">✖</span>';
                hintDiv.style.cursor = 'pointer';
                hintDiv.onclick = function() { clearPhoneInput('addMemberPhone', 'addMemberHint'); };
                if (addBtn) {
                    addBtn.style.opacity = '0.5';
                    addBtn.style.pointerEvents = 'none';
                }
            });
    }, 600);
}

function checkMemberForGroup() {
    var phoneInput = document.getElementById('groupAddMemberPhone');
    var rawPhone = cleanPhone(phoneInput.value);
    var hintDiv = document.getElementById('groupAddMemberHint');
    var addBtn = document.getElementById('groupAddMemberBtn');
    
    if (memberCheckForGroupTimeout) clearTimeout(memberCheckForGroupTimeout);
    
    if (rawPhone.length > 0 && rawPhone.length < 11) {
        hintDiv.className = 'input-hint warning';
        hintDiv.innerHTML = '⚠️ Введите 10 цифр после +7 <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'groupAddMemberPhone\', \'groupAddMemberHint\')">✖</span>';
        hintDiv.style.cursor = 'pointer';
        hintDiv.onclick = function() { clearPhoneInput('groupAddMemberPhone', 'groupAddMemberHint'); };
        if (addBtn) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        }
        return;
    }
    
    if (rawPhone.length !== 11 || !rawPhone.startsWith('7')) {
        hintDiv.className = 'input-hint';
        hintDiv.innerHTML = '';
        hintDiv.onclick = null;
        if (addBtn) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        }
        return;
    }
    
    if (rawPhone === myPhone) {
        hintDiv.className = 'input-hint error';
        hintDiv.innerHTML = '❌ Вы не можете добавить сами себя <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'groupAddMemberPhone\', \'groupAddMemberHint\')">✖</span>';
        hintDiv.style.cursor = 'pointer';
        hintDiv.onclick = function() { clearPhoneInput('groupAddMemberPhone', 'groupAddMemberHint'); };
        if (addBtn) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        }
        return;
    }
    
    memberCheckForGroupTimeout = setTimeout(function() {
        var isAlreadyMember = false;
        if (currentGroupMembers) {
            for (var i = 0; i < currentGroupMembers.length; i++) {
                if (currentGroupMembers[i].user_phone === rawPhone) {
                    isAlreadyMember = true;
                    break;
                }
            }
        }
        
        if (isAlreadyMember) {
            hintDiv.className = 'input-hint error';
            hintDiv.innerHTML = '❌ Пользователь уже в группе <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'groupAddMemberPhone\', \'groupAddMemberHint\')">✖</span>';
            hintDiv.style.cursor = 'pointer';
            hintDiv.onclick = function() { clearPhoneInput('groupAddMemberPhone', 'groupAddMemberHint'); };
            if (addBtn) {
                addBtn.style.opacity = '0.5';
                addBtn.style.pointerEvents = 'none';
            }
            return;
        }
        
        fetch('api.php?action=search_user&phone=' + encodeURIComponent(rawPhone))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.user) {
                    hintDiv.className = 'input-hint success';
                    hintDiv.innerHTML = '✅ ' + escapeHtml(data.user.name) + ' - можно добавить';
                    hintDiv.onclick = null;
                    if (addBtn) {
                        addBtn.style.opacity = '1';
                        addBtn.style.pointerEvents = 'auto';
                    }
                } else {
                    hintDiv.className = 'input-hint error';
                    hintDiv.innerHTML = '❌ Пользователь не зарегистрирован <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'groupAddMemberPhone\', \'groupAddMemberHint\')">✖</span>';
                    hintDiv.style.cursor = 'pointer';
                    hintDiv.onclick = function() { clearPhoneInput('groupAddMemberPhone', 'groupAddMemberHint'); };
                    if (addBtn) {
                        addBtn.style.opacity = '0.5';
                        addBtn.style.pointerEvents = 'none';
                    }
                }
            })
            .catch(() => {
                hintDiv.className = 'input-hint error';
                hintDiv.innerHTML = '❌ Ошибка проверки <span style="float:right; cursor:pointer; font-weight:bold;" onclick="clearPhoneInput(\'groupAddMemberPhone\', \'groupAddMemberHint\')">✖</span>';
                hintDiv.style.cursor = 'pointer';
                hintDiv.onclick = function() { clearPhoneInput('groupAddMemberPhone', 'groupAddMemberHint'); };
                if (addBtn) {
                    addBtn.style.opacity = '0.5';
                    addBtn.style.pointerEvents = 'none';
                }
            });
    }, 600);
}

function clearPhoneInput(inputId, hintId) {
    var input = document.getElementById(inputId);
    var hintDiv = document.getElementById(hintId);
    
    if (input) { input.value = ''; }
    if (hintDiv) {
        hintDiv.className = 'input-hint';
        hintDiv.innerHTML = '';
        hintDiv.onclick = null;
    }
    
    var addBtn = null;
    if (inputId === 'addMemberPhone') {
        addBtn = document.getElementById('addMemberBtn');
    } else if (inputId === 'groupAddMemberPhone') {
        addBtn = document.getElementById('groupAddMemberBtn');
    }
    
    if (addBtn) {
        addBtn.style.opacity = '0.5';
        addBtn.style.pointerEvents = 'none';
    }
}

var originalAddMemberToCreateGroup = addMemberToCreateGroup;
addMemberToCreateGroup = function() {
    originalAddMemberToCreateGroup();
    document.getElementById('addMemberPhone').value = '';
    document.getElementById('addMemberHint').className = 'input-hint';
    document.getElementById('addMemberHint').innerHTML = '';
    var addBtn = document.getElementById('addMemberBtn');
    if (addBtn) {
        addBtn.style.opacity = '0.5';
        addBtn.style.pointerEvents = 'none';
    }
};

var originalAddMemberToGroup = addMemberToGroup;
addMemberToGroup = function() {
    originalAddMemberToGroup();
    document.getElementById('groupAddMemberPhone').value = '';
    document.getElementById('groupAddMemberHint').className = 'input-hint';
    document.getElementById('groupAddMemberHint').innerHTML = '';
    var addBtn = document.getElementById('groupAddMemberBtn');
    if (addBtn) {
        addBtn.style.opacity = '0.5';
        addBtn.style.pointerEvents = 'none';
    }
};

var originalShowCreateGroupModal = showCreateGroupModal;
showCreateGroupModal = function() {
    if (loadSettings.disable_groups === 1) {
        alert('❌ Создание групповых чатов отключено администратором');
        return;
    }
    originalShowCreateGroupModal();
    document.getElementById('groupNameHint').className = 'input-hint';
    document.getElementById('groupNameHint').innerHTML = '';
    document.getElementById('addMemberHint').className = 'input-hint';
    document.getElementById('addMemberHint').innerHTML = '';
    document.getElementById('createGroupName').value = '';
    document.getElementById('addMemberPhone').value = '';
    var submitBtn = document.getElementById('createGroupSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('disabled');
    }
    var addBtn = document.getElementById('addMemberBtn');
    if (addBtn) {
        addBtn.style.opacity = '0.5';
        addBtn.style.pointerEvents = 'none';
    }
};

function updateGroupButtonVisibility() {
    var createBtn = document.getElementById('createGroupBtn');
    if (createBtn) {
        createBtn.style.display = (loadSettings.disable_groups === 1) ? 'none' : 'block';
    }
    
    // ✅ Если группы отключены и открыта группа - закрываем её
    if (loadSettings.disable_groups === 1 && currentGroupId) {
        currentGroupId = null;
        document.getElementById('contactName').innerHTML = 'Выберите чат';
        document.getElementById('messagesContainer').innerHTML = '';
        document.getElementById('groupMenuBtn').style.display = 'none';
    }
}

if ('serviceWorker' in navigator) { 
    navigator.serviceWorker.register('/sw.js').then(function(reg) { console.log('Service Worker зарегистрирован'); }).catch(function(err) { console.log('Ошибка:', err); }); 
}

</script>
</body>
</html>
