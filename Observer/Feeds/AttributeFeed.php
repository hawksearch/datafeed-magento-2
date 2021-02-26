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

use HawkSearch\Datafeed\Block\Adminhtml\System\Config\FieldsMapping;
use HawkSearch\Datafeed\Model\Config\Attributes as ConfigAttributes;
use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Config\Source\ProductAttributes;
use HawkSearch\Datafeed\Model\CsvWriter;
use HawkSearch\Datafeed\Model\Datafeed;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Review\Model\Review;
use Magento\Store\Model\Store;
use Magento\Review\Model\ResourceModel\Review\SummaryFactory;

class AttributeFeed implements ObserverInterface
{
    /**#@+
     * Constants
     */
    const FEED_COLUMNS = [
        'unique_id',
        'key',
        'value'
    ];
    const ADDITIONAL_ATTRIBUTES_HANDLERS = [
        'category_id' => 'getCategoryIds',
        'rating_summary' => 'getRatingSummary',
        'reviews_count' => 'getReviewsCount',
    ];
    /**#@-*/

    /**
     * @var int
     */
    private $counter = 0;

    /**
     * @var string
     */
    private $filename = 'attributes';

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ConfigFeed
     */
    private $feedConfigProvider;

    /**
     * @var ConfigAttributes
     */
    private $attributesConfigProvider;

    /**
     * @var SummaryFactory
     */
    private $sumResourceFactory;

    /**
     * @var EavAttribute[]
     */
    private $attributes;

    /**
     * ItemFeed constructor.
     * @param ProductCollectionFactory $productCollectionFactory
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param ConfigFeed $feedConfigProvider
     * @param ConfigAttributes $attributesConfigProvider
     * @param Json $jsonSerializer
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        ConfigFeed $feedConfigProvider,
        ConfigAttributes $attributesConfigProvider,
        Json $jsonSerializer,
        SummaryFactory $sumResourceFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->feedConfigProvider = $feedConfigProvider;
        $this->attributesConfigProvider = $attributesConfigProvider;
        $this->jsonSerializer = $jsonSerializer;
        $this->sumResourceFactory = $sumResourceFactory;
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

        $feedExecutor->log('START ---- ' . $this->filename . ' ----');

        try {
            //prepare map
            $feedExecutor->log('- Prepare attributes map');
            $configurationMap = $this->jsonSerializer->unserialize($this->attributesConfigProvider->getMapping($store));

            $map = [];
            foreach ($configurationMap as $field) {
                if (!empty($field[FieldsMapping::HAWK_ATTRIBUTE_CODE])
                    && !in_array($field[FieldsMapping::HAWK_ATTRIBUTE_CODE], ItemFeed::FEED_ATTRIBUTES)
                    && !empty($field[FieldsMapping::MAGENTO_ATTRIBUTE])) {
                    $map[$field[FieldsMapping::HAWK_ATTRIBUTE_CODE]] = $field[FieldsMapping::MAGENTO_ATTRIBUTE];
                }
            }

            //prepare product collection
            $feedExecutor->log('- Prepare product collection');
            /** @var ProductCollection $collection */
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addPriceData();
            $collection->addStoreFilter($store);
            $collection->setPageSize($this->feedConfigProvider->getBatchLimit());
            $this->appendReviewSummaryToCollection($collection);

            //init output
            $output = $feedExecutor->initOutput($this->filename, $store->getCode());

            //adding column names
            $feedExecutor->log('- Adding column names');
            $output->appendRow(self::FEED_COLUMNS);

            //adding product data
            $feedExecutor->log('- Adding product data');

            $currentPage = 1;
            do {
                $feedExecutor->log(sprintf('- Starting product page %d', $currentPage));

                $collection->clear();
                $collection->setCurPage($currentPage);

                $start = time();
                /** @var Product $product */
                foreach ($collection->getItems() as $product) {
                    foreach ($map as $field => $magentoAttribute) {
                        $this->handleProductAttributeValues(
                            $product,
                            $output,
                            $field,
                            $magentoAttribute
                        );
                    }
                }

                $feedExecutor->log(
                    sprintf('- It took %d seconds to export page %d', time() - $start, $currentPage)
                );

                $currentPage++;
            } while ($currentPage <= $collection->getLastPageNumber());

            $output->closeOutput();
        } catch (FileSystemException $e) {
            $feedExecutor->log('- ERROR');
            $feedExecutor->log($e->getMessage());
        } catch (NoSuchEntityException $e) {
            $feedExecutor->log('- ERROR');
            $feedExecutor->log($e->getMessage());
        } catch (LocalizedException $e) {
            $feedExecutor->log('- ERROR');
            $feedExecutor->log($e->getMessage());
        } finally {
            $feedExecutor->setTimeStampData(
                [$this->filename . '.' . $this->feedConfigProvider->getOutputFileExtension(), $this->counter]
            );
        }

        $feedExecutor->log('END ---- ' . $this->filename . ' ----');
    }

    /**
     * @param Product $product
     * @param CsvWriter $output
     * @param string $field
     * @param string $attribute
     * @return void
     * @throws FileSystemException|LocalizedException
     */
    private function handleProductAttributeValues(
        Product $product,
        CsvWriter $output,
        string $field,
        string $attribute
    ) {
        $value = null;
        switch ($attribute) {
            case ProductAttributes::SEPARATE_METHOD:
                if (isset(self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field])
                    && is_callable([$this, self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field]])) {
                    $value = $this->{self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field]}($product);
                }
                break;
            default:
                $eavAttribute = $this->getAttributeByCode($attribute);
                $value = $eavAttribute->getSource()->getOptionText($product->getData($attribute));

                if ($value === false) {
                    $value = $product->getData($attribute);
                }
        }

        if ($value !== null) {
            if (!is_array($value)) {
                $values = $this->handleMultipleValues((string)$value);
            } else {
                $values = $value;
            }

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
    }

    /**
     * @return EavAttribute[]
     */
    private function getAttributeCollection()
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
     * @param $attributeCode
     * @return EavAttribute|null
     */
    private function getAttributeByCode($attributeCode)
    {
        return $this->getAttributeCollection()[$attributeCode] ?? null;
    }


    /**
     * @param ProductCollection $productCollection
     * @return $this
     */
    private function appendReviewSummaryToCollection(ProductCollection $productCollection)
    {
        $this->sumResourceFactory->create()->appendSummaryFieldsToCollection(
            $productCollection,
            (string)$productCollection->getStoreId(),
            Review::ENTITY_PRODUCT_CODE
        );

        return $this;
    }

    /**
     * @param string $value
     * @return string[]
     */
    private function handleMultipleValues(string $value)
    {
        if (strpos($value, ',') !== false) {
            $value = explode(',', $value);
        }

        return (array)$value;
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getCategoryIds(Product $product)
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
    private function getRatingSummary(Product $product)
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
    private function getReviewsCount(Product $product)
    {
        $value = null;
        if ($product->getRatingSummary() && $product->getReviewsCount() > 0) {
            $value = $product->getReviewsCount();
        }
        return $value;
    }
}
