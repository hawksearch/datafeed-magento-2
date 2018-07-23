<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 7/23/18
 * Time: 4:17 PM
 */

namespace HawkSearch\Datafeed\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollection;

class Attributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var AttributeCollection
     */
    private $attributeCollectionFactory;

    public function __construct(AttributeCollection $collectionFactory)
    {
        $this->attributeCollectionFactory = $collectionFactory;
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
            $ret[] = ['value' => $item->getAttributeCode(), 'label' => $item->getFrontendLabel()];
        }
        return $ret;
    }
}