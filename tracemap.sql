-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 27, 2015 at 05:12 PM
-- Server version: 10.1.9-MariaDB
-- PHP Version: 5.6.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tracemap`
--
CREATE DATABASE IF NOT EXISTS `tracemap` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `tracemap`;

-- --------------------------------------------------------

--
-- Table structure for table `hops`
--

CREATE TABLE `hops` (
  `id` int(11) UNSIGNED NOT NULL,
  `search_id` int(11) UNSIGNED NOT NULL,
  `hop_number` tinyint(3) UNSIGNED NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `ip` varchar(46) NOT NULL,
  `rtt1` double UNSIGNED NOT NULL,
  `rtt2` double UNSIGNED NOT NULL,
  `rtt3` double UNSIGNED NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ip_location_cache`
--

CREATE TABLE `ip_location_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(46) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `asn` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `country_code` varchar(255) NOT NULL,
  `isp` varchar(255) NOT NULL,
  `org` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `region_name` varchar(255) NOT NULL,
  `timezone` varchar(255) NOT NULL,
  `zip` varchar(20) NOT NULL,
  `latitude` varchar(50) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `search`
--

CREATE TABLE `search` (
  `id` int(11) UNSIGNED NOT NULL,
  `url` varchar(255) NOT NULL,
  `requester_ip` varchar(46) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hops`
--
ALTER TABLE `hops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ip_location_cache`
--
ALTER TABLE `ip_location_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `search`
--
ALTER TABLE `search`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hops`
--
ALTER TABLE `hops`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
--
-- AUTO_INCREMENT for table `ip_location_cache`
--
ALTER TABLE `ip_location_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `search`
--
ALTER TABLE `search`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
