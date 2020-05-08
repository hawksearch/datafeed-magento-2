<?php
/**
 * Created by PhpStorm.
 * User: astayart
 * Date: 11/5/18
 * Time: 8:52 AM
 */

namespace HawkSearch\Datafeed\Model;

use HawkSearch\Datafeed\Helper\Data;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\App\Emulation;

class ImageCache extends AbstractModel
{
    const SCRIPT_NAME = 'Image Cache';
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var ImageFactory
     */
    private $imageHelperFactory;

    /**
     * ImageCache constructor.
     *
     * @param Data                  $helper
     * @param Emulation             $emulation
     * @param CollectionFactory     $productCollectionFactory
     * @param ImageFactory          $imageHelperFactory
     * @param Context               $context
     * @param Registry              $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null       $resourceCollection
     * @param array                 $data
     */
    public function __construct(
        Data $helper,
        Emulation $emulation,
        CollectionFactory $productCollectionFactory,
        ImageFactory $imageHelperFactory,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->helper = $helper;
        $this->emulation = $emulation;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageHelperFactory = $imageHelperFactory;
    }

    public function refreshImageCache()
    {
        $this->helper->log('starting refreshImageCache()');

        /**
 * @var \Magento\Store\Model\ResourceModel\Store\Collection $stores 
*/
        $stores = $this->helper->getSelectedStores();

        /**
 * @var \Magento\Store\Model\Store $store 
*/
        foreach ($stores as $store) {
            try {
                $this->helper->log(sprintf('Starting environment for store %s', $store->getName()));

                $this->emulation->startEnvironmentEmulation($store->getId());
                /**
 * @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products 
*/
                $products = $this->productCollectionFactory->create()
                    ->addAttributeToSelect(['small_image'])
                    ->addStoreFilter($store);
                $products->setPageSize($this->helper->getBatchLimit());
                $pages = $products->getLastPageNumber();

                $currentPage = 1;
                $imageHelper = $this->imageHelperFactory->create();
                do {
                    $this->helper->log(sprintf('going to page %d of images', $currentPage));
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
                $this->helper->log(
                    sprintf(
                        "General Exception %s at generateFeed() line %d, stack:\n%s",
                        $e->getMessage(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    )
                );
            }
        }
        $this->helper->log('Done generating image cache for selected stores, goodbye');
    }
}
