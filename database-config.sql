-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 29, 2015 at 08:57 AM
-- Server version: 5.5.42-37.1-log
-- PHP Version: 5.4.23

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=118 ;

-- --------------------------------------------------------

--
-- Table structure for table `currentPot`
--

DROP TABLE IF EXISTS `currentPot`;
CREATE TABLE IF NOT EXISTS `currentPot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ownerSteamID` bigint(11) NOT NULL,
  `itemName` text NOT NULL,
  `itemPrice` int(11) NOT NULL,
  `itemIcon` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

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
  `allItems` text NOT NULL,
  `paid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `nextPot`
--

DROP TABLE IF EXISTS `nextPot`;
CREATE TABLE IF NOT EXISTS `nextPot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ownerSteamID` bigint(11) NOT NULL,
  `itemName` text NOT NULL,
  `itemPrice` int(11) NOT NULL,
  `itemIcon` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `steamUserID` bigint(20) NOT NULL,
  `steamAvatarURL` text COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
