ALTER TABLE `account` change `username` `username` varchar(255) DEFAULT '';

ALTER TABLE `account` change `password` `password` varchar(32) DEFAULT '';

ALTER TABLE `account` change `email` `email` varchar(255) DEFAULT '';

ALTER TABLE `account` ADD COLUMN `openid` varchar(255) DEFAULT NULL;

DROP index email on account;

DROP index username on account;
