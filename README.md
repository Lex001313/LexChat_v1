# LexChat – корпоративный мессенджер для малого бизнеса

************************************************************************************************************************************
<img width="1268" height="883" alt="image" src="https://github.com/user-attachments/assets/f76515b1-7c98-461e-a51f-f0bd272589e3" />
************************************************************************************************************************
<img width="497" height="859" alt="image" src="https://github.com/user-attachments/assets/d587d625-0c05-434d-879e-e6685d86214f" />
************************************************************************************************************************


**LexChat** – это веб-мессенджер с WebSocket-уведомлениями и автоматическим fallback на HTTP polling. Работает на полностью бесплатных сервисах: InfinityFree (PHP/MySQL) + Render (Node.js) + Gmail SMTP.

## Возможности

- Личные и групповые чаты
- Отправка файлов (изображения, документы)
- Голосовые сообщения (запись через браузер)
- Аватары пользователей и групп
- Админ-панель: рассылки, настройка цветов и фона, управление пользователями
- Автоматическое переключение на polling при отключении WebSocket
- Адаптивный дизайн (мобильные устройства)

## Архитектура
<img width="994" height="653" alt="image" src="https://github.com/user-attachments/assets/6d27d9d0-853f-44dc-aa05-b7a690b1ff6f" />

- **Клиент**: HTML/CSS/JS (index.php) + Socket.IO client
- **Бэкенд**: PHP (api.php) на InfinityFree, MySQL
- **WebSocket-сервер**: Node.js + Socket.IO на Render (бесплатно)
- **Fallback**: периодические AJAX-запросы к api.php

## Быстрый старт (для разработки)

1. Склонируйте репозиторий
2. Настройте подключение к БД в `connect.php` (или через переменные окружения)
3. Импортируйте `bd.sql` в MySQL
4. Запустите PHP-сервер: `php -S localhost:8000`
5. В отдельном терминале перейдите в папку `server` и выполните `npm install && npm start`
6. Откройте `http://localhost:8000`

## Развёртывание на бесплатных хостингах

Подробная инструкция в [DEPLOY.md](https://github.com/Lex001313/LexChat_v1/blob/main/DEPLOY.md). 
Основные шаги:
- Загрузить PHP-файлы на InfinityFree
- Задеплоить папку `server` на Render (Web Service)
- Настроить UptimeRobot для пинга
- В админ-панели указать URL WebSocket-сервера

## Технологии

- **Frontend**: vanilla JS, Socket.IO client
- **Backend**: PHP 7.4+, MySQL, PDO
- **WebSocket**: Node.js, Express, Socket.IO
- **Дополнительно**: PHPMailer, Web Audio API, PWA (Service Worker)

## Лицензия

MIT – свободно для использования и модификации.

## Контакты

Автор: R.I.Moskalenko (Lex0013) / GitHub
