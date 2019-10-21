-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 21, 2019 at 06:51 PM
-- Server version: 10.2.23-MariaDB-log
-- PHP Version: 7.3.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `caglartest`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblGiftType`
--

CREATE TABLE `tblGiftType` (
  `gtID` tinyint(3) UNSIGNED NOT NULL,
  `gtName` varchar(50) COLLATE utf8_turkish_ci NOT NULL,
  `gtOrder` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `gtStatus` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Dumping data for table `tblGiftType`
--

INSERT INTO `tblGiftType` (`gtID`, `gtName`, `gtOrder`, `gtStatus`) VALUES
(1, 'Free Coin', 0, 1),
(2, 'Life', 1, 1),
(3, 'Bonus Pass', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblUser`
--

CREATE TABLE `tblUser` (
  `userID` bigint(20) UNSIGNED NOT NULL,
  `userUID` varchar(32) COLLATE utf8_turkish_ci NOT NULL,
  `userEmail` varchar(255) COLLATE utf8_turkish_ci NOT NULL,
  `userPassword` varchar(50) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Password hash',
  `userFullName` varchar(255) COLLATE utf8_turkish_ci NOT NULL,
  `userCoin` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `userLifeCount` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `userItems` text COLLATE utf8_turkish_ci NOT NULL,
  `userStatus` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Dumping data for table `tblUser`
--

INSERT INTO `tblUser` (`userID`, `userUID`, `userEmail`, `userPassword`, `userFullName`, `userCoin`, `userLifeCount`, `userItems`, `userStatus`) VALUES
(1, '31188F6196634D01973ACC821E09CCEA', 'caglaryildirim@gmail.com', '513106c051f94528f1d386926aa65e1a', 'Caglar Yildirim', 0, 5, '', 1),
(2, 'D266A4C71A6E43C7B457F34E0865D033', 'user2@gmail.com', '513106c051f94528f1d386926aa65e1a', 'User 2', 0, 5, '', 1),
(3, 'D266A4C71A6E43C7B457F34E0865D032', 'user3@gmail.com', '513106c051f94528f1d386926aa65e1a', 'User 3', 0, 5, '', 1),
(4, 'D266A4C71A6E43C7B457F34E0865D045', 'user4@gmail.com', '513106c051f94528f1d386926aa65e1a', 'User 4', 0, 5, '', 1),
(5, 'D266A4C71A6E43C7B457F34E0865D056', 'user5@gmail.com', '513106c051f94528f1d386926aa65e1a', 'User 4', 0, 5, '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblUserFriend`
--

CREATE TABLE `tblUserFriend` (
  `friendSourceUserID` bigint(20) NOT NULL,
  `friendTargetUserID` bigint(20) NOT NULL,
  `friendBlocked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Dumping data for table `tblUserFriend`
--

INSERT INTO `tblUserFriend` (`friendSourceUserID`, `friendTargetUserID`, `friendBlocked`) VALUES
(1, 2, 0),
(1, 3, 0),
(1, 4, 0),
(2, 1, 0),
(3, 1, 0),
(4, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tblUserGiftLog`
--

CREATE TABLE `tblUserGiftLog` (
  `glID` bigint(20) UNSIGNED NOT NULL,
  `fromUserID` bigint(20) NOT NULL,
  `toUserID` bigint(20) NOT NULL,
  `giftDate` date NOT NULL,
  `giftTime` int(10) UNSIGNED NOT NULL COMMENT 'Gift send time as UTC timestamp',
  `giftSendType` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblUserGiftQueue`
--

CREATE TABLE `tblUserGiftQueue` (
  `fromUserID` bigint(20) NOT NULL,
  `toUserID` bigint(20) NOT NULL,
  `giftDate` date NOT NULL,
  `giftTime` int(10) UNSIGNED NOT NULL COMMENT 'Gift send time as UTC timestamp',
  `giftSendType` tinyint(3) UNSIGNED NOT NULL,
  `giftAccepted` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0: Waiting, 1: Accepted, 2: Expired'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblGiftType`
--
ALTER TABLE `tblGiftType`
  ADD PRIMARY KEY (`gtID`);

--
-- Indexes for table `tblUser`
--
ALTER TABLE `tblUser`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `userUID` (`userUID`),
  ADD UNIQUE KEY `userEmail` (`userEmail`);

--
-- Indexes for table `tblUserFriend`
--
ALTER TABLE `tblUserFriend`
  ADD PRIMARY KEY (`friendSourceUserID`,`friendTargetUserID`);

--
-- Indexes for table `tblUserGiftLog`
--
ALTER TABLE `tblUserGiftLog`
  ADD PRIMARY KEY (`glID`),
  ADD KEY `FK_UserGiftType` (`giftSendType`),
  ADD KEY `IX_UserSocialScore` (`fromUserID`,`giftDate`);

--
-- Indexes for table `tblUserGiftQueue`
--
ALTER TABLE `tblUserGiftQueue`
  ADD PRIMARY KEY (`fromUserID`,`toUserID`,`giftDate`),
  ADD KEY `FK_UserGiftType` (`giftSendType`),
  ADD KEY `IX_UserIncomingGifts` (`toUserID`,`giftAccepted`,`giftTime`) USING BTREE,
  ADD KEY `IX_GiftExpire` (`giftDate`,`giftAccepted`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblGiftType`
--
ALTER TABLE `tblGiftType`
  MODIFY `gtID` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblUser`
--
ALTER TABLE `tblUser`
  MODIFY `userID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblUserGiftLog`
--
ALTER TABLE `tblUserGiftLog`
  MODIFY `glID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblUserGiftQueue`
--
ALTER TABLE `tblUserGiftQueue`
  ADD CONSTRAINT `FK_UserGiftType` FOREIGN KEY (`giftSendType`) REFERENCES `tblGiftType` (`gtID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
