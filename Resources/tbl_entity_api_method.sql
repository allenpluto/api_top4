-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Oct 21, 2016 at 06:05 AM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

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
-- Table structure for table `tbl_entity_api_method`
--

DROP TABLE IF EXISTS `tbl_entity_api_method`;
CREATE TABLE IF NOT EXISTS `tbl_entity_api_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendly_url` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alternate_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_id` int(11) NOT NULL DEFAULT '-1',
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=403 ;

--
-- Dumping data for table `tbl_entity_api_method`
--

INSERT INTO `tbl_entity_api_method` (`id`, `friendly_url`, `name`, `alternate_name`, `description`, `image_id`, `enter_time`, `update_time`) VALUES
(1, 'list-available-method', 'List Available Method', '', 'Basic Function, accessible by anyone who has a valid api token, list available method for current account', -1, '2016-10-21 03:27:58', '2016-10-21 03:27:58'),
(100, 'insert-account', 'Insert Account', '', 'Insert one or multiple new Accounts', -1, '2016-10-21 03:29:17', '2016-10-21 03:35:47'),
(101, 'insert-business', 'Insert Business', '', 'Insert one or multiple new Businesses', -1, '2016-10-21 03:29:47', '2016-10-21 03:35:52'),
(200, 'delete-account', 'Delete Account', '', 'Delete one or multiple Accounts', -1, '2016-10-21 03:31:06', '2016-10-21 03:35:57'),
(201, 'delete-business', 'Delete Business', '', 'Delete one or multiple Businesses', -1, '2016-10-21 03:32:03', '2016-10-21 03:36:01'),
(300, 'update-account', 'Update Account', '', 'Update one or multiple account information', -1, '2016-10-21 03:34:42', '2016-10-21 03:36:06'),
(301, 'update-business', 'Update Business', '', 'Update one or multiple business information', -1, '2016-10-21 03:35:24', '2016-10-21 03:36:11'),
(400, 'select-account', 'Select Account', '', 'Get one or multiple account details by id', -1, '2016-10-21 03:37:41', '2016-10-21 03:38:18'),
(401, 'select-business', 'Select Business', '', 'Get one or multiple business details by id', -1, '2016-10-21 03:37:41', '2016-10-21 03:37:41'),
(402, 'select-business-by-uri', 'Select Business by URI', '', 'Search for business(es) with given web uri', -1, '2016-10-21 03:39:27', '2016-10-21 03:39:27');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
