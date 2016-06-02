DROP TABLE IF EXISTS `group`;

DROP TABLE IF EXISTS `group_member`;

ALTER TABLE `account` ADD COLUMN `invited` boolean DEFAULT 0;