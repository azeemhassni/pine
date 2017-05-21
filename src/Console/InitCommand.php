<?php

namespace Pine\Console;


use Pine\Config\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class InitCommand
 *
 * @package Pine\Console
 */
class InitCommand extends Command
{
    /**
     * @var array
     */
    protected $questions = [];

    /**
     * InitCommand constructor.
     *
     * @param null $name
     * @param Config $config
     */
    public function __construct( Config $config, $name = null )
    {
        $this->questions = [
            'host'        => ['Please provide database host address? ', 'localhost'],
            'username'    => ['Database username? ', 'root'],
            'password'    => ['Database Password? ', ''],
            'author'      => ["What's your name? ", get_current_user()],
            'email'       => ["What's your email address ", "admin@localhost"],
            'wp_username' => ['Default username for wordpress? ', 'admin'],
        ];

        parent::__construct($config, $name);
    }

    /**
     * Configure init Command
     */
    public function configure()
    {
        $this->setName('init')
            ->setDescription('Configure Pine to your customized preferences');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute( InputInterface $input, OutputInterface $output )
    {
        $helper = $this->getHelper('question');
        $output->writeln("Pine Interactive Setup");

        if ($this->config->isConfigured()) {
            $this->loadDefaults();
        }

        foreach ($this->questions as $key => $question) {
            $question = new Question(
                sprintf("<comment>%s</comment> <info>[ %s ]</info>: ", $question[ 0 ], $question[ 1 ]),
                $question[ 1 ]
            );
            $this->config->set($key, $helper->ask($input, $output, $question));
        }

        $output->writeln(sprintf("<question>Saving configuration to %s</question>", $this->config->getConfigPath()));
        $this->config->saveConfig();
    }

    /**
     * Set default values as previously configured.
     *
     * @return InitCommand
     */
    protected function loadDefaults()
    {
        array_walk($this->questions, function ( &$array, $key ) {
            $array[ 1 ] = $this->config->get($key);

            return $array;
        });

        return $this;
    }


}