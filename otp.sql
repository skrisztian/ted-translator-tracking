-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 23, 2014 at 10:42 PM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";

--
-- Database: `archifa_otp`
--
CREATE DATABASE IF NOT EXISTS `archifa_otp` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `archifa_otp`;

-- --------------------------------------------------------

--
-- Table structure for table `otp_activity`
--

CREATE TABLE IF NOT EXISTS `otp_activity` (
  `id` bigint(20) unsigned NOT NULL,
  `comment` text,
  `created` datetime DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `language_url` varchar(250) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `user` varchar(50) DEFAULT NULL,
  `video_id` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `language_url` (`language_url`),
  KEY `type` (`type`),
  KEY `video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `otp_api_meta`
--

CREATE TABLE IF NOT EXISTS `otp_api_meta` (
  `total_count_name` varchar(50) NOT NULL,
  `total_count_value` bigint(20) unsigned DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`total_count_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `otp_languages`
--

CREATE TABLE IF NOT EXISTS `otp_languages` (
  `language_id` varchar(50) CHARACTER SET utf8 NOT NULL,
  `language_name` varchar(150) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`language_id`),
  UNIQUE KEY `language_name` (`language_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `otp_subtitles`
--

CREATE TABLE IF NOT EXISTS `otp_subtitles` (
  `video_id` varchar(250) NOT NULL,
  `created_hu` datetime DEFAULT NULL,
  `id` bigint(20) unsigned DEFAULT NULL,
  `is_original` tinyint(1) DEFAULT NULL,
  `is_translation` tinyint(1) DEFAULT NULL,
  `language_code` varchar(50) DEFAULT NULL,
  `speaker_name` varchar(250) DEFAULT NULL,
  `num_versions` int(20) unsigned DEFAULT NULL,
  `official_signoff_count` int(11) DEFAULT NULL,
  `original_language_code` varchar(50) DEFAULT NULL,
  `reviewer` varchar(50) DEFAULT NULL,
  `approver` varchar(50) DEFAULT NULL,
  `subtitle_count` bigint(20) unsigned DEFAULT NULL,
  `title_hu` varchar(250) DEFAULT NULL,
  `title_orig` varchar(250) DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT NULL,
  `project` varchar(50) DEFAULT NULL,
  `subtitles_complete` tinyint(1) DEFAULT NULL,
  `ted_link` varchar(250) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_orig` datetime DEFAULT NULL,
  PRIMARY KEY (`video_id`),
  KEY `video_id` (`video_id`),
  KEY `created_hu` (`created_hu`),
  KEY `id` (`id`),
  KEY `is_original` (`is_original`),
  KEY `is_translation` (`is_translation`),
  KEY `language_code` (`language_code`),
  KEY `speaker_name` (`speaker_name`),
  KEY `num_versions` (`num_versions`),
  KEY `original_language_code` (`original_language_code`),
  KEY `reviewer` (`reviewer`),
  KEY `approver` (`approver`),
  KEY `subtitle_count` (`subtitle_count`),
  KEY `title_hu` (`title_hu`),
  KEY `title_orig` (`title_orig`),
  KEY `duration` (`duration`),
  KEY `poject` (`project`),
  KEY `subtitles_complete` (`subtitles_complete`),
  KEY `ted_link` (`ted_link`),
  KEY `last_update` (`last_update`),
  KEY `created_orig` (`created_orig`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `otp_subtitle_versions`
--

CREATE TABLE IF NOT EXISTS `otp_subtitle_versions` (
  `video_id` varchar(50) NOT NULL,
  `version_no` smallint(5) unsigned NOT NULL,
  `author` varchar(50) DEFAULT NULL,
  `published` tinyint(1) DEFAULT NULL,
  `text_change` float DEFAULT NULL,
  `time_change` float DEFAULT NULL,
  `diff_no` bigint(20) unsigned DEFAULT NULL,
  UNIQUE KEY `video_id` (`video_id`,`version_no`),
  KEY `author` (`author`),
  KEY `published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `otp_tasks`
--

CREATE TABLE IF NOT EXISTS `otp_tasks` (
  `id` bigint(20) unsigned NOT NULL,
  `approved` tinyint(3) unsigned DEFAULT NULL,
  `assignee` varchar(50) DEFAULT NULL,
  `completed` datetime DEFAULT NULL,
  `type` enum('Translate','Review','Approve','Subtitle') DEFAULT NULL,
  `video_id` varchar(50) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `priority` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `approved` (`approved`),
  KEY `assignee` (`assignee`),
  KEY `completed` (`completed`),
  KEY `type` (`type`),
  KEY `video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `otp_translators`
--

CREATE TABLE IF NOT EXISTS `otp_translators` (
  `amara_id` varchar(50) NOT NULL,
  `ted_id` bigint(20) unsigned DEFAULT NULL,
  `full_name` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `first_name` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `last_name` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `amara_pic_link` varchar(250) DEFAULT NULL,
  `ted_pic_link` varchar(250) DEFAULT NULL,
  `amara_role` enum('contributor','manager','owner') NOT NULL,
  `amara_ted_member` tinyint(1) DEFAULT '1' NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`amara_id`),
  KEY `ted_id` (`ted_id`),
  KEY `full_name` (`full_name`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`),
  KEY `amara_pic_link` (`amara_pic_link`),
  KEY `ted_pic_link` (`ted_pic_link`),
  KEY `amara_role` (`amara_role`),
  KEY `amara_ted_member` (`amara_ted_member`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `otp_translator_languages`
--

CREATE TABLE IF NOT EXISTS `otp_translator_languages` (
  `amara_id` varchar(50) NOT NULL,
  `language_id` varchar(50) NOT NULL,
  KEY `amara_id` (`amara_id`),
  KEY `language_id` (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ted_translators`
--

CREATE TABLE IF NOT EXISTS `ted_translators` (
  `ted_id` bigint(20) unsigned NOT NULL,
  `ted_full_name` varchar(250) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ted_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;