SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `hops` (
  `id` int(11) UNSIGNED NOT NULL,
  `searchID` int(11) UNSIGNED NOT NULL,
  `hopNumber` tinyint(3) UNSIGNED NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `ip` varchar(46) NOT NULL,
  `rtt1` double NOT NULL,
  `rtt2` double NOT NULL,
  `rtt3` double NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `ip_locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(46) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `asn` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `countryCode` varchar(255) NOT NULL,
  `isp` varchar(255) NOT NULL,
  `org` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `regionName` varchar(255) NOT NULL,
  `timezone` varchar(255) NOT NULL,
  `zip` varchar(20) NOT NULL,
  `latitude` varchar(50) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `search` (
  `id` int(11) UNSIGNED NOT NULL,
  `url` varchar(255) NOT NULL,
  `requesterIP` varchar(46) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `hops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniqueHop` (`searchID`,`hopNumber`,`message`(767));

ALTER TABLE `ip_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniqueHost` (`ip`,`hostname`);

ALTER TABLE `search`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `hops`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ip_locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `search`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
