<?php

namespace Pine\Console;

use GuzzleHttp\Client;
use Pine\Config\Config;
use Pine\Generators\Theme;
use Pine\WPCli;
use Pine\WPInstaller;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class NewCommand.
 */
class NewCommand extends Command
{
    /**
     * @var string download the package from
     */
    protected $baseUrl = 'https://wordpress.org';

    /**
     * @var
     */
    protected $cacheDirectory;
    /**
     * @var string app name
     */
    protected $app;
    /**
     * @var string wordpress version to download
     */
    protected $version;
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * NewCommand constructor.
     *
     * @param Config $config
     * @param null $name
     */
    public function __construct( Config $config, $name = null )
    {
        $this->cacheDirectory = $this->getCacheDirectory();
        $this->createCacheDirectory();
        parent::__construct($config, $name);
    }

    /**
     * @return string
     */
    protected function getCacheDirectory()
    {
        return isset($_SERVER[ 'HOME' ]) ?
            $_SERVER[ 'HOME' ] . DIRECTORY_SEPARATOR . '.timber_installer' . DIRECTORY_SEPARATOR :
            getcwd();
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute( InputInterface $input, OutputInterface $output )
    {
        if (!$this->config->isConfigured()) {
            $output->writeln('<info>Your pine installation is not configured, please complete the interactive setup to configure it.</info>');
            $this->call('init', $output);
            $this->config->load();
        }

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->setApp($input->getArgument('name'))->setVersion($input->getArgument('version'));

        $this->verifyApplicationDoesNotExists(
            $directory = ( $input->getArgument('name') ) ? getcwd() . '/' . $input->getArgument('name') : getcwd()
        );
        $this->setApp($input->getArgument('name'))->setVersion($input->getArgument('version'));

        $this->createApplicationDirectory();

        $zipFile = $this->download($output);

        $output->writeln('<info>Extracting package</info>');
        $this->extract($zipFile);

        $output->writeln('<info>Generating WordPress theme & installing timber</info>');
        ( new Theme($this->getApp(), $input, $output, $this->config) )->generate();

        // install WordPress
        if ($input->hasOption('skip-install')) {
            ( new WPInstaller($this, $this->config, $input, $output, new WPCli($input, $output)) )->install(
                realpath($this->getApp())
            );
        }

        $output->writeln('<comment>All done! Build something amazing.</comment>');
    }

    /**
     * @param $directory
     * @return bool
     */
    protected function verifyApplicationDoesNotExists( $directory )
    {
        $isEmpty = ( count(glob("$directory/*")) === 0 ) ? true : false;

        if (( is_dir($directory) || is_file($directory) ) && $directory != getcwd() && !$isEmpty) {
            throw new RuntimeException('Application already exists!');
        }

        return true;
    }

    /**
     * Create application directory.
     *
     * @throws RuntimeException
     */
    protected function createApplicationDirectory()
    {
        $directory = getcwd() . '/' . $this->getApp();

        if (!is_writable(dirname($directory))) {
            throw new RuntimeException(sprintf('%s is not writable by current user', $directory));
        }

        if (is_dir($directory) && !file_exists($directory)) {
            mkdir($directory);
        }
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
     * Download WordPress Zip package.
     *
     * @param OutputInterface $output
     * @return string
     */
    public function download( $output )
    {
        $zipFilePath = $this->getZipFilePath();

        if (file_exists($zipFilePath) && $this->verifyZipIntegrity()) {
            $output->writeln('Using WordPress from cache');

            return $zipFilePath;
        }

        $file = ( new Client([
            'verify' => false,
        ]) );

        $zipFileResource  = fopen($zipFilePath, 'w');
        $downloadProgress = new ProgressBar($output);
        $downloadProgress->setFormat('<comment>Downloading WordPress : %downloaded%M of %total_size%M %percent_now%%</comment>');
        $downloadProgress->start();

        $file->request('GET', $this->getUrl(), [
            'sink'     => $zipFileResource,
            'progress' => function (
                $downloadTotal,
                $downloadedBytes,
                $uploadTotal,
                $uploadedBytes
            ) use ( $downloadProgress ) {
                $progressValue = 0;
                if ($downloadedBytes > 0) {
                    $progressValue = ( $downloadedBytes / $downloadTotal ) * 100;
                }

//                if ($downloadTotal) {
//                    print_r(func_get_args());
//                    echo PHP_EOL;
//                    // given / total * 100
//                    print_r(( $downloadedBytes / $downloadTotal ) * 100);
//                    echo PHP_EOL;
//
//                    exit;
//                }
//                var_dump($progressValue);
//                if($progressValue > 100) {
//                    return;
//                }
//                echo $progressValue . PHP_EOL;
                $downloadProgress->advance(round($progressValue, 2));
                $downloadProgress->setMessage(round($progressValue, 2), 'percent_now');
                $downloadProgress->setMessage(round($downloadedBytes / 1024 / 1024, 2), 'downloaded');
                $downloadProgress->setMessage(round($downloadTotal / 1024 / 1024, 2), 'total_size');
            },
        ]);

        $output->writeln('');

        return $zipFilePath;
    }

    /**
     * @return string
     */
    protected function getZipFilePath()
    {
        if ($version = $this->getVersion()) {
            return $this->cacheDirectory . "wordpress-$version.zip";
        }

        return $this->cacheDirectory . 'wordpress.zip';
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
     * @param string $algorithm md5|sha1
     * @return bool
     */
    protected function verifyZipIntegrity( $algorithm = 'md5' )
    {
        $request        = new Client([
            'verify' => false,
        ]);
        $response       = $request->get($this->getUrl($algorithm));
        $remoteChecksum = $response->getBody();
        $localChecksum  = md5_file($this->getZipFilePath());
        if ($algorithm == 'md5' && ( $remoteChecksum != $localChecksum )) {
            unlink($this->getZipFilePath());
            echo "Cannot verify integrity of {$this->getZipFilePath()}.\n We have deleted the file.\n Downloading it again. \n";

            return false;
        }

        return true;
    }

    /**
     * Get WordPress ZIP file URL.
     *
     * @param null $checksum
     * @return mixed null|md5|sha1
     */
    protected function getUrl( $checksum = null )
    {
        $url = $this->baseUrl . '/latest.zip';
        if ($version = $this->getVersion()) {
            $url = $this->baseUrl . '/wordpress-' . $version . '.zip';
        }

        if ($checksum) {
            $url .= ".$checksum";
        }

        return $url;
    }

    /**
     * @param $file
     */
    public function extract( $file )
    {
        $projectPath = getcwd() . '/' . $this->getApp();
        $zip         = new \ZipArchive();
        $zip->open($file);
        $zip->extractTo($projectPath);

        $this->move($projectPath . '/wordpress/', $projectPath);
        $this->delete($projectPath . '/wordpress/');
    }

    /**
     * Move files from one directory to another.
     *
     * @param $source
     * @param $destination
     * @link http://stackoverflow.com/a/27290570/2641971
     */
    public function move( $source, $destination )
    {
        $this->fileSystem = new Filesystem();

        if (!is_dir($destination)) {
            $this->fileSystem->mkdir($destination);
        }

        $directoryIterator = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator          = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $this->fileSystem->mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                $this->fileSystem->rename($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

    /**
     * Delete a directory recursively.
     *
     * @param $directory
     * @return bool
     * @link http://stackoverflow.com/a/1653776/2641971
     */
    public function delete( $directory )
    {
        if (!file_exists($directory)) {
            return true;
        }

        if (!is_dir($directory)) {
            return unlink($directory);
        }

        foreach (scandir($directory) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->delete($directory . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($directory);
    }

    /**
     * Configures the new command.
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new WordPress application with Timber.')
            ->addArgument('name', InputArgument::REQUIRED, 'Your applications\'s name')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version of WordPress to download [optional]')
            ->addOption('url', null, InputOption::VALUE_OPTIONAL, 'Site URL [optional]')
            ->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Database table prefix [optional]', null)
            ->addOption('db', null, InputOption::VALUE_OPTIONAL, 'Database name [optional]', null)
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'Application title [optional]', null)
            ->addOption('npm', null, InputOption::VALUE_NONE, 'Pass this option if you want to install npm packages')
            ->addOption('skip-install', 's', InputOption::VALUE_NONE, 'Skip wordpress installation');
    }
}
