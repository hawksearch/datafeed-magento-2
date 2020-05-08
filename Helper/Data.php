<?php
/**
 * Copyright (c) 2018 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace HawkSearch\Datafeed\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Shell;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as ioFile;
use Composer\Util\Filesystem as UtilFileSystem;
use Magento\Framework\HTTP\ZendClient;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * @package HawkSearch\Datafeed\Helper
 */
class Data extends AbstractHelper
{
    /**
     *
     */
    const DEFAULT_FEED_PATH = 'hawksearch/feeds';
    /**
     *
     */
    const IMAGECACHE_LOCK_FILENAME = 'hawksearchImageCache.lock';
    /**
     *
     */
    const CONFIG_LOCK_FILENAME = 'hawksearchFeedLock.lock';
    /**
     *
     */
    const CONFIG_SUMMARY_FILENAME = 'hawksearchFeedSummary.json';
    /**
     *
     */
    const CONFIG_FEED_PATH = 'hawksearch_datafeed/feed/feed_path';
    /**
     *
     */
    const CONFIG_MODULE_ENABLED = 'hawksearch_datafeed/general/enabled';
    /**
     *
     */
    const CONFIG_LOGGING_ENABLED = 'hawksearch_datafeed/general/logging_enabled';
    /**
     *
     */
    const CONFIG_INCLUDE_OOS = 'hawksearch_datafeed/feed/stockstatus';
    /**
     *
     */
    const CONFIG_BATCH_LIMIT = 'hawksearch_datafeed/feed/batch_limit';
    /**
     *
     */
    const CONFIG_IMAGE_WIDTH = 'hawksearch_datafeed/imagecache/image_width';
    /**
     *
     */
    const CONFIG_IMAGE_HEIGHT = 'hawksearch_datafeed/imagecache/image_height';
    /**
     *
     */
    const CONFIG_INCLUDE_DISABLED = 'hawksearch_datafeed/feed/itemstatus';
    /**
     *
     */
    const CONFIG_BUFFER_SIZE = '65536';
    /**
     *
     */
    const CONFIG_OUTPUT_EXTENSION = 'txt';
    /**
     *
     */
    const CONFIG_FIELD_DELIMITER = 'tab';
    /**
     *
     */
    const CONFIG_SELECTED_STORES = 'hawksearch_datafeed/feed/stores';
    /**
     *
     */
    const CONFIG_CRONLOG_FILENAME = 'hawksearchCronLog.log';
    /**
     *
     */
    const CONFIG_CRON_ENABLE = 'hawksearch_datafeed/feed/cron_enable';
    /**
     *
     */
    const CONFIG_CRON_EMAIL = 'hawksearch_datafeed/feed/cron_email';
    /**
     *
     */
    const CONFIG_CRON_IMAGECACHE_ENABLE = 'hawksearch_datafeed/imagecache/cron_enable';
    /**
     *
     */
    const CONFIG_CRON_IMAGECACHE_EMAIL = 'hawksearch_datafeed/imagecache/cron_email';
    /**
     *
     */
    const CONFIG_TRIGGER_REINDEX = 'hawksearch_datafeed/feed/reindex';
    /**
     *
     */
    const CONFIG_IMAGECACHE_LOCK_PATH = 'hawksearch_datafeed/feed/image_cache_lock_path';
    /**
     *
     */
    const CONFIG_COMBINE_MULTISELECT_ATTS = 'hawksearch_datafeed/feed/combine_multiselect';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ZendClient
     */
    private $zendClient;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var
     */
    private $selectedStores;
    /**
     * @var UtilFileSystem
     */
    private $utilFileSystem;
    /**
     * @var CollectionFactory
     */
    private $storeCollectionFactory;
    /**
     * @var ioFile
     */
    private $fileDirectory;
    /**
     * @var File
     */
    private $file;
    /**
     * @var Shell
     */
    private $shell;
    /**
     * Data constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Filesystem            $filesystem
     * @param ZendClient            $zendClient
     * @param CollectionFactory     $storeCollectionFactory
     * @param Context               $context
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        ZendClient $zendClient,
        CollectionFactory $storeCollectionFactory,
        Context $context,
        ioFile $fileDirectory,
        File $file,
        Shell $shell,
        UtilFileSystem $utilFileSystem
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->zendClient = $zendClient;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->fileDirectory = $fileDirectory;
        $this->file = $file;
        $this->shell = $shell;
        $this->utilFileSystem = $utilFileSystem;
    }

    /**
     * @param  $data
     * @param  null $store
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfigurationData($data, $store = null)
    {
        $storeScope = ScopeInterface::SCOPE_STORE;
        if (empty($store)) {
            $store = $this->storeManager->getStore();
        }
        return $this->scopeConfig->getValue($data, $storeScope, $store);
    }

    /**
     * @param  $store
     * @return bool
     */
    public function getTriggerReindex($store)
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_TRIGGER_REINDEX,
            ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function moduleIsEnabled()
    {
        return $this->getConfigurationData(self::CONFIG_MODULE_ENABLED);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loggingIsEnabled()
    {
        return $this->getConfigurationData(self::CONFIG_LOGGING_ENABLED);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function includeOutOfStockItems()
    {
        return $this->getConfigurationData(self::CONFIG_INCLUDE_OOS);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function includeDisabledItems()
    {
        return $this->getConfigurationData(self::CONFIG_INCLUDE_DISABLED);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBatchLimit()
    {
        return $this->getConfigurationData(self::CONFIG_BATCH_LIMIT);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGenEmail()
    {
        return $this->getConfigurationData('trans_email/ident_general/email');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGenName()
    {
        return $this->getConfigurationData('trans_email/ident_general/name');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageWidth()
    {
        return $this->getConfigurationData(self::CONFIG_IMAGE_WIDTH);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageHeight()
    {
        return $this->getConfigurationData(self::CONFIG_IMAGE_HEIGHT);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCronEmail()
    {
        return $this->getConfigurationData(self::CONFIG_CRON_EMAIL);
    }

    /**
     * @return string
     */
    public function getFieldDelimiter()
    {
        if (strtolower(self::CONFIG_FIELD_DELIMITER) == 'tab') {
            return "\t";
        } else {
            return ",";
        }
    }

    /**
     * @return int|string|null
     */
    public function getBufferSize()
    {
        $size = self::CONFIG_BUFFER_SIZE;
        return is_numeric($size) ? $size : null;
    }

    /**
     * @return string
     */
    public function getOutputFileExtension()
    {
        return self::CONFIG_OUTPUT_EXTENSION;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFeedFilePath()
    {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_FEED_PATH);
        if (!$relPath) {
            $relPath = self::DEFAULT_FEED_PATH;
        }
        /**
 * @var \Magento\Framework\Filesystem\Directory\Write $writer 
*/
        $mediaWriter = $this->filesystem->getDirectoryWrite('media');
        $mediaWriter->create($relPath);

        return $mediaWriter->getAbsolutePath($relPath);
    }

    /**
     * @return string
     */
    public function getLockFilename()
    {
        return self::CONFIG_LOCK_FILENAME;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getSummaryFilename()
    {
        return $this->getFeedFilePath() . DIRECTORY_SEPARATOR . self::CONFIG_SUMMARY_FILENAME;
    }

    /**
     * @return \Magento\Store\Model\ResourceModel\Store\Collection
     */
    public function getSelectedStores()
    {
        if (!isset($this->selectedStores)) {
            $this->selectedStores = $this->storeCollectionFactory->create();
            $ids = explode(',', $this->scopeConfig->getValue(self::CONFIG_SELECTED_STORES));
            $this->selectedStores->addIdFilter($ids);
        }
        return $this->selectedStores;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCronEnabled()
    {
        return $this->getConfigurationData(self::CONFIG_CRON_ENABLE);
    }

    /**
     * @return string
     */
    public function getCronLogFilename()
    {
        return self::CONFIG_CRONLOG_FILENAME;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCronImagecacheEnable()
    {
        return $this->getConfigurationData(self::CONFIG_CRON_IMAGECACHE_ENABLE);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCronImagecacheEmail()
    {
        return $this->getConfigurationData(self::CONFIG_CRON_IMAGECACHE_EMAIL);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function isFeedLocked()
    {
        $lockfile = implode(DIRECTORY_SEPARATOR, [$this->getFeedFilePath(), $this->getLockFilename()]);
        if ($this->fileDirectory->fileExists($lockfile)) {
            $this->log('FEED IS LOCKED!');
            return true;
        }
        return false;
    }

    /**
     * @param  string $scriptName
     * @return int
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function createFeedLocks($scriptName = '')
    {
        $lockfilename = implode(
            DIRECTORY_SEPARATOR,
            [$this->getFeedFilePath(),
                $this->getLockFilename()]
        );
        return $this->file->filePutContents(
            $lockfilename,
            json_encode(
                ['date' => date('Y-m-d H:i:s'),
                'script' => $scriptName
                ]
            )
        );
    }

    /**
     * @param  string $scriptName
     * @param  bool   $kill
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function removeFeedLocks($scriptName = '', $kill = false)
    {
        $lockfilename = implode(DIRECTORY_SEPARATOR, [$this->getFeedFilePath(), $this->getLockFilename()]);

        if ($this->fileDirectory->fileExists($lockfilename)) {
            $data = json_decode($this->file->fileGetContents($lockfilename));
            if (!empty($data) && isset($data->script) && $kill) {
                $procs = shell_exec(sprintf('pgrep -af %s', preg_replace('/^(.)/', '[${1}]', $data->script)));
                if ($procs) {
                    $procs = explode("\n", $procs);
                    foreach ($procs as $proc) {
                        $pid = explode(' ', $proc, 2)[0];
                        if (is_numeric($pid)) {
                            exec(sprintf('kill %s', $pid));
                        }
                    }
                }
            }
            return $this->utilFileSystem->unlink($lockfilename);
        }
        return false;
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function runDatafeed()
    {
        $tmppath = $this->filesystem->getDirectoryWrite('tmp')->getAbsolutePath();
        $tmpfile = tempnam($tmppath, 'hawkfeed_');

        $parts = explode(DIRECTORY_SEPARATOR, __FILE__);

        array_pop($parts);
        $parts[] = 'Runfeed.php';
        $runfile = implode(DIRECTORY_SEPARATOR, $parts);
        $root = BP;

        $f = $this->file->fileOpen($tmpfile, 'w');

        $phpbin = PHP_BINDIR . DIRECTORY_SEPARATOR . "php";

        $this->file->fileWrite($f, "$phpbin -d memory_limit=-1 $runfile -r $root -t $tmpfile\n");
        $this->file->fileClose($f);

        $cronlog = implode(DIRECTORY_SEPARATOR, [$this->getFeedFilePath(), $this->getCronLogFilename()]);

        shell_exec("/bin/sh $tmpfile > $cronlog 2>&1 &");
    }

    /**
     * @param  $summary
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function writeSummary($summary)
    {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_FEED_PATH);
        if (!$relPath) {
            $relPath = self::DEFAULT_FEED_PATH;
        }

        $summaryFile = implode(DIRECTORY_SEPARATOR, [$relPath, self::CONFIG_SUMMARY_FILENAME]);
        $writer = $this->filesystem->getDirectoryWrite('media');
        $writer->writeFile($summaryFile, json_encode($summary, JSON_PRETTY_PRINT));
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function refreshImageCache()
    {
        $tmppath = $this->filesystem->getDirectoryWrite('tmp')->getAbsolutePath();
        $tmpfile = tempnam($tmppath, 'hawkimage_');

        $parts = explode(DIRECTORY_SEPARATOR, __FILE__);
        array_pop($parts);
        $parts[] = 'Runfeed.php';
        $runfile = implode(DIRECTORY_SEPARATOR, $parts);
        $root = BP;

        $f = $this->file->fileOpen($tmpfile, 'w');

        $phpbin = PHP_BINDIR . DIRECTORY_SEPARATOR . "php";

        $this->file->fileWrite($f, "$phpbin -d memory_limit=-1 $runfile -i true -r $root -t $tmpfile\n");
        $this->file->fileClose($f);

        $cronlog = implode(DIRECTORY_SEPARATOR, [$this->getFeedFilePath(), $this->getCronLogFilename()]);
        shell_exec("/bin/sh $tmpfile > $cronlog 2>&1 &");
    }

    /**
     * @param  $basename
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getPathForFile($basename)
    {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_FEED_PATH);
        if (!$relPath) {
            $relPath = self::DEFAULT_FEED_PATH;
        }

        $dir = implode(DIRECTORY_SEPARATOR, [$relPath, $this->storeManager->getStore()->getCode()]);

        $mediaWriter = $this->filesystem->getDirectoryWrite('media');
        $mediaWriter->create($dir);

        return sprintf(
            '%s%s%s.%s',
            $mediaWriter->getAbsolutePath($dir),
            DIRECTORY_SEPARATOR,
            $basename,
            $this->getOutputFileExtension()
        );
    }

    /**
     * @param  \Magento\Store\Model\Store $store
     * @return int
     * @throws \Zend_Http_Client_Exception
     */
    public function triggerReindex(\Magento\Store\Model\Store $store)
    {
        $this->log('triggerReindex called');
        if (!$this->scopeConfig->isSetFlag(
            'hawksearch_datafeed/feed/reindex',
            ScopeInterface::SCOPE_STORE,
            $store
        )
        ) {
            $this->log('HawkSearch reindex disabled, not triggering reindex');
            return false;
        }
        $apiUrl = $this->getTriggerReindexUrl($store);
        $this->log(sprintf('using reindex url "%s"', $apiUrl));

        $this->zendClient->resetParameters(true);
        $this->zendClient->setUri($apiUrl);
        $this->log('setUri called on zendClient');
        $this->zendClient->setMethod(ZendClient::POST);
        $this->log('setMethod called on zendClient');

        $apiKey = $this->scopeConfig->getValue(
            'hawksearch_datafeed/hawksearch_api/api_key',
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $this->log(sprintf('setting hawk Api key to "%s"', $apiKey));

        $this->zendClient->setHeaders('X-HawkSearch-ApiKey', $apiKey);
        $this->zendClient->setHeaders('Accept', 'application/json');

        $this->log('making request...');
        $response = $this->zendClient->request();
        $this->log(
            sprintf(
                "request made, response is:\n%s\n\n%s",
                $response->getHeadersAsString(),
                $response->getBody()
            )
        );
        return isset($response) ? true : false;
    }

    /**
     * @param  \Magento\Store\Model\Store $store
     * @return string
     */
    private function getTriggerReindexUrl(\Magento\Store\Model\Store $store)
    {
        $mode = $this->scopeConfig->getValue(
            'hawksearch_datafeed/hawksearch_api/api_mode',
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $apiUrl = $this->scopeConfig->getValue(
            sprintf(
                'hawksearch_datafeed/hawksearch_api/api_url_%s',
                $mode
            ), ScopeInterface::SCOPE_STORE, $store
        );
        $apiUrl = rtrim($apiUrl, '/');

        $apiVersion = $this->scopeConfig->getValue(
            'hawksearch_datafeed/hawksearch_api/api_ver',
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return sprintf('%s/api/%s/index', $apiUrl, $apiVersion);
    }

    /**
     * @param  $message
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function log($message)
    {
        if ($this->loggingIsEnabled()) {
            $this->_logger->addDebug($message);
        }
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function isImageCacheLocked()
    {
        $lockfile = $this->getImageCacheLockFile();
        return $this->filesystem->getDirectoryWrite(
            DirectoryList::MEDIA
        )->isExist($lockfile);
    }

    /**
     * @return string
     */
    private function getImageCacheLockFile()
    {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_IMAGECACHE_LOCK_PATH);
        return implode(DIRECTORY_SEPARATOR, [$relPath, self::IMAGECACHE_LOCK_FILENAME]);
    }

    /**
     * @param  string $scriptName
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createImageCacheLocks($scriptName = '')
    {
        $lockFile = $this->getImageCacheLockFile();
        $content = json_encode(['date' => date('Y-m-d H:i:s'), 'script' => $scriptName]);
        try {
            $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            return $writer->writeFile($lockFile, $content);
        } catch (\Exception $exception) {
            $this->log('failed to create Image cache lock file: ' . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  $SCRIPT_NAME
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function removeImageCacheLocks($SCRIPT_NAME)
    {
        $lockFile = $this->getImageCacheLockFile();
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        try {
            return $writer->delete($lockFile);
        } catch (\Exception $exception) {
            $this->log('Failed to remove Image Cache lock file: ' . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCombineMultiselectAttributes()
    {
        return $this->getConfigurationData(self::CONFIG_COMBINE_MULTISELECT_ATTS);
    }
}
