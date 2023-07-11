ALTER TABLE `#__wp2joomla_categories` ADD COLUMN `adapter` VARCHAR(50) NOT NULL DEFAULT 'wordpress' AFTER `title`;
ALTER TABLE `#__wp2joomla_categories` ADD COLUMN `parent_id_adapter` INT(11) NULL DEFAULT 0 AFTER `id_adapter`;
ALTER TABLE `#__wp2joomla_articles` ADD COLUMN `adapter` VARCHAR(50) NOT NULL DEFAULT 'wordpress' AFTER `title`;