<?php

namespace HawkSearch\Datafeed\Model\Generator;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use HawkSearch\Datafeed\Model\CsvWriterFactory;


class Content extends \Magento\Framework\Model\AbstractExtensibleModel
{
    /**
     * @var \HawkSearch\Datafeed\Helper\Data
     */
    private $helper;
    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;
    /**
     * @var CsvWriterFactory
     */
    private $csvWriter;

    public function __construct(
        \HawkSearch\Datafeed\Helper\Data $helper,
        PageCollectionFactory $pageCollectionFactory,
        CsvWriterFactory $csvWriter,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $resource, $resourceCollection, $data);
        $this->helper = $helper;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->csvWriter = $csvWriter;
    }

    public function execute($store)
    {
        $this->helper->log('starting getContentData()');
        /** @var \Magento\Cms\Model\ResourceModel\Page\Collection $collection */
        $collection = $this->pageCollectionFactory->create();
        $collection->addStoreFilter($store->getId());
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        $collection->addFieldToFilter('hawk_exclude', ['neq' => 1]);

        $output = $this->csvWriter->create();
        $output->init($this->helper->getPathForFile($store, 'content'), $this->helper->getFieldDelimiter(), $this->helper->getBufferSize());
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
        $this->helper->log('done with getting content data');
    }
}