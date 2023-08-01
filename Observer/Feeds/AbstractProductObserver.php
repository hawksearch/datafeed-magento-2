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

namespace HawkSearch\Datafeed\Observer\Feeds;

use HawkSearch\Datafeed\Model\Config\Attributes as ConfigAttributes;
use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Config\Source\ProductAttributes;
use HawkSearch\Datafeed\Model\CsvWriter;
use HawkSearch\Datafeed\Model\Datafeed;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Review\Model\ResourceModel\Review\SummaryFactory as ReviewSummaryFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\Store;

abstract class AbstractProductObserver implements ObserverInterface
{
    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * @var EavAttribute[]
     */
    private $attributes;

    /**
     * @var int
     */
    protected $counter = 0;

    /**
     * @var bool
     */
    protected $isMultiRowFeed = false;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ConfigAttributes
     */
    private $attributesConfigProvider;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ReviewSummaryFactory
     */
    private $reviewSummaryFactory;

    /**
     * @var Stock
     */
    private $stockHelper;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var ConfigFeed
     */
    private $feedConfigProvider;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * AbstractProductObserver constructor.
     * @param Json $jsonSerializer
     * @param ConfigAttributes $attributesConfigProvider
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ReviewSummaryFactory $reviewSummaryFactory
     * @param Stock $stockHelper
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param ConfigFeed $feedConfigProvider
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
        ProductMetadataInterface $productMetadata
    ){
        $this->jsonSerializer = $jsonSerializer;
        $this->attributesConfigProvider = $attributesConfigProvider;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->reviewSummaryFactory = $reviewSummaryFactory;
        $this->stockHelper = $stockHelper;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->feedConfigProvider = $feedConfigProvider;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Datafeed $feedExecutor */
        $feedExecutor = $observer->getData('model');

        /** @var Store $store */
        $store = $observer->getData('store');

        $this->counter = 0;

        $feedExecutor->log('START ---- ' . $this->getFileName() . ' ----');

        try {
            //init output
            $output = $feedExecutor->initOutput($this->getFileName(), $store->getCode());

            //adding column names
            $feedExecutor->log('- Adding column names');

            $output->appendRow($this->getFeedColumns());

            //adding product data
            $feedExecutor->log('- Adding product data');

            //prepare map
            $feedExecutor->log('- Prepare attributes map');
            $map = $this->attributesConfigProvider->getMapping(
                $store,
                $this->getFilteredFeedFields(),
                $this->getExcludedFeedFields()
            );

            //prepare product collection
            $feedExecutor->log('- Prepare product collection');
            //reset product collection because of the shared observer object for multiple stores
            $this->productCollection = null;
            $collection = $this->getProductCollection($store);
            $feedExecutor->log($collection->getSelect()->__toString());

            $currentPage = 1;
            do {
                $feedExecutor->log(sprintf('- Starting product page %d', $currentPage));
                $start = time();

                $collection->clear();
                $collection->setCurPage($currentPage);
                $feedExecutor->log(sprintf('Items count: %d', count($collection->getItems())));

                /** @var Product $product */
                foreach ($collection->getItems() as $product) {
                    $product->setStoreId($store->getId());
                    $row = [];
                    foreach ($map as $field => $magentoAttribute) {
                        $value = $this->getProductAttributeValue($product, $field, $magentoAttribute);
                        $row[] = $value;

                        $this->handleProductAttributeValues(
                            $product,
                            $output,
                            $field,
                            $value
                        );
                    }

                    $additionalData = $this->getAdditionalProductData($product);
                    if ($additionalData) {
                        foreach ($additionalData as $additionalField => $additionalValue) {
                            $product->setData($additionalField, $additionalValue);
                            $value = $this->getProductAttributeValue($product, $additionalField, $additionalField);
                            $row[] = $value;

                            $this->handleProductAttributeValues(
                                $product,
                                $output,
                                $additionalField,
                                $value
                            );
                        }
                    }
                    if (!$this->isMultiRowFeed) {
                        $output->appendRow($row);
                        $this->counter++;
                    }
                }

                $feedExecutor->log(
                    sprintf('- It took %d seconds to export page %d', time() - $start, $currentPage)
                );

                $currentPage++;
            } while ($currentPage <= $collection->getLastPageNumber());

            $output->closeOutput();
        } catch (FileSystemException | LocalizedException $e) {
            $feedExecutor->log('- ERROR');
            $feedExecutor->log($e->getMessage());
        } finally {
            $feedExecutor->setTimeStampData(
                [$this->getFileName() . '.' . $this->feedConfigProvider->getOutputFileExtension(), $this->counter]
            );
        }

        $feedExecutor->log('END ---- ' . $this->getFileName() . ' ----');
    }

    /**
     * Get list of field custom handlers
     * @return array
     */
    abstract protected function getCustomHandlers(): array;

    /**
     * @return string
     */
    abstract protected function getFileName();

    /**
     * @return string[]
     */
    abstract protected function getFeedColumns();

    /**
     * @return string[]
     */
    abstract protected function getExcludedFeedFields();

    /**
     * @return string[]
     */
    abstract protected function getFilteredFeedFields();

    /**
     * @param string $field
     * @return string|null
     */
    protected function getFieldCustomHandler(string $field)
    {
        $handler = null;
        $handlers = $this->getCustomHandlers();
        if (isset($handlers[$field])
            && method_exists($this, $handlers[$field])) {
            $handler = $handlers[$field];
        }

        return $handler;
    }

    /**
     * @param int|string|Store|null $store
     * @return ProductCollection
     */
    protected function getProductCollection($store)
    {
        if ($this->productCollection === null) {
            /** @var ProductCollection $productCollection */
            $this->productCollection = $this->productCollectionFactory->create();
            $this->productCollection->addAttributeToSelect('*');
            $this->productCollection->addPriceData();
            $this->productCollection->addStoreFilter($store);
            $this->productCollection->setPageSize($this->feedConfigProvider->getBatchLimit());
            $this->appendReviewSummaryToCollection($this->productCollection);
            $this->stockHelper->addIsInStockFilterToCollection($this->productCollection);
        }

        return $this->productCollection;
    }

    /**
     * @param ProductCollection $productCollection
     * @return $this
     */
    private function appendReviewSummaryToCollection(ProductCollection $productCollection)
    {
        $storeId = $productCollection->getStoreId();
        if (version_compare($this->productMetadata->getVersion(), '2.4.0', '<')) {
            $storeId = (string)$storeId;
        }

        $this->reviewSummaryFactory->create()->appendSummaryFieldsToCollection(
            $productCollection,
            $storeId,
            Review::ENTITY_PRODUCT_CODE
        );

        return $this;
    }

    /**
     * @param Product $product
     * @param CsvWriter $output
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    protected function handleProductAttributeValues(
        Product $product,
        CsvWriter $output,
        string $field,
        $value
    ) {
        return $this;
    }

    protected function getProductAttributeValue(
        Product $product,
        string $field,
        string $attribute
    ) {
        $value = null;
        switch ($attribute) {
            case ProductAttributes::SEPARATE_METHOD:
                if ($customHandler = $this->getFieldCustomHandler($field)) {
                    $value = $this->{$customHandler}($product);
                }
                break;
            case '':
                break;
            default:
                $value = $product->getData($attribute);
                $eavAttribute = $this->getAttributeByCode($attribute);

                if ($value !== null && $eavAttribute) {
                    if (!is_array($value) && $eavAttribute->usesSource()) {
                        $value = $product->getAttributeText($attribute);
                        if (!is_scalar($value) && !is_array($value)) {
                            $value = (string)$value;
                        }

                        if ($this->isMultiRowFeed) {
                            $value = (array)$value;
                        }
                    }

                    if ($value === false) {
                        $value = $eavAttribute->getFrontend()->getValue($product);
                    }
                }
        }

        return $value;
    }

    /**
     * @param $attributeCode
     * @return EavAttribute|null
     */
    protected function getAttributeByCode($attributeCode)
    {
        return $this->getAttributeCollection()[$attributeCode] ?? null;
    }

    /**
     * @return EavAttribute[]
     */
    protected function getAttributeCollection()
    {
        if ($this->attributes === null) {
            /** @var AttributeCollection $attributeCollection */
            $attributeCollection = $this->attributeCollectionFactory->create();
            /** @var EavAttribute $attribute */
            foreach ($attributeCollection as $attribute) {
                $this->attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        return $this->attributes;
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    protected function getAdditionalProductData(ProductInterface $product)
    {
        return [];
    }
}
