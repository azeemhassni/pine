<?php

namespace Pine\Traits;

use Symfony\Component\Process\Process;

/**
 * Class Composer.
 */
trait Composer
{
    /**
     * @param $package
     */
    protected function install( $package )
    {
        $command = $this->findComposer() . ' require ' . $package;
        $process = new Process($command);
        $process->run(function ( $type, $line ) {
            echo $line . PHP_EOL;
        });
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }
}
