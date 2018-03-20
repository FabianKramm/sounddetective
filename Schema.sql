-- phpMyAdmin SQL Dump
-- version 3.5.8.1
-- http://www.phpmyadmin.net
--
-- Host: dd21118
-- Erstellungszeit: 20. Mrz 2018 um 15:12
-- Server Version: 5.6.38-nmm1-log
-- PHP-Version: 5.5.38-nmm2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `d029d4b0`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `albums`
--

CREATE TABLE IF NOT EXISTS `albums` (
  `spotify_id` varchar(32) NOT NULL,
  `release_date` int(11) NOT NULL,
  PRIMARY KEY (`spotify_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `artists`
--

CREATE TABLE IF NOT EXISTS `artists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `popularity` int(11) DEFAULT NULL,
  `last_update_related` int(11) DEFAULT NULL,
  `last_update_related_calculated` int(11) DEFAULT NULL,
  `spotify_id` varchar(32) NOT NULL,
  `image_background` varchar(256) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `last_update` int(11) DEFAULT NULL,
  `image` varchar(256) DEFAULT NULL,
  `image_small` varchar(256) DEFAULT NULL,
  `last_update_top` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spotify_id` (`spotify_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `artists_related`
--

CREATE TABLE IF NOT EXISTS `artists_related` (
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  `importance` int(11) NOT NULL,
  PRIMARY KEY (`from_id`,`to_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `artists_related_calculated`
--

CREATE TABLE IF NOT EXISTS `artists_related_calculated` (
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  `difference` double NOT NULL,
  PRIMARY KEY (`from_id`,`to_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `charts`
--

CREATE TABLE IF NOT EXISTS `charts` (
  `genre_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `place` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`genre_id`,`song_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `detective`
--

CREATE TABLE IF NOT EXISTS `detective` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(32) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `image` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `min_song_pop` int(11) DEFAULT NULL,
  `max_song_pop` int(11) NOT NULL,
  `min_artist_pop` int(11) DEFAULT NULL,
  `max_artist_pop` int(11) DEFAULT NULL,
  `min_release_date` int(11) DEFAULT NULL,
  `max_release_date` int(11) DEFAULT NULL,
  `exclude_remix` int(11) DEFAULT NULL,
  `exclude_acoustic` int(11) DEFAULT NULL,
  `exclude_collection` int(11) DEFAULT NULL,
  `last_used` int(11) NOT NULL,
  `last_update` int(11) NOT NULL,
  `public_type` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `detective_artists`
--

CREATE TABLE IF NOT EXISTS `detective_artists` (
  `detective_id` int(11) NOT NULL,
  `artist_id` int(11) NOT NULL,
  `distance` double NOT NULL,
  PRIMARY KEY (`detective_id`,`artist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `detective_exclude_artists`
--

CREATE TABLE IF NOT EXISTS `detective_exclude_artists` (
  `detective_id` int(11) NOT NULL,
  `artist_id` int(11) NOT NULL,
  `last_update` int(11) NOT NULL,
  PRIMARY KEY (`detective_id`,`artist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `detective_exclude_songs`
--

CREATE TABLE IF NOT EXISTS `detective_exclude_songs` (
  `detective_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `last_update` int(11) NOT NULL,
  PRIMARY KEY (`detective_id`,`song_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ip2location`
--

CREATE TABLE IF NOT EXISTS `ip2location` (
  `ip_from` int(10) unsigned DEFAULT NULL,
  `ip_to` int(10) unsigned DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `country_name` varchar(64) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `songs`
--

CREATE TABLE IF NOT EXISTS `songs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(512) NOT NULL,
  `artists` varchar(512) NOT NULL,
  `created` int(11) NOT NULL,
  `last_update` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `songs_youtube`
--

CREATE TABLE IF NOT EXISTS `songs_youtube` (
  `song_id` int(11) NOT NULL,
  `video_id` varchar(32) NOT NULL,
  `country_code` char(2) DEFAULT NULL,
  `last_update` int(11) NOT NULL,
  PRIMARY KEY (`song_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tracks`
--

CREATE TABLE IF NOT EXISTS `tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) NOT NULL,
  `spotify_id` varchar(64) NOT NULL,
  `isrc` varchar(24) DEFAULT NULL,
  `current_popularity` int(11) NOT NULL,
  `crossed_popularity` int(11) DEFAULT NULL,
  `arist_id` int(11) DEFAULT NULL,
  `artist_pop` int(11) DEFAULT NULL,
  `artist_id2` int(11) DEFAULT NULL,
  `artist_id3` int(11) DEFAULT NULL,
  `release_date` int(11) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `is_remix` int(11) DEFAULT NULL,
  `is_acoustic` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  `last_update` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `password` varchar(128) DEFAULT NULL,
  `facebook_name` varchar(256) DEFAULT NULL,
  `facebook_id` varchar(128) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `spotify_name` varchar(256) DEFAULT NULL,
  `spotify_id` varchar(128) DEFAULT NULL,
  `last_login` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  `reset_code` varchar(128) DEFAULT NULL,
  `reset_valid_till` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_collection`
--

CREATE TABLE IF NOT EXISTS `user_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UserId` (`user_id`,`song_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_track`
--

CREATE TABLE IF NOT EXISTS `user_track` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(16) DEFAULT NULL,
  `ip` varchar(32) DEFAULT NULL,
  `browser` varchar(256) DEFAULT NULL,
  `platform` varchar(128) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(256) DEFAULT NULL,
  `params` text,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `wrong_video`
--

CREATE TABLE IF NOT EXISTS `wrong_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) NOT NULL,
  `ip` varchar(32) DEFAULT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
