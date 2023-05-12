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

use HawkSearch\Connector\Helper\Url as UrlHelper;
use HawkSearch\Datafeed\Model\Config\Attributes as ConfigAttributes;
use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Product\AttributeFeedService;
use HawkSearch\Datafeed\Model\Product as ProductDataProvider;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\CatalogInventory\Helper\Stock;
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
     * @var Configurable
     */
    private $configurableType;

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
     * @param Configurable $configurableType
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
        ReviewSummaryFactory $reviewSummaryFactory,
        Stock $stockHelper,
        AttributeCollectionFactory $attributeCollectionFactory,
        ConfigFeed $feedConfigProvider,
        Configurable $configurableType,
        AttributeFeedService $attributeFeedService,
        ImageHelper $imageHelper,
        UrlHelper $urlHelper,
        ProductDataProvider $productDataProvider,
        ProductMetadataInterface $productMetadata
    ) {
        parent::__construct(
            $jsonSerializer,
            $attributesConfigProvider,
            $productCollectionFactory,
            $reviewSummaryFactory,
            $stockHelper,
            $attributeCollectionFactory,
            $feedConfigProvider,
            $productMetadata
        );

        $this->configurableType = $configurableType;
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
        $store = $product->getStore();
        return substr((string) $product->getProductUrl(1), strlen((string) $store->getBaseUrl()));
    }

    /**
     * @param Product $product
     * @return int|string
     */
    protected function getGroupId(Product $product)
    {
        $ids = $this->productDataProvider->getParentProductIds([$product->getId()]);

        if (!$ids) {
            $ids = [$product->getId()];
        }

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
}
