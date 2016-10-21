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
-- Table structure for table `tbl_rel_api_to_api_method`
--

DROP TABLE IF EXISTS `tbl_rel_api_to_api_method`;
CREATE TABLE IF NOT EXISTS `tbl_rel_api_to_api_method` (
  `api_id` int(11) NOT NULL,
  `api_method_id` int(11) NOT NULL,
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `relationship` varchar(100) NOT NULL DEFAULT 'authorized manager',
  PRIMARY KEY (`api_id`,`api_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_rel_api_to_api_method`
--

INSERT INTO `tbl_rel_api_to_api_method` (`api_id`, `api_method_id`, `enter_time`, `update_time`, `relationship`) VALUES
(2, 402, '2016-10-21 03:57:30', '2016-10-21 03:57:30', 'authorized manager');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
