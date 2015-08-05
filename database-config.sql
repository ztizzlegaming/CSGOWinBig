-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 05, 2015 at 08:30 AM
-- Server version: 5.5.42-37.1-log
-- PHP Version: 5.4.23
--
-- Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

DROP TABLE IF EXISTS `chat`;
CREATE TABLE IF NOT EXISTS `chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `steamUserID` bigint(11) NOT NULL,
  `text` text COLLATE latin1_general_ci NOT NULL,
  `date` text COLLATE latin1_general_ci NOT NULL,
  `time` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=125 ;

-- --------------------------------------------------------

--
-- Table structure for table `currentPot`
--

DROP TABLE IF EXISTS `currentPot`;
CREATE TABLE IF NOT EXISTS `currentPot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contextId` int(11) NOT NULL,
  `assetId` int(11) NOT NULL,
  `ownerSteamId` text NOT NULL,
  `ownerSteamId32` text NOT NULL,
  `itemName` text NOT NULL,
  `itemPrice` int(11) NOT NULL,
  `itemRarityName` text NOT NULL,
  `itemRarityColor` varchar(6) NOT NULL,
  `itemIcon` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
CREATE TABLE IF NOT EXISTS `history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `winnerSteamID` bigint(20) NOT NULL,
  `userPutInPrice` int(11) NOT NULL,
  `potPrice` int(11) NOT NULL,
  `allItemsJson` text NOT NULL,
  `paid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
