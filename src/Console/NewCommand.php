<?php namespace Pine\Console;

use Pine\Generators\Theme;
use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

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
     * @var Filesystem
     */
    protected $fileSystem;

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
            ->addArgument('version', InputArgument::OPTIONAL, 'The version of WordPress to download')
            ->addOption('npm', null, InputOption::VALUE_NONE, 'Pass this option if you want to install npm packages');


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

        $zipFile = $this->download($output);

        $output->writeln('<info>Extracting package</info>');
        $this->extract($zipFile);

        $output->writeln('<info>Generating WordPress theme & installing timber</info>');
        ( new Theme($this->getApp(), $input, $output) )->generate();

        // install WordPress

        if ($input->hasOption('db')) {

        }

        $output->writeln('<comment>All done! Build something amazing.</comment>');
    }

    /**
     * Download WordPress Zip package
     *
     * @param OutputInterface $output
     * @return string
     */
    public function download( $output )
    {
        $zipFilePath = $this->getZipFilePath();

        if (file_exists($zipFilePath)) {
            $this->verifyZipIntegrity();
            return $zipFilePath;
        }

        $file = ( new Client([
            'verify' => false,
        ]) );

        $zipFileResource  = fopen($zipFilePath, 'w');
        $downloadProgress = new ProgressBar($output);
        $downloadProgress->setFormatDefinition('custom', '<info>Downloading WordPress: %downloaded%%</info>');
        $downloadProgress->setFormat('custom');
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
                $downloadProgress->advance();
                $downloadProgress->setMessage(round($progressValue, 2), 'downloaded');
            },
        ]);
        $output->writeln("");
        return $zipFilePath;
    }

    /**
     * Get WordPress ZIP file URL
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
     * @return string
     */
    protected function getCacheDirectory()
    {
        return isset($_SERVER[ 'HOME' ]) ?
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
        $zip         = new \ZipArchive();
        $zip->open($file);
        $zip->extractTo($projectPath);

        $this->move($projectPath . "/wordpress/", $projectPath);
        $this->delete($projectPath . "/wordpress/");
    }

    /**
     * Delete a directory recursively
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
     * Move files from one directory to another
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
     * Create application directory
     */
    protected function createApplicationDirectory()
    {
        $directory = getcwd() . '/' . $this->getApp();
        if (is_dir($directory) && !file_exists($directory)) {
            mkdir($directory);
        }
    }

    /**
     * @param string $algorithm md5|sha1
     * @throws \Exception
     */
    protected function verifyZipIntegrity( $algorithm = 'md5' )
    {
        $request        = new Client([
            'verify' => false
        ]);
        $response       = $request->get($this->getUrl($algorithm));
        $remoteChecksum = $response->getBody();
        $localChecksum  = md5_file($this->getZipFilePath());
        if ($algorithm == 'md5' && ( $remoteChecksum != $localChecksum )) {
            unlink($this->getZipFilePath());
            throw new \Exception("Cannot verify integrity of {$this->getZipFilePath()}.\n We have deleted the file.\n Please try again.");
        }
    }
}