-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 25, 2016 at 12:50 AM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `allen_frame_trial`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_entity_api_key`
--

DROP TABLE IF EXISTS `tbl_entity_api_key`;
CREATE TABLE IF NOT EXISTS `tbl_entity_api_key` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendly_uri` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alternate_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_id` int(11) NOT NULL DEFAULT '0',
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `account_id` int(11) NOT NULL,
  `ip_restriction` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=25 ;

--
-- Dumping data for table `tbl_entity_api_key`
--

INSERT INTO `tbl_entity_api_key` (`id`, `friendly_uri`, `name`, `alternate_name`, `description`, `image_id`, `enter_time`, `update_time`, `account_id`, `ip_restriction`) VALUES
(2, '', '36ca-e750-31af-9f80-4018-0f31-b7eb-3f03', '', '', 0, '2016-10-17 05:18:34', '2016-10-17 05:21:07', 10001, '111.167.5.248'),
(3, '', '0650-2370-f1fa-24bf-019d-800f-a1b3-cf66', '', '', 0, '2016-10-17 06:24:26', '2016-11-20 22:52:11', 10001, '*.*.*.*,192.168.1.*,111.167.5.248,127.0.0.*,127.0.0.1'),
(4, '', 'aa6e-a00c-b4c1-ff24-1a13-8f9f-e09b-cfaf', '', '', 0, '2016-10-17 06:31:21', '2016-11-10 03:28:15', 10002, '*.*.*.*,127.0.0.1,111.167.5.248'),
(5, '', '4f69-ed90-c1c8-ff49-efa1-9f14-3b84-9f26', '', '', 0, '2016-10-24 21:48:16', '2016-11-10 04:36:20', 10002, '127.0.0.1'),
(6, '', 'c6b5-e010-db1d-3f46-c109-ff40-0bbc-dbf1', '', '', 0, '2016-11-01 00:49:55', '2016-11-01 00:49:55', 10001, ''),
(24, '', '3813-506e-87b1-a11a-3714-ce9f-4c91-2413', 'Crazy Domain API Key 1', '', 0, '2016-11-23 04:26:05', '2016-11-23 04:26:05', 10003, '127.0.0.1');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
