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

    public function store($updateNulls = false)
    {
        $migratorCategory = new MigratorCategoryTable($this->_db);
        $migratorCategory->load(['id_adapter' => $this->id_adapter]);
        
        $this->id = $migratorCategory->id_joomla;
        $this->isNew = !$migratorCategory->id_joomla;

        try {
        
            if (!parent::store($updateNulls)) {
                return false;
            }
            
            $migratorCategory->title = $this->title;
            $migratorCategory->id_joomla = $this->id;
            $migratorCategory->id_adapter = $this->id_adapter;

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
}
