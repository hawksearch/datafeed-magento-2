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
use HawkSearch\Datafeed\Model\Config\Source\ProductAttributes;
use HawkSearch\Datafeed\Model\ConfigProvider;
use HawkSearch\Datafeed\Model\CsvWriter;
use HawkSearch\Datafeed\Model\Datafeed;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Review\Model\Review;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

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
     * @var ProductCollection
     */
    private $productCollectionFactory;

    /**
     * @var AttributeCollection
     */
    private $attributeCollectionFactory;

    /**
     * @var ConfigProvider
     */
    private $config;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Review
     */
    private $review;

    /**
     * ItemFeed constructor.
     * @param ProductCollection $productCollectionFactory
     * @param AttributeCollection $attributeCollectionFactory
     * @param ConfigProvider $config
     * @param Json $jsonSerializer
     * @param Review $review
     */
    public function __construct(
        ProductCollection $productCollectionFactory,
        AttributeCollection $attributeCollectionFactory,
        ConfigProvider $config,
        Json $jsonSerializer,
        Review $review
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->review = $review;
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
            $configurationMap = $this->jsonSerializer->unserialize($this->config->getMapping($store));

            $map = [];
            $magentoAttributes = [];
            foreach ($configurationMap as $field) {
                if (!empty($field[FieldsMapping::HAWK_ATTRIBUTE_CODE])
                    && !in_array($field[FieldsMapping::HAWK_ATTRIBUTE_CODE], ItemFeed::FEED_ATTRIBUTES)
                    && !empty($field[FieldsMapping::MAGENTO_ATTRIBUTE])) {
                    $map[$field[FieldsMapping::HAWK_ATTRIBUTE_CODE]] = $field[FieldsMapping::MAGENTO_ATTRIBUTE];
                    $magentoAttributes[] = $field[FieldsMapping::MAGENTO_ATTRIBUTE];
                }
            }

            //prepare attributes collection
            $feedExecutor->log('- Prepare attributes collection');
            $attributeCollection = $this->attributeCollectionFactory->create();
            $attributeCollection->addFieldToFilter('attribute_code', ['in' => $magentoAttributes]);
            $attributeCollection->load();

            //create mapping: attribute_code => attribute entity if attribute has a source model
            $attributeMapping = [];
            /** @var Attribute $attribute */
            foreach ($attributeCollection->getItems() as $attribute) {
                if ($attribute->getSource()) {
                    $attributeMapping[$attribute->getAttributeCode()] = $attribute;
                }
            }

            //prepare product collection
            $feedExecutor->log('- Prepare product collection');
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addPriceData();
            $collection->addStoreFilter($store);
            $collection->setPageSize($this->config->getBatchLimit());

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
                $this->review->appendSummary($collection); //TODO find another way to get reviews
                $collection->load();
                $start = time();
                /** @var Product $product */
                foreach ($collection->getItems() as $product) {
                    foreach ($map as $field => $magentoAttribute) {
                        $this->handleProductAttributeValues(
                            $product,
                            $store,
                            $output,
                            $field,
                            $magentoAttribute,
                            $attributeMapping
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
                [$this->filename . '.' . $this->config::CONFIG_OUTPUT_EXTENSION, $this->counter]
            );
        }

        $feedExecutor->log('END ---- ' . $this->filename . ' ----');
    }

    /**
     * @param Product $product
     * @param Store $store
     * @param CsvWriter $output
     * @param string $field
     * @param string $attribute
     * @param Attribute[] $attributeMapping
     * @return void
     * @throws FileSystemException|LocalizedException
     */
    private function handleProductAttributeValues(
        Product $product,
        Store $store,
        CsvWriter $output,
        string $field,
        string $attribute,
        array $attributeMapping
    ) {
        switch ($attribute) {
            case ProductAttributes::SEPARATE_METHOD:
                if (isset(self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field])
                && is_callable([$this, self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field]])) {
                    $this->{self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field]}($product, $store, $output, $field);
                }
                break;
            default:
                if (isset($attributeMapping[$attribute])) {
                    $value = $attributeMapping[$attribute]->getSource()->getOptionText($product->getData($attribute));
                } else {
                    $value = $product->getData($attribute);
                }
                if ($product->getData($attribute)) {
                    $output->appendRow([
                        $product->getSku(),
                        $field,
                        $value
                    ]);
                    $this->counter++;
                }
        }
    }

    /**
     * @param Product $product
     * @param Store $store
     * @param CsvWriter $output
     * @param string $field
     * @return void
     * @throws FileSystemException
     */
    private function getCategoryIds(Product $product, Store $store, CsvWriter $output, string $field)
    {
        foreach ($product->getCategoryIds() as $id) {
            $output->appendRow([$product->getSku(), $field, $id]);
            $this->counter++;
        }
    }

    /**
     * @param Product $product
     * @param Store $store
     * @param CsvWriter $output
     * @param string $field
     * @return void
     * @throws FileSystemException
     */
    private function getRatingSummary(Product $product, Store $store, CsvWriter $output, string $field)
    {
        if (($rs = $product->getRatingSummary()) && $rs->getReviewsCount() > 0) {
            $output->appendRow([$product->getSku(), $field, $rs->getRatingSummary()]);
            $this->counter++;
        }
    }

    /**
     * @param Product $product
     * @param Store $store
     * @param CsvWriter $output
     * @param string $field
     * @return void
     * @throws FileSystemException
     */
    private function getReviewsCount(Product $product, Store $store, CsvWriter $output, string $field)
    {
        if (($rs = $product->getRatingSummary()) && $rs->getReviewsCount() > 0) {
            $output->appendRow([$product->getSku(), $field, $rs->getReviewsCount()]);
            $this->counter++;
        }
    }
}
