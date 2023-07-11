<?php

namespace AlejoASotelo\Table;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Category;
use AlejoASotelo\Table\MigratorCategoryTable;

/**
 * Article table
 *
 * @since  1.5
 */
class CategoryFinalTable extends Category
{
    public $isNew = true;

    public $id_adapter;

    protected $cache = [];

    public function store($updateNulls = false)
    {
        $migratorCategory = new MigratorCategoryTable($this->_db);
        $migratorCategory->load(['id_adapter' => $this->id_adapter, 'adapter' => $this->adapter]);

        $this->id = $migratorCategory->id_joomla;
        $this->isNew = !$migratorCategory->id_joomla;

        // Si tiene padre, lo busca en la tabla migrator
        if (isset($this->parent_id_adapter) && $this->parent_id_adapter > 1) {
            $migratorCategory->parent_id_adapter = $this->parent_id_adapter;

            $migratorCategoryParent = $this->getParent($this->parent_id_adapter, $this->adapter);

            if ($migratorCategoryParent && $migratorCategoryParent->id_joomla) {
                $this->parent_id = $migratorCategoryParent->id_joomla;
                $this->setLocation($this->parent_id, 'last-child');
            }
        }

        try {

            if (!parent::store($updateNulls)) {
                return false;
            }

            $migratorCategory->title = $this->title;
            $migratorCategory->id_joomla = $this->id;
            $migratorCategory->id_adapter = $this->id_adapter;
            $migratorCategory->adapter = $this->adapter;

            $date = Factory::getDate()->toSql();
            if ($migratorCategory->id) {
                $migratorCategory->modified = $date;
            } else {
                $migratorCategory->created = $date;
            }

            $result = $migratorCategory->store();
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
     * @return MigratorCategoryTable
     */
    protected function getParent($parentIdAdapter, $adapter)
    {
        $storeId = 'getParent-' . $parentIdAdapter . '-' . $adapter;

        if (!isset($this->cache[$storeId])) {
            $parent = new MigratorCategoryTable($this->_db);
            $parent->load(['id_adapter' => $parentIdAdapter, 'adapter' => $adapter]);

            if (!$parent->id_joomla) {
                return false;
            }

            $this->cache[$storeId] = $parent;
        }

        return $this->cache[$storeId];
    }
}
