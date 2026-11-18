-- phpMyAdmin SQL Dump
-- version 5.2.3deb1
-- https://www.phpmyadmin.net/
--
-- 호스트: localhost:3306
-- 생성 시간: 26-11-18 10:02
-- 서버 버전: 8.4.13-0ubuntu0.26.04.2
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

-- --------------------------------------------------------

--
-- 테이블 구조 `Animes`
--

CREATE TABLE `Animes` (
  `anime_id` int NOT NULL,
  `anime_name` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `anime_image` varchar(512) NOT NULL,
  `release_year` year NOT NULL,
  `anime_quarter` int NOT NULL,
  `anime_type` char(100) NOT NULL,
  `seires_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='애니메이션 정보';

--
-- 테이블의 덤프 데이터 `Animes`
--

INSERT INTO `Animes` (`anime_id`, `anime_name`, `anime_image`, `release_year`, `anime_quarter`, `anime_type`, `seires_id`) VALUES
(1, 'Fate/stay night', 'https://cdn.anidb.net/images/main/276301.jpg', '2006', 1, 'TV', 1),
(2, 'Fate/stay night[Unlimited Blade Works]', 'https://cdn.anidb.net/images/main/223748.jpg', '2010', 1, 'Movie', 1),
(3, 'Fate/stay night[Unlimited Blade Works]', 'https://cdn.anidb.net/images/main/223749.jpg', '2014', 4, 'TV', 1),
(4, '강철의 연금술사', 'https://cdn.anidb.net/images/main/221537.jpg', '2003', 4, 'TV', 2),
(5, '강철의 연금술사: 샴발라를 정복하는 자', 'https://cdn.anidb.net/images/main/221539.jpg', '2005', 3, 'Movie', 2),
(6, '강철의 연금술사 BROTHERHOOD', 'https://cdn.anidb.net/images/main/221536.jpg', '2009', 2, 'TV', 2);

-- --------------------------------------------------------

--
-- 테이블 구조 `History`
--

CREATE TABLE `History` (
  `history_id` int NOT NULL,
  `status` tinyint NOT NULL,
  `rating` decimal(10,0) NOT NULL,
  `anime_id` int NOT NULL,
  `user_id` int NOT NULL
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

CREATE TABLE `Series` (
  `seires_id` int NOT NULL,
  `seires_name` char(100) NOT NULL
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

CREATE TABLE `Users` (
  `user_id` int NOT NULL,
  `password` char(100) NOT NULL,
  `age` int NOT NULL,
  `login_id` char(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='회원 정보';

--
-- 테이블의 덤프 데이터 `Users`
--

INSERT INTO `Users` (`user_id`, `password`, `age`, `login_id`) VALUES
(1, '1234', 20, '1'),
(2, '1234', 30, '2');

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `Animes`
--
ALTER TABLE `Animes`
  ADD PRIMARY KEY (`anime_id`);

--
-- 테이블의 인덱스 `History`
--
ALTER TABLE `History`
  ADD PRIMARY KEY (`history_id`);

--
-- 테이블의 인덱스 `Series`
--
ALTER TABLE `Series`
  ADD PRIMARY KEY (`seires_id`);

--
-- 테이블의 인덱스 `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`user_id`);

--
-- 덤프된 테이블의 AUTO_INCREMENT
--

--
-- 테이블의 AUTO_INCREMENT `Animes`
--
ALTER TABLE `Animes`
  MODIFY `anime_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 테이블의 AUTO_INCREMENT `History`
--
ALTER TABLE `History`
  MODIFY `history_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 테이블의 AUTO_INCREMENT `Series`
--
ALTER TABLE `Series`
  MODIFY `seires_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 테이블의 AUTO_INCREMENT `Users`
--
ALTER TABLE `Users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
