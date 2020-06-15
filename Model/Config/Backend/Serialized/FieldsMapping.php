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

namespace HawkSearch\Datafeed\Model\Config\Backend\Serialized;

use HawkSearch\Connector\Gateway\Instruction\InstructionManagerPool;
use HawkSearch\Connector\Gateway\InstructionException;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use HawkSearch\Datafeed\Api\Data\HawkSearchFieldInterfaceFactory;

class FieldsMapping extends ArraySerialized
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var InstructionManagerPool
     */
    private $instructionManagerPool;

    /**
     * @var HawkSearchFieldInterfaceFactory
     */
    private $hawkSearchFieldFactory;

    /**
     * FieldsMapping constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param InstructionManagerPool $instructionManagerPool
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        InstructionManagerPool $instructionManagerPool,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer
        );
        $this->serializer = $serializer;
        $this->instructionManagerPool = $instructionManagerPool;
    }

    /**
     * Processing object after load data
     *
     * @return void
     * @throws InstructionException
     * @throws NotFoundException
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
//        $hawkFields = $this->instructionManagerPool->get('hawksearch')->executeByCode('getFields');
//        $savedMapping = $this->getValue();
//
//        $newValue = [];
//
//        foreach ($hawkFields->get() as $field) {
//            $newValue[$field['Name']] = [
//                'hawk_attribute_label' => $field['Label'],
//                'hawk_attribute_code' => $field['Name'],
//                'magento_attribute' => $savedMapping[$field['Name']] ?? ''
//            ];
//        }
//
//        $this->setValue($newValue);
    }

    /**
     * Unset array element with '__empty' key
     *
     * @return $this
     */
    public function beforeSave()
    {
//        $value = $this->getValue();
//
//        $mapping = [];
//
//        foreach ($value as $attributeMap) {
//            if (is_array($attributeMap)
//                && isset($attributeMap['magento_attribute'])
//                && isset($attributeMap['hawk_attribute_code'])
//                && $attributeMap['magento_attribute']
//                && $attributeMap['hawk_attribute_code']
//            ) {
//                $mapping[$attributeMap['hawk_attribute_code']] = $attributeMap['magento_attribute'];
//            }
//        }
//
//        $this->setValue($mapping);
        $hawkFields = $this->instructionManagerPool->get('hawksearch')->executeByCode('postField');
        return parent::beforeSave();
    }
}
