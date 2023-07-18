CREATE TABLE IF NOT EXISTS `#__wp2joomla_tags` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`id_joomla` INT(11) NOT NULL,
	`id_adapter` INT(11) NOT NULL,
	`parent_id_adapter` INT(11),
	`title` VARCHAR(255) NOT NULL,
	`adapter` VARCHAR(50) NOT NULL DEFAULT 'k2',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
