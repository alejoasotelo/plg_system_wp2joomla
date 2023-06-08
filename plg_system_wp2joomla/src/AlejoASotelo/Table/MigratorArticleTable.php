<?php

namespace AlejoASotelo\Table;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Article table
 *
 * @since  1.5
 */
class MigratorArticleTable extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  Database connector object
	 *
	 * @since   1.6
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__wp2joomla_articles', 'id', $db);
	}

}
