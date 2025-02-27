-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2025-02-20 07:19:40
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `project_tracker`
--
CREATE DATABASE IF NOT EXISTS `project_tracker` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `project_tracker`;

-- --------------------------------------------------------

--
-- テーブルの構造 `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('未着手','進行中','レビュー中','保留中','完了','中止') NOT NULL DEFAULT '未着手',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `projects`
--

INSERT INTO `projects` (`id`, `name`, `status`, `created_at`, `updated_at`, `parent_id`) VALUES
(1, 'サイドグリッパー', '進行中', '2025-02-19 10:35:35', '2025-02-19 10:57:54', NULL),
(2, 'OMS油圧バイブロ', '中止', '2025-02-19 10:46:25', '2025-02-19 11:30:51', NULL),
(5, 'SR50バイブロ', '未着手', '2025-02-19 11:28:37', '2025-02-19 11:28:50', NULL),
(6, '案件一覧表作成', '進行中', '2025-02-19 11:29:26', '2025-02-20 13:04:44', NULL),
(8, '鋼管チャックのは基準決める', '進行中', '2025-02-20 10:41:26', '2025-02-20 13:07:13', NULL),
(10, 'テスト', '完了', '2025-02-20 13:06:02', '2025-02-20 13:06:56', 6),
(11, 'テスト2', '未着手', '2025-02-20 13:06:16', '2025-02-20 13:06:22', 6);

-- --------------------------------------------------------

--
-- テーブルの構造 `project_history`
--

CREATE TABLE `project_history` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `status` enum('未着手','進行中','レビュー中','保留中','完了','中止') DEFAULT NULL,
  `author` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `project_history`
--

INSERT INTO `project_history` (`id`, `project_id`, `content`, `status`, `author`, `created_at`) VALUES
(1, 1, NULL, '進行中', '堀内', '2025-02-19 10:35:50'),
(2, 1, 'トルコ出向しました', NULL, '堀内', '2025-02-19 10:36:12'),
(3, 1, '横浜港に到着しました', NULL, '堀内', '2025-02-19 10:36:46'),
(4, 1, '取り行きました', NULL, '堀内', '2025-02-19 10:37:07'),
(5, 2, NULL, '保留中', '堀内', '2025-02-19 10:46:36'),
(6, 2, 'ハカンさんに確認中', NULL, '堀内', '2025-02-19 10:47:08'),
(9, 1, '本社に納品', NULL, '堀内', '2025-02-19 10:54:57'),
(10, 1, '公田に移動', NULL, '堀内', '2025-02-19 10:55:09'),
(11, 1, '取付', NULL, '堀内', '2025-02-19 10:55:29'),
(12, 1, '動作かっくにん', NULL, '堀内', '2025-02-19 10:55:38'),
(13, 1, 'トラブル発生', NULL, '堀内', '2025-02-19 10:57:54'),
(14, 5, '試験中', NULL, '堀内', '2025-02-19 11:28:50'),
(15, 6, NULL, '進行中', '堀内', '2025-02-19 11:29:44'),
(16, 6, '作成中', NULL, '堀内', '2025-02-19 11:30:10'),
(17, 2, NULL, '中止', '堀内', '2025-02-19 11:30:25'),
(18, 2, '返信してくれないので進まない', NULL, '堀内', '2025-02-19 11:30:51'),
(19, 8, 'やっすん資料待ち', NULL, '堀内', '2025-02-20 10:42:23'),
(20, 8, '稲葉さんに話持ってく', NULL, '堀内', '2025-02-20 10:45:05'),
(22, 6, 'ＤＤＤＤＤＤＤＤ', NULL, '堀内', '2025-02-20 13:04:44'),
(25, 11, 'ＳＳＳＳＳＳ', NULL, '堀内', '2025-02-20 13:06:22'),
(26, 10, '4444444444444', NULL, '堀内', '2025-02-20 13:06:39'),
(27, 10, NULL, '完了', '堀内', '2025-02-20 13:06:56'),
(28, 8, NULL, '進行中', '堀内', '2025-02-20 13:07:13');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- テーブルのインデックス `project_history`
--
ALTER TABLE `project_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- テーブルの AUTO_INCREMENT `project_history`
--
ALTER TABLE `project_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `project_history`
--
ALTER TABLE `project_history`
  ADD CONSTRAINT `project_history_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
