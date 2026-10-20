-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 13, 2026 at 06:58 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nexa`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `post_id`, `parent_id`, `comment_text`, `created_at`) VALUES
(4, 1, 5, NULL, 'wawergltr', '2026-07-10 05:49:19'),
(5, 1, 5, NULL, 'its no', '2026-09-14 03:11:19'),
(21, 2, 14, NULL, 'this is datha?', '2026-09-30 05:47:47'),
(22, 2, 13, NULL, 'so amazing', '2026-09-30 05:48:00'),
(23, 3, 20, NULL, 'nice', '2026-09-30 05:49:52'),
(24, 4, 14, NULL, 'i know this one', '2026-09-30 05:52:16'),
(25, 4, 23, NULL, 'so amazing', '2026-09-30 05:52:26'),
(26, 4, 21, NULL, 'i want to go this place', '2026-09-30 05:52:42'),
(27, 4, 20, NULL, 'WOW', '2026-09-30 05:52:48'),
(28, 4, 18, NULL, 'Stunnnnn', '2026-09-30 05:53:19'),
(29, 4, 15, NULL, '懐かしい', '2026-09-30 05:53:52'),
(30, 1, 25, NULL, 'where', '2026-09-30 06:00:10'),
(54, 1, 15, 29, 'i feel too', '2026-09-30 06:10:25'),
(55, 4, 25, 30, 'russia mate', '2026-09-30 06:11:39'),
(56, 4, 16, NULL, 'pretty', '2026-09-30 06:12:15'),
(57, 4, 14, 21, 'i think so', '2026-09-30 06:12:45'),
(58, 4, 13, NULL, 'absolute stun', '2026-09-30 06:12:58'),
(59, 4, 10, NULL, 'Giffff', '2026-09-30 06:13:12'),
(60, 1, 26, NULL, 'amazing', '2026-09-30 06:17:35'),
(61, 1, 25, 55, 'thanks', '2026-09-30 06:17:49'),
(62, 1, 25, NULL, 'thanks', '2026-09-30 06:17:56'),
(64, 1, 23, 25, 'sooo', '2026-09-30 06:18:28'),
(66, 1, 21, NULL, 'me too', '2026-09-30 06:27:18'),
(67, 3, 20, 27, 'thankssss', '2026-09-30 07:13:33'),
(68, 3, 18, NULL, 'so preety', '2026-09-30 07:13:48'),
(69, 6, 26, 60, 'so', '2026-10-02 01:43:29'),
(70, 1, 26, 69, 'thanks', '2026-10-02 01:50:58'),
(71, 1, 24, NULL, 'nice', '2026-10-02 02:21:57'),
(72, 1, 21, 26, 'where', '2026-10-02 02:26:05'),
(73, 1, 21, 72, 'fsfd', '2026-10-02 02:26:10'),
(74, 1, 20, 23, 'so', '2026-10-02 02:26:20'),
(75, 2, 29, NULL, 'はい', '2026-10-02 02:40:25'),
(76, 2, 28, NULL, 'hello', '2026-10-02 02:40:37'),
(77, 2, 29, NULL, 'where reply ?', '2026-10-06 04:59:51'),
(78, 3, 29, NULL, 'oke', '2026-10-06 05:04:17'),
(79, 3, 16, NULL, 'omg', '2026-10-06 05:04:44'),
(80, 3, 29, NULL, 'hai', '2026-10-06 05:09:36'),
(81, 3, 10, NULL, 'enjoy', '2026-10-06 05:09:48'),
(82, 3, 13, NULL, 'omg', '2026-10-06 05:09:58'),
(83, 1, 42, NULL, 'ｇｇｇｇ', '2026-10-08 02:49:26'),
(84, 1, 42, NULL, '😂😂😂😂', '2026-10-08 03:18:24');

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` int NOT NULL,
  `comment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comment_likes`
--

INSERT INTO `comment_likes` (`id`, `comment_id`, `user_id`, `created_at`) VALUES
(1, 71, 1, '2026-10-02 02:25:25'),
(2, 26, 1, '2026-10-02 02:26:07');

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `id` int NOT NULL,
  `follower_id` int NOT NULL,
  `following_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(33, 4, 1, '2026-10-01 03:20:33'),
(34, 4, 2, '2026-10-01 04:26:15'),
(35, 4, 3, '2026-10-01 04:26:16'),
(52, 3, 1, '2026-10-01 04:39:44'),
(53, 3, 2, '2026-10-01 04:39:48'),
(55, 2, 3, '2026-10-01 04:45:20'),
(56, 2, 1, '2026-10-01 04:45:49'),
(66, 6, 1, '2026-10-02 01:41:02'),
(68, 2, 6, '2026-10-07 06:38:26'),
(73, 1, 4, '2026-10-08 02:55:20');

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `friend_id` int NOT NULL,
  `status` enum('pending','accepted') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `friends`
--

INSERT INTO `friends` (`id`, `user_id`, `friend_id`, `status`, `created_at`) VALUES
(13, 1, 4, 'accepted', '2026-10-01 11:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `friend_requests`
--

INSERT INTO `friend_requests` (`id`, `sender_id`, `receiver_id`, `status`, `created_at`) VALUES
(16, 2, 3, 'accepted', '2026-10-01 04:51:35'),
(17, 4, 2, 'pending', '2026-10-01 04:53:20'),
(18, 4, 3, 'pending', '2026-10-01 04:53:21'),
(26, 8, 9, 'accepted', '2026-10-08 01:34:41');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `reaction` varchar(16) NOT NULL DEFAULT '??',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `reaction`, `created_at`) VALUES
(25, 2, 16, '??', '2026-09-30 05:35:25'),
(27, 2, 14, '??', '2026-09-30 05:47:36'),
(28, 3, 20, '??', '2026-09-30 05:49:45'),
(29, 3, 18, '??', '2026-09-30 05:49:56'),
(30, 3, 16, '??', '2026-09-30 05:49:58'),
(31, 3, 15, '??', '2026-09-30 05:49:59'),
(33, 3, 13, '??', '2026-09-30 05:50:01'),
(35, 3, 5, '??', '2026-09-30 05:50:07'),
(36, 3, 21, '??', '2026-09-30 05:50:33'),
(37, 3, 23, '??', '2026-09-30 05:51:35'),
(38, 4, 23, '??', '2026-09-30 05:51:57'),
(39, 4, 21, '??', '2026-09-30 05:51:58'),
(40, 4, 20, '??', '2026-09-30 05:52:00'),
(41, 4, 18, '??', '2026-09-30 05:52:01'),
(42, 4, 16, '??', '2026-09-30 05:52:02'),
(43, 4, 15, '??', '2026-09-30 05:52:03'),
(44, 4, 14, '??', '2026-09-30 05:52:04'),
(45, 4, 13, '??', '2026-09-30 05:53:58'),
(46, 4, 10, '??', '2026-09-30 05:54:00'),
(47, 4, 5, '??', '2026-09-30 05:54:01'),
(49, 4, 26, '??', '2026-09-30 05:58:16'),
(50, 4, 25, '??', '2026-09-30 05:58:18'),
(51, 4, 24, '??', '2026-09-30 05:58:19'),
(55, 1, 24, '??', '2026-09-30 05:58:50'),
(56, 1, 23, '??', '2026-09-30 05:58:52'),
(57, 1, 21, '??', '2026-09-30 05:58:53'),
(58, 1, 20, '??', '2026-09-30 05:58:55'),
(59, 1, 18, '??', '2026-09-30 05:58:57'),
(60, 1, 16, '??', '2026-09-30 05:58:58'),
(61, 1, 15, '??', '2026-09-30 05:59:00'),
(62, 1, 14, '??', '2026-09-30 05:59:01'),
(63, 1, 13, '??', '2026-09-30 05:59:02'),
(64, 1, 10, '??', '2026-09-30 05:59:04'),
(72, 1, 28, '??', '2026-10-02 02:10:19'),
(74, 1, 25, '??', '2026-10-02 02:15:19'),
(75, 1, 26, '??', '2026-10-02 02:15:23'),
(77, 1, 29, '??', '2026-10-02 02:26:33'),
(78, 2, 29, '??', '2026-10-02 02:40:16'),
(79, 2, 28, '??', '2026-10-02 02:40:28'),
(80, 2, 33, '??', '2026-10-02 03:07:42'),
(82, 2, 15, '??', '2026-10-06 05:00:07'),
(83, 2, 13, '??', '2026-10-06 05:00:13'),
(84, 2, 10, '??', '2026-10-06 05:00:15'),
(87, 3, 28, '??', '2026-10-06 05:04:20'),
(88, 3, 26, '??', '2026-10-06 05:04:24'),
(89, 3, 33, '??', '2026-10-06 05:09:29'),
(90, 3, 29, '??', '2026-10-06 05:09:31'),
(91, 3, 10, '??', '2026-10-06 05:09:44'),
(92, 3, 14, '??', '2026-10-06 05:10:01'),
(93, 3, 34, '??', '2026-10-07 05:39:43'),
(94, 2, 41, '??', '2026-10-07 05:40:40'),
(96, 1, 33, '??', '2026-10-08 01:15:30'),
(106, 1, 42, '❤️', '2026-10-08 03:18:51'),
(131, 10, 42, '😂', '2026-10-08 04:39:54'),
(132, 10, 41, '👀', '2026-10-09 02:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message_text` text,
  `image_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_request` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `image_path`, `is_read`, `is_request`, `created_at`) VALUES
(1, 7, 8, 'Hello from testrun 🙂', NULL, 1, 0, '2026-10-08 01:20:06'),
(2, 7, 8, '😀🎉❤️', NULL, 1, 0, '2026-10-08 01:20:06'),
(3, 7, 8, '', 'uploads/MSG_6ac6eff913ace5.22881501.png', 1, 0, '2026-10-08 01:20:57'),
(7, 9, 8, 'Request after reset', NULL, 1, 0, '2026-10-08 01:34:41'),
(8, 9, 8, 'We are friends now', NULL, 1, 0, '2026-10-08 01:34:41');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `actor_id` int NOT NULL,
  `type` enum('like_post','comment_post','like_comment','reply_comment','message') NOT NULL,
  `post_id` int DEFAULT NULL,
  `comment_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `post_id`, `comment_id`, `is_read`, `created_at`) VALUES
(1, 1, 2, 'comment_post', 29, 77, 1, '2026-10-06 04:59:51'),
(3, 1, 2, 'like_post', 13, NULL, 1, '2026-10-06 05:00:13'),
(4, 1, 2, 'like_post', 10, NULL, 1, '2026-10-06 05:00:15'),
(5, 2, 3, 'like_post', 33, NULL, 0, '2026-10-06 05:04:10'),
(6, 1, 3, 'like_post', 29, NULL, 1, '2026-10-06 05:04:14'),
(7, 1, 3, 'comment_post', 29, 78, 1, '2026-10-06 05:04:17'),
(8, 6, 3, 'like_post', 28, NULL, 0, '2026-10-06 05:04:20'),
(9, 4, 3, 'like_post', 26, NULL, 0, '2026-10-06 05:04:24'),
(10, 1, 3, 'comment_post', 16, 79, 1, '2026-10-06 05:04:44'),
(11, 2, 3, 'like_post', 33, NULL, 1, '2026-10-06 05:09:29'),
(12, 1, 3, 'like_post', 29, NULL, 1, '2026-10-06 05:09:31'),
(13, 1, 3, 'comment_post', 29, 80, 1, '2026-10-06 05:09:36'),
(14, 1, 3, 'like_post', 10, NULL, 1, '2026-10-06 05:09:44'),
(15, 1, 3, 'comment_post', 10, 81, 1, '2026-10-06 05:09:48'),
(16, 1, 3, 'comment_post', 13, 82, 1, '2026-10-06 05:09:58'),
(17, 1, 3, 'like_post', 14, NULL, 1, '2026-10-06 05:10:01'),
(18, 1, 3, 'like_post', 34, NULL, 1, '2026-10-07 05:39:43'),
(19, 1, 2, 'like_post', 41, NULL, 1, '2026-10-07 05:40:40'),
(20, 2, 1, 'like_post', 33, NULL, 0, '2026-10-08 01:15:28'),
(21, 2, 1, 'like_post', 33, NULL, 0, '2026-10-08 01:15:30'),
(22, 8, 7, 'message', NULL, NULL, 1, '2026-10-08 01:20:06'),
(23, 8, 7, 'message', NULL, NULL, 0, '2026-10-08 01:20:57'),
(26, 8, 9, 'message', NULL, NULL, 1, '2026-10-08 01:34:41'),
(27, 8, 9, 'message', NULL, NULL, 0, '2026-10-08 01:34:41'),
(28, 2, 1, 'like_post', 5, NULL, 0, '2026-10-08 03:56:08'),
(29, 2, 1, 'like_post', 5, NULL, 0, '2026-10-08 04:22:52'),
(30, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:29:13'),
(31, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:29:15'),
(32, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:42'),
(33, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:45'),
(34, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:47'),
(35, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:49'),
(36, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:52'),
(37, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:53'),
(38, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:54'),
(39, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:55'),
(40, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:34:58'),
(41, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:35:00'),
(42, 1, 10, 'like_post', 41, NULL, 0, '2026-10-08 04:35:03'),
(43, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:35:08'),
(44, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:36:14'),
(45, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:36:17'),
(46, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:36:18'),
(47, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:36:19'),
(48, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:36:20'),
(49, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:36:21'),
(50, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:36:23'),
(51, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:39:47'),
(52, 1, 10, 'like_post', 42, NULL, 0, '2026-10-08 04:39:54'),
(53, 1, 10, 'like_post', 41, NULL, 0, '2026-10-09 02:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `caption` text,
  `image_path` varchar(255) DEFAULT NULL,
  `visibility` enum('public','followers','friends','only_me') DEFAULT 'public',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `caption`, `image_path`, `visibility`, `created_at`) VALUES
(5, 2, 'still studying', 'uploads/POST_6a507d6ca94533.12612915.jpg', 'public', '2026-07-10 05:04:44'),
(10, 1, 'can i try this img', 'uploads/POST_6abc93e9e42b99.77345197.gif', 'public', '2026-09-30 04:45:29'),
(13, 1, 'Bagan ( Myanmar  )', 'uploads/1790745602_6abc9c02216f4.jpg', 'public', '2026-09-30 05:20:02'),
(14, 1, 'YAMAYANA', 'uploads/1790745720_6abc9c78af484.jpg', 'public', '2026-09-30 05:22:00'),
(15, 1, 'Sunset of  Bagan ( Myanmar )', 'uploads/1790745805_6abc9ccd4c7b6.jpg', 'public', '2026-09-30 05:23:25'),
(16, 1, 'Kyoto ( Japan )', 'uploads/1790745886_6abc9d1e3cb10.jpg', 'public', '2026-09-30 05:24:46'),
(18, 2, 'This is Brazil', 'uploads/1790746947_6abca1436abcd.jpg', 'public', '2026-09-30 05:42:27'),
(20, 2, 'Rome', 'uploads/1790747353_6abca2d99431e.jpg', 'public', '2026-09-30 05:49:13'),
(21, 3, 'This is Nepal', 'uploads/1790747431_6abca3270e861.jpg', 'public', '2026-09-30 05:50:31'),
(23, 3, 'INDIA', 'uploads/1790747490_6abca3624162a.jpg', 'public', '2026-09-30 05:51:30'),
(24, 4, 'ITALY', 'uploads/1790747791_6abca48fc2c1e.jpg', 'public', '2026-09-30 05:56:31'),
(25, 4, 'RUSSIA', 'uploads/1790747804_6abca49c7f790.jpg', 'public', '2026-09-30 05:56:44'),
(26, 4, 'Nothran Light ( NORWAY )', 'uploads/1790747815_6abca4a7dd2a7.jpg', 'public', '2026-09-30 05:56:55'),
(28, 6, 'no caption', 'uploads/1790905147_6abf0b3b931b8.gif', 'public', '2026-10-02 01:39:07'),
(29, 1, 'let me know if you have any trouble', NULL, 'public', '2026-10-02 02:11:48'),
(33, 2, 'Test pic', 'uploads/post_6abf1d64972e09.23227729_0.jpg', 'public', '2026-10-02 02:56:36'),
(34, 1, '旅行が好きです。機械や時間があればどこかに行きたい。', 'uploads/post_6ac4933f2f8737.18614332_0.jpg', 'public', '2026-10-06 06:20:47'),
(38, 1, 'only me', 'uploads/post_6ac5da61a610c3.71961040_0.jpg', 'only_me', '2026-10-07 05:36:33'),
(39, 1, 'follower only', 'uploads/post_6ac5daab163b09.41642759_0.png', 'followers', '2026-10-07 05:37:47'),
(40, 1, 'friends only', 'uploads/post_6ac5dac9bcb9b3.92806216_0.jpg', 'friends', '2026-10-07 05:38:17'),
(41, 1, 'public', 'uploads/post_6ac5db4f47c239.01074787_0.jpg', 'public', '2026-10-07 05:40:31'),
(42, 1, 'public2', 'uploads/post_6ac5e3f87baf50.91422979_0.jpg', 'public', '2026-10-07 06:17:28'),
(43, 6, 'followewr and fri', 'uploads/post_6ac5e8b176a188.69592123_0.png', 'followers', '2026-10-07 06:37:37');

-- --------------------------------------------------------

--
-- Table structure for table `post_media`
--

CREATE TABLE `post_media` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `post_media`
--

INSERT INTO `post_media` (`id`, `post_id`, `media_path`, `created_at`) VALUES
(10, 33, 'uploads/post_6abf1d64972e09.23227729_0.jpg', '2026-10-02 02:56:36'),
(11, 33, 'uploads/post_6abf1d649767b7.73176100_1.jpg', '2026-10-02 02:56:36'),
(12, 33, 'uploads/post_6abf1d6497c079.42784906_2.jpg', '2026-10-02 02:56:36'),
(13, 33, 'uploads/post_6abf1d64980951.32884554_3.jpg', '2026-10-02 02:56:36'),
(14, 33, 'uploads/post_6abf1d64984b71.27112204_4.jpg', '2026-10-02 02:56:36'),
(15, 34, 'uploads/post_6ac4933f2f8737.18614332_0.jpg', '2026-10-06 06:20:47'),
(16, 34, 'uploads/post_6ac4933f2feb59.18089378_1.jpg', '2026-10-06 06:20:47'),
(22, 38, 'uploads/post_6ac5da61a610c3.71961040_0.jpg', '2026-10-07 05:36:33'),
(23, 38, 'uploads/post_6ac5da61a64db3.25606455_1.jpg', '2026-10-07 05:36:33'),
(24, 39, 'uploads/post_6ac5daab163b09.41642759_0.png', '2026-10-07 05:37:47'),
(25, 39, 'uploads/post_6ac5daab1674d4.94092171_1.jpg', '2026-10-07 05:37:47'),
(26, 40, 'uploads/post_6ac5dac9bcb9b3.92806216_0.jpg', '2026-10-07 05:38:17'),
(27, 40, 'uploads/post_6ac5dac9bd1053.16981629_1.jpg', '2026-10-07 05:38:17'),
(28, 40, 'uploads/post_6ac5dac9bd4a46.73725255_2.jpg', '2026-10-07 05:38:17'),
(29, 41, 'uploads/post_6ac5db4f47c239.01074787_0.jpg', '2026-10-07 05:40:31'),
(30, 41, 'uploads/post_6ac5db4f49b7b5.13954464_1.jpg', '2026-10-07 05:40:31'),
(31, 42, 'uploads/post_6ac5e3f87baf50.91422979_0.jpg', '2026-10-07 06:17:28'),
(32, 43, 'uploads/post_6ac5e8b176a188.69592123_0.png', '2026-10-07 06:37:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` text,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher') NOT NULL DEFAULT 'student',
  `user_code` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `school_name` varchar(150) DEFAULT NULL,
  `language_code` varchar(20) NOT NULL DEFAULT 'ja-JP',
  `default_visibility` enum('public','followers','friends','only_me') NOT NULL DEFAULT 'public'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `username`, `email`, `password`, `bio`, `profile_pic`, `created_at`, `profile_image`, `role`, `user_code`, `department`, `class_name`, `school_name`, `language_code`, `default_visibility`) VALUES
(1, '250161', 'admin', '250161gd@yse-c.net', '$2y$10$z4954b.Euqs6PVkIzl96Q.2HonFvkW8XrevZMzh3JMa1WTfcsGpXi', 'hello we are linker group in YSE!!', 'uploads/AVATAR_6a5061e0d99450.32494110.jpg', '2026-07-10 02:29:44', 'uploads/avatar_1_1790744798.jpg', 'student', NULL, NULL, NULL, NULL, 'ja-JP', 'public'),
(2, '250118', 'nan', '250118yd@yse-c.net', '$2y$10$21Hx6NBeHvZBAiv2EEamXOpZh7ZX4w2SA.VXcTKy4v9/TlkyrW6mW', 'Hello i m nan shwe yee phoo thit . from GS21.\r\ni m SINGLE', 'default.png', '2026-07-10 04:59:52', 'uploads/avatar_2_1790743677.png', 'student', NULL, NULL, NULL, NULL, 'ja-JP', 'public'),
(3, '250154', 'Ru', '250154as@yse-c.net', '$2y$10$sgDP/xPIwjWZWITsy//u7e11e1EVSwlhRIfybovKTny.01BK0bLCa', NULL, 'default.png', '2026-09-29 06:33:18', 'uploads/avatar_3_1790743729.png', 'student', NULL, NULL, NULL, NULL, 'ja-JP', 'public'),
(4, '250162', 'cyan', '250162gd@yse-c.net', '$2y$10$iTEXAXLrKzDY/2HoyE.JcOOWESs8jf2P/3xlsqkZFpO6ckITYevMy', NULL, 'default.png', '2026-09-29 06:49:54', 'uploads/avatar_4_1790743596.png', 'student', NULL, NULL, NULL, NULL, 'ja-JP', 'public'),
(6, '250000', 'YOO', '250000gd@yse-c.net', '$2y$10$hsr2YpxRgXtRZpVuLBvtdeEE6xULlCE0MU6ZECYLff/YwT5JZ1xEO', NULL, 'default.png', '2026-10-02 01:37:32', 'uploads/avatar_6_1790905106.jpg', 'teacher', '250000', 'Global Systems', NULL, NULL, 'ja-JP', 'public'),
(7, '999999', 'testrun', 'testrun@local.test', '$2y$10$rDqInNk8f0CWCnFlhgmx0uDo94BB1TtZZvXlBu9bgFsVU48Diip9K', NULL, 'default.png', '2026-10-08 01:06:33', NULL, 'student', NULL, NULL, NULL, NULL, 'ja-JP', 'public'),
(8, '999998', 'testrun2', 'testrun2@local.test', '$2y$10$Liy1uXI/5nE7yzZ.U.Tlp.9pvqY0qgxnSQlilUxXyqlV6NUAjNDji', NULL, 'default.png', '2026-10-08 01:20:05', NULL, 'student', NULL, NULL, NULL, NULL, 'ja-JP', 'public'),
(9, '999997', 'testrun3', 'testrun3@local.test', '$2y$10$RyAXZQ.mKtss1bpjRSpIxu2q9ynXghOR4Rta09vOReVMATUxpfbxy', NULL, 'default.png', '2026-10-08 01:33:15', NULL, 'student', NULL, NULL, NULL, NULL, 'ja-JP', 'public'),
(10, '250153', 'AD', '250153fx@yse-c.net', '$2y$10$dLRbWC2sJVYi/P/WI9P0pOsL5QhJr27OYK4q6l2Yxe/eO.1Fr7Bw2', NULL, 'default.png', '2026-10-08 04:09:17', 'uploads/avatar_10_1791432720.png', 'student', '250153', 'Global Systems', NULL, NULL, 'ja-JP', 'only_me');

-- --------------------------------------------------------

--
-- Table structure for table `user_blocks`
--

CREATE TABLE `user_blocks` (
  `blocker_id` int NOT NULL,
  `blocked_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_comment_like` (`comment_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `friend_id` (`friend_id`);

--
-- Indexes for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`sender_id`,`receiver_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_post` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pair` (`receiver_id`,`sender_id`,`is_read`),
  ADD KEY `fk_msg_sender` (`sender_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `actor_id` (`actor_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_UserPost` (`user_id`);

--
-- Indexes for table `post_media`
--
ALTER TABLE `post_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `user_blocks`
--
ALTER TABLE `user_blocks`
  ADD PRIMARY KEY (`blocker_id`,`blocked_id`),
  ADD KEY `fk_blk_blocked` (`blocked_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `follows`
--
ALTER TABLE `follows`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `post_media`
--
ALTER TABLE `post_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD CONSTRAINT `friend_requests_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friend_requests_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `FK_UserPost` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_media`
--
ALTER TABLE `post_media`
  ADD CONSTRAINT `post_media_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_blocks`
--
ALTER TABLE `user_blocks`
  ADD CONSTRAINT `fk_blk_blocked` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_blk_blocker` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
