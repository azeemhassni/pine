<?php

namespace Pine;

use Pine\Traits\Composer;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class TimberInstaller.
 */
class TimberInstaller
{
    use Composer;

    /**
     * @var
     */
    private $themeDirectory;
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * TimberInstaller constructor.
     *
     * @param $themeDirectory
     * @param OutputInterface $output
     */
    public function __construct($themeDirectory, OutputInterface $output)
    {
        $this->themeDirectory = $themeDirectory;
        $this->output         = $output;
    }

    /**
     * Composer require timber/timber.
     */
    public function install()
    {
        $command = $this->findComposer() . ' require timber/timber';
        $process = new Process($command, $this->themeDirectory);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) {
            $this->output->writeln($line);
        });

        return $this;
    }
}
