<?php

namespace AlejoASotelo\Adapter;

interface Wp2JoomlaAdapterInterface
{

    public function __construct($db, $user, $wpContentPath);

    public function getName();

    /**
     * Undocumented function
     *
     * @return array
     */
    public function listArticles();

    /**
     * Undocumented function
     *
     * @return array
     */
    public function listCategories();

    /**
     * @return array
     */
    public function listTags();

    public function setDatabase($db);

    public function getDatabase();

    public function setUser($user);

    public function getUser();
}