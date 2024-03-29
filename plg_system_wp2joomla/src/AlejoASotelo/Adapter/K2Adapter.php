<?php

namespace AlejoASotelo\Adapter;

use Joomla\Registry\Registry;
use AlejoASotelo\Adapter\Wp2JoomlaAdapterInterface;
use AlejoASotelo\Table\ArticleFinalTable;
use AlejoASotelo\Table\MigratorCategoryTable;
use AlejoASotelo\Table\CategoryFinalTable;
use AlejoASotelo\Table\TagFinalTable;
use Joomla\String\StringHelper;

class K2Adapter implements Wp2JoomlaAdapterInterface
{

    const CATEGORY_UNCATEGORIZED = 2;

    protected $cache = [];

    protected $db;

    protected $user;

    protected $wpContentPath;

    public function __construct($db, $user, $wpContentPath)
    {
        $this->db = $db;
        $this->user = $user;
        $this->wpContentPath = rtrim($wpContentPath, '/');
    }

    public function getName() {
        return 'k2';
    }

    /**
     * Undocumented function
     *
     * @return array<ArticleFinalTable>
     */
    public function listArticles()
    {
        $k2Articles = $this->getK2Articles();

        $category = new MigratorCategoryTable($this->db);

        $articles = [];
        foreach ($k2Articles as $k2Article) {

            if ($k2Article->catid > 0) {
                $category->load(['id_adapter' => $k2Article->catid, 'adapter' => $this->getName()]);
                $categoryId = !$category->id ? self::CATEGORY_UNCATEGORIZED : $category->id_joomla;
            } else {
                $categoryId = self::CATEGORY_UNCATEGORIZED;
            }

            $article = new ArticleFinalTable($this->db);
            $article->id_adapter = $k2Article->id;
            $article->catid = $categoryId;
            $article->title = $k2Article->title;
            $article->alias = $this->generateNewAlias($articles, \JFilterOutput::stringURLSafe($k2Article->title), $article->id_adapter);
            $article->published = $k2Article->published;
            $article->state = $k2Article->published;
            $article->language = '*';
            $article->introtext = $k2Article->introtext;
            $article->fulltext = $k2Article->fulltext;
            $article->created = $k2Article->created;
            $article->created_by = $this->user->id;
            $article->created_by_alias = $this->user->name;
            $article->modified = $k2Article->modified;
            $article->modified_by = $this->user->id;
            $article->publish_up = $k2Article->publish_up;
            $article->publish_down = null;
            $article->access = $k2Article->access;
            $article->featured = $k2Article->featured;
            $article->hits = 0;
            $article->metakey = '';
            $article->metadesc = '';
            $article->hits = $k2Article->hits;
			$article->images = '{}';
			$article->urls = '{}';
			$article->attribs = '{}';
			$article->metadata = '{}';

            $image = $this->getK2ImagePath($k2Article->id);

            if (!empty($image)) {
                $registry = new Registry;
                $registry->set('image_intro', $image);
                $registry->set('float_into', '');
                $registry->set('image_intro_alt', '');
                $registry->set('image_intro_caption', '');
                $registry->set('image_fulltext', '');
                $registry->set('float_fulltext', '');
                $registry->set('image_fulltext_alt', '');
                $registry->set('image_fulltext_caption', '');

                $article->images = (string)$registry;
            }

            $articles[] = $article;
        }

        return $articles;
    }

    public function getK2Articles()
    {
        $query = $this->db->getQuery(true);

        $query
            ->select('`id`, `title`, `alias`, `introtext`, `fulltext`, `created`, `catid`')
            ->select('`published`')
            ->select('`created_by_alias`, `checked_out`, `checked_out_time`, `modified`, `publish_up`, `publish_down`, `access`, `featured`, `hits`, `language`')
            ->from('#__k2_items')
            // ->setLimit(10)
            ->order('id ASC');

        return $this->db->setQuery($query)->loadObjectList();
    }

    public function getK2ImagePath($id) {
        $filename = md5("Image" . $id);
        return 'images/k2/' . $filename . '.jpg';
    }

    /**
     * Undocumented function
     *
     * @return array<CategoryFinalTable>
     */
    public function listCategories()
    {
        $k2Categories = $this->getCategories();

        $categories = [];

        foreach ($k2Categories as $k2Category) {
            $category = new CategoryFinalTable($this->db);
            $category->id_adapter = $k2Category->id;
            $category->parent_id = 1;
            $category->parent_id_adapter = $k2Category->parent_id ?: 1;
            $category->title = $k2Category->name;
            $category->alias = $this->generateNewAlias($categories, \JFilterOutput::stringURLSafe($k2Category->name), $category->id_adapter);
            $category->extension = 'com_content';
            $category->published = 1;
            $category->language = '*';
            $category->params = ['category_layout' => '', 'image' => ''];
            $category->metadata = ['author' => '', 'robots' => ''];
            $category->rules = [
                'core.edit.state' => [],
                'core.edit.delete' => [],
                'core.edit.edit' => [],
                'core.edit.state' => [],
                'core.edit.own' => [1 => true]
            ];

            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function listTags()
    {
        $k2Categories = $this->getCategories();

        $tags = [];

        foreach ($k2Categories as $k2Category) {
            $tag = new TagFinalTable($this->db);
            $tag->id_adapter = $k2Category->id;
            $tag->parent_id = 1;
            $tag->parent_id_adapter = $k2Category->parent_id ?: 1;
            $tag->title = $k2Category->name;
            $tag->alias = $this->generateNewAlias($tags, \JFilterOutput::stringURLSafe($k2Category->name), $tag->id_adapter);
            $tag->description = '';
            $tag->published = 1;
            $tag->level = $k2Category->level;
            $tag->language = '*';
            $tag->params = json_encode(['tag_layout' => '', 'tag_link_class' => '']);
            $tag->metadata = json_encode(['author' => '', 'robots' => '']);
            $tag->metadesc = '';
            $tag->metakey = '';
            $tag->images = '{"image_intro":"","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"","float_fulltext":"","image_fulltext_alt":"","image_fulltext_caption":""}';
            $tag->urls = '{}';

            $tags[] = $tag;
        }

        return $tags;
    }

    protected function generateNewAlias($items, $alias, $idAdapter)
    {
        $i = 0;
        $len = count($items);
        while ($i < $len) {
            if ($items[$i]->alias == $alias && $items[$i]->id_adapter != $idAdapter) {
                $alias = StringHelper::increment($alias, 'dash');
                $i = -1;
            }
            
            $i++;
        }
        
        return $alias;
    }

    protected function getCategories()
    {
        $cacheId = 'getCategories';

        if (!isset($this->cache[$cacheId])) {
            $this->cache[$cacheId] = $this->getK2CategoriesTree();
        }

        return $this->cache[$cacheId];
    }

    /**
     * Devuelve el arbol de categorias K2 con los campos listos para categorías Joomla
     *
     * @param integer $offsetId Este valor se suma a los IDs de las categorías
     * @return Array<object> Categorías K2
     */
    public function getK2CategoriesTree($parentId = 0, $level = 1)
    {
        $query = $this->db->getQuery(true);

        $query
            ->select('id, name, CONCAT(alias, "-k2") alias, published, access, parent parent_id, language, "com_content" extension, ' . $level . ' level, "" path, 0 asset_id')
            ->from('#__k2_categories')
            ->where('parent = ' . $parentId);

        $categories = $this->db->setQuery($query)->loadObjectList();

        foreach ($categories as &$category) {
            $children = $this->getK2CategoriesTree($category->id, $level + 1);
            
            foreach ($children as $child) {
                $categories[] = $child;
            }
        }

        return $categories;
    }

    public function setDatabase($db)
    {
        $this->db = $db;
    }

    public function getDatabase()
    {
        return $this->db;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

}
