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
use HawkSearch\Datafeed\Model\CsvWriter;
use HawkSearch\Datafeed\Model\Product\AttributeFeedService;
use HawkSearch\Datafeed\Model\Product\PriceManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Review\Model\ResourceModel\Review\SummaryFactory as ReviewSummaryFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;


class AttributeFeed extends AbstractProductObserver
{
    /**#@+
     * Constants
     */
    const ADDITIONAL_ATTRIBUTES_HANDLERS = [
        'category_id' => 'getCategoryIds',
        'rating_summary' => 'getRatingSummary',
        'reviews_count' => 'getReviewsCount',
    ];
    /**#@-*/

    /**
     * @var bool
     */
    protected $isMultiRowFeed = true;

    /**
     * @var string
     */
    private $filename = 'attributes';

    /**
     * @var PriceManagementInterface
     */
    private $priceManagement;

    /**
     * @var AttributeFeedService
     */
    private $attributeFeedService;

    /**
     * AttributeFeed constructor.
     * @param Json $jsonSerializer
     * @param ConfigAttributes $attributesConfigProvider
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ReviewSummaryFactory $reviewSummaryFactory
     * @param Stock $stockHelper
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param ConfigFeed $feedConfigProvider
     * @param PriceManagementInterface $priceManagement
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
        PriceManagementInterface $priceManagement,
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

        $this->priceManagement = $priceManagement;
        $this->attributeFeedService = $attributeFeedService;
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getCategoryIds(Product $product)
    {
        $values = [];
        foreach ($product->getCategoryIds() as $id) {
            $values[] = $id;
        }
        return $values;
    }

    /**
     * @param Product $product
     * @return string|null
     */
    protected function getRatingSummary(Product $product)
    {
        $value = null;
        if ($product->getRatingSummary() && $product->getReviewsCount() > 0) {
            $value = $product->getRatingSummary();
        }

        return $value;
    }

    /**
     * @param Product $product
     * @return string|null
     */
    protected function getReviewsCount(Product $product)
    {
        $value = null;
        if ($product->getRatingSummary() && $product->getReviewsCount() > 0) {
            $value = $product->getReviewsCount();
        }
        return $value;
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
        return $this->attributeFeedService->getPreconfiguredAttributesColumns();
    }

    /**
     * @inheritdoc
     */
    protected function getExcludedFeedFields()
    {
        return $this->attributeFeedService->getPreconfiguredItemsColumns();
    }

    /**
     * @inheritdoc
     */
    protected function getFilteredFeedFields()
    {
        return [];
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    protected function getAdditionalProductData(ProductInterface $product)
    {
        $priceInfo = [];
        $this->priceManagement->collectPrices($product, $priceInfo);

        return $priceInfo;
    }

    /**
     * @param Product $product
     * @param CsvWriter $output
     * @param string $field
     * @param mixed $value
     * @return $this
     * @throws FileSystemException
     */
    protected function handleProductAttributeValues(
        Product $product,
        CsvWriter $output,
        string $field,
        $value
    ) {
        if ($value !== null) {
            $values = (array)$value;

            foreach ($values as $value) {
                $output->appendRow(
                    [
                        $product->getSku(),
                        $field,
                        $value
                    ]
                );
                $this->counter++;
            }
        }

        return $this;
    }
}
