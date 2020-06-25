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
use HawkSearch\Datafeed\Model\Datafeed;
use HawkSearch\Datafeed\Model\FieldsManagement;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Store\Model\Store;

class ItemFeed implements ObserverInterface
{
    /**#@+
     * Constants
     */
    const FEED_ATTRIBUTES = [
        'product_id',
        'unique_id',
        'name',
        'url_detail',
        'image',
        'price_retail',
        'price_sale',
        'price_special',
        'price_special_from_date',
        'price_special_to_date',
        'group_id',
        'description_short',
        'description_long',
        'brand',
        'sku',
        'sort_default',
        'sort_rating',
        'is_free_shipping',
        'is_new',
        'is_on_sale',
        'keyword',
        'metric_inventory',
        'minimal_price',
        'type_id'
    ];
    const ADDITIONAL_ATTRIBUTES_HANDLERS = [
        'url_detail' => 'getUrl',
        'group_id' => 'getGroupId',
        'is_on_sale' => 'isOnSale',
        'metric_inventory' => 'getSalableQuantity',
    ];
    /**#@-*/

    /**
     * @var string
     */
    private $filename = 'items';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ConfigProvider
     */
    private $config;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $salableQnt;

    /**
     * ItemFeed constructor.
     * @param CollectionFactory $collectionFactory
     * @param ConfigProvider $config
     * @param Json $jsonSerializer
     * @param Configurable $configurableType
     * @param GetSalableQuantityDataBySku $salableQnt
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigProvider $config,
        Json $jsonSerializer,
        Configurable $configurableType,
        GetSalableQuantityDataBySku $salableQnt
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->configurableType = $configurableType;
        $this->salableQnt = $salableQnt;
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

        $counter = 0;

        $feedExecutor->log('START ---- ' . $this->filename . ' ----');

        try {
            //prepare map
            $feedExecutor->log('- Prepare attributes map');
            $configurationMap = $this->jsonSerializer->unserialize($this->config->getMapping($store));

            $map = [];
            foreach (self::FEED_ATTRIBUTES as $field) {
                $map[$field] = $configurationMap[$field . FieldsManagement::FIELD_SUFFIX]
                    [FieldsMapping::MAGENTO_ATTRIBUTE] ?? '';
            }

            //prepare product collection
            $feedExecutor->log('- Prepare product collection');
            $collection = $this->collectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addPriceData();
            $collection->addStoreFilter($store);
            $collection->setPageSize($this->config->getBatchLimit());

            //init output
            $output = $feedExecutor->initOutput($this->filename, $store->getCode());

            //adding column names
            $feedExecutor->log('- Adding column names');
            $output->appendRow(self::FEED_ATTRIBUTES);

            //adding product data
            $feedExecutor->log('- Adding product data');

            $currentPage = 1;
            do {
                $feedExecutor->log(sprintf('- Starting product page %d', $currentPage));
                $collection->clear();
                $collection->setCurPage($currentPage);
                $collection->load();
                $start = time();
                /** @var Product $product */
                foreach ($collection->getItems() as $product) {
                    $row = [];
                    foreach ($map as $field => $magentoAttribute) {
                        $row[] = $this->handleProductAttributeValues($product, $store, $field, $magentoAttribute);
                    }
                    $output->appendRow($row);
                    $counter++;
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
        } finally {
            $feedExecutor->setTimeStampData([$this->filename . '.' . $this->config::CONFIG_OUTPUT_EXTENSION, $counter]);
        }

        $feedExecutor->log('END ---- ' . $this->filename . ' ----');
    }

    /**
     * @param Product $product
     * @param Store $store
     * @param string $field
     * @param string $attribute
     * @return mixed|string|null
     */
    private function handleProductAttributeValues(Product $product, Store $store, string $field, string $attribute)
    {
        $value = '';

        switch ($attribute) {
            case ProductAttributes::SEPARATE_METHOD:
                $value = isset(self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field])
                && is_callable([$this, self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field]]) ?
                    $this->{self::ADDITIONAL_ATTRIBUTES_HANDLERS[$field]}($product, $store) : '';
                break;
            case '':
                break;
            default:
                $value = $product->getData($attribute);
        }

        return $value;
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
    private function getSalableQuantity(Product $product, Store $store)
    {
        $value = 0;
        if ($product->getTypeId() === Type::TYPE_SIMPLE) {
            foreach ($this->salableQnt->execute($product->getSku()) as $source) {
                $value += $source['qty'] ?? 0;
            }
        }

        return $value;
    }
}
