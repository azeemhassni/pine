<?php

namespace Pine\Generators;

use Pine\Config\Config;
use Pine\TimberInstaller;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Class Theme.
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
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var Config
     */
    protected $config;

    /**
     * Theme constructor.
     *
     * @param $name
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Config $config
     */
    public function __construct($name, InputInterface $input, OutputInterface $output, Config $config)
    {
        $this->name       = $name;
        $this->path       = getcwd() . '/' . $name . '/wp-content/themes/' . $name;
        $this->fileSystem = new Filesystem();
        $this->input      = $input;
        $this->output     = $output;
        $this->config     = $config;
    }

    /**
     * Generate theme.
     */
    public function generate()
    {
        $this->createDirectory()
            ->installTimber()
            ->scaffoldWPTheme()
            ->replaceThemeName();

        if ($this->input->getOption('npm')) {
            $this->setupGulp();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function replaceThemeName()
    {
        $files = [
            $this->path . '/style.css',
            $this->path . '/package.json',
        ];

        /*
         * Replace Author & Theme Name in style.css File
         */
        foreach ($files as $file) {
            file_put_contents(
                $file,
                str_replace(
                    ['@THEME_NAME@', '@AUTHOR_NAME@'],
                    [$this->name, $this->config->get('author')],
                    file_get_contents($file)
                )
            );
        }

        return $this;
    }

    /**
     * Generate basic Timber theme.
     *
     * @return $this
     */
    protected function scaffoldWPTheme()
    {
        $this->copyFiles($this->getThemeFilesDirectory());

        return $this;
    }

    /**
     * Copy files to created theme recursively.
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
     * Theme boilerplate directory.
     *
     * @return string
     */
    public function getThemeFilesDirectory()
    {
        return dirname(dirname(__DIR__)) . '/theme/';
    }

    /**
     * Install timber.
     *
     * @return Theme
     */
    protected function installTimber()
    {
        (new TimberInstaller($this->path, $this->output))->install();

        return $this;
    }

    /**
     * Create theme directory.
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
     * @return Theme
     */
    public function setupGulp()
    {
        $process = new Process("cd $this->path && npm install");

        $process->setTimeout(2 * 3600);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) {
            $this->output->writeln($line);
        });

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
}
