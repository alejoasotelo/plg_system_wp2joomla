<?php

namespace AlejoASotelo\Table;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Content;
use Joomla\CMS\Workflow\Workflow;
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
        $workflow = new Workflow('com_content.article');
        $state = 1;

        if ($workflow->getAssociation($idArticle)) {
            return $workflow->updateAssociations([$idArticle], $state);
        }

        return $workflow->createAssociation($idArticle, $state);
    }
}
