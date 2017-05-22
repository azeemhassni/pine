<?php
/**
 * Created by PhpStorm.
 * User: azi
 * Date: 5/17/17
 * Time: 5:06 PM.
 */

namespace Pine\Console;

use Pine\Config\Config;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config, $name = null)
    {
        $this->config = $config;
        parent::__construct($name);
    }

    public function call($commandName, OutputInterface $output, $args = [])
    {
        $command = $this->getApplication()->find($commandName);
        $input = new ArrayInput($args);
        $command->run($input, $output);
    }
}
