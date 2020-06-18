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

use HawkSearch\Datafeed\Helper\Data as Helper;
use Magento\Catalog\Helper\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\Filesystem\Io\File;

class Datafeed extends AbstractModel
{
    const FEED_PATH = 'hawksearch/feeds';
    const SCRIPT_NAME = 'Datafeed';
    private $feedSummary;
    private $productAttributes;
    private $helper;
    private $stockHelper;
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
     * @var Manager
     */
    private $moduleManager;
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
     * @var Manager
     */
    private $eventManager;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var SftpManagement
     */
    private $sftpManagement;

    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $timeStampData = [];

    /**
     * Constructor
     * @param AttributeCollection $attributeCollection
     * @param ProductCollection $productCollection
     * @param Review $review
     * @param \HawkSearch\Datafeed\Model\CsvWriterFactory $csvWriter
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryFactory $categoryHelperFactory
     * @param ConfigurableFactory $configurableFactory
     * @param PageCollectionFactory $pageCollectionFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param EventManager $eventManager
     * @param Emulation $emulation
     * @param Helper $helper
     * @param EmailFactory $emailFactory
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param DateTimeFactory $dateTimeFactory
     * @param SftpManagement $sftpManagement
     * @param File $file
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        AttributeCollection $attributeCollection,
        ProductCollection $productCollection,
        Review $review,
        CsvWriterFactory $csvWriter,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryFactory $categoryHelperFactory,
        ConfigurableFactory $configurableFactory,
        PageCollectionFactory $pageCollectionFactory,
        Manager $moduleManager,
        EventManager $eventManager,
        Emulation $emulation,
        Helper $helper,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        DateTimeFactory $dateTimeFactory,
        SftpManagement $sftpManagement,
        File $file,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->stockHelper = $stockHelper;
        $this->emulation = $emulation;
        $this->attributeCollection = $attributeCollection;
        $this->productCollection = $productCollection;
        $this->review = $review;
        $this->csvWriter = $csvWriter;
        $this->moduleManager = $moduleManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryHelperFactory = $categoryHelperFactory;
        $this->configurableFactory = $configurableFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->eventManager = $eventManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->sftpManagement = $sftpManagement;
        $this->file = $file;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->productAttributes = [
            'entity_id',
            'sku',
            'name',
            'url',
            'small_image',
            'msrp',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'short_description',
            'description',
            'meta_keyword',
            'qty'
        ];
        $this->feedSummary = new \stdClass();
    }

    /**
     * @param array $data
     * @return void
     */
    public function setTimeStampData(array $data)
    {
        $this->timeStampData[] = $data;
    }

    /**
     * Recursively sets up the category tree without introducing
     * duplicate data.
     *
     * @param $pid
     * @param $all
     * @param $tree
     */
    private function rFind($pid, &$all, &$tree)
    {
        foreach ($all as $item) {
            if ($item['pid'] == $pid) {
                $tree[] = $item;
                $this->rFind($item['id'], $all, $tree);
            }
        }
    }

    /**
     * @param string $filePath
     * @return string
     */
    public function getBaseName(string $filePath)
    {
        $pathInfo = $this->file->getPathInfo($filePath);
        return $pathInfo['basename'] ?? '';
    }

    private function getCategoryData(\Magento\Store\Model\Store $store)
    {
        $this->log('starting _getCategoryData()');
        $filename = $this->helper->getPathForFile('hierarchy');

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active', 'parent_id', 'position', 'include_in_menu']);
        $collection->addAttributeToFilter('is_active', ['eq' => '1']);
        $collection->addAttributeToSort('entity_id')->addAttributeToSort('parent_id')->addAttributeToSort('position');
        $collection->setPageSize($this->helper->getBatchLimit());
        $pages = $collection->getLastPageNumber();
        $currentPage = 1;

        $this->log(sprintf('going to open feed file %s', $filename));

        $output = $this->csvWriter->create()
            ->init($filename, $this->helper->getFieldDelimiter(), $this->helper->getBufferSize());
        $this->log('file open, going to append header and root');
        $base = $store->getBaseUrl();

        $categoryHelper = $this->categoryHelperFactory->create();
        $cats = [];
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
                $cats[] = [
                    'id' => $cat->getId(),
                    'name' => $cat->getName(),
                    'pid' => $cat->getParentId(),
                    'pos' => $cat->getPosition(),
                    'ia' => $cat->getIsActive(),
                    'url' => $category_url,
                    'inmenu' => $cat->getIncludeInMenu()
                ];
            }
            $currentPage++;
        } while ($currentPage <= $pages);

        $rcid = $store->getRootCategoryId();
        $myCategories = [];
        foreach ($cats as $storecat) {
            if ($storecat['id'] == $rcid) {
                $myCategories[] = $storecat;
            }
        }

        $this->log("using root category id: $rcid");
        $this->rFind($rcid, $cats, $myCategories);

        $output->appendRow([
            'category_id',
            'category_name',
            'parent_category_id',
            'sort_order',
            'is_active',
            'category_url',
            'include_in_menu'
        ]);
        $output->appendRow(['1', 'Root', '0', '0', '1', '/', '1']);
        $this->log('header and root appended');

        foreach ($myCategories as $final) {
            $output->appendRow([
                $final['id'],
                $final['name'],
                $final['pid'],
                $final['pos'],
                $final['ia'],
                $final['url'],
                $final['inmenu']
            ]);
        }

        $this->timeStampData[] = [$this->getBaseName($filename), count($myCategories)+1];
        $this->log('done with _getCategoryData()');
        return true;
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
        /** @var \Magento\Cms\Model\ResourceModel\Page\Collection $collection */
        $collection = $this->pageCollectionFactory->create();
        $collection->addStoreFilter($store->getId());
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        $collection->addFieldToFilter('hawk_exclude', ['neq' => 1]);

        $output = $this->csvWriter->create()
            ->init(
                $this->helper->getPathForFile('content'),
                $this->helper->getFieldDelimiter(),
                $this->helper->getBufferSize()
            );
        $output->appendRow(['unique_id', 'name', 'url_detail', 'description_short', 'created_date']);

        foreach ($collection as $page) {
            $output->appendRow([
                $page->getPageId(),
                $page->getTitle(),
                sprintf('%s%s', $store->getBaseUrl(), $page->getIdentifier()),
                $page->getContentHeading(),
                $page->getCreationTime()
            ]);
        }
        $this->log('done with getting content data');
        $this->timeStampData[] = [
            $this->getBaseName($this->helper->getPathForFile('content')),
            $collection->getSize()];
        return true;
    }

    /**
     * @return void
     */
    private function generateTimestamp()
    {
        try {
            /** @var CsvWriter $output */
            $output = $this->csvWriter->create()->init(
                $this->helper->getPathForFile('timestamp'),
                $this->helper->getFieldDelimiter(),
                $this->helper->getBufferSize()
            );
            $time = $this->dateTimeFactory->create();
            $output->appendRow([$time->gmtDate('c')]);
            foreach ($this->timeStampData as $argument) {
                $output->appendRow($argument);
            }
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function generateFeed()
    {
        /** @var \Magento\Store\Model\ResourceModel\Store\Collection $stores */
        $stores = $this->helper->getSelectedStores();

        /** @var \Magento\Store\Model\Store $store */
        foreach ($stores as $store) {
            try {
                $this->log(sprintf('Starting environment for store %s', $store->getName()));

                $this->emulation->startEnvironmentEmulation($store->getId());

                $this->log(sprintf('Setting feed folder for store_code %s', $store->getCode()));
                $this->feedSummary->stores[$store->getCode()] = ['start_time' => date(DATE_ATOM)];

                $this->timeStampData = [];
                $this->timeStampData[] = ['dataset', 'full'];

                //exports Category Data
                $this->getCategoryData($store);

                //exports CMS / Content Data
                $this->getContentData($store);

                // emit events to allow extended feeds
                $this->eventManager->dispatch(
                    'hawksearch_datafeed_generate_custom_feeds',
                    ['model' => $this, 'store' => $store]
                );

                //generate timestamp file
                $this->generateTimestamp();

                // trigger reindex on hawksearch end
                $this->helper->triggerReindex($store);

                $this->feedSummary->stores[$store->getCode()]['end_time'] = date(DATE_ATOM);

                // end emulation
                $this->emulation->stopEnvironmentEmulation();
            } catch (\Exception $e) {
                $this->log(
                    sprintf(
                        "General Exception %s at generateFeed() line %d, stack:\n%s",
                        $e->getMessage(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    )
                );
                throw $e;
            }
        }

        if ($this->helper->isSftpEnabled()) {
            $this->sftpManagement->processFilesToSftp();
        }

        $this->log(sprintf('going to write summary file %s', $this->helper->getSummaryFilename()));
        $this->feedSummary->complete = date(DATE_ATOM);
        $this->helper->writeSummary($this->feedSummary);
        $this->log('all done, goodbye');
    }

    public function log($message)
    {
        $this->helper->log($message);
    }
}
