-- phpMyAdmin SQL Dump
-- version 4.5.0.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 11. Jan 2016 um 14:08
-- Server-Version: 10.0.17-MariaDB
-- PHP-Version: 5.6.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `tracemap`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hops`
--

CREATE TABLE `hops` (
  `id` int(11) UNSIGNED NOT NULL,
  `searchID` int(11) UNSIGNED NOT NULL,
  `hopNumber` tinyint(3) UNSIGNED NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `ip` varchar(46) NOT NULL,
  `rtt1` double UNSIGNED NOT NULL,
  `rtt2` double UNSIGNED NOT NULL,
  `rtt3` double UNSIGNED NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ip_locations`
--

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
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `search`
--

CREATE TABLE `search` (
  `id` int(11) UNSIGNED NOT NULL,
  `url` varchar(255) NOT NULL,
  `requesterIP` varchar(46) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `hops`
--
ALTER TABLE `hops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniqueHop` (`searchID`,`hopNumber`,`message`(767));

--
-- Indizes für die Tabelle `ip_locations`
--
ALTER TABLE `ip_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `search`
--
ALTER TABLE `search`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `hops`
--
ALTER TABLE `hops`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `ip_locations`
--
ALTER TABLE `ip_locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `search`
--
ALTER TABLE `search`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
