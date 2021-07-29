SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

DROP TABLE IF EXISTS `kadro_language_code`;
DROP TABLE IF EXISTS `kadro_traduki`;
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

--
-- Indexes for table `kadro_language_code`
--
ALTER TABLE `kadro_language_code`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ISO_3` (`Part3`);

--
-- AUTO_INCREMENT for table `kadro_language_code`
--
ALTER TABLE `kadro_language_code`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


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
-- Indexes for table `kadro_traduki`
--
ALTER TABLE `kadro_traduki`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kategorio` (`kategorio`,`sekcio`,`referenco`);

--
-- AUTO_INCREMENT for table `kadro_traduki`
--
ALTER TABLE `kadro_traduki`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


COMMIT;
