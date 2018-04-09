<?php
/**
 * Copyright (c) 2013 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace HawkSearch\Datafeed\Model;

use HawkSearch\Datafeed\Model\CsvWriterFactory;
use Magento\Framework\Model\AbstractModel;
use HawkSearch\Datafeed\Helper\Data as Helper;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Magento\Store\Model\App\Emulation;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Review\Model\Review;
use HawkSearch\Datafeed\Model\Email;
use Magento\Catalog\Helper\CategoryFactory;
use Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

class Datafeed extends AbstractModel
{
    private $feedSummary;
    private $productAttributes;
    private $helper;
    private $stockHelper;
    private $imageHelper;
    /**
     * @var StoreCollection
     */
    private $storeCollection;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var AttributeCollection
     */
    private $attributeCollection;
    /**
     * @var ProductCollection
     */
    private $productCollection;
    /**
     * @var Review
     */
    private $review;
    /**
     * @var \HawkSearch\Datafeed\Model\CsvWriter
     */
    private $csvWriter;
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;
    /**
     * @var \HawkSearch\Datafeed\Model\Email
     */
    private $email;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var CategoryFactory
     */
    private $categoryHelperFactory;
    /**
     * @var ConfigurableFactory
     */
    private $configurableFactory;
    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;

    /**
     * Constructor
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Helper $helper
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     * @param StoreCollection $storeCollection
     * @param Emulation $emulation
     * @param AttributeCollection $attributeCollection
     * @param ProductCollectionFactory $productCollection
     * @param Review $review
     * @param \HawkSearch\Datafeed\Model\CsvWriterFactory $csvWriter
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Helper $helper,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        StoreCollection $storeCollection,
        Emulation $emulation,
        AttributeCollection $attributeCollection,
        ProductCollection $productCollection,
        Review $review,
        CsvWriterFactory $csvWriter,
        Email $email,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryFactory $categoryHelperFactory,
        ConfigurableFactory $configurableFactory,
        PageCollectionFactory $pageCollectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->helper = $helper;
        $this->stockHelper = $stockHelper;
        $this->imageHelper = $imageHelperFactory;
        $this->productAttributes = array('entity_id', 'sku', 'name', 'url', 'small_image', 'msrp', 'price', 'special_price', 'special_from_date', 'special_to_date', 'short_description', 'description', 'meta_keyword', 'qty');
        $this->feedSummary = new \stdClass();
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->storeCollection = $storeCollection;
        $this->emulation = $emulation;
        $this->attributeCollection = $attributeCollection;
        $this->productCollection = $productCollection;
        $this->review = $review;
        $this->csvWriter = $csvWriter;
        $this->moduleManager = $moduleManager;
        $this->email = $email;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryHelperFactory = $categoryHelperFactory;
        $this->configurableFactory = $configurableFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * Adds a log entry to the hawksearch proxy log. Logging must
     * be enabled for both the module and Magneto
     *
     * @param $message
     */
    public function log($message)
    {
        if ($this->helper->loggingIsEnabled()) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/hawksearch.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info("HAWKSEARCH: $message");
        }
    }

    /**
     * Recursively sets up the category tree without introducing
     * duplicate data.
     *
     * @param $pid
     * @param $all
     * @param $tree
     */
    private function r_find($pid, &$all, &$tree)
    {
        foreach ($all as $item) {
            if ($item['pid'] == $pid) {
                $tree[] = $item;
                $this->r_find($item['id'], $all, $tree);
            }
        }
    }


    private function getCategoryData(\Magento\Store\Model\Store $store)
    {
        $this->log('starting _getCategoryData()');
        $filename = $this->helper->getPathForFile('hierarchy');

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(array('name', 'is_active', 'parent_id', 'position', 'include_in_menu'));
        $collection->addAttributeToFilter('is_active', array('eq' => '1'));
        $collection->addAttributeToSort('entity_id')->addAttributeToSort('parent_id')->addAttributeToSort('position');
        $collection->setPageSize($this->helper->getBatchLimit());
        $pages = $collection->getLastPageNumber();
        $currentPage = 1;

        $this->log(sprintf('going to open feed file %s', $filename));

        $output = $this->csvWriter->create()->init($filename, $this->helper->getFieldDelimiter(), $this->helper->getBufferSize());
        $this->log('file open, going to append header and root');
        $output->appendRow(array('category_id', 'category_name', 'parent_category_id', 'sort_order', 'is_active', 'category_url', 'include_in_menu'));
        $output->appendRow(array('1', 'Root', '0', '0', '1', '/', '1'));
        $this->log('header and root appended');
        $base = $store->getBaseUrl();

        $categoryHelper = $this->categoryHelperFactory->create();
        $cats = array();
        do {
            //$this->log(sprintf('getting category page %d', $currentPage));
            $collection->setCurPage($currentPage);
            $collection->clear();
            $collection->load();
            foreach ($collection as $cat) {


                $fullUrl = $categoryHelper->getCategoryUrl($cat);
                $category_url = substr($fullUrl, strlen($base));
                if (substr($category_url, 0, 1) != '/') {
                    $category_url = '/' . $category_url;
                }
                //$this->log(sprintf("got full category url: %s, returning relative url %s", $fullUrl, $category_url));
                $cats[] = array(
                    'id' => $cat->getId(),
                    'name' => $cat->getName(),
                    'pid' => $cat->getParentId(),
                    'pos' => $cat->getPosition(),
                    'ia' => $cat->getIsActive(),
                    'url' => $category_url,
                    'inmenu' => $cat->getIncludeInMenu()
                );
            }
            $currentPage++;
        } while ($currentPage <= $pages);

        $rcid = $store->getRootCategoryId();
        $myCategories = array();
        foreach ($cats as $storecat) {
            if ($storecat['id'] == $rcid) {
                $myCategories[] = $storecat;
            }
        }

        $this->log("using root category id: $rcid");
        $this->r_find($rcid, $cats, $myCategories);

        foreach ($myCategories as $final) {
            $output->appendRow(array(
                $final['id'],
                $final['name'],
                $final['pid'],
                $final['pos'],
                $final['ia'],
                $final['url'],
                $final['inmenu']
            ));
        }

        $this->log('done with _getCategoryData()');
        return true;
    }

    private function getAttributeData(\Magento\Store\Model\Store $store)
    {
        $this->log('starting _getAttributeData');
        $filename = $this->helper->getPathForFile('attributes');
        $labelFilename = $this->helper->getPathForFile('labels');

        $this->log(sprintf('exporting attribute labels for store %s', $store->getName()));
        $start = time();
        $pac = $this->attributeCollection->create();
        $pac->addSearchableAttributeFilter();
        $pac->addStoreLabel($store->getId());
        $attributes = array();

        $labels = $this->csvWriter->create()->init($labelFilename, $this->helper->getFieldDelimiter(), $this->helper->getBufferSize());
        $labels->appendRow(array('key', 'store_label'));
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $att */
        foreach ($pac as $att) {
            $attributes[$att->getAttributeCode()] = $att;
            $labels->appendRow(array($att->getAttributeCode(), $att->getStoreLabel()));
        }
        $labels->closeOutput();
        $this->log(sprintf('Label export took %d seconds', time() - $start));

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
        $products = $this->productCollection->create();
        $feedCodes = array_diff(array_keys($attributes), $this->productAttributes);
        if (!in_array('sku', $feedCodes)) {
            array_push($feedCodes, 'sku');
        }
        $this->log(sprintf('searchable atts: %s', implode(', ', array_keys($attributes))));
        $this->log(sprintf('adding attributes to select: %s', implode(', ', $feedCodes)));
        $products->addAttributeToSelect($feedCodes);

        $products->addStoreFilter($store);

        if (!$this->helper->includeDisabledItems()) {
            $this->log('adding status filter');
            $products->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        }

        if (!$this->helper->includeOutOfStockItems()) {
            $this->log('adding out of stock filter');
            $this->stockHelper->addIsInStockFilterToCollection($products);
        }


        $this->log(sprintf('going to open feed file %s', $filename));
        $output = $this->csvWriter->create()->init($filename, $this->helper->getFieldDelimiter(), $this->helper->getBufferSize());
        $this->log('feed file open, appending header');
        $output->appendRow(array('unique_id', 'key', 'value'));

        $products->setPageSize($this->helper->getBatchLimit());
        $pages = $products->getLastPageNumber();
        $currentPage = 1;

        do {
            $this->log(sprintf('starting attribute export for page %d', $currentPage));
            $start = time();
            $products->setCurPage($currentPage);
            $products->clear();
            $this->review->appendSummary($products);
            $products->load();
            foreach ($products as $product) {
                foreach ($feedCodes as $attcode) {
                    if ($product->getData($attcode) === null) {
                        continue;
                    }
                    $source = $attributes[$attcode]->getSource();
                    if ($source instanceof \Magento\Eav\Model\Entity\Attribute\Source\Table) {
//						TODO: These table based items need to be broken into separate line items
                        $output->appendRow(array(
                            $product->getSku(),
                            $attcode,
                            $product->getResource()->getAttribute($attcode)->getFrontend()->getValue($product)
                        ));
                    } elseif ($source instanceof \Magento\Catalog\Model\Product\Visibility
                        || $source instanceof \Magento\Tax\Model\TaxClass\Source\Product
                        || $source instanceof \Magento\Catalog\Model\Product\Attribute\Source\Status
                    ) {
                        $output->appendRow(array(
                            $product->getSku(),
                            $attcode,
                            $source->getOptionText($product->getData($attcode))
                        ));
                    } else {
                        $output->appendRow(array(
                            $product->getSku(),
                            $attcode,
                            $product->getData($attcode)
                        ));
                    }
                }
                foreach ($product->getCategoryIds() as $id) {
                    $output->appendRow(array($product->getSku(), 'category_id', $id));
                }
                if (($rs = $product->getRatingSummary()) && $rs->getReviewsCount() > 0) {
                    $output->appendRow(array($product->getSku(), 'rating_summary', $rs->getRatingSummary()));
                    $output->appendRow(array($product->getSku(), 'reviews_count', $rs->getReviewsCount()));
                }
            }

            $this->log(sprintf('page %d took %d seconds to export', $currentPage, time() - $start));
            $currentPage++;
        } while ($currentPage <= $pages);
    }

    private function getProductData(\Magento\Store\Model\Store $store)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
        $products = $this->productCollection->create();
        $products->addAttributeToSelect($this->productAttributes);
        $products->addMinimalPrice();
        $products->addStoreFilter($store);

        if (!$this->helper->includeDisabledItems()) {
            $this->log('adding status filter');
            $products->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        }

        if (!$this->helper->includeOutOfStockItems()) {
            $this->log('adding out of stock filter');
            $this->stockHelper->addIsInStockFilterToCollection($products);
        }

        // taken from the product grid collection:
        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $products->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );
        }

        $filename = $this->helper->getPathForFile('items');

        $output = $this->csvWriter->create()->init($filename, $this->helper->getFieldDelimiter(), $this->helper->getBufferSize());
        $output->appendRow(array(
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
            'type_id'));

        $products->setPageSize($this->helper->getBatchLimit());
        $pages = $products->getLastPageNumber();
        $currentPage = 1;
        $configurable = $this->configurableFactory->create();
        do {
            $this->log(sprintf('Starting product page %d', $currentPage));
            $products->setCurPage($currentPage);
            $products->clear();
            $start = time();
            $products->load();
            $seconds = time() - $start;
            $this->log(sprintf('it took %d seconds to load product page %d', $seconds, $currentPage));
            $start = time();
            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($products as $product) {
                $output->appendRow(array(
                    $product->getId(),
                    $product->getSku(),
                    $product->getName(),
                    substr($product->getProductUrl(1), strlen($store->getBaseUrl())),
                    $product->getSmallImage(),
                    $product->getMsrp(),
                    $product->getPrice(),
                    $product->getSpecialPrice(),
                    $product->getSpecialFromDate(),
                    $product->getSpecialToDate(),
                    $this->getGroupId($product, $configurable),
                    $product->getShortDescription(),
                    $product->getDescription(),
                    '',
                    $product->getSku(),
                    '',
                    '',
                    '',
                    '',
                    $product->getSpecialPrice() ? 1 : 0,
                    $product->getMetaKeyword(),
                    $product->getQty(),
                    $product->getMinimalPrice(),
                    $product->getTypeId()
                ));
            }
            $this->log(sprintf('it took %d seconds to export page %d', time() - $start, $currentPage));
            $currentPage++;
        } while ($currentPage <= $pages);

        $this->log('done with _getProductData()');
    }

    private function getGroupId(\Magento\Catalog\Model\Product $product, $configurable)
    {
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            $vals = implode(",", $configurable->getParentIdsByChild($product->getId()));
            if (!empty($vals)) {
                return $vals;
            }
        }
        return $product->getId();
    }

    private function getContentData(\Magento\Store\Model\Store $store)
    {
        $this->log('starting getContentData()');
        $collection = $this->pageCollectionFactory->create();
        $collection->addStoreFilter($store->getId());

        $output = $this->csvWriter->create()->init($this->helper->getPathForFile('content'), $this->helper->getFieldDelimiter(), $this->helper->getBufferSize());
        $output->appendRow(array('unique_id', 'name', 'url_detail', 'description_short', 'created_date'));

        foreach ($collection as $page) {
            $output->appendRow(array(
                $page->getPageId(),
                $page->getTitle(),
                sprintf('%s%s', $store->getBaseUrl(), $page->getIdentifier()),
                $page->getContentHeading(),
                $page->getCreationTime()
            ));
        }
        $this->log('done with getting content data');
    }

    public function cronGenerateImagecache()
    {
        if ($this->helper->getCronImagecacheEnable()) {
            if ($this->helper->isFeedLocked()) {
                $message = "Hawksearch Datafeed is currently locked, not generating feed at this time.";
            } else {
                try {
                    $this->helper->createFeedLocks();
                    $this->refreshImageCache();
                    $message = "HawkSeach Imagecache Generated!";
                } catch (\Exception $e) {
                    $message = sprintf('There has been an error: %s', $e->getMessage());
                    $this->helper->removeFeedLocks();
                }
            }
            $msg = array('message' => $message);
            $this->email->sendEmail($msg);
        }
    }

    public function cronGenerateDatafeed()
    {
        if ($this->helper->getCronEnabled()) {
            if ($this->helper->isFeedLocked()) {
                $message = "Hawksearch Datafeed is currently locked, not generating feed at this time.";
            } else {
                try {
                    $this->helper->createFeedLocks();
                    $this->generateFeed();
                    $message = "HawkSeach Datafeed Generated!";
                } catch (\Exception $e) {
                    $message = sprintf('There has been an error: %s', $e->getMessage());
                    $this->helper->removeFeedLocks();
                }
            }

            $msg = array('message' => $message);
            $this->email->sendEmail($msg);
        }
    }

    public function generateFeed()
    {
        $selectedStores = $this->helper->getSelectedStores();
        $stores = $this->storeCollection;
        $stores->addIdFilter($selectedStores);

        /** @var \Magento\Store\Model\Store $store */
        foreach ($stores as $store) {
            try {
                $this->log(sprintf('Starting environment for store %s', $store->getName()));

                $this->emulation->startEnvironmentEmulation($store->getId());

                $this->log(sprintf('Setting feed folder for store_code %s', $store->getCode()));
                $this->feedSummary->stores[$store->getCode()] = ['start_time' => date(DATE_ATOM)];

                //exports Category Data
                //$this->getCategoryData($store);

                //exports Product Data
                //$this->getProductData($store);

                //exports Attribute Data
                //$this->getAttributeData($store);

                //exports CMS / Content Data
                $this->getContentData($store);

                // trigger reindex on hawksearch end
                //$this->helper->triggerReindex($store);

                $this->feedSummary->stores[$store->getCode()]['end_time'] = date(DATE_ATOM);

                // end emulation
                $this->emulation->stopEnvironmentEmulation();
            } catch (\Exception $e) {
                $this->log(sprintf("General Exception %s at generateFeed() line %d, stack:\n%s", $e->getMessage(), $e->getLine(), $e->getTraceAsString()));
                throw $e;
            }

        }
        $this->log(sprintf('going to write summary file %s', $this->helper->getSummaryFilename()));
        $this->feedSummary->complete = date(DATE_ATOM);
        $this->helper->writeSummary($this->feedSummary);

        $this->log('done generating data feed files, going to remove lock files.');
        $this->helper->removeFeedLocks();
        $this->log('all done, goodbye');

    }


    public function refreshImageCache()
    {
        $this->log('starting refreshImageCache()');

        $selectedStores = $this->helper->getSelectedStores();
        $this->storeCollection->addIdFilter($selectedStores);

        /** @var \Magento\Store\Model\Store $store */
        foreach ($this->storeCollection as $store) {
            try {
                $this->log(sprintf('Starting environment for store %s', $store->getName()));

                $this->emulation->startEnvironmentEmulation($store->getId());
                /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
                $products = $this->productCollection->create()
                    ->addAttributeToSelect(array('small_image'))
                    ->addStoreFilter($store);
                $products->setPageSize($this->helper->getBatchLimit());
                $pages = $products->getLastPageNumber();

                $currentPage = 1;
                $imageHelper = $this->imageHelper->create();
                do {
                    $this->log(sprintf('going to page %d of images', $currentPage));
                    $products->clear();
                    $products->setCurPage($currentPage);
                    $products->load();

                    foreach ($products as $product) {
                        if (empty($this->helper->getImageHeight())) {
                            $imageHelper->init($product, 'hawksearch_autosuggest_image')
                                ->resize($this->helper->getImageWidth())
                                ->save();
                        } else {
                            $imageHelper->init($product, 'hawksearch_autosuggest_image')
                                ->resize($this->helper->getImageWidth(), $this->helper->getImageHeight())
                                ->save();
                        }
                    }

                    $currentPage++;

                } while ($currentPage <= $pages);

                // end emulation
                $this->emulation->stopEnvironmentEmulation();

            } catch (\Exception $e) {
                $this->log(sprintf("General Exception %s at generateFeed() line %d, stack:\n%s", $e->getMessage(), $e->getLine(), $e->getTraceAsString()));
            }

        }
        $this->helper->removeFeedLocks();
        $this->log('Done generating image cache for selected stores, goodbye');
    }
}