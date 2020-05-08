<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 7/23/18
 * Time: 4:17 PM
 */

namespace HawkSearch\Datafeed\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollection;
use Magento\Eav\Model\Entity\Attribute\Config;

class Attributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var AttributeCollection
     */
    private $attributeCollectionFactory;
    /**
     * @var Config
     */
    private $attributeConfig;

    /**
     * Attributes constructor.
     *
     * @param AttributeCollection $collectionFactory
     * @param Config              $attributeConfig
     */
    public function __construct(AttributeCollection $collectionFactory, Config $attributeConfig)
    {
        $this->attributeCollectionFactory = $collectionFactory;
        $this->attributeConfig = $attributeConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $pac = $this->attributeCollectionFactory->create();
        $ret = [];
        foreach ($pac->getItems() as $item) {
            $locked = $this->attributeConfig->getLockedFields($item);
            if (isset($locked['is_searchable'])) {
                continue;
            }
            $ret[] = ['value' => $item->getAttributeCode(), 'label' => $item->getFrontendLabel()];
        }
        return $ret;
    }
}
