-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 25, 2016 at 02:15 AM
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
-- Table structure for table `tbl_entity_api_method`
--

DROP TABLE IF EXISTS `tbl_entity_api_method`;
CREATE TABLE IF NOT EXISTS `tbl_entity_api_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendly_uri` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alternate_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_id` int(11) NOT NULL DEFAULT '-1',
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `field` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=403 ;

--
-- Dumping data for table `tbl_entity_api_method`
--

INSERT INTO `tbl_entity_api_method` (`id`, `friendly_uri`, `name`, `alternate_name`, `description`, `image_id`, `enter_time`, `update_time`, `field`) VALUES
(1, 'list_available_method', 'List Available Method', '', 'Basic Function, accessible by anyone who has a valid api token, list available method for current account', -1, '2016-10-21 03:27:58', '2016-10-23 22:48:12', ''),
(100, 'insert_account', 'Insert Account', '', 'Insert one or multiple new Accounts', -1, '2016-10-21 03:29:17', '2016-10-23 22:59:16', ''),
(101, 'insert_business', 'Insert Business', '', 'Insert one or multiple new Businesses', -1, '2016-10-21 03:29:47', '2016-10-24 03:11:26', '[{"name":"name","type":"String","max_length":"250","description":"Business Name","mandatory":"true"},{"name":"street_address","type":"String","max_length":"500","description":"Street Address for the business, POB is not acceptable","mandatory":"true"}]'),
(200, 'delete_account', 'Delete Account', '', 'Delete one or multiple Accounts', -1, '2016-10-21 03:31:06', '2016-10-23 22:59:25', ''),
(201, 'delete_business', 'Delete Business', '', 'Delete one or multiple Businesses', -1, '2016-10-21 03:32:03', '2016-10-23 22:59:30', ''),
(300, 'update_account', 'Update Account', '', 'Update one or multiple account information', -1, '2016-10-21 03:34:42', '2016-10-23 22:59:36', ''),
(301, 'update_business', 'Update Business', '', 'Update one or multiple business information', -1, '2016-10-21 03:35:24', '2016-10-23 22:59:40', ''),
(400, 'select_account', 'Select Account', '', 'Get one or multiple account details by id', -1, '2016-10-21 03:37:41', '2016-10-23 22:59:44', ''),
(401, 'select_business', 'Select Business', '', 'Get one or multiple business details by id', -1, '2016-10-21 03:37:41', '2016-10-24 03:22:37', '[{"name":"id","type":"Integer","description":"Listing ID","mandatory":"true"}]'),
(402, 'select_business_by_uri', 'Select Business by URI', '', 'Search for business(es) with given web uri', -1, '2016-10-21 03:39:27', '2016-10-24 03:24:06', '[{"name":"uri","type":"String","max_length":"200","description":"Website URI, e.g. www.top4.com.au","mandatory":"true"}]');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
