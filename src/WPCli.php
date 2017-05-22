<?php

namespace Pine;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class WPCli.
 */
class WPCli
{
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var string path to wp-cli binary
     */
    protected $wp;

    /**
     * WPCli constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->wp = dirname(__DIR__).'/vendor/bin/wp';
    }

    /**
     * Setup wp-config.php.
     *
     * @param array $args
     */
    public function config($args = [])
    {
        $args = $this->parseArgs([
            'dbname'   => '',
            'dbhost'   => '',
            'dbuser'   => '',
            'dbpass'   => '',
            'dbprefix' => 'wp_',
        ], $args);

        $this->execute('core config', $args);
    }

    /**
     * @param array $defaults
     * @param array $args
     *
     * @return array
     */
    public function parseArgs(array $defaults, array $args)
    {
        foreach ($args as $key => $value) {
            if (!empty($value)) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Execute a wp-cli command.
     *
     * @param $command
     * @param array $args
     *
     * @return Process
     */
    public function execute($command, $args = [])
    {
        $command = "$this->wp $command ";
        foreach ($args as $arg => $value) {
            if (substr($arg, 0, strlen('prompt=')) == 'prompt=') {
                $command .= "--$arg ";
                continue;
            }

            $command .= "--$arg='$value' ";
        }

        $process = new Process($command);
        $process->setTty(true);
        $process->run();

        return $process;
    }

    /**
     * Install wordpress.
     *
     * @param $args
     *
     * @return Process
     */
    public function install($args)
    {
        $args = $this->parseArgs([
            'url'            => 'localhost/'.$this->input->getArgument('name'),
            'title'          => 'Just another WordPress site',
            'admin_user'     => '',
            'admin_password' => null,
            'admin_email'    => '',
            'skip-email'     => false,
            'path'           => '',
        ], $args);

        return $this->execute('core install', $args);
    }
}
