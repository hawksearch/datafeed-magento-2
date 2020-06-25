<?php
/**
 *  Copyright (c) 2020 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *  FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *  IN THE SOFTWARE.
 */
declare(strict_types=1);

namespace HawkSearch\Datafeed\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ResourceModel\Store\Collection;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ConfigProvider
 * System config provider
 */
class ConfigProvider
{
    /**#@+
     * Configuration paths
     */
    const BATCH_LIMIT = 'hawksearch_datafeed/feed/batch_limit';
    const CONFIG_CRON_ENABLE = 'hawksearch_datafeed/feed/cron_enable';
    const CONFIG_CRON_EMAIL = 'hawksearch_datafeed/feed/cron_email';
    const CONFIG_SEND_FILES_TO_SFTP = 'hawksearch_datafeed/hawksearch_sftp/enabled';
    const CONFIG_SFTP_HOST = 'hawksearch_datafeed/hawksearch_sftp/host';
    const CONFIG_SFTP_USERNAME = 'hawksearch_datafeed/hawksearch_sftp/username';
    const CONFIG_SFTP_PASSWORD = 'hawksearch_datafeed/hawksearch_sftp/password';
    const CONFIG_SFTP_FOLDER = 'hawksearch_datafeed/hawksearch_sftp/folder';
    const CONFIG_SELECTED_STORES = 'hawksearch_datafeed/feed/stores';
    const CONFIG_LOGGING_ENABLED = 'hawksearch_datafeed/general/logging_enabled';
    const GEN_EMAIL = 'trans_email/ident_general/email';
    const GEN_NAME = 'trans_email/ident_general/name';
    const MAPPING = 'hawksearch_datafeed/attributes/mapping';
    const TRIGGER_REINDEX_FLAG = 'hawksearch_datafeed/feed/reindex';

    const DEFAULT_FEED_PATH = 'hawksearch/feeds';
    const CONFIG_SUMMARY_FILENAME = 'hawksearchFeedSummary.json';
    const CONFIG_BUFFER_SIZE = '65536';
    const CONFIG_OUTPUT_EXTENSION = 'txt';
    /**#@-*/

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Core store manager interface
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var CollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Current store instance
     *
     * @var StoreInterface
     */
    private $store = null;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $fileSystem
     * @param CollectionFactory $storeCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Filesystem $fileSystem,
        CollectionFactory $storeCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->fileSystem = $fileSystem;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getMapping($store = null) : ?string
    {
        return $this->getConfig(self::MAPPING, $store);
    }

    /**
     * @param null $store
     * @return int | null
     */
    public function getBatchLimit($store = null) : ?int
    {
        return (int)$this->getConfig(self::BATCH_LIMIT, $store);
    }

    /**
     * @param null $store
     * @return bool | null
     */
    public function triggerHawkReindex($store = null) : ?bool
    {
        return (bool)$this->getConfig(self::TRIGGER_REINDEX_FLAG, $store);
    }

    /**
     * @param null $store
     * @return int | null
     */
    public function getBufferSize($store = null) : ?int
    {
        return (int)$this->getConfig(self::CONFIG_BUFFER_SIZE, $store);
    }

    /**
     * @param null $store
     * @return bool | null
     */
    public function isCronEnabled($store = null) : ?bool
    {
        return (bool)$this->getConfig(self::CONFIG_CRON_ENABLE, $store);
    }

    /**
     * @param null $store
     * @return bool | null
     */
    public function isLoggingEnabled($store = null) : ?bool
    {
        return (bool)$this->getConfig(self::CONFIG_LOGGING_ENABLED, $store);
    }

    /**
     * @param null $store
     * @return bool | null
     */
    public function isSftpEnabled($store = null) : ?bool
    {
        return (bool)$this->getConfig(self::CONFIG_SEND_FILES_TO_SFTP, $store);
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getSftpHost($store = null) : ?string
    {
        return $this->getConfig(self::CONFIG_SFTP_HOST, $store);
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getSftpUser($store = null) : ?string
    {
        return $this->getConfig(self::CONFIG_SFTP_USERNAME, $store);
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getSftpPassword($store = null) : ?string
    {
        return $this->getConfig(self::CONFIG_SFTP_PASSWORD, $store);
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getSftpFolder($store = null) : ?string
    {
        return $this->getConfig(self::CONFIG_SFTP_FOLDER, $store);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getFeedFilePath() : string
    {
        $mediaWriter = $this->fileSystem->getDirectoryWrite('media');
        $mediaWriter->create(self::DEFAULT_FEED_PATH);

        return $mediaWriter->getAbsolutePath(self::DEFAULT_FEED_PATH);
    }

    /**
     * @param null $store
     * @return Collection
     */
    public function getSelectedStores($store = null) : Collection
    {
        return $this->storeCollectionFactory->create()->addIdFilter(
            explode(',', $this->getConfig(self::CONFIG_SELECTED_STORES, $store))
        )->load();
    }

    /**
     * @return string
     */
    public function getFieldDelimiter() : string
    {
        return "\t";
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getCronEmail($store = null) : ?string
    {
        return $this->getConfig(self::CONFIG_CRON_EMAIL, $store);
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getGenEmail($store = null) : ?string
    {
        return $this->getConfig(self::GEN_EMAIL, $store);
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getGenName($store = null) : ?string
    {
        return $this->getConfig(self::GEN_NAME, $store);
    }

    /**
     * Retrieve store object
     *
     * @param StoreInterface|int|null $store
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore($store = null) : StoreInterface
    {
        if ($store) {
            if ($store instanceof StoreInterface) {
                $this->store = $store;
            } elseif (is_int($store)) {
                $this->store = $this->storeManager->getStore($store);
            }
        } else {
            $this->store = $this->storeManager->getStore();
        }

        return $this->store;
    }

    /**
     * Get Store Config value for path
     *
     * @param string $path Path to config value. Absolute from root or Relative from initialized root
     * @param int|StoreInterface|null $store
     * @return mixed
     */
    private function getConfig($path, $store)
    {
        $value = null;

        if ($store === null) {
            $store = $this->store;
        }

        try {
            $value = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->getStore($store)
            );
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
        }

        return $value;
    }
}
