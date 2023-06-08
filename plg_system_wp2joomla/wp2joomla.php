<?php

\defined('_JEXEC') or die;

JLoader::registerNamespace('\\AlejoASotelo', __DIR__ . '/src/AlejoASotelo', false, true,'psr4');

use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Psr\Container\ContainerInterface;
use AlejoASotelo\Console\MigrateCategoriesCommand;
use AlejoASotelo\Console\MigrateArticlesCommand;

class PlgSystemWP2Joomla extends CMSPlugin implements SubscriberInterface
{
    protected $db;

    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_EXECUTE => 'registerCommands',
        ];
    }

    public function registerCommands(): void
    {
        Factory::getContainer()->share(
            'migrate.categories',
            function (ContainerInterface $container) {
                return new MigrateCategoriesCommand($this->db);
            },
            true
        );
        
        Factory::getContainer()->share(
            'migrate.articles',
            function (ContainerInterface $container) {
                return new MigrateArticlesCommand($this->db);
            },
            true
        );

        Factory::getContainer()->get(WritableLoaderInterface::class)->add('migrate:categories', 'migrate.categories');
        Factory::getContainer()->get(WritableLoaderInterface::class)->add('migrate:articles', 'migrate.articles');
    }

}