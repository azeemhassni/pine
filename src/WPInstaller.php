<?php

namespace Pine;

use Pine\Config\Config;
use Pine\Console\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class WPInstaller.
 */
class WPInstaller
{
    /**
     * @var \PDO
     */
    protected $connection;
    /**
     * @var string the path to wordpress project
     */
    protected $path;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var WPCli
     */
    private $WPCli;
    /**
     * @var Command
     */
    private $command;

    /**
     * WPInstaller constructor.
     *
     * @param Command $command
     * @param Config $config
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param WPCli $WPCli
     */
    public function __construct(
        Command $command,
        Config $config,
        InputInterface $input,
        OutputInterface $output,
        WPCli $WPCli
    )
    {
        $this->config = $config;
        $this->input = $input;
        $this->output = $output;
        $this->WPCli = $WPCli;
        $this->command = $command;
    }

    /**
     * @param $path
     */
    public function install($path)
    {
        $this->path = $path;
        $this->connect()
            ->createDatabase()
            ->configure()
            ->run()
            ->activateTheme();
    }

    /**
     * @return $this
     */
    protected function activateTheme()
    {
        $theme = $this->input->getArgument('name');
        $this->WPCli->execute("theme activate  $theme", [
            'path' => $this->pathToWordPress(),
        ]);

        return $this;
    }

    /**
     * @return bool|string
     */
    public function pathToWordPress()
    {
        return realpath($this->input->getArgument('name'));
    }

    /**
     * Run WordPress installation.
     *
     * @return $this
     */
    protected function run()
    {
        $args = [
            'url' => $this->input->getOption('url'),
            'title' => $this->input->getOption('title'),
            'admin_user' => $this->config->get('wp_username'),
            'admin_email' => $this->config->get('email'),
            'skip-email' => true,
            'path' => realpath($this->input->getArgument('name')),
        ];

        $this->output->writeln("<comment>Installing WordPress, you'll be asked for admin password which is optional if not provided we'll generate one for you.<comment>");

        // ask for admin password and feed it to wp-cli
        $helper = $this->command->getHelper('question');
        $question = new Question('<info>Please enter new password for WordPress admin panel : </info>', null);
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $args['admin_password'] = $helper->ask($this->input, $this->output, $question);

        $this->WPCli->install($args);

        return $this;
    }

    /**
     * Configure wordpress (wp-config).
     *
     * @return $this
     */
    protected function configure()
    {
        $this->WPCli->config([
            'dbname' => $this->input->getOption('db') ?: $this->input->getArgument('name'),
            'dbuser' => $this->config->get('username'),
            'dbpass' => $this->config->get('password'),
            'dbhost' => $this->config->get('host'),
            'path' => realpath($this->input->getArgument('name')),
            'dbprefix' => $this->input->getOption('prefix') ?: $this->input->getArgument('name') . '_',
        ]);

        return $this;
    }

    /**
     * Create database
     *
     * @return $this
     */
    protected function createDatabase()
    {
        $this->output->writeln('Creating database');
        $created = $this->connection->query(
            sprintf('CREATE DATABASE IF NOT EXISTS `%s`',
                $this->input->getOption('db') ?: $this->input->getArgument('name'))
        );

        if (!$created) {
            $error = $this->connection->errorInfo();
            $this->output->writeln(sprintf('<error>%s</error>'), end($error));
        }

        return $this;
    }

    /**
     * Make sure we can connect to the configured mysql server.
     *
     * @return $this
     */
    protected function connect()
    {
        $host = $this->config->get('host');
        $username = $this->config->get('username');
        $password = $this->config->get('password');

        try {
            $this->connection = new \PDO(sprintf('mysql:host=%s', $host), $username, $password);
        } catch (\PDOException $exception) {
            throw new RuntimeException($exception->getMessage());
        }

        return $this;
    }
}
