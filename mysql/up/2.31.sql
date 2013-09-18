DROP TABLE IF EXISTS `account_email_changes`;

CREATE TABLE `account_email_changes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `tries` tinyint(3) unsigned DEFAULT NULL,
  `date_try` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci

CREATE INDEX `idx_email_changes` ON account_email_changes(`user_id`);