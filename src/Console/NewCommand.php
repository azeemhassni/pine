<?php namespace Azi\Console;

use Azi\Generators\Theme;
use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NewCommand
 *
 * @package Azi
 */
class NewCommand extends Command
{
    /**
     * @var $baseUrl string download the package from
     */
    protected $baseUrl = 'https://wordpress.org';

    /**
     * @var
     */
    protected $cacheDirectory;

    protected $app;
    protected $version;

    /**
     * NewCommand constructor.
     *
     * @param null $name
     */
    public function __construct( $name = null )
    {
        $this->cacheDirectory = $this->getCacheDirectory();
        $this->createCacheDirectory();
        parent::__construct($name);
    }

    /**
     * @return mixed
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param mixed $app
     * @return NewCommand
     */
    public function setApp( $app )
    {
        $this->app = $app;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     * @return NewCommand
     */
    public function setVersion( $version )
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Configures the new command.
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new WordPress application with Timber.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Your applications\'s name')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version of wordpress to download');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute( InputInterface $input, OutputInterface $output )
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->verifyApplicationDoesNotExists(
            $directory = ( $input->getArgument('name') ) ? getcwd() . '/' . $input->getArgument('name') : getcwd()
        );

        $this->setApp($input->getArgument('name'))->setVersion($input->getArgument('version'));

        $this->createApplicationDirectory();

        $output->writeln('<info>Downloading wordpress..</info> this might take a while');
        $zipFile = $this->download();
        $output->writeln('<info>Extracting package</info>');
        $this->extract($zipFile);
        $output->writeln('<info>Generating wordpress theme & installing timber</info>');
        (new Theme($this->getApp(), $input, $output))->generate();
        $output->writeln('<comment>All done! Build something amazing.</comment>');
    }

    /**
     * Download WordPress Zip package
     *
     * @return string
     */
    public function download()
    {
        $zipFilePath = $this->getZipFilePath();

        if (file_exists($zipFilePath)) {
            return $zipFilePath;
        }

        $file = (new Client())->get($this->getUrl());
        file_put_contents($zipFilePath, $file->getBody());
        return $zipFilePath;
    }

    /**
     * Get WordPress ZIP file URL
     *
     * @return string
     */
    protected function getUrl()
    {
        if ($version = $this->getVersion()) {
            return $this->baseUrl . '/wordpress-' . $version . '.zip';
        }

        return $this->baseUrl . '/latest.zip';
    }

    /**
     * @param $directory
     */
    protected function verifyApplicationDoesNotExists( $directory )
    {
        if (( is_dir($directory) || is_file($directory) ) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * @return string
     */
    protected function getCacheDirectory()
    {
        return isset( $_SERVER[ 'HOME' ] ) ?
            $_SERVER[ 'HOME' ] . DIRECTORY_SEPARATOR . '.timber_installer' . DIRECTORY_SEPARATOR :
            getcwd();
    }

    /**
     * @return string
     */
    protected function getZipFilePath()
    {
        if ($version = $this->getVersion()) {
            return $this->cacheDirectory . "wordpress-$version.zip";
        }

        return $this->cacheDirectory . "wordpress.zip";
    }

    /**
     * Create .timber_installer directory in User's home directory
     * so that we will not need to download the zip package
     * again for future installations.
     */
    private function createCacheDirectory()
    {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }
    }

    /**
     * @param $file
     */
    public function extract( $file )
    {
        $projectPath = getcwd() . '/' . $this->getApp();

        $zip = new \ZipArchive();
        $zip->open($file);
        $zip->extractTo($projectPath);

        # Kind of a hack but works.
        exec("mv " . $projectPath . "/wordpress/* $projectPath");
        rmdir($projectPath . '/wordpress');
    }

    /**
     * Create application directory
     */
    protected function createApplicationDirectory()
    {
        mkdir(getcwd() . '/' . $this->getApp());
    }
}