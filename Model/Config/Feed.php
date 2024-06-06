<?php
/**
 * Copyright (c) 2023 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace HawkSearch\Datafeed\Model\Config;

use HawkSearch\Connector\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

class Feed extends ConfigProvider
{
    /**#@+
     * Configuration paths
     */
    const CONFIG_STORES = 'stores';
    const CONFIG_BATCH_LIMIT = 'batch_limit';
    const CONFIG_REINDEX = 'reindex';
    const CONFIG_CRON_ENABLE = 'cron_enable';
    const CONFIG_CRON_EMAIL = 'cron_email';
    const CONFIG_BUFFER_SIZE = 'buffer_size';
    const CONFIG_OUTPUT_FILE_EXT = 'output_file_ext';
    const CONFIG_FEED_PATH = 'feed_path';
    const CONFIG_SUMMARY_FILENAME = 'summary_filename';
    const CONFIG_REMOVE_PUB_IN_ASSETS_URL = 'remove_pub_in_assets_url';
    const CONFIG_FEED_LOCKER = 'feed_locker';
    /**#@-*/

    const CSV_DELIMITER = "\t";

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var array
     */
    private $filterStores = [];

    /**
     * Feed constructor.
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Filesystem $fileSystem
     * @param null $configRootPath
     * @param null $configGroup
     */
    public function __construct(
        StoreCollectionFactory $storeCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        Filesystem $fileSystem,
        $configRootPath = null,
        $configGroup = null
    ) {
        parent::__construct($scopeConfig, $configRootPath, $configGroup);
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Set stores filter
     *
     * @param array $stores
     * @return $this
     */
    public function setStoresToFilter(array $stores)
    {
        $this->filterStores = $stores;
        return $this;
    }

    /**
     * @param null|int|string $store
     * @return StoreCollection
     */
    public function getStores($store = null): StoreCollection
    {
        $storesCollection = $this->storeCollectionFactory->create();
        if (!empty($this->filterStores)) {
            $storesCollection->addFieldToFilter(
                'main_table.code',
                ['in' => $this->filterStores]
            );
        }

        return $storesCollection->addIdFilter(
            explode(
                ',',
                (string)$this->getConfig(self::CONFIG_STORES, $store) ?: ''
            )
        )->load();
    }

    /**
     * @param null|int|string $store
     * @return int
     */
    public function getBatchLimit($store = null): int
    {
        return (int)$this->getConfig(self::CONFIG_BATCH_LIMIT, $store);
    }

    /**
     * @param null|int|string $store
     * @return bool
     */
    public function isReindex($store = null): bool
    {
        return !!$this->getConfig(self::CONFIG_REINDEX, $store);
    }

    /**
     * @param null|int|string $store
     * @return bool
     */
    public function isCronEnabled($store = null): bool
    {
        return !!$this->getConfig(self::CONFIG_CRON_ENABLE, $store);
    }

    /**
     * @param null|int|string $store
     * @return string
     */
    public function getCronEmail($store = null): string
    {
        return(string)$this->getConfig(self::CONFIG_CRON_EMAIL, $store);
    }

    /**
     * @return string
     */
    public function getFieldDelimiter(): string
    {
        return self::CSV_DELIMITER;
    }

    /**
     * @param null|int|string $store
     * @return int
     */
    public function getBufferSize($store = null): int
    {
        return (int)$this->getConfig(self::CONFIG_BUFFER_SIZE, $store);
    }

    /**
     * @param null|int|string $store
     * @return string
     */
    public function getOutputFileExtension($store = null): string
    {
        return (string)$this->getConfig(self::CONFIG_OUTPUT_FILE_EXT, $store);
    }

    /**
     * @param null|int|string $store
     * @return string | null
     * @throws FileSystemException
     */
    public function getFeedPath($store = null): ?string
    {
        $mediaWriter = $this->fileSystem->getDirectoryWrite('media');
        $mediaWriter->create($this->getConfig(self::CONFIG_FEED_PATH, $store));

        return $mediaWriter->getAbsolutePath($this->getConfig(self::CONFIG_FEED_PATH, $store));
    }

    /**
     * @param null|int|string $store
     * @return string
     */
    public function getSummaryFilename($store = null): string
    {
        return (string)$this->getConfig(self::CONFIG_SUMMARY_FILENAME, $store);
    }

    /**
     * @param null|int|string $store
     * @return bool
     */
    public function isRemovePubInAssetsUrl($store = null)
    {
        return !!$this->getConfig(self::CONFIG_REMOVE_PUB_IN_ASSETS_URL, $store);
    }

    /**
     * @param null|int|string $store
     * @return string
     */
    public function getFeedLocker($store = null): string
    {
        return (string)$this->getConfig(self::CONFIG_FEED_LOCKER, $store);
    }
}
