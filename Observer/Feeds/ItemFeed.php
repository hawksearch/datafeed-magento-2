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

namespace HawkSearch\Datafeed\Observer\Feeds;

use HawkSearch\Datafeed\Model\Config\Attributes as ConfigAttributes;
use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Product\AttributeFeedService;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Model\Store;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Review\Model\ResourceModel\Review\SummaryFactory as ReviewSummaryFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

class ItemFeed extends AbstractProductObserver
{
    /**#@+
     * Constants
     */
    const ADDITIONAL_ATTRIBUTES_HANDLERS = [
        'url_detail' => 'getUrl',
        'group_id' => 'getGroupId',
        'is_on_sale' => 'isOnSale',
        'metric_inventory' => 'getStockStatus',
    ];
    /**#@-*/

    /**
     * @var string
     */
    private $filename = 'items';

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var AttributeFeedService
     */
    private $attributeFeedService;

    /**
     * ItemFeed constructor.
     * @param Json $jsonSerializer
     * @param ConfigAttributes $attributesConfigProvider
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ReviewSummaryFactory $reviewSummaryFactory
     * @param Stock $stockHelper
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param ConfigFeed $feedConfigProvider
     * @param Configurable $configurableType
     * @param AttributeFeedService $attributeFeedService
     */
    public function __construct(
        Json $jsonSerializer,
        ConfigAttributes $attributesConfigProvider,
        ProductCollectionFactory $productCollectionFactory,
        ReviewSummaryFactory $reviewSummaryFactory,
        Stock $stockHelper,
        AttributeCollectionFactory $attributeCollectionFactory,
        ConfigFeed $feedConfigProvider,
        Configurable $configurableType,
        AttributeFeedService $attributeFeedService
    ) {
        parent::__construct(
            $jsonSerializer,
            $attributesConfigProvider,
            $productCollectionFactory,
            $reviewSummaryFactory,
            $stockHelper,
            $attributeCollectionFactory,
            $feedConfigProvider
        );

        $this->configurableType = $configurableType;
        $this->attributeFeedService = $attributeFeedService;
    }

    /**
     * @param Product $product
     * @param Store $store
     * @return false|string
     */
    private function getUrl(Product $product, Store $store)
    {
        return substr($product->getProductUrl(1), strlen($store->getBaseUrl()));
    }

    /**
     * @param Product $product
     * @param Store $store
     * @return int|string
     */
    private function getGroupId(Product $product, Store $store)
    {
        if ($product->getTypeId() === Type::TYPE_SIMPLE
            && $ids = implode(",", $this->configurableType->getParentIdsByChild($product->getId()))) {
            return $ids;
        }
        return $product->getId();
    }

    /**
     * @param Product $product
     * @param Store $store
     * @return int
     */
    private function isOnSale(Product $product, Store $store)
    {
        return $product->getSpecialPrice() ? 1 : 0;
    }

    /**
     * @param Product $product
     * @param Store $store
     * @return int|mixed
     */
    private function getStockStatus(Product $product, Store $store)
    {
        return $product->getData('is_salable');
    }

    /**
     * @inheritdoc
     */
    protected function getCustomHandlers(): array
    {
        return self::ADDITIONAL_ATTRIBUTES_HANDLERS;
    }

    /**
     * @inheritdoc
     */
    protected function getFileName()
    {
        return $this->filename;
    }

    /**
     * @inheritdoc
     */
    protected function getFeedColumns()
    {
        return $this->attributeFeedService->getPreconfiguredItemsColumns();
    }

    /**
     * @inheritdoc
     */
    protected function getExcludedFeedFields()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getFilteredFeedFields()
    {
        return $this->attributeFeedService->getPreconfiguredItemsColumns();
    }
}
