-- phpMyAdmin SQL Dump
-- version 4.0.10.14
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Oct 25, 2016 at 05:18 PM
-- Server version: 5.6.33
-- PHP Version: 5.6.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `top4_domain1`
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
(10002, 402, '2016-10-21 03:57:30', '2016-10-21 03:57:30', 'allow');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
