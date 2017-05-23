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
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        // try from local vendor/bin
        $this->wp = dirname(__DIR__) . '/vendor/bin/wp';

        if (!file_exists($this->wp)) {
            // load it from global vendor/bin (../../../bin/wp )
            $this->wp = dirname(dirname(dirname(__DIR__))) . '/bin/wp';
        }

        // make it cross-platform
        $this->wp = str_replace("/", DIRECTORY_SEPARATOR, $this->wp);

    }

    /**
     * Setup wp-config.php.
     *
     * @param array $args
     */
    public function config($args = [])
    {
        $args = $this->parseArgs([
            'dbname' => '',
            'dbhost' => '',
            'dbuser' => '',
            'dbpass' => '',
            'dbprefix' => 'wp_',
        ], $args);

        $this->execute('core config', $args);
    }

    /**
     * @param array $defaults
     * @param array $args
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
     * @return Process
     */
    public function execute($command, $args = [])
    {
        $command = sprintf('"%s" %s', $this->wp, $command);

        foreach ($args as $arg => $value) {
            if (substr($arg, 0, strlen('prompt=')) == 'prompt=') {
                $command .= " --$arg ";
                continue;
            }

            $command .= " --$arg=\"$value\"";
        }

        $process = new Process($command);
        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) {
            $this->output->writeln($line);
        });

        return $process;
    }

    /**
     * Install wordpress.
     *
     * @param $args
     * @return Process
     */
    public function install($args)
    {
        $args = $this->parseArgs([
            'url' => 'localhost/' . $this->input->getArgument('name'),
            'title' => 'Just another WordPress site',
            'admin_user' => 'admin',
            'admin_password' => null,
            'admin_email' => '',
            'skip-email' => false,
            'path' => '',
        ], $args);

        return $this->execute('core install', $args);
    }
}
