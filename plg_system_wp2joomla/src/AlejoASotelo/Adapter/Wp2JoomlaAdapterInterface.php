<?php

namespace AlejoASotelo\Adapter;

interface Wp2JoomlaAdapterInterface
{

    public function __construct($db, $user, $wpContentPath);

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

    public function setDatabase($db);

    public function getDatabase();

    public function setUser($user);

    public function getUser();
}