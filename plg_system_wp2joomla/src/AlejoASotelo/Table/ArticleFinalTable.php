<?php

namespace AlejoASotelo\Table;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Content;
use AlejoASotelo\Table\MigratorArticleTable;

/**
 * Article table
 *
 * @since  1.5
 */
class ArticleFinalTable extends Content
{
    public $isNew = true;

    public $id_adapter;

    public function store($updateNulls = false)
    {
        $articleMigration = new MigratorArticleTable($this->_db);
        $articleMigration->load(['id_adapter' => $this->id_adapter]);

        $this->id = $articleMigration->id_joomla;
        $this->isNew = !$articleMigration->id_joomla;

        try {

            if (!parent::store($updateNulls)) {
                return false;
            }

            $this->saveWorkflow($this->id);

            $articleMigration->title = $this->title;
            $articleMigration->id_joomla = $this->id;
            $articleMigration->id_adapter = $this->id_adapter;

            $date = Factory::getDate()->toSql();
            if ($articleMigration->id) {
                $articleMigration->modified = $date;
            } else {
                $articleMigration->created = $date;
            }

            $result = $articleMigration->store();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            $result = false;
        }

        return $result;
    }

    public function saveWorkflow($idArticle)
    {
        $stageID = 1;
        $extenstion = "'com_content.article'";

        $query = $this->_db->getQuery(true);

        $colums = array('item_id', 'stage_id', 'extension');
        $values = array($idArticle, $stageID, $extenstion);

        $query
            ->insert('#__workflow_associations')
            ->columns($colums)
            ->values(implode(',', $values));

        $this->_db->setQuery($query);
        $this->_db->execute();
    }
}
