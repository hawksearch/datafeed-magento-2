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
use Magento\Eav\Model\Entity\Attribute\Config;



class Attributes extends \Magento\Framework\App\Config\Value
{
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;
    /**
     * @var Config
     */
    private $attributeConfig;

    /**
     * Attributes constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param Config $attributeConfig
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AttributeCollectionFactory $attributeCollectionFactory,
        Config $attributeConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->attributeConfig = $attributeConfig;
    }

    public function afterLoad()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $pac */
        $pac = $this->attributeCollectionFactory->create();
        $pac->addSearchableAttributeFilter();
        $values = [];
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $item */
        foreach ($pac as $item) {
            $locked = $this->attributeConfig->getLockedFields($item);
            if(isset($locked['is_searchable'])) {
                continue;
            }
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