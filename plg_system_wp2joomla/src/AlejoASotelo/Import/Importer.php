<?php

namespace AlejoASotelo\Import;

use AlejoASotelo\Adapter\Wp2JoomlaAdapterInterface;
use AlejoASotelo\Table\MigratorCategoryTable;
use AlejoASotelo\Table\CategoryFinalTable;
use AlejoASotelo\Table\TagFinalTable;

class Importer
{
    protected $adapter;

    protected $io;

    protected $db;

    public function __construct(Wp2JoomlaAdapterInterface $adapter, Object $io, $db = null)
    {
        $this->adapter = $adapter;
        $this->db = $db;
        $this->io = $io;
    }

    public function import()
    {
        $categories = $this->adapter->listCategories();
        $products = $this->adapter->listArticles();

        $this->io->writeln("Categorías importadas:");
        foreach ($categories as $category) {
            $this->io->writeln($category->getName());
        }

        $this->io->writeln("Artículos importados:");
        foreach ($products as $product) {
            $this->io->writeln($product->getName());
        }
    }

    public function importCategories()
    {
        $categories = $this->adapter->listCategories();

        foreach ($categories as $adapterCategory) {
            /** @var CategoryFinalTable $category */
            $category = $this->saveCategory($adapterCategory, $this->adapter->getName());
            
            if (!$category) {
                $this->io->error('Error al crear la categoría: ' . $adapterCategory->title);
            } else {
                $this->io->writeln('Categoría '.($category->isNew ? 'creada' : 'actualizada').': ' . $category->title);
            }
        }

        $this->io->success("Categorías migradas!");

        return true;
    }

    public function importTags()
    {
        $tags = $this->adapter->listTags();

        foreach ($tags as $adapterTag) {
            /** @var TagFinalTable $tag */
            $tag = $this->saveTag($adapterTag, $this->adapter->getName());
            
            if (!$tag) {
                $this->io->error('Error al crear el tag: ' . $adapterTag->title);
            } else {
                $this->io->writeln('Tag '.($tag->isNew ? 'creado' : 'actualizado').': ' . $tag->title);
            }
        }

        $this->io->success("Tags migrados!");

        return true;
    }

    public function importArticles()
    {
        $articles = $this->adapter->listArticles();

        foreach ($articles as $adapterArticle) {
            /** @var ArticleFinalTable $article */
            $article = $this->saveArticle($adapterArticle, $this->adapter->getName());
            
            if (!$article) {
                $this->io->error('Error al crear el artículo: ' . $adapterArticle->title);
            } else {
                $this->io->writeln('Artículo '.($article->isNew ? 'creado' : 'actualizado').': ' . $article->title);
            }
        }

        $this->io->success("Artículos migrados!");

        return true;
    }

    /**
     * Undocumented function
     *
     * @param CategoryFinalTable $categoryFinal
     * @param string $adapterName
     * 
     * @return void
     */
    protected function saveCategory($categoryFinal, $adapterName = 'wordpress')
    {
        $categoryFinal->adapter = $adapterName;
        if (!$categoryFinal->store()) {
            $this->io->error('Error al crear la categoría: ' . \JText::_($categoryFinal->getError()));
            return false;
        }
        
        return $categoryFinal;
    }
    
    /**
     * Undocumented function
     *
     * @param TagFinalTable $tagFinal
     * @param string $adapterName
     * 
     * @return void
     */
    protected function saveTag($tagFinal, $adapterName = 'k2')
    {
        $tagFinal->adapter = $adapterName;
        if (!$tagFinal->store()) {
            $this->io->error('Error al crear el tag: ' . \JText::_($tagFinal->getError()));
            return false;
        }
        
        return $tagFinal;
    }

    /**
     * Undocumented function
     *
     * @param ArticleFinalTable $articleFinal
     * @return void
     */
    protected function saveArticle($articleFinal, $adapterName = 'wordpress')
    {
        $articleFinal->adapter = $adapterName;
        if (!$articleFinal->store()) {
            $this->io->error('Error al crear el artículo: ' . \JText::_($articleFinal->getError()));
            return false;
        }
        
        return $articleFinal;
    }
}
