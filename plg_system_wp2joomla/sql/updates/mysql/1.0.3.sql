DROP TABLE IF EXISTS `#__wp2joomla_categories`;

CREATE TABLE `#__wp2joomla_categories` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`id_joomla` INT(11) NOT NULL,
	`id_wordpress` INT(11) NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__wp2joomla_articles`;

CREATE TABLE `#__wp2joomla_articles` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`id_joomla` INT(11) NOT NULL,
	`id_wordpress` INT(11) NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
