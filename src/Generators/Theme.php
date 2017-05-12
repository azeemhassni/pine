<?php

namespace Azi\Generators;

use Azi\TimberInstaller;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Class Theme
 *
 * @package Azi\Generators
 */
class Theme
{
    protected $themeFilesDirectory;

    /**
     * @var
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var InputInterface $input
     */
    protected $input;

    /**
     * @var OutputInterface $output
     */
    protected $output;

    /**
     * Theme constructor.
     *
     * @param $name
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct($name, InputInterface $input, OutputInterface $output)
    {
        $this->name = $name;
        $this->path = getcwd() . '/' . $name . '/wp-content/themes/' . $name;
        $this->fileSystem = new Filesystem();
        $this->input = $input;
        $this->output = $output;
    }

    public function getThemeFilesDirectory()
    {
        return dirname(dirname(__DIR__)) . '/theme/';
    }

    /**
     * Generate theme
     */
    public function generate()
    {
        $this->createDirectory()
            ->installTimber()
            ->scaffoldWPTheme()
            ->replaceThemeName();

        if(!$this->input->getOption('skip-npm')) {
            $this->setupGulp();
        }

//            ->setupGulp();

        return $this;
    }

    /**
     * @return Process
     */
    public function setupGulp()
    {
        $process = new Process("cd $this->path && echo {} > package.json && npm install gulp gulp-clean-css gulp-concat gulp-imagemin gulp-rename gulp-sass gulp-uglify  --save-dev");


        $process->setTimeout(2 * 3600);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ( $type, $line ) {
            $this->output->writeln($line);

        });

        return $this;
    }


    /**
     * Create theme directory
     */
    protected function createDirectory()
    {
        if (is_dir($this->path) && is_file($this->path)) {
            throw new RuntimeException('There is a theme with the same name');
        }

        mkdir($this->path);

        return $this;
    }

    /**
     * Install timber
     */
    protected function installTimber()
    {
        (new TimberInstaller($this->path, $this->output))->install();

        return $this;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param InputInterface $input
     * @return Theme
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     * @return Theme
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Copy files to created theme recursively
     *
     * @param $directory
     * @return $this
     */
    protected function copyFiles($directory)
    {
        $files = glob($directory . '/*');

        foreach ($files as $item) {

            if (is_dir($item)) {
                $this->copyFiles($item);
                continue;
            }

            $name = str_replace($this->getThemeFilesDirectory(), '', $item);
            $this->fileSystem->copy($item, $this->path . $name);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function scaffoldWPTheme()
    {
        $this->copyFiles($this->getThemeFilesDirectory());
        return $this;
    }

    /**
     * @return $this
     */
    private function replaceThemeName()
    {
        $stylesheet = $this->path . '/style.css';

        file_put_contents(
            $stylesheet,
            str_replace(
                ['@THEME_NAME@', '@AUTHOR_NAME@'],
                [$this->name, get_current_user()],
                file_get_contents($stylesheet)
            )
        );

        return $this;
    }
}