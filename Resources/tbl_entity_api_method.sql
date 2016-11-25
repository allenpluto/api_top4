-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 25, 2016 at 12:51 AM
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
  `friendly_uri` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alternate_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_id` int(11) NOT NULL DEFAULT '-1',
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `field` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=421 ;

--
-- Dumping data for table `tbl_entity_api_method`
--

INSERT INTO `tbl_entity_api_method` (`id`, `friendly_uri`, `name`, `alternate_name`, `description`, `image_id`, `enter_time`, `update_time`, `field`) VALUES
(1, 'list_available_method', 'List Available Method', '', 'Basic Function, accessible by anyone who has a valid api token, list available method for current account', -1, '2016-10-21 03:27:58', '2016-10-23 22:48:12', ''),
(100, 'insert_account', 'Insert Account', '', 'Create new Account', -1, '2016-10-21 03:29:17', '2016-11-17 06:21:34', '[{"name":"username","type":"String","length":100,"mandatory":"true","description":"user unique identification, Email address, e.g. allen@top4.com.au"},{"name":"first_name","type":"String","length":50,"mandatory":"true","description":"user first name, e.g. Allen"},{"name":"last_name","type":"String","length":50,"mandatory":"true","description":"user last name, e.g. Shrestha"},{"name":"password","type":"String","length":20,"mandatory":"false","description":"user password to login, if not provided, system will automatically generate a random password of 8 characters string"},{"name":"company","type":"String","length":50,"mandatory":"false","description":"company name for user"},{"name":"address","type":"String","length":50,"mandatory":"false","description":"street address, e.g. 339 Windsor Rd"},{"name":"address2","type":"String","length":50,"mandatory":"false","description":"street address additional info, unit number, level, subpremise, e.g. Unit 2"},{"name":"city","type":"String","length":50,"mandatory":"false","description":"suburb, e.g. Baulkham Hills"},{"name":"state","type":"String","length":50,"mandatory":"false","description":"state, e.g. NSW"},{"name":"zip","type":"String","length":4,"mandatory":"false","description":"postcode, e.g. 2153"},{"name":"latitude","type":"Decimal","length":"11,8","mandatory":"false","description":"geo location latitude, e.g. -34.56314822"},{"name":"longitude","type":"Decimal","length":"11,8","mandatory":"false","description":"geo location longitude, e.g. 150.47264858"},{"name":"phone","type":"String","length":50,"mandatory":"false","description":"personal contact number, e.g. 0412499255"},{"name":"fax","type":"String","length":50,"mandatory":"false","description":"personal contact fax, e.g. 0296552722"},{"name":"email","type":"String","length":50,"mandatory":"false","description":"personal contact email, e.g. example@gmail.com"},{"name":"url","type":"String","length":50,"mandatory":"false","description":"personal website, e.g. www.mywebsite.com"},{"name":"nickname","type":"String","length":50,"mandatory":"false","description":"nickname, user alias on top4, e.g. Brilliant Scientist"},{"name":"personal_message","type":"String","length":500,"mandatory":"false","description":"self introduction, e.g. Dr Shrestha has worked in digital marketing industry for over 15 years."}]'),
(101, 'insert_business', 'Insert Business', '', 'Create new Business', -1, '2016-10-21 03:29:47', '2016-11-18 05:30:37', '[{"name":"title","type":"String","length":"200","mandatory":"true","description":"Business Name"},{"name":"latitude","type":"Decimal","length":"11,8","mandatory":"true","description":"Business Geo Location Latitude, e.g. -37.81936431"},{"name":"longitude","type":"Decimal","length":"11,8","mandatory":"true","description":"Business Geo Location Longitude, e.g. 144.99874667"},{"name":"category","type":"String","length":"200","mandatory":"true","description":"Business Category, any schema category that belong to http:\\/\\/schema.org\\/LocalBusiness, e.g. HomeAndConstructionBusiness,Plumber"},{"name":"abn","type":"String","length":"50","mandatory":"false","description":"Australian Business Number, 11 digits number without spacing or special characters, e.g. 43121890435"},{"name":"address","type":"String","length":"50","mandatory":"false","description":"Street Address only, street number and route name that Google can recognize, e.g. 331 Windsor Road"},{"name":"address2","type":"String","length":"50","mandatory":"false","description":"Additional address information, company name, unit number, level and etc. e.g. Level 2, Web Guys Agency"},{"name":"city","type":"String","length":"200","mandatory":"false","description":"Australian suburb name, please use the Australian POST standard name, e.g. CAROLINE SPRINGS"},{"name":"state","type":"String","length":"50","mandatory":"false","description":"Australian state, max 3 characters short name, e.g. NSW, VIC, QLD, WA, SA, TAS, ACT"},{"name":"zip","type":"String","length":"10","mandatory":"false","description":"Australian postcode, 4 digits number, e.g. 0810, 2153"},{"name":"phone","type":"String","length":"50","mandatory":"false","description":"Phone number, 6 - 10 digits number without spacing or special characters, e.g. 0293168372, 131612"},{"name":"alternate_phone","type":"String","length":"50","mandatory":"false","description":"Phone number, 6 - 10 digits number without spacing or special characters, e.g. 0293168372, 131612"},{"name":"mobile_phone","type":"String","length":"50","mandatory":"false","description":"Mobile Phone number, 10 digits number without spacing or special characters, e.g. 0432966233"},{"name":"fax","type":"String","length":"50","mandatory":"false","description":"Fax number, 6 - 10 digits number without spacing or special characters, e.g. 0293168372, 131612"},{"name":"email","type":"String","length":"50","mandatory":"false","description":"Email address"},{"name":"url","type":"String","length":"200","mandatory":"false","description":"Business website url, start with http or https, e.g. http:\\/\\/www.example.com.au\\/"},{"name":"facebook_link","type":"String","length":"200","mandatory":"false","description":"Business Facebook landing page, e.g. https:\\/\\/www.facebook.com\\/Example-Business\\/"},{"name":"twitter_link","type":"String","length":"200","mandatory":"false","description":"Business Twitter landing page, e.g. https:\\/\\/www.twitter.com\\/Example"},{"name":"linkedin_link","type":"String","length":"200","mandatory":"false","description":"Business LinkedIN landing page, e.g. https:\\/\\/www.linkedin.com\\/company\\/example-business"},{"name":"blog_link","type":"String","length":"200","mandatory":"false","description":"Business blog page, e.g. https:\\/\\/blog.example.com.au, https:\\/\\/exampleuser.wordpress.com\\/business\\/"},{"name":"pinterest_link","type":"String","length":"50","mandatory":"false","description":"Business Pinterest landing page, e.g. https:\\/\\/www.pinterest.com\\/example\\/"},{"name":"googleplus_link","type":"String","length":"200","mandatory":"false","description":"Business Googleplus landing page, e.g. https:\\/\\/plus.google.com\\/+ExampleBusiness\\/"},{"name":"business_type","type":"String","length":"20","mandatory":"false","description":"Business Type, small (0-10 people), medium (10-50 people), large (51+ people), brand, default to small e.g. medium"},{"name":"description","type":"String","length":"200","mandatory":"false","description":"Short Description, short summary for business, use for introduction, meta description..., e.g. XXXX is Australia\\u2019s leading XXXX brand and is focused on innovative solutions and impeccable design specialising in ...."},{"name":"long_description","type":"String","length":"2000","mandatory":"false","description":"Long Description, main body content of business, use for overview"},{"name":"keywords","type":"String","length":"500","mandatory":"false","description":"Keywords phrases, separate by line breaker"}]'),
(200, 'delete_account', 'Delete Account', '', 'Delete one or multiple Accounts', -1, '2016-10-21 03:31:06', '2016-10-23 22:59:25', ''),
(201, 'delete_business', 'Delete Business', '', 'Delete one or multiple Businesses', -1, '2016-10-21 03:32:03', '2016-10-23 22:59:30', ''),
(300, 'update_account', 'Update Account', '', 'Update one or multiple account information', -1, '2016-10-21 03:34:42', '2016-10-23 22:59:36', ''),
(301, 'update_business', 'Update Business', '', 'Update one or multiple business information', -1, '2016-10-21 03:35:24', '2016-10-23 22:59:40', ''),
(400, 'select_account', 'Select Account', '', 'Get one or multiple account details by id', -1, '2016-10-21 03:37:41', '2016-10-23 22:59:44', ''),
(401, 'select_business', 'Select Business', '', 'Get one or multiple business details by id', -1, '2016-10-21 03:37:41', '2016-10-24 03:22:37', '[{"name":"id","type":"Integer","description":"Listing ID","mandatory":"true"}]'),
(410, 'select_account_by_username', 'Select Account by Username', '', 'Get account details by username', -1, '2016-11-16 06:26:14', '2016-11-17 04:25:19', '[{"name":"username","type":"String","length":100,"mandatory":"true","description":"user unique identification, Email address, e.g. allen@top4.com.au"}]'),
(411, 'select_account_by_token', 'Select Account by Token', '', 'Get account details by token', -1, '2016-11-16 06:26:14', '2016-11-17 04:24:30', '[{"name":"token","type":"String","length":100,"mandatory":"true","description":"user request token, e.g. 145d243fc763c2ad4c09ba2b250a60e7"}]'),
(420, 'select_business_by_uri', 'Select Business by URI', '', 'Search for business(es) with given web uri', -1, '2016-10-21 03:39:27', '2016-11-17 04:24:45', '[{"name":"uri","type":"String","length":"200","description":"Website URI, e.g. www.top4.com.au","mandatory":"true"}]');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
