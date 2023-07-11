<?php

namespace AlejoASotelo\Adapter;

use Joomla\Registry\Registry;
use AlejoASotelo\Adapter\Wp2JoomlaAdapterInterface;
use AlejoASotelo\Table\ArticleFinalTable;
use AlejoASotelo\Table\MigratorCategoryTable;
use AlejoASotelo\Table\CategoryFinalTable;

class K2Adapter implements Wp2JoomlaAdapterInterface
{

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
        return [];
        /*$wpArticles = $this->getPosts();

        $categoryDefault = 2;
        $category = new MigratorCategoryTable($this->db);

        $articles = [];

        foreach ($wpArticles as $wpArticle) {
            $wpCategories = $this->getCategoriesFromPost($wpArticle->id);

            if (count($wpCategories) > 0) {
                $category->load(['id_adapter' => $wpCategories[0]]);
                $categoryId = !$category->id ? $categoryDefault : $category->id_joomla;
            } else {
                $categoryId = $categoryDefault;
            }

            $article = new ArticleFinalTable($this->db);
            $article->id_adapter = $wpArticle->id;
            $article->catid = $categoryId;
            $article->title = $wpArticle->title;
            $article->alias = \JFilterOutput::stringURLSafe($wpArticle->title);
            $article->published = 1;
            $article->state = 1;
            $article->language = '*';
            $article->introtext = $wpArticle->content;
            $article->fulltext = '';
            $article->created = date('Y-m-d H:i:s', strtotime($wpArticle->created));
            $article->created_by = $this->user->id;
            $article->created_by_alias = $this->user->name;
            $article->modified = date('Y-m-d H:i:s', strtotime($wpArticle->modified));
            $article->modified_by = $this->user->id;
            $article->publish_up =null;
            $article->hits = 0;
            $article->metakey = '';
            $article->metadesc = '';
            $article->access = 1;
            $article->hits = 0;
            $article->featured = 0;
			$article->images = '{}';
			$article->urls = '{}';
			$article->attribs = '{}';
			$article->metadata = '{}';

            $images = $this->findImagesByPostID($wpArticle->id);

            if ($images) {
                $image = $images[0];

                $imagePath = str_replace($this->wpContentPath, '', $image->path);
                $imagePath = 'images/' . ltrim($imagePath, "/");
                
                $registry = new Registry();
                $registry->set('image_intro', $imagePath);
                $registry->set('image_intro_alt', $image->alt);
                $registry->set('image_intro_caption', $image->caption);
                $registry->set('float_intro', '');
                $registry->set('image_fulltext', '');
                $registry->set('image_fulltext_alt', '');
                $registry->set('image_fulltext_caption', '');
                $registry->set('float_fulltext', '');

                $article->images = (string)$registry;
            }

            $articles[] = $article;
        }

        return $articles;*/
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
            $category->alias = \JFilterOutput::stringURLSafe($k2Category->name);
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
