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

use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Datafeed;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

class CategoryFeed implements ObserverInterface
{
    /**#@+
     * Constants
     */
    const FEED_COLUMNS = [
        'category_id',
        'category_name',
        'parent_category_id',
        'sort_order',
        'is_active',
        'category_url',
        'include_in_menu'
    ];
    const ROOT_CATEGORY = [
        '1',
        'Root',
        '0',
        '0',
        '1',
        '/',
        '1'
    ];
    /**#@-*/

    /**
     * @var string
     */
    private $filename = 'hierarchy';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ConfigFeed
     */
    private $feedConfigProvider;

    /**
     * ItemFeed constructor.
     * @param CollectionFactory $collectionFactory
     * @param ConfigFeed $feedConfigProvider
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigFeed $feedConfigProvider
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->feedConfigProvider = $feedConfigProvider;
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
            //prepare categories collection
            $feedExecutor->log('- Prepare categories collection');
            $collection = $this->collectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('is_active', ['eq' => '1']);
            $collection->addAttributeToSort('entity_id')
                ->addAttributeToSort('parent_id')
                ->addAttributeToSort('position');
            $collection->setPageSize($this->feedConfigProvider->getBatchLimit());

            //init output
            $output = $feedExecutor->initOutput($this->filename, $store->getCode());

            //adding column names
            $feedExecutor->log('- Adding column names');
            $output->appendRow(self::FEED_COLUMNS);

            //adding Root category
            $feedExecutor->log('- Adding Root category');
            $output->appendRow(self::ROOT_CATEGORY);
            $counter++;

            //adding attribute data
            $feedExecutor->log('- Adding categories data');

            $base = $store->getBaseUrl();
            $currentPage = 1;
            $cats = [];

            do {
                $feedExecutor->log(sprintf('- Starting category page %d', $currentPage));

                $collection->clear();
                $collection->setCurPage($currentPage);
                $collection->load();
                $start = time();

                /** @var Category $category */
                foreach ($collection->getItems() as $category) {
                    $category_url = substr((string) $category->getUrl(), strlen((string) $base));
                    if (substr($category_url, 0, 1) != '/') {
                        $category_url = '/' . $category_url;
                    }

                    $cats[] = [
                        'id' => $category->getId(),
                        'name' => $category->getName(),
                        'pid' => $category->getParentId(),
                        'pos' => $category->getPosition(),
                        'ia' => $category->getIsActive(),
                        'url' => $category_url,
                        'inmenu' => $category->getIncludeInMenu()
                    ];
                }

                $feedExecutor->log(
                    sprintf('- It took %d seconds to export page %d', time() - $start, $currentPage)
                );

                $currentPage++;
            } while ($currentPage <= $collection->getLastPageNumber());

            $rcid = $store->getRootCategoryId();
            $myCategories = [];
            foreach ($cats as $storecat) {
                if ($storecat['id'] == $rcid) {
                    $myCategories[] = $storecat;
                }
            }

            $feedExecutor->log("using root category id: $rcid");
            $this->rFind($rcid, $cats, $myCategories);

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

                $counter++;
            }

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
                [$this->filename . '.' . $this->feedConfigProvider->getOutputFileExtension(), $counter]
            );
        }

        $feedExecutor->log('END ---- ' . $this->filename . ' ----');
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
}
