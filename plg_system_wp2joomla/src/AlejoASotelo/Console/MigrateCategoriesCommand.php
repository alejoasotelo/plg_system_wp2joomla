<?php

namespace AlejoASotelo\Console;

use Joomla\CMS\User\User;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Filter\InputFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCategoriesCommand  extends AbstractCommand
{
    
    use DatabaseAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'migrate:categories';

    /**
     * SymfonyStyle Object
     * @var   object
     * @since 4.0.0
     */
    private $ioStyle;

    /**
     * Stores the Input Object
     * @var   object
     * @since 4.0.0
     */
    private $cliInput;

    /**
     * The username
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $userId;

    protected $cache = [];

    /**
     * Command constructor.
     *
     * @param   DatabaseInterface  $db  The database
     *
     * @since   4.2.0
     */
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct();

        $this->setDatabase($db);
    }

    /**
     * Internal function to execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer  The command exit code
     *
     * @since   4.0.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $this->configureIO($input, $output);
        $this->ioStyle->title('Ingrese un Id de usuario');
        $this->userId       = (int)$this->getStringFromOption('userId', 'Por favor ingrese un ID de usuario');
        $action       = $this->getStringFromOption('action', 'Por favor ingrese una acción: migrate-categories or migrate-articles');

        if (!$this->userId) {
            $this->ioStyle->error("El usuario #" . $this->userId . " no existe!");

            return Command::FAILURE;
        }

        if (!in_array($action, ['migrate-categories', 'migrate-articles'])) {
            $this->ioStyle->error("La acción " . $action . " no existe!");

            return Command::FAILURE;
        }

        $user = User::getInstance($this->userId);

        if (!$user->id) {
            $this->ioStyle->error('El usuario con id = ' . $this->userId .' no existe');

            return Command::FAILURE;
        }

        switch ($action) {
            case 'migrate-categories':
                $this->migrateCategories();
                break;
            case 'migrate-articles':
                $this->migrateArticles();
                break;
        }

        return Command::SUCCESS;
    }

    protected function migrateCategories() {
        $wpCategories = $this->getWPCategories();

        foreach ($wpCategories as $wpCategory) {
            $category = $this->createJoomlaCategory($wpCategory);
            if (!$category) {
                $this->ioStyle->error('Error al crear la categoría: ' . $wpCategory->name);
            } else {
                $this->ioStyle->writeln('Category created: ' . $category->title);
            }
        }

        $this->ioStyle->success("Categorías migradas!");
    }

    protected function createJoomlaCategory($wpCategory)
    {
        $joomlaCategory = new \JTableCategory($this->getDatabase());
        $joomlaCategory->title = $wpCategory->name;
        $joomlaCategory->alias = \JFilterOutput::stringURLSafe($wpCategory->name);
        $joomlaCategory->extension = 'com_content';
        $joomlaCategory->published = 1;
        
        if (!$joomlaCategory->store()) {
            $this->ioStyle->error('Error al crear la categoría: ' . \JText::_($joomlaCategory->getError()));
            return false;
        }
        
        return $joomlaCategory;
    }

    protected function migrateArticles() {
        $wpArticles = $this->getWPArticles();

        foreach ($wpArticles as $wpArticle) {
            $this->ioStyle->writeln('Article: ' . $wpArticle->title);
        }

        $this->ioStyle->success(count($wpArticles) . " . Artículos migrados!");
    }

    protected function getWPCategories()
    {
        $cacheId = 'wp_categories';

        if (!isset($this->cache[$cacheId])) {
            $db    = $this->getDatabase();
            $query = $this->getWPTermsQuery($db, 'category');
            $db->setQuery($query);

            $this->cache[$cacheId] = $db->loadObjectList();
        }

        return $this->cache[$cacheId];
    }

    protected function getWPTags() 
    {
        $cacheId = 'wp_categories';

        if (!isset($this->cache[$cacheId])) {
            $db    = $this->getDatabase();
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

    protected function getWPArticles()
    {
        // SELECT * FROM wp_posts WHERE ID IN (SELECT object_id FROM wp_term_relationships WHERE wp_posts.post_type = 'post')
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('ID as id, post_title as title, post_name alias, post_content as content, post_excerpt as introtext, post_date as created, post_modified as modified')
            ->from($db->qn('wp_posts'))
            ->where($db->qn('ID') . ' IN (SELECT object_id FROM wp_term_relationships WHERE wp_posts.post_type = "post")');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Method to get groupId by groupName
     *
     * @param   string  $groupName  name of group
     *
     * @return  integer
     *
     * @since   4.0.0
     */
    protected function getGroupId($groupName)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = :groupName')
            ->bind(':groupName', $groupName);
        $db->setQuery($query);

        return $db->loadResult();
    }

    /**
     * Method to get a value from option
     *
     * @param   string  $option    set the option name
     * @param   string  $question  set the question if user enters no value to option
     *
     * @return  string
     *
     * @since   4.0.0
     */
    public function getStringFromOption($option, $question): string
    {
        $answer = (string) $this->cliInput->getOption($option);

        while (!$answer) {
            if ($option === 'password') {
                $answer = (string) $this->ioStyle->askHidden($question);
            } else {
                $answer = (string) $this->ioStyle->ask($question);
            }
        }

        return $answer;
    }

    /**
     * Method to get a value from option
     *
     * @return  array
     *
     * @since   4.0.0
     */
    protected function getUserGroups(): array
    {
        $groups = $this->getApplication()->getConsoleInput()->getOption('usergroup');
        $db     = $this->getDatabase();

        $groupList = [];

        // Group names have been supplied as input arguments
        if (!\is_null($groups) && $groups[0]) {
            $groups = explode(',', $groups);

            foreach ($groups as $group) {
                $groupId = $this->getGroupId($group);

                if (empty($groupId)) {
                    $this->ioStyle->error("Invalid group name '" . $group . "'");
                    throw new InvalidOptionException("Invalid group name " . $group);
                }

                $groupList[] = $this->getGroupId($group);
            }

            return $groupList;
        }

        // Generate select list for user
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__usergroups'))
            ->order($db->quoteName('id') . 'ASC');
        $db->setQuery($query);

        $list = $db->loadColumn();

        $choice = new ChoiceQuestion(
            'Please select a usergroup (separate multiple groups with a comma)',
            $list
        );
        $choice->setMultiselect(true);

        $answer = (array) $this->ioStyle->askQuestion($choice);

        foreach ($answer as $group) {
            $groupList[] = $this->getGroupId($group);
        }

        return $groupList;
    }

    /**
     * Configure the IO.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    private function configureIO(InputInterface $input, OutputInterface $output)
    {
        $this->cliInput = $input;
        $this->ioStyle  = new SymfonyStyle($input, $output);
    }

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function configure(): void
    {
        $help = "<info>%command.name%</info> will add a user
		\nUsage: <info>php %command.full_name%</info>";

        $this->addOption('userId', null, InputOption::VALUE_REQUIRED, 'userId');
        $this->setDescription('Ingrese un ID de usuario');
        $this->addOption('action', null, InputOption::VALUE_REQUIRED, 'action');
        $this->setDescription('Ingrese una acción');
        $this->setHelp($help);
    }
}