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
class MigratorCategoryTable extends Table
{
    protected $cache = [];

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  Database connector object
	 *
	 * @since   1.6
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__wp2joomla_categories', 'id', $db);
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields to the Table instance properties.
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.
	 *                           If not set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found.
	 *
	 * @since   1.7.0
	 * @throws  \InvalidArgumentException
	 * @throws  \RuntimeException
	 * @throws  \UnexpectedValueException
	 */
	public function load($keys = null, $reset = true)
    {
        $cacheId = md5('load-' . json_encode($keys));

        if (!isset($this->cache[$cacheId])) {
            $this->cache[$cacheId] = parent::load($keys, $reset);
        }

        return $this->cache[$cacheId];
    }

}
