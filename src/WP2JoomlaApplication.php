<?php

namespace AlejoASotelo\Console;

use Joomla\CMS\Application\ConsoleApplication;

class WP2JoomlaApplication extends ConsoleApplication {

    public function ren()
    {
        $app = Factory::getApplication();
        $mvcFactory = $app->bootComponent('com_content')->getMVCFactory();
        $articleModel = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);

        $article = [
            'catid' => 2,
            'alias' => 'tttttttttttttttt44',
            'title' => '123My Article Title 44',
            'introtext' => 'My Article Intro Text',
            'fulltext' => 'My Article Full Text',
            'state' => 1,
            'language' => '*',
        ];

        if (!$articleModel->save($article)){
            throw new Exception($articleModel->getError());
        }
    }
}