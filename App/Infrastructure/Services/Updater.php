<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Codefy\Framework\Codefy;
use Codefy\Framework\Factory\FileLoggerFactory;
use Composer\Semver\Comparator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use ReflectionException;
use RuntimeException;
use ZipArchive;

use function array_map;
use function base64_encode;
use function clearstatcache;
use function Codefy\Framework\Helpers\base_path;
use function Codefy\Framework\Helpers\storage_path;
use function count;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function dirname;
use function fclose;
use function file_get_contents;
use function filter_var;
use function fopen;
use function function_exists;
use function fwrite;
use function ini_get;
use function ini_set;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function mkdir;
use function parse_ini_string;
use function Qubus\Support\Helpers\add_trailing_slash;
use function Qubus\Support\Helpers\is_writable;
use function sprintf;
use function str_replace;
use function stream_context_create;
use function strlen;
use function strrchr;
use function substr;
use function unlink;

use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const FILTER_VALIDATE_URL;

final class Updater
{
    private ?string $latestVersion = null;

    private array $updates = [];

    private ?CacheInterface $cache = null;

    private ?LoggerInterface $log = null;

    private array $simulationResults = [];

    private string $tempDir = '';

    private string $installDir = '';

    private string $branch = '';

    private string $username = '';

    private string $password = '';

    private array $onEachUpdateFinishCallbacks = [];

    private array $onAllUpdateFinishCallbacks = [];

    private bool $sslVerifyHost = true;

    protected string $updateUrl = 'https://example.com/updates/';

    protected string $updateFile = 'update.json';

    protected ?string $currentVersion = null;

    public int $dirPermissions = 0755;

    public string $updateScriptName = '_upgrade.php';

    public int $cacheTtl = 1800;

    public const int NO_UPDATE_AVAILABLE = 0;

    public const int ERROR_VERSION_CHECK = 20;

    public const int ERROR_TEMP_DIR = 30;

    public const int ERROR_INSTALL_DIR = 35;

    public const int ERROR_DOWNLOAD_UPDATE = 40;

    public const int ERROR_DELETE_TEMP_UPDATE = 50;

    public const int ERROR_SIMULATE = 70;

    /**
     * Create new instance
     *
     * @param string|null $tempDir
     * @param string|null $installDir
     * @param int $maxExecutionTime
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?string $tempDir = null, ?string $installDir = null, int $maxExecutionTime = 60)
    {
        // Init logger
        $this->log = FileLoggerFactory::getLogger();

        $this->setTempDir($tempDir ?? storage_path('temp') . Codefy::$PHP::DS);
        $this->setInstallDir($installDir ?? base_path());

        $this->latestVersion  = '0.0.0';
        $this->currentVersion = '0.0.0';

        // Init cache
        $this->cache = SimpleCacheObjectCacheFactory::make(namespace: 'auto_updater');

        ini_set('max_execution_time', $maxExecutionTime);
    }

    /**
     * Set the temporary download directory.
     *
     * @param string $dir
     * @return bool
     */
    public function setTempDir(string $dir): bool
    {
        $dir = add_trailing_slash($dir);

        if (!is_dir($dir)) {
            $this->log->debug(sprintf('Creating new temporary directory "%s"', $dir));

            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                $this->log->critical(sprintf('Could not create temporary directory "%s"', $dir));

                return false;
            }
        }

        $this->tempDir = $dir;

        return true;
    }

    /**
     * Set the installation directory.
     *
     * @param string $dir
     * @return bool
     */
    public function setInstallDir(string $dir): bool
    {
        $dir = add_trailing_slash($dir);

        if (!is_dir($dir)) {
            $this->log->debug(sprintf('Creating new install directory "%s"', $dir));

            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                $this->log->critical(sprintf('Could not create install directory "%s"', $dir));

                return false;
            }
        }

        $this->installDir = $dir;

        return true;
    }

    /**
     * Set the update filename.
     *
     * @param string $updateFile
     * @return Updater
     */
    public function setUpdateFile(string $updateFile): Updater
    {
        $this->updateFile = $updateFile;

        return $this;
    }

    /**
     * Set the update filename.
     *
     * @param string $updateUrl
     * @return Updater
     */
    public function setUpdateUrl(string $updateUrl): Updater
    {
        $this->updateUrl = $updateUrl;

        return $this;
    }

    /**
     * Set the update branch.
     *
     * @param string $branch branch
     * @return Updater
     */
    public function setBranch(string $branch): Updater
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * Set the cache component.
     *
     * @param CacheInterface $adapter
     * @param int $ttl
     * @return Updater
     */
    public function setCache(CacheInterface $adapter, int $ttl): Updater
    {
        $this->cache    = $adapter;
        $this->cacheTtl = $ttl;

        return $this;
    }

    /**
     * Set the version of the current installed software.
     *
     * @param string $currentVersion
     * @return Updater
     */
    public function setCurrentVersion(string $currentVersion): Updater
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }

    /**
     * Set username and password for basic authentication.
     *
     * @param string $username
     * @param string $password
     * @return Updater
     */
    public function setBasicAuth(string $username, string $password): Updater
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Set authentication header if username and password exist.
     *
     * @return null|resource
     */
    private function useBasicAuth()
    {
        if ($this->username && $this->password) {
            return stream_context_create(array(
                'http' => array(
                    'header' => "Authorization: Basic " . base64_encode("$this->username:$this->password")
                )
            ));
        }

        return null;
    }

    /**
     * Replace the logger internally used by the given logger instance.
     *
     * @param LoggerInterface $logger
     * @return Updater
     */
    public function setLogger(LoggerInterface $logger): Updater
    {
        $this->log = $logger;

        return $this;
    }

    /**
     * Get the name of the latest version.
     *
     * @return string
     */
    public function getLatestVersion(): string
    {
        return $this->latestVersion;
    }

    /**
     * Get an array of versions which will be installed.
     *
     * @return array
     */
    public function getVersionsToUpdate(): array
    {
        if (count($this->updates) > 0) {
            return array_map(static function ($update) {
                return $update['version'];
            }, $this->updates);
        }

        return [];
    }

    /**
     * Get the results of the last simulation.
     *
     * @return array
     */
    public function getSimulationResults(): array
    {
        return $this->simulationResults;
    }

    /**
     * @return bool
     */
    public function getSslVerifyHost(): bool
    {
        return $this->sslVerifyHost;
    }

    /**
     * @param bool $sslVerifyHost
     * @return Updater
     */
    public function setSslVerifyHost(bool $sslVerifyHost): Updater
    {
        $this->sslVerifyHost = $sslVerifyHost;

        return $this;
    }

    /**
     * Check for a new version
     *
     * @param int $timeout Download timeout in seconds (Only applied for downloads via curl)
     * @return int|bool
     *         true: New version is available
     *         false: Error while checking for update
     *         int: Status code (i.e. Updater::NO_UPDATE_AVAILABLE)
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function checkUpdate(int $timeout = 10): bool|int
    {
        $this->log->notice('Checking for a new update...');

        // Reset previous updates
        $this->latestVersion = '0.0.0';
        $this->updates       = [];

        $versions = $this->cache->get(key: 'update-versions');

        // Create absolute url to update file
        $updateFile = $this->updateUrl . '/' . $this->updateFile;
        if (!empty($this->branch)) {
            $updateFile .= '.' . $this->branch;
        }

        // Check if cache is empty
        if ($versions === null || $versions === false) {
            $this->log->debug(sprintf('Get new updates from %s', $updateFile));

            // Read update file from update server
            if (function_exists('curl_version') && $this->isValidUrl($updateFile)) {
                $update = $this->downloadCurl($updateFile, $timeout);

                if ($update === false) {
                    $this->log->error(sprintf('Could not download update file "%s" via curl!', $updateFile));

                    throw new Exception($updateFile);
                }
            } else {
                $update = @file_get_contents($updateFile, false, $this->useBasicAuth());

                if ($update === false) {
                    $this->log->error(sprintf(
                        'Could not download update file "%s" via file_get_contents!',
                        $updateFile
                    ));

                    throw new Exception($updateFile);
                }
            }

            // Parse update file
            $updateFileExtension = substr(strrchr($this->updateFile, '.'), 1);
            switch ($updateFileExtension) {
                case 'ini':
                    $versions = parse_ini_string($update, true);
                    if (!is_array($versions)) {
                        $this->log->error('Unable to parse ini update file!');

                        throw new Exception(sprintf('Could not parse update ini file %s!', $this->updateFile));
                    }

                    $versions = array_map(static function ($block) {
                        return $block['url'] ?? false;
                    }, $versions);

                    break;
                case 'json':
                    $versions = (array) json_decode($update, false);
                    if (!is_array($versions)) {
                        $this->log->error('Unable to parse json update file!');

                        throw new Exception(sprintf('Could not parse update json file %s!', $this->updateFile));
                    }

                    break;
                default:
                    $this->log->error(sprintf('Unknown file extension "%s"', $updateFileExtension));

                    throw new Exception(sprintf('Unknown file extension for update file %s!', $this->updateFile));
            }

            $this->cache->set(key: 'update-versions', value: $versions, ttl: $this->cacheTtl);
        } else {
            $this->log->debug('Got updates from cache');
        }

        if (!is_array($versions)) {
            $this->log->error(sprintf('Could not read versions from server %s', $updateFile));

            return false;
        }

        // Check for latest version
        foreach ($versions as $version => $updateUrl) {
            if (Comparator::greaterThan($version, $this->currentVersion)) {
                if (Comparator::greaterThan($version, $this->latestVersion)) {
                    $this->latestVersion = $version;
                }

                $this->updates[] = [
                    'version' => $version,
                    'url'     => $updateUrl,
                ];
            }
        }

        // Sort versions to install
        usort($this->updates, static function ($a, $b) {
            if (Comparator::equalTo($a['version'], $b['version'])) {
                return 0;
            }

            return Comparator::lessThan($a['version'], $b['version']) ? - 1 : 1;
        });

        if ($this->newVersionAvailable()) {
            $this->log->debug(sprintf('New version "%s" available', $this->latestVersion));

            return true;
        }

        $this->log->debug('No new version available');

        return self::NO_UPDATE_AVAILABLE;
    }

    /**
     * Check if a new version is available.
     *
     * @return bool
     */
    public function newVersionAvailable(): bool
    {
        return Comparator::greaterThan($this->latestVersion, $this->currentVersion);
    }

    /**
     * Check if url is valid.
     *
     * @param string $url
     * @return bool
     */
    protected function isValidUrl(string $url): bool
    {
        return (filter_var($url, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * Download file via curl.
     *
     * @param string $url URL to file
     * @param int $timeout
     * @return string|false
     */
    protected function downloadCurl(string $url, int $timeout = 10): false|string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->sslVerifyHost ? 2 : 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyHost);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        $update = curl_exec($curl);

        $success = true;
        if (curl_error($curl)) {
            $success = false;
            $this->log->error(sprintf(
                'Could not download update "%s" via curl: %s!',
                $url,
                curl_error($curl)
            ));
        }
        curl_close($curl);

        return ($success === true) ? $update : false;
    }

    /**
     * Download the update
     *
     * @param string $updateUrl Url where to download from
     * @param string $updateFile Path where to save the download
     * @return bool
     * @throws Exception
     * @throws Exception
     */
    protected function downloadUpdate(string $updateUrl, string $updateFile): bool
    {
        $this->log->info(sprintf('Downloading update "%s" to "%s"', $updateUrl, $updateFile));
        if (function_exists('curl_version') && $this->isValidUrl($updateUrl)) {
            $update = $this->downloadCurl($updateUrl);
            if ($update === false) {
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            $update = @file_get_contents($updateUrl, false, $this->useBasicAuth());

            if ($update === false) {
                $this->log->error(sprintf('Could not download update "%s"!', $updateUrl));

                throw new Exception($updateUrl);
            }
        } else {
            throw new RuntimeException('No valid download method found!');
        }

        $handle = fopen($updateFile, 'wb');
        if (!$handle) {
            $this->log->error(sprintf('Could not open file handle to save update to "%s"!', $updateFile));

            return false;
        }

        if (!fwrite($handle, $update)) {
            $this->log->error(sprintf('Could not write update to file "%s"!', $updateFile));
            fclose($handle);

            return false;
        }

        fclose($handle);

        return true;
    }

    /**
     * Simulate update process.
     *
     * @param string $updateFile
     * @return bool
     */
    protected function simulateInstall(string $updateFile): bool
    {
        $this->log->notice('[SIMULATE] Install new version');
        clearstatcache();

        // Check if zip file could be opened
        $zip = new ZipArchive();
        $resource = $zip->open($updateFile);
        if ($resource !== true) {
            $this->log->error(sprintf('Could not open zip file "%s", error: %d', $updateFile, $resource));

            return false;
        }

        $files           = [];
        $simulateSuccess = true;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileStats        = $zip->statIndex($i);
            $filename         = $fileStats['name'];
            $foldername       = $this->installDir . dirname($filename);
            $absoluteFilename = $this->installDir . $filename;

            $files[$i] = [
                'filename'          => $filename,
                'foldername'        => $foldername,
                'absolute_filename' => $absoluteFilename,
            ];

            $this->log->debug(sprintf('[SIMULATE] Updating file "%s"', $filename));

            // Check if parent directory is writable
            if (!is_dir($foldername)) {
                if (!mkdir($foldername) && !is_dir($foldername)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $foldername));
                }
                $this->log->debug(sprintf('[SIMULATE] Create directory "%s"', $foldername));
                $files[$i]['parent_folder_exists'] = false;

                $parent = dirname($foldername);
                if (!is_writable($parent)) {
                    $files[$i]['parent_folder_writable'] = false;

                    $simulateSuccess = false;
                    $this->log->warning(sprintf('[SIMULATE] Directory "%s" has to be writeable!', $parent));
                } else {
                    $files[$i]['parent_folder_writable'] = true;
                }
            }

            // Skip if entry is a directory
            if ($filename[strlen($filename) - 1] === Codefy::$PHP::DS) {
                continue;
            }

            // Write to file
            if (file_exists($absoluteFilename)) {
                $files[$i]['file_exists'] = true;
                if (!is_writable($absoluteFilename)) {
                    $files[$i]['file_writable'] = false;

                    $simulateSuccess = false;
                    $this->log->warning(sprintf('[SIMULATE] Could not overwrite "%s"!', $absoluteFilename));
                }
            } else {
                $files[$i]['file_exists'] = false;

                if (is_dir($foldername)) {
                    if (!is_writable($foldername)) {
                        $files[$i]['file_writable'] = false;

                        $simulateSuccess = false;
                        $this->log->warning(sprintf(
                            '[SIMULATE] The file "%s" could not be created!',
                            $absoluteFilename
                        ));
                    } else {
                        $files[$i]['file_writable'] = true;
                    }
                } else {
                    $files[$i]['file_writable'] = true;

                    $this->log->debug(sprintf('[SIMULATE] The file "%s" could be created', $absoluteFilename));
                }
            }

            if ($filename === $this->updateScriptName) {
                $this->log->debug(sprintf('[SIMULATE] Update script "%s" found', $absoluteFilename));
                $files[$i]['update_script'] = true;
            } else {
                $files[$i]['update_script'] = false;
            }
        }

        $zip->close();

        $this->simulationResults = $files;

        return $simulateSuccess;
    }

    /**
     * Install update.
     *
     * @param string $updateFile Path to the update file
     * @param int|bool $simulateInstall Check for directory and file permissions instead of installing the update
     * @param string $version
     * @return int|bool
     */
    protected function install(string $updateFile, bool $simulateInstall, string $version): int|bool
    {
        $this->log->notice(sprintf('Trying to install update "%s"', $updateFile));

        // Check if install should be simulated
        if ($simulateInstall) {
            if ($this->simulateInstall($updateFile)) {
                $this->log->notice(sprintf('Simulation of update "%s" process succeeded', $version));

                return true;
            }

            $this->log->critical(sprintf('Simulation of update  "%s" process failed!', $version));

            return self::ERROR_SIMULATE;
        }

        clearstatcache();

        // Install only if simulateInstall === false

        // Check if zip file could be opened
        $zip = new ZipArchive();
        $resource = $zip->open($updateFile);
        if ($resource !== true) {
            $this->log->error(sprintf('Could not open zip file "%s", error: %d', $updateFile, $resource));

            return false;
        }

        // Read every file from archive
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileStats        = $zip->statIndex($i);
            $filename         = str_replace(array('/', '\\'), Codefy::$PHP::DS, $fileStats['name']);
            $foldername       = str_replace(
                array('/', '\\'),
                Codefy::$PHP::DS,
                $this->installDir . dirname($filename)
            );
            $absoluteFilename = str_replace(array('/', '\\'), Codefy::$PHP::DS, $this->installDir . $filename);
            $this->log->debug(sprintf('Updating file "%s"', $filename));

            if (!is_dir($foldername) && !mkdir($foldername, $this->dirPermissions, true) && !is_dir($foldername)) {
                $this->log->error(sprintf('Directory "%s" has to be writeable!', $foldername));

                return false;
            }

            // Skip if entry is a directory
            if ($filename[strlen($filename) - 1] === Codefy::$PHP::DS) {
                continue;
            }

            // Extract file
            if ($zip->extractTo($this->installDir, $fileStats['name']) === false) {
                $this->log->error(sprintf('Could not read zip entry "%s"', $fileStats['name']));
                continue;
            }

            //If file is a update script, include
            if ($filename === $this->updateScriptName) {
                $this->log->debug(sprintf('Try to include update script "%s"', $absoluteFilename));
                require($absoluteFilename);

                $this->log->info(sprintf('Update script "%s" included!', $absoluteFilename));
                if (!unlink($absoluteFilename)) {
                    $this->log->warning(sprintf('Could not delete update script "%s"!', $absoluteFilename));
                }
            }
        }

        $zip->close();

        $this->log->notice(sprintf('Update "%s" successfully installed', $version));

        return true;
    }

    /**
     * Update to the latest version
     *
     * @param bool $simulateInstall Check for directory and file permissions before copying files (Default: true)
     * @param bool $deleteDownload Delete download after update (Default: true)
     * @return int|bool
     * @throws Exception
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function update(bool $simulateInstall = true, bool $deleteDownload = true): bool|int
    {
        $this->log->info('Trying to perform update');

        // Check for latest version
        if ($this->latestVersion === null || count($this->updates) === 0) {
            $this->checkUpdate();
        }

        if ($this->latestVersion === null || count($this->updates) === 0) {
            $this->log->error('Could not get latest version from server!');

            return self::ERROR_VERSION_CHECK;
        }

        // Check if current version is up-to-date
        if (!$this->newVersionAvailable()) {
            $this->log->warning('No update available!');

            return self::NO_UPDATE_AVAILABLE;
        }

        foreach ($this->updates as $update) {
            $this->log->debug(sprintf('Update to version "%s"', $update['version']));

            // Check for temp directory
            if (empty($this->tempDir) || !is_dir($this->tempDir) || !is_writable($this->tempDir)) {
                $this->log->critical(sprintf(
                    'Temporary directory "%s" does not exist or is not writeable!',
                    $this->tempDir
                ));

                return self::ERROR_TEMP_DIR;
            }

            // Check for install directory
            if (empty($this->installDir) || !is_dir($this->installDir) || !is_writable($this->installDir)) {
                $this->log->critical(sprintf(
                    'Install directory "%s" does not exist or is not writeable!',
                    $this->installDir
                ));

                return self::ERROR_INSTALL_DIR;
            }

            $updateFile = $this->tempDir . $update['version'] . '.zip';

            // Download update
            if (!is_file($updateFile)) {
                if (!$this->downloadUpdate($update['url'], $updateFile)) {
                    $this->log->critical(sprintf(
                        'Failed to download update from "%s" to "%s"!',
                        $update['url'],
                        $updateFile
                    ));

                    return self::ERROR_DOWNLOAD_UPDATE;
                }

                $this->log->debug(sprintf('Latest update downloaded to "%s"', $updateFile));
            } else {
                $this->log->info(sprintf('Latest update already downloaded to "%s"', $updateFile));
            }

            // Install update
            $result = $this->install($updateFile, $simulateInstall, $update['version']);
            if ($result === true) {
                $this->runOnEachUpdateFinishCallbacks($update['version'], $simulateInstall);
                if ($deleteDownload) {
                    $this->log->debug(sprintf(
                        'Trying to delete update file "%s" after successful update',
                        $updateFile
                    ));
                    if (unlink($updateFile)) {
                        $this->log->info(sprintf('Update file "%s" deleted after successful update', $updateFile));
                    } else {
                        $this->log->error(sprintf(
                            'Could not delete update file "%s" after successful update!',
                            $updateFile
                        ));

                        return self::ERROR_DELETE_TEMP_UPDATE;
                    }
                }
            } else {
                if ($deleteDownload) {
                    $this->log->debug(sprintf('Trying to delete update file "%s" after failed update', $updateFile));
                    if (unlink($updateFile)) {
                        $this->log->info(sprintf('Update file "%s" deleted after failed update', $updateFile));
                    } else {
                        $this->log->error(sprintf(
                            'Could not delete update file "%s" after failed update!',
                            $updateFile
                        ));
                    }
                }

                return false;
            }
        }

        $this->runOnAllUpdateFinishCallbacks($this->getVersionsToUpdate());

        return true;
    }

    /**
     * Add callback which is executed after each update finished.
     *
     * @param callable $callback
     * @return $this
     */
    public function onEachUpdateFinish(callable $callback): self
    {
        $this->onEachUpdateFinishCallbacks[] = $callback;

        return $this;
    }

    /**
     * Add callback which is executed after all updates finished.
     *
     * @param callable $callback
     * @return $this
     */
    public function setOnAllUpdateFinishCallbacks(callable $callback): self
    {
        $this->onAllUpdateFinishCallbacks[] = $callback;

        return $this;
    }

    /**
     * Run callbacks after each update finished.
     *
     * @param string $updateVersion
     * @param bool $simulate
     * @return void
     */
    private function runOnEachUpdateFinishCallbacks(string $updateVersion, bool $simulate): void
    {
        foreach ($this->onEachUpdateFinishCallbacks as $callback) {
            $callback($updateVersion, $simulate);
        }
    }

    /**
     * Run callbacks after all updates finished.
     *
     * @param array $updatedVersions
     * @return void
     */
    private function runOnAllUpdateFinishCallbacks(array $updatedVersions): void
    {
        foreach ($this->onAllUpdateFinishCallbacks as $callback) {
            $callback($updatedVersions);
        }
    }
}
