-- phpMyAdmin SQL Dump
-- version 5.2.3deb1
-- https://www.phpmyadmin.net/
--
-- 호스트: localhost:3306
-- 생성 시간: 26-11-05 02:49
-- 서버 버전: 8.4.11-0ubuntu0.26.04.1
-- PHP 버전: 8.5.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 데이터베이스: `AnimationInformation`
--
CREATE DATABASE IF NOT EXISTS `AnimationInformation` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `AnimationInformation`;

-- --------------------------------------------------------

--
-- 테이블 구조 `Animes`
--

CREATE TABLE IF NOT EXISTS `Animes` (
  `anime_id` int NOT NULL AUTO_INCREMENT,
  `anime_name` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `release_date` date NOT NULL,
  `anime_type` char(100) NOT NULL,
  `seires_id` int NOT NULL,
  PRIMARY KEY (`anime_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='애니메이션 정보';

--
-- 테이블의 덤프 데이터 `Animes`
--

INSERT INTO `Animes` (`anime_id`, `anime_name`, `release_date`, `anime_type`, `seires_id`) VALUES
(1, 'Fate/stay night', '2006-01-07', 'TV', 1),
(2, 'Fate/stay night[Unlimited Blade Works]', '2010-01-23', 'Movie', 1),
(3, 'Fate/stay night[Unlimited Blade Works]', '2014-10-05', 'TV', 1),
(4, '강철의 연금술사', '2003-10-04', 'TV', 2),
(5, '강철의 연금술사: 샴발라를 정복하는 자', '2005-07-23', 'Movie', 2),
(6, '강철의 연금술사 BROTHERHOOD', '2009-04-05', 'TV', 2);

-- --------------------------------------------------------

--
-- 테이블 구조 `History`
--

CREATE TABLE IF NOT EXISTS `History` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `status` tinyint NOT NULL,
  `rating` decimal(10,0) NOT NULL,
  `anime_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='회원 기록';

--
-- 테이블의 덤프 데이터 `History`
--

INSERT INTO `History` (`history_id`, `status`, `rating`, `anime_id`, `user_id`) VALUES
(1, 1, 8, 4, 1),
(2, 1, 9, 6, 1),
(3, 1, 8, 1, 2),
(4, 1, 7, 6, 2);

-- --------------------------------------------------------

--
-- 테이블 구조 `Series`
--

CREATE TABLE IF NOT EXISTS `Series` (
  `seires_id` int NOT NULL AUTO_INCREMENT,
  `seires_name` char(100) NOT NULL,
  PRIMARY KEY (`seires_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='시리즈 정보';

--
-- 테이블의 덤프 데이터 `Series`
--

INSERT INTO `Series` (`seires_id`, `seires_name`) VALUES
(1, 'Fate/staty night'),
(2, '강철의 연금술사');

-- --------------------------------------------------------

--
-- 테이블 구조 `Users`
--

CREATE TABLE IF NOT EXISTS `Users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `password` int NOT NULL,
  `age` int NOT NULL,
  `nickname` char(100) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='회원 정보';

--
-- 테이블의 덤프 데이터 `Users`
--

INSERT INTO `Users` (`user_id`, `password`, `age`, `nickname`) VALUES
(1, 1234, 20, '갑'),
(2, 1234, 30, '을');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
