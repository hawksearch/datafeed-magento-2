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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
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
    const MAPPING = 'hawksearch_datafeed/attributes/mapping';
    const INCLUDE_OUT_OF_STOCK_ITEMS = 'hawksearch_datafeed/feed/stockstatus';
    const INCLUDE_DISABLED_ITEMS = 'hawksearch_datafeed/feed/itemstatus';
    const FEED_PATH = 'hawksearch_datafeed/feed/feed_path';
    const BATCH_LIMIT = 'hawksearch_datafeed/feed/batch_limit';
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
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
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
     * @return bool | null
     */
    public function includeOutOfStockItems($store = null) : ?bool
    {
        return (bool)$this->getConfig(self::INCLUDE_OUT_OF_STOCK_ITEMS, $store);
    }

    /**
     * @param null $store
     * @return bool | null
     */
    public function includeDisabledItems($store = null) : ?bool
    {
        return (bool)$this->getConfig(self::INCLUDE_DISABLED_ITEMS, $store);
    }

    /**
     * @param null $store
     * @return string | null
     */
    public function getFeedPath($store = null) : ?string
    {
        return $this->getConfig(self::FEED_PATH, $store);
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
