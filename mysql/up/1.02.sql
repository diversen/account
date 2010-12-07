DROP TABLE IF EXISTS `auth`;

CREATE TABLE IF NOT EXISTS `auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `password` varchar(32) NOT NULL,
  `email` varchar(64) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified` boolean DEFAULT '0',
  `md5_key` varchar(32) DEFAULT '',
  `admin` int(1) DEFAULT '0',
  `super` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;