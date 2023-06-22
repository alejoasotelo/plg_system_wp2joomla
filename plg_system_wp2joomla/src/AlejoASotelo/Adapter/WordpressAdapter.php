<?php

namespace AlejoASotelo\Adapter;

use Joomla\Registry\Registry;
use AlejoASotelo\Adapter\Wp2JoomlaAdapterInterface;
use AlejoASotelo\Table\ArticleFinalTable;
use AlejoASotelo\Table\MigratorCategoryTable;
use AlejoASotelo\Table\CategoryFinalTable;

class WordpressAdapter implements Wp2JoomlaAdapterInterface
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

    /**
     * Undocumented function
     *
     * @return array<ArticleFinalTable>
     */
    public function listArticles()
    {
        $wpArticles = $this->getPosts();

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

                $imagePath = ltrim($image->path, "/");
                $imagePath = str_replace($this->wpContentPath, 'images/', $imagePath);
                
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

        return $articles;
    }

    /**
     * Undocumented function
     *
     * @return array<CategoryFinalTable>
     */
    public function listCategories()
    {
        $wpCategories = $this->getCategories();

        $categories = [];

        foreach ($wpCategories as $wpCategory) {
            $category = new CategoryFinalTable($this->db);
            $category->id_adapter = $wpCategory->id;
            $category->parent_id = 1;
            $category->title = $wpCategory->name;
            $category->alias = \JFilterOutput::stringURLSafe($wpCategory->name);
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
        $cacheId = 'wp_categories';

        if (!isset($this->cache[$cacheId])) {
            $db    = $this->db;
            $query = $this->getWPTermsQuery($db, 'category');
            $db->setQuery($query);

            $this->cache[$cacheId] = $db->loadObjectList();
        }

        return $this->cache[$cacheId];
    }

    public function getCategoriesFromPost($postId)
    {
        $db    = $this->db;
        $query = $db->getQuery(true)
            ->select('terms.term_id')
            ->from($db->qn('wp_terms', 'terms'))
            ->innerJoin($db->qn('wp_term_taxonomy', 'taxonomy') . ' ON ' . $db->qn('terms.term_id') . ' = ' . $db->qn('taxonomy.term_id'))
            ->innerJoin($db->qn('wp_term_relationships', 'relationships') . ' ON ' . $db->qn('taxonomy.term_taxonomy_id') . ' = ' . $db->qn('relationships.term_taxonomy_id'))
            ->innerJoin($db->qn('wp_posts', 'posts') . ' ON ' . $db->qn('relationships.object_id') . ' = ' . $db->qn('posts.ID'))
            ->where($db->qn('taxonomy.taxonomy') . ' = ' . $db->q('category'))
            ->where($db->qn('posts.ID') . ' = ' . $db->q($postId));

        $db->setQuery($query);

        $wpCategories = $db->loadObjectList();

        $categories = [];

        foreach ($wpCategories as $wpCategory) {
            $categories[] = $wpCategory->term_id;
        }

        return $categories;
    }

    protected function getPosts()
    {
        $db    = $this->db;
        $query = $db->getQuery(true)
            ->select('ID as id, post_title as title, post_name alias, post_content as content, post_excerpt as introtext, post_date as created, post_modified as modified')
            ->from($db->qn('wp_posts'))
            ->where($db->qn('ID') . ' IN (SELECT object_id FROM wp_term_relationships WHERE wp_posts.post_type = "post")');

        $db->setQuery($query);

        return $db->loadObjectList();
    }
    
    /**
     * Devuelve las imagenes de un post
     *
     * @param array|int $id
     * @return array<(path, caption, alt)>
     */
    protected function findImagesByPostID($id)
    {
        if (!$id) {
            return false;
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select('guid path, post_title caption, post_excerpt alt')
            ->from($db->qn('wp_posts'))
            ->where($db->qn('post_parent') . ' IN (' . $db->q($id) . ')')
            ->where($db->qn('post_type') . ' = ' . $db->q('attachment'))
            ->where($db->qn('post_mime_type') . ' LIKE ' . $db->q('image%'));

        $db->setQuery($query);

        $images = $db->loadObjectList();

        return count($images) > 0 ? $images : false;
    }

    protected function getWPTags()
    {
        $cacheId = 'wp_categories';

        if (!isset($this->cache[$cacheId])) {
            $db    = $this->db;
            $query = $this->getWPTermsQuery($db, 'post_tag');
            $db->setQuery($query);

            $this->cache[$cacheId] = $db->loadObjectList();
        }

        return $this->cache[$cacheId];
    }

    protected function getWPTermsQuery($db, $taxonomy)
    {
        // SELECT * FROM wp_terms WHERE term_id IN (SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'category')
        // SELECT * FROM wp_terms WHERE term_id IN (SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'post_tag')
        $query = $db->getQuery(true)
            ->select('term_id as id, name')
            ->from($db->qn('wp_terms'))
            ->where($db->qn('term_id') . ' IN (SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = :taxonomy)')
            ->bind(':taxonomy', $taxonomy);

        return $query;
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
