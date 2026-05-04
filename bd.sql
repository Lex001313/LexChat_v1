-- Структура таблицы `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT current_timestamp(),
  `is_online` tinyint(1) DEFAULT 0,
  `last_active` timestamp NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  KEY `idx_phone` (`phone`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Структура таблицы `messages`
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_phone` varchar(20) DEFAULT NULL,
  `to_phone` varchar(20) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `time` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `audio_path` varchar(500) DEFAULT NULL,
  `audio_duration` int(11) DEFAULT 0,
  `video_path` varchar(500) DEFAULT NULL,
  `video_thumbnail` varchar(500) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'sent',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_from_to` (`from_phone`,`to_phone`),
  KEY `idx_time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Структура таблицы `groups`
CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Структура таблицы `group_members`
CREATE TABLE `group_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `user_phone` varchar(20) NOT NULL,
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  `role` enum('admin','member') DEFAULT 'member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_member` (`group_id`,`user_phone`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Структура таблицы `group_messages`
CREATE TABLE `group_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `from_phone` varchar(20) NOT NULL,
  `text` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `video_path` varchar(500) DEFAULT NULL,
  `audio_path` varchar(500) DEFAULT NULL,
  `audio_duration` int(11) DEFAULT NULL,
  `time` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'sent',
  `is_read` tinyint(4) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Структура таблицы `broadcast_messages`
CREATE TABLE `broadcast_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `from_phone` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Структура таблицы `user_broadcast_read`
CREATE TABLE `user_broadcast_read` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_phone` varchar(20) NOT NULL,
  `broadcast_id` int(11) NOT NULL,
  `read_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_read` (`user_phone`,`broadcast_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

