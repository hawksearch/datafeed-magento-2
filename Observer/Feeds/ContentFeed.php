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

use HawkSearch\Datafeed\Model\ConfigProvider;
use HawkSearch\Datafeed\Model\Datafeed;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

class ContentFeed implements ObserverInterface
{
    /**#@+
     * Constants
     */
    const FEED_COLUMNS = [
        'unique_id',
        'name',
        'url_detail',
        'description_short',
        'created_date'
    ];
    /**#@-*/

    /**
     * @var string
     */
    private $filename = 'content';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ConfigProvider
     */
    private $config;

    /**
     * ItemFeed constructor.
     * @param CollectionFactory $collectionFactory
     * @param ConfigProvider $config
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigProvider $config
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
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
            //prepare cms pages collection
            $feedExecutor->log('- Prepare cms pages collection');
            $collection = $this->collectionFactory->create();
            $collection->addStoreFilter($store->getId());
            $collection->addFieldToFilter('is_active', ['eq' => 1]);
            $collection->addFieldToFilter('hawk_exclude', ['neq' => 1]);
            $collection->setPageSize($this->config->getBatchLimit());

            //init output
            $output = $feedExecutor->initOutput($this->filename, $store->getCode());

            //adding column names
            $feedExecutor->log('- Adding column names');
            $output->appendRow(self::FEED_COLUMNS);

            //adding attribute data
            $feedExecutor->log('- Adding pages data');

            $currentPage = 1;
            do {
                $feedExecutor->log(sprintf('- Starting content page %d', $currentPage));
                $collection->clear();
                $collection->setCurPage($currentPage);
                $collection->load();
                $start = time();

                /** @var Page $page */
                foreach ($collection->getItems() as $page) {
                    $output->appendRow([
                        $page->getId(),
                        $page->getTitle(),
                        sprintf('%s%s', $store->getBaseUrl(), $page->getIdentifier()),
                        $page->getContentHeading(),
                        $page->getCreationTime()
                    ]);
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
}
