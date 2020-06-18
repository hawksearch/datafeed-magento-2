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
use HawkSearch\Datafeed\Helper\Data;
use HawkSearch\Datafeed\Model\ConfigProvider;
use HawkSearch\Datafeed\Model\CsvWriterFactory;
use HawkSearch\Datafeed\Model\Datafeed;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class LabelFeed implements ObserverInterface
{
    /**#@+
     * Constants
     */
    const FEED_COLUMNS = [
        'key',
        'store_label'
    ];
    /**#@-*/

    /**
     * @var string
     */
    private $filename = 'labels';

    /**
     * @var CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var ConfigProvider
     */
    private $config;

    /**
     * @var CsvWriterFactory
     */
    private $csvFactory;

    /**
     * @var Data
     */
    private $helper; //TODO helper should be removed as soon as all required updates are done

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * ItemFeed constructor.
     * @param CollectionFactory $attributeCollectionFactory
     * @param ConfigProvider $config
     * @param CsvWriterFactory $csvFactory
     * @param Data $helper
     * @param Json $jsonSerializer
     */
    public function __construct(
        CollectionFactory $attributeCollectionFactory,
        ConfigProvider $config,
        CsvWriterFactory $csvFactory,
        Data $helper,
        Json $jsonSerializer
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->config = $config;
        $this->csvFactory = $csvFactory;
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
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
            //prepare attributes to select
            $feedExecutor->log('- Prepare attributes to select');
            $configurationMap = $this->jsonSerializer->unserialize($this->config->getMapping($store));

            $attributesToSelect = [];
            foreach ($configurationMap as $field) {
                if (!empty($field[FieldsMapping::MAGENTO_ATTRIBUTE])) {
                    $attributesToSelect[] = $field[FieldsMapping::MAGENTO_ATTRIBUTE];
                }
            }

            //prepare attribute collection
            $feedExecutor->log('- Prepare attribute collection');
            $collection = $this->attributeCollectionFactory->create();
            $collection->addFieldToFilter('attribute_code', ['in' => $attributesToSelect]);
            $collection->addStoreLabel($store->getId());
            $collection->setPageSize($this->config->getBatchLimit());

            //TODO this helper functionality should be moved to the $feedExecutor
            $output = $this->csvFactory->create()->init(
                $this->helper->getPathForFile($this->filename),
                $this->helper->getFieldDelimiter(),
                $this->helper->getBufferSize()
            );

            //adding column names
            $feedExecutor->log('- Adding column names');
            $output->appendRow(self::FEED_COLUMNS);

            //adding attribute data
            $feedExecutor->log('- Adding attribute data');

            $currentPage = 1;
            do {
                $feedExecutor->log(sprintf('- Starting attribute page %d', $currentPage));
                $collection->clear();
                $collection->setCurPage($currentPage);
                $collection->load();
                $start = time();

                /** @var Attribute $attribute */
                foreach ($collection->getItems() as $attribute) {
                    $output->appendRow([
                        $attribute->getAttributeCode(),
                        $attribute->getStoreLabel()
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
        } catch (NoSuchEntityException $e) {
            $feedExecutor->log('- ERROR');
            $feedExecutor->log($e->getMessage());
        } finally {
            $feedExecutor->setTimeStampData([$this->filename . '.' . $this->helper::CONFIG_OUTPUT_EXTENSION, $counter]);
        }

        $feedExecutor->log('END ---- ' . $this->filename . ' ----');
    }
}
