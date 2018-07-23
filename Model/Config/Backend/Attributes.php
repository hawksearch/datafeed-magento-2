<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 7/23/18
 * Time: 4:38 PM
 */

namespace HawkSearch\Datafeed\Model\Config\Backend;


use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;


class Attributes extends \Magento\Framework\App\Config\Value
{
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AttributeCollectionFactory $attributeCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    public function afterLoad()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $pac */
        $pac = $this->attributeCollectionFactory->create();
        $pac->addSearchableAttributeFilter();
        $values = [];
        foreach ($pac as $item) {
            $values[] = $item->getAttributeCode();
        }
        $this->setValue(implode(',', $values));
        return parent::afterLoad();
    }

    public function afterSave()
    {
        $newValues = explode(',',$this->getValue());

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $pac */
        $pac = $this->attributeCollectionFactory->create();
        foreach ($pac as $item) {
            if(in_array($item->getAttributeCode(), $newValues)){
                $item->setIsSearchable(true);
            }else{
                $item->setIsSearchable(false);
            }
        }
        $pac->save();
        return parent::afterSave();
    }

}