-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 28, 2021 at 12:39 PM
-- Server version: 8.0.25-0ubuntu0.20.04.1
-- PHP Version: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `kadro_acl`
--

CREATE TABLE `kadro_acl` (
  `operator_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kadro_action_logger`
--

CREATE TABLE `kadro_action_logger` (
  `query_table` varchar(88) NOT NULL,
  `query_id` int NOT NULL,
  `query_type` char(1) NOT NULL COMMENT 'CRUD',
  `query_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `query_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kadro_language_code`
--

CREATE TABLE `kadro_language_code` (
  `id` int NOT NULL,
  `Part3` char(3) NOT NULL COMMENT 'Id in original dump',
  `Part2B` char(3) DEFAULT NULL COMMENT 'Part2B in original dump',
  `Part2T` char(3) DEFAULT NULL COMMENT 'Part2T in original dump',
  `Part1` char(2) DEFAULT NULL COMMENT 'Part1 in original dump',
  `Scope` char(1) NOT NULL COMMENT 'Scope in original dump',
  `Type` char(1) NOT NULL COMMENT 'Type in original dump',
  `Ref_Name` varchar(150) NOT NULL COMMENT 'Ref_Name in original dump',
  `Comment` varchar(150) DEFAULT NULL COMMENT 'Comment in original dump'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kadro_operator`
--

CREATE TABLE `kadro_operator` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'dont delete operators',
  `name` varchar(255) NOT NULL,
  `email` text,
  `phone` varchar(30) DEFAULT NULL,
  `language_code` varchar(3) NOT NULL DEFAULT 'fra' COMMENT 'iso-639-3 code',
  `note` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kadro_permission`
--

CREATE TABLE `kadro_permission` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kadro_traduki`
--

CREATE TABLE `kadro_traduki` (
  `id` int NOT NULL,
  `kategorio` varchar(50) NOT NULL COMMENT 'kadro, models, menus',
  `sekcio` varchar(50) NOT NULL COMMENT 'err, model type, ..',
  `referenco` varchar(100) NOT NULL,
  `epo` text,
  `fra` text,
  `nld` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kadro_acl`
--
ALTER TABLE `kadro_acl`
  ADD PRIMARY KEY (`operator_id`,`permission_id`),
  ADD KEY `kadro_acl-has-kadro_permission.id` (`permission_id`);

--
-- Indexes for table `kadro_action_logger`
--
ALTER TABLE `kadro_action_logger`
  ADD PRIMARY KEY (`query_table`,`query_id`,`query_type`,`query_on`);

--
-- Indexes for table `kadro_language_code`
--
ALTER TABLE `kadro_language_code`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ISO_3` (`Part3`);

--
-- Indexes for table `kadro_operator`
--
ALTER TABLE `kadro_operator`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `MODEL_operator_USAGE_IS_UNIQUE_username` (`username`) USING BTREE;

--
-- Indexes for table `kadro_permission`
--
ALTER TABLE `kadro_permission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `MODEL_permission_USAGE_IS_UNIQUE_name` (`name`) USING BTREE;

--
-- Indexes for table `kadro_traduki`
--
ALTER TABLE `kadro_traduki`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kategorio` (`kategorio`,`sekcio`,`referenco`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kadro_language_code`
--
ALTER TABLE `kadro_language_code`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7858;

--
-- AUTO_INCREMENT for table `kadro_operator`
--
ALTER TABLE `kadro_operator`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kadro_permission`
--
ALTER TABLE `kadro_permission`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kadro_traduki`
--
ALTER TABLE `kadro_traduki`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kadro_acl`
--
ALTER TABLE `kadro_acl`
  ADD CONSTRAINT `kadro_acl-has-kadro_operator.id` FOREIGN KEY (`operator_id`) REFERENCES `kadro_operator` (`id`),
  ADD CONSTRAINT `kadro_acl-has-kadro_permission.id` FOREIGN KEY (`permission_id`) REFERENCES `kadro_permission` (`id`);
COMMIT;
