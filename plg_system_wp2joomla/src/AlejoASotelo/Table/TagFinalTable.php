<?php

namespace AlejoASotelo\Table;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Tags\Administrator\Table\TagTable;
use AlejoASotelo\Table\MigratorTagTable;

/**
 * Article table
 *
 * @since  1.5
 */
class TagFinalTable extends TagTable
{
    public $isNew = true;

    public $id_adapter;

    protected $cache = [];

    public function store($updateNulls = false)
    {
        $migratorTag = new MigratorTagTable($this->_db);
        $migratorTag->load(['id_adapter' => $this->id_adapter, 'adapter' => $this->adapter]);

        $this->id = $migratorTag->id_joomla;
        $this->isNew = !$migratorTag->id_joomla;

        // Si tiene padre, lo busca en la tabla migrator
        if (isset($this->parent_id_adapter) && $this->parent_id_adapter > 1) {
            $migratorTag->parent_id_adapter = $this->parent_id_adapter;

            $migratorTagParent = $this->getParent($this->parent_id_adapter, $this->adapter);

            if ($migratorTagParent && $migratorTagParent->id_joomla) {
                $this->parent_id = $migratorTagParent->id_joomla;
                $this->setLocation($this->parent_id, 'last-child');
            }
        }

        try {

            if (!parent::store($updateNulls)) {
                return false;
            }

            $migratorTag->title = $this->title;
            $migratorTag->id_joomla = $this->id;
            $migratorTag->id_adapter = $this->id_adapter;
            $migratorTag->adapter = $this->adapter;

            $date = Factory::getDate()->toSql();
            if ($migratorTag->id) {
                $migratorTag->modified = $date;
            } else {
                $migratorTag->created = $date;
            }

            $result = $migratorTag->store();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            $result = false;
        }

        return $result;
    }

    /**
     * Undocumented function
     *
     * @param [type] $parentIdAdapter
     * @param [type] $adapter
     * @return MigratorTagTable
     */
    protected function getParent($parentIdAdapter, $adapter)
    {
        $storeId = 'getParent-' . $parentIdAdapter . '-' . $adapter;

        if (!isset($this->cache[$storeId])) {
            $parent = new MigratorTagTable($this->_db);
            $parent->load(['id_adapter' => $parentIdAdapter, 'adapter' => $adapter]);

            if (!$parent->id_joomla) {
                return false;
            }

            $this->cache[$storeId] = $parent;
        }

        return $this->cache[$storeId];
    }
}
