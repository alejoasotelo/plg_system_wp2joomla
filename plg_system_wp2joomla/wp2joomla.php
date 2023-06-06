<?php

\defined('_JEXEC') or die;

JLoader::registerNamespace('\\AlejoASotelo\\Console', __DIR__ . '/src/AlejoASotelo/Console', false, true,'psr4');

use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use AlejoASotelo\Console\MigrateCategoriesCommand;
use Psr\Container\ContainerInterface;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;

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

        Factory::getContainer()->get(WritableLoaderInterface::class)->add('migrate:categories', 'migrate.categories');
    }

}