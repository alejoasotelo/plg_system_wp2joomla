<?php

namespace AlejoASotelo\Console;

use AlejoASotelo\Import\Importer;
use Joomla\CMS\User\User;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use AlejoASotelo\Adapter\WordpressAdapter;
use AlejoASotelo\Adapter\K2Adapter;

class MigrateArticlesCommand  extends AbstractCommand
{
    
    use DatabaseAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'migrate:articles';

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

    /**
     * Undocumented variable
     *
     * @var User
     */
    protected $user;

    protected $adapterName = 'wordpress';

    /**
     * Ruta de wordpress a la carpeta wp-content.
     * Ejemplos: 
     * - http://miweb.com.ar/wp-content o
     * - http://miweb.com.ar/wordpress/wp-content
     *
     * @var string
     */
    protected $wpContentPath;

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
        
        $this->ioStyle->title('Desde dónde importar? wordpress o k2');
        $this->adapterName = strtolower($this->getStringFromOption('adapter', 'Por favor ingrese wordpress o k2'));

        if (!in_array($this->adapterName, ['wordpress', 'k2'])) {
            $this->ioStyle->error("El adaptador " . $this->adapterName . " no existe!");

            return Command::FAILURE;
        }

        $this->ioStyle->title('Ingrese un Id de usuario');
        $this->userId       = (int)$this->getStringFromOption('userId', 'Por favor ingrese un ID de usuario');
        if (!$this->userId) {
            $this->ioStyle->error("El usuario #" . $this->userId . " no existe!");

            return Command::FAILURE;
        }

        $this->user = User::getInstance($this->userId);

        if (!$this->user->id) {
            $this->ioStyle->error('El usuario con id = ' . $this->userId .' no existe');

            return Command::FAILURE;
        }

        if ($this->adapterName == 'wordpress') {        
            $this->wpContentPath = $this->getStringFromOption('wpContentPath', 'Por favor ingrese la url a la carpeta wp-content de Wordpress. Ejemplo: http://miweb.com.ar/wp-content o http://miweb.com.ar/wordpress/wp-content');
            if (empty($this->wpContentPath)) {
                $this->ioStyle->error('La ruta a la carpeta wp-content no puede estar vacía');
                return Command::FAILURE;
            }
        }

        $this->migrateArticles();

        return Command::SUCCESS;
    }

    protected function migrateArticles()
    {
        $db = $this->getDatabase();

        if ($this->adapterName == 'k2') {
            $adapter = new K2Adapter($db, $this->user, '');
        } else {
            $adapter = new WordpressAdapter($db, $this->user, $this->wpContentPath);
        }

        $importer = new Importer($adapter, $this->ioStyle, $db);
        $importer->importArticles();
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

        $this->addOption('adapter', null, InputOption::VALUE_REQUIRED, 'adapter');
        $this->setDescription('Ingrese desde dónde importart wordpress o k2');

        $this->addOption('wpContentPath', null, InputOption::VALUE_REQUIRED, 'wpContentPath');
        $this->setDescription('Ingrese la url a la carpeta wp-content de Wordpress si eligió el adaptador = wordpress. Ejemplo: http://miweb.com.ar/wp-content o http://miweb.com.ar/wordpress/wp-content');
        $this->setHelp($help);
    }
}