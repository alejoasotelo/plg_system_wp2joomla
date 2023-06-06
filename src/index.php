<?php

/**
 * @package    Joomla.Cli
 *
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// We are a valid entry point.
const _JEXEC = 1;

// Define the application's minimum supported PHP version as a constant so it can be referenced within the application.
const JOOMLA_MINIMUM_PHP = '7.2.5';

if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<')) {
    echo 'Sorry, your PHP version is not supported.' . PHP_EOL;
    echo 'Your command line php needs to be version ' . JOOMLA_MINIMUM_PHP . ' or newer to run the Joomla! CLI Tools' . PHP_EOL;
    echo 'The version of PHP currently running this code, at the command line, is PHP version ' . PHP_VERSION . '.' . PHP_EOL;
    echo 'Please note, the version of PHP running your commands here, may be different to the version that is used by ';
    echo 'your web server to run the Joomla! Web Application' . PHP_EOL;

    exit;
}

// Load system defines
if (file_exists(dirname(dirname(__DIR__)) . '/defines.php')) {
    require_once dirname(dirname(__DIR__)) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(dirname(__DIR__)));
    require_once JPATH_BASE . '/includes/defines.php';
}

// Check for presence of vendor dependencies not included in the git repository
if (!file_exists(JPATH_LIBRARIES . '/vendor/autoload.php') || !is_dir(JPATH_ROOT . '/media/vendor')) {
    echo 'It looks like you are trying to run Joomla! from our git repository.' . PHP_EOL;
    echo 'To do so requires you complete a couple of extra steps first.' . PHP_EOL;
    echo 'Please see https://docs.joomla.org/Special:MyLanguage/J4.x:Setting_Up_Your_Local_Environment for further details.' . PHP_EOL;

    exit;
}

// Check if installed
if (
    !file_exists(JPATH_CONFIGURATION . '/configuration.php')
    || (filesize(JPATH_CONFIGURATION . '/configuration.php') < 10)
) {
    echo 'Install Joomla to run cli commands' . PHP_EOL;

    exit;
}

// Get the framework.
require_once JPATH_BASE . '/includes/framework.php';
require_once __DIR__ . '/WP2JoomlaApplication.php';

use Joomla\CMS\Application\ConsoleApplication;
use Joomla\Event\DispatcherInterface;
use Joomla\DI\Container;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\Console\Loader\LoaderInterface;
use Joomla\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;

// Boot the DI container
$container = \Joomla\CMS\Factory::getContainer();

/*
 * Alias the session service keys to the CLI session service as that is the primary session backend for this application
 *
 * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
 * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
 * deprecated to be removed when the class name alias is removed as well.
 */
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(SessionInterface::class, 'session.cli');

$container->alias(AlejoASotelo\Console\WP2JoomlaApplication::class, 'JApplicationWP2Joomla')
    ->share('JApplicationWP2Joomla',
        function (Container $container) {
            $dispatcher = $container->get(DispatcherInterface::class);

            // Console uses the default system language
            $config = $container->get('config');
            $locale = $config->get('language');
            $debug  = $config->get('debug_lang');

            $lang = $container->get(LanguageFactoryInterface::class)->createLanguage($locale, $debug);

            $app = new AlejoASotelo\Console\WP2JoomlaApplication($config, $dispatcher, $container, $lang);

            // The session service provider needs Factory::$application, set it if still null
            if (Factory::$application === null) {
                Factory::$application = $app;
            }

            $app->setCommandLoader($container->get(LoaderInterface::class));
            $app->setLogger($container->get(LoggerInterface::class));
            $app->setSession($container->get(SessionInterface::class));
            $app->setUserFactory($container->get(UserFactoryInterface::class));
            $app->setDatabase($container->get(DatabaseInterface::class));

            return $app;
        },
        true
    );


$app = $container->get(AlejoASotelo\Console\WP2JoomlaApplication::class);

\Joomla\CMS\Factory::$application = $app;

$app->execute();
