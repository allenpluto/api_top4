-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 25, 2016 at 05:19 AM
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
-- Table structure for table `tbl_rel_api_to_api_method`
--

DROP TABLE IF EXISTS `tbl_rel_api_to_api_method`;
CREATE TABLE IF NOT EXISTS `tbl_rel_api_to_api_method` (
  `api_id` int(11) NOT NULL,
  `api_method_id` int(11) NOT NULL,
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `relationship` varchar(100) NOT NULL DEFAULT 'allow',
  PRIMARY KEY (`api_id`,`api_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_rel_api_to_api_method`
--

INSERT INTO `tbl_rel_api_to_api_method` (`api_id`, `api_method_id`, `enter_time`, `update_time`, `relationship`) VALUES
(10001, 401, '2016-10-24 00:00:05', '2016-10-24 00:00:05', 'allow'),
(10001, 420, '2016-10-24 00:00:05', '2016-10-24 00:00:05', 'allow'),
(10002, 420, '2016-10-21 03:57:30', '2016-10-21 03:57:30', 'allow'),
(10003, 100, '2016-11-15 06:12:26', '2016-11-15 06:12:26', 'allow'),
(10003, 101, '2016-11-15 06:12:16', '2016-11-15 06:12:16', 'allow'),
(10003, 410, '2016-11-15 06:21:30', '2016-11-15 06:21:30', 'allow'),
(10003, 411, '2016-11-15 06:21:30', '2016-11-15 06:21:30', 'allow');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
