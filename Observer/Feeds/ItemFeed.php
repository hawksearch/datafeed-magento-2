<?php

/**
 * Copyright (c) 2024 Hawksearch (www.hawksearch.com) - All Rights Reserved
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

namespace HawkSearch\Datafeed\Observer\Feeds;

use Exception;
use HawkSearch\Connector\Helper\Url as UrlHelper;
use HawkSearch\Datafeed\Model\Config\Attributes as ConfigAttributes;
use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Product\AttributeFeedService;
use HawkSearch\Datafeed\Model\Product as ProductDataProvider;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Review\Model\ResourceModel\Review\SummaryFactory as ReviewSummaryFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\ProductMetadataInterface;

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
        'image' => 'getImage'
    ];
    /**#@-*/

    /**
     * @var string
     */
    private $filename = 'items';

    /**
     * @var AttributeFeedService
     */
    private $attributeFeedService;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var ProductDataProvider
     */
    private $productDataProvider;

    /**
     * @var ConfigFeed
     */
    private $feedConfigProvider;

    /**
     * ItemFeed constructor.
     * @param Json $jsonSerializer
     * @param ConfigAttributes $attributesConfigProvider
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ReviewSummaryFactory $reviewSummaryFactory
     * @param Stock $stockHelper
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param ConfigFeed $feedConfigProvider
     * @param AttributeFeedService $attributeFeedService
     * @param ImageHelper $imageHelper
     * @param UrlHelper $urlHelper
     * @param ProductDataProvider $productDataProvider
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        Json $jsonSerializer,
        ConfigAttributes $attributesConfigProvider,
        ProductCollectionFactory $productCollectionFactory,
        Stock $stockHelper,
        AttributeCollectionFactory $attributeCollectionFactory,
        ConfigFeed $feedConfigProvider,
        AttributeFeedService $attributeFeedService,
        ImageHelper $imageHelper,
        UrlHelper $urlHelper,
        ProductMetadataInterface $productMetadata,
        ProductDataProvider $productDataProvider
    ) {
        parent::__construct(
            $jsonSerializer,
            $attributesConfigProvider,
            $productCollectionFactory,
            $stockHelper,
            $attributeCollectionFactory,
            $feedConfigProvider
        );

        $this->attributeFeedService = $attributeFeedService;
        $this->imageHelper = $imageHelper;
        $this->urlHelper = $urlHelper;
        $this->productDataProvider = $productDataProvider;
        $this->feedConfigProvider = $feedConfigProvider;
    }

    /**
     * @param Product $product
     * @return false|string
     */
    protected function getUrl(Product $product)
    {
        /**
         * To avoid loading data from url_rewrite table twice set request_path attribute to false
         */
        if (!$product->hasData('request_path')) {
            $product->setData('request_path', false);
        }

        return substr((string) $product->getProductUrl(1), strlen((string) $product->getStore()->getBaseUrl()));
    }

    /**
     * @param Product $product
     * @return int|string
     */
    protected function getGroupId(Product $product)
    {
        $ids = $product->hasParentIds() ? $product->getParentIds() : [$product->getId()];
        return implode(",", $ids);
    }

    /**
     * @param Product $product
     * @return int
     */
    protected function isOnSale(Product $product)
    {
        return $product->getSpecialPrice() ? 1 : 0;
    }

    /**
     * @param Product $product
     * @return int|mixed
     */
    protected function getStockStatus(Product $product)
    {
        return $product->getData('is_salable');
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getImage(Product $product)
    {
        $imageUrl = $this->imageHelper->init($product, 'product_small_image')->getUrl();
        $uri = $this->urlHelper->getUriInstance($imageUrl);

        $store = $product->getStore();
        if ($this->feedConfigProvider->isRemovePubInAssetsUrl($store)) {
            /** @link  https://github.com/magento/magento2/issues/9111 */
            $uri = $this->urlHelper->removeFromUriPath($uri, ['pub']);
        }

        return (string)$uri->withScheme('');
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

    /**
     * Add parent ids data to loaded items
     *
     * @param ProductCollection $collection
     * @return void
     * @throws Exception
     */
    private function addParentIdsToCollection(ProductCollection $collection)
    {
        $parentsMap = $this->productDataProvider->getParentsByChildMap(array_keys($collection->getItems()));
        /** @var Product $item */
        foreach ($collection->getItems() as $item) {
            if (isset($parentsMap[$item->getId()])) {
                $item->setData('parent_ids', $parentsMap[$item->getId()]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function prepareCollection(ProductCollection $collection): AbstractProductObserver
    {
        $collection->addUrlRewrite();
        return $this;
    }

    /**
     * @param ProductCollection $collection
     * @return ItemFeed
     * @throws LocalizedException
     * @throws Exception
     */
    protected function afterLoadProductCollection(ProductCollection $collection)
    {
        $this->addParentIdsToCollection($collection);
        return parent::afterLoadProductCollection($collection);
    }
}
