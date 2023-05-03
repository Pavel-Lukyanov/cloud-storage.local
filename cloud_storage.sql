-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 03 2023 г., 13:13
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cloud_storage`
--

-- --------------------------------------------------------

--
-- Структура таблицы `files`
--

CREATE TABLE `files` (
  `id` int NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название файла',
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Оригинальное название файла',
  `user_id` int NOT NULL COMMENT 'id польователя которому принадлежит файл',
  `parent_folder_id` int DEFAULT NULL COMMENT 'id папки родительской папки',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Путь к файлу на сервере',
  `file_size` bigint NOT NULL COMMENT 'Размер файла',
  `file_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип файла',
  `file_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания файла'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `files`
--

INSERT INTO `files` (`id`, `file_name`, `original_name`, `user_id`, `parent_folder_id`, `file_path`, `file_size`, `file_type`, `file_created_at`) VALUES
(5, '1_1682668018_asd.jpg', '', 1, 0, 'E:/Pavel/OSPanel/domains/cloud-storage.local/files/1/', 6914, 'image/jpeg', '2023-04-28 07:46:58'),
(6, '1_1682668224_Скриншот 28-04-2023 124246.jpg', '', 1, 0, 'E:/Pavel/OSPanel/domains/cloud-storage.local/files/1/', 15885, 'image/jpeg', '2023-04-28 07:50:24'),
(7, '7_1682668362_Скриншот 28-04-2023 124010.jpg', '', 7, 0, 'E:/Pavel/OSPanel/domains/cloud-storage.local/files/7/', 119218, 'image/jpeg', '2023-04-28 07:52:42'),
(8, '1_1683098084_Скриншот 03-05-2023 121434.jpg', '', 1, NULL, '/files/1/', 1147, 'image/jpeg', '2023-05-03 07:14:44'),
(9, '1_1683098170_Скриншот 03-05-2023 121434.jpg', '', 1, NULL, '/files/1/', 1147, 'image/jpeg', '2023-05-03 07:16:10'),
(10, '1_1683098322_Скриншот 03-05-2023 121434.jpg', 'Скриншот 03-05-2023 121434.jpg', 1, NULL, '/files/1/', 1147, 'image/jpeg', '2023-05-03 07:18:42'),
(11, '1_1683100964_Скриншот 03-05-2023 121434.jpg', 'Скриншот 03-05-2023 121434.jpg', 1, 1, '/files/1/', 1147, 'image/jpeg', '2023-05-03 08:02:44'),
(13, '1_1683105126_Скриншот 03-05-2023 121434.jpg', 'Скриншот 03-05-2023 121434.jpg', 1, 1, '/files/1/', 1147, 'image/jpeg', '2023-05-03 09:12:06');

-- --------------------------------------------------------

--
-- Структура таблицы `folders`
--

CREATE TABLE `folders` (
  `id` int NOT NULL,
  `folder_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT ' Название папки',
  `user_id` int NOT NULL COMMENT 'Пользователь создавший папку',
  `parent_folder_id` int DEFAULT NULL COMMENT 'Родительская папка',
  `folder_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Путь к папке на сервере',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания папки',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата обновления папки'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `folders`
--

INSERT INTO `folders` (`id`, `folder_name`, `user_id`, `parent_folder_id`, `folder_path`, `created_at`, `updated_at`) VALUES
(1, '1', 1, NULL, '/files/1/', '2023-05-03 12:12:06', '2023-05-03 12:12:06'),
(15, 'Новая папка', 1, 1, '/files/1/', '2023-05-03 13:10:31', '2023-05-03 13:10:31'),
(16, 'Новая папка', 1, 1, '/files/1/', '2023-05-03 13:12:26', '2023-05-03 13:12:26'),
(17, 'тестовая папка', 1, 1, '/files/1/', '2023-05-03 13:12:50', '2023-05-03 13:12:50');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(155) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(155) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `role`, `email`, `password`) VALUES
(1, 'admin', 'PavelAlexandrov86@yandex.ru', '$2y$10$yg9uYjRZ.FrWQTHn2VH8ueXAfCepBNZ0Ctip8ubN5I2WyBzoqiPuG'),
(7, 'user', 'test@yandex.ru', '$2y$10$yg9uYjRZ.FrWQTHn2VH8ueXAfCepBNZ0Ctip8ubN5I2WyBzoqiPuG'),
(8, 'admin', 'PavelAlexandrov86@yandex.ru', '$2y$10$KIu1Fezu/OqnRf0CSREEK.X.lPUkymR0P8I61USBOUNoOhRFFws4G');

-- --------------------------------------------------------

--
-- Структура таблицы `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `expiration_time`) VALUES
(11, 1, '47910f9a679160c656d2037735474dce1e94ae7f8fcc2fc430ebd31cd42f83cb', '2023-04-29 09:37:01'),
(12, 1, '26d84f1e78dfe6e79c19b1a989b844feefa0e2fa89f4f71f8e40210364ae4f06', '2023-04-28 10:37:42'),
(13, 1, '4e3ba3c0080a44fdadf8a3c4f49989ad377bf2c64af9b4b62ee3adaf6064dfda', '2023-04-28 10:37:54'),
(14, 1, '01ccb7c78823c74394d604de3e88e7f3ed549450b2349413a2887a127d325c9b', '2023-04-28 10:38:58'),
(17, 1, '5ce4ce4a98f5ac08b90d159eb4f3e05e5cc8734372c9b5902da9d9a0e5c0c89f', '2023-05-04 10:12:03');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_folder_id` (`parent_folder_id`),
  ADD KEY `parent_folder_id_2` (`parent_folder_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `files`
--
ALTER TABLE `files`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `folders_ibfk_2` FOREIGN KEY (`parent_folder_id`) REFERENCES `folders` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
