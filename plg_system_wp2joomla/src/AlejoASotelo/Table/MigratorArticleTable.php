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

	public function find($keys = null, $reset = true)
	{
		if (empty($keys))
		{
			$empty = true;
			$keys  = array();

			// If empty, use the value of the current key
			foreach ($this->_tbl_keys as $key)
			{
				$empty      = $empty && empty($this->$key);
				$keys[$key] = $this->$key;
			}

			// If empty primary key there's is no need to load anything
			if ($empty)
			{
				return true;
			}
		}
		elseif (!\is_array($keys))
		{
			// Load by primary key.
			$keyCount = \count($this->_tbl_keys);

			if ($keyCount)
			{
				if ($keyCount > 1)
				{
					throw new \InvalidArgumentException('Table has multiple primary keys specified, only one primary key value provided.');
				}

				$keys = array($this->getKeyName() => $keys);
			}
			else
			{
				throw new \RuntimeException('No table keys defined.');
			}
		}

		// Initialise the query.
		$query = $this->_db->getQuery(true)
			->select('*')
			->from($this->_tbl);
		$fields = array_keys($this->getProperties());

		foreach ($keys as $field => $value)
		{
			// Check that $field is in the table.
			if (!\in_array($field, $fields))
			{
				throw new \UnexpectedValueException(sprintf('Missing field in database: %s &#160; %s.', \get_class($this), $field));
			}

			// Add the search tuple to the query.
			$query->where($this->_db->quoteName($field) . ' = ' . $this->_db->quote($value));
		}

		$this->_db->setQuery($query);

		$rows = $this->_db->loadObjectList();

		// Check that we have a result.
		if (!count($rows))
		{
			return false;
		}

		return $rows;
	}

}
