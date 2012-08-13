CREATE TABLE `account_sub` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT '',
  `password` varchar(32) DEFAULT '',
  `email` varchar(255) DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified` tinyint(1) DEFAULT '0',
  `md5_key` varchar(32) DEFAULT '',
  `admin` int(1) DEFAULT '0',
  `super` int(1) DEFAULT '0',
  `url` varchar(255) DEFAULT '',
  `type` varchar(255) DEFAULT '',
  `parent` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`,`password`,`type`),
  KEY `idx_url` (`url`,`type`),
  KEY `idx_md5` (`md5_key`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8