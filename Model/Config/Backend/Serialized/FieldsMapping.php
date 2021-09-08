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

use HawkSearch\Connector\Api\Data\HawkSearchFieldInterface;
use HawkSearch\Connector\Gateway\Http\ClientInterface;
use HawkSearch\Connector\Gateway\Instruction\InstructionManagerPool;
use HawkSearch\Connector\Gateway\InstructionException;
use HawkSearch\Datafeed\Model\Config\Admin\StoreViewConfigResolver;
use HawkSearch\Datafeed\Model\Config\Attributes as AttributesConfig;
use HawkSearch\Datafeed\Model\FieldsManagement;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;


class FieldsMapping extends ArraySerialized
{
    /**#@+
     * Constants
     */
    const NEW_ROW_PATTERN_ID = '/^_\d*_\d*$/';
    const NEW_FILED_PATTERN_NAME = '/.+_new$/';
    const NEW_FILED_NAME_SUFFIX = '_new';
    /**#@-*/

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var InstructionManagerPool
     */
    private $instructionManagerPool;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var ManagerInterface
     */
    private $message;

    /**
     * @var StoreViewConfigResolver
     */
    private $storeViewConfigResolver;

    /**
     * FieldsMapping constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param InstructionManagerPool $instructionManagerPool
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param ManagerInterface $message
     * @param StoreViewConfigResolver $storeViewConfigResolver
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
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ManagerInterface $message,
        StoreViewConfigResolver $storeViewConfigResolver,
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
        $this->productAttributeRepository = $productAttributeRepository;
        $this->message = $message;
        $this->storeViewConfigResolver = $storeViewConfigResolver;
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        $submitValue = $this->getValue();

        $valueToSave = [];
        $newFields = [];
        $errors = [];

        if (is_array($submitValue)) {
            unset($submitValue['__empty']);

            foreach ($submitValue as $id => $value) {
                $codeKey = $this->getElementFieldCodeKey($value);
                if ($codeKey === null) {
                    continue;
                }

                if ($value[AttributesConfig::MAGENTO_ATTRIBUTE] === "" || $value[$codeKey] === "") {
                    continue;
                }

                if (preg_match(self::NEW_ROW_PATTERN_ID, $id)
                    && preg_match(self::NEW_FILED_PATTERN_NAME, $codeKey)
                ) {
                    $newFields[$id] = $value;
                } elseif (preg_match(self::NEW_ROW_PATTERN_ID, $id)) {
                    $valueToSave[$value[$codeKey] . FieldsManagement::FIELD_SUFFIX] = $value;
                } else {
                    $valueToSave[$id] = $value;
                }
            }

            foreach ($newFields as $newField) {
                try {
                    $this->storeViewConfigResolver->resolve(true);
                    $createdField = $this->addNewFiled($newField);
                    $valueToSave[$createdField->getName() . FieldsManagement::FIELD_SUFFIX] = [
                        AttributesConfig::HAWK_ATTRIBUTE_CODE => $createdField->getName(),
                        AttributesConfig::MAGENTO_ATTRIBUTE => $newField[
                            AttributesConfig::MAGENTO_ATTRIBUTE] ?? '',
                    ];

                } catch (LocalizedException $e) {
                    $errors[] = $e->getMessage();
                } finally {
                    $this->storeViewConfigResolver->unresolve();
                }
            }
        }

        if ($errors) {
            $this->message->addErrorMessage(implode("; <br/>", $errors));
        }

        $this->setValue($valueToSave);

        return parent::beforeSave();
    }

    /**
     * Return variation of a field code key
     * @param array $elementData
     * @return string|null
     */
    protected function getElementFieldCodeKey($elementData)
    {
        return isset($elementData[AttributesConfig::HAWK_ATTRIBUTE_CODE])
            ? AttributesConfig::HAWK_ATTRIBUTE_CODE
            : (isset($elementData[AttributesConfig::HAWK_ATTRIBUTE_CODE . self::NEW_FILED_NAME_SUFFIX])
                ? AttributesConfig::HAWK_ATTRIBUTE_CODE . self::NEW_FILED_NAME_SUFFIX
                : null
            );
    }

    /**
     * @param array $field
     * @return HawkSearchFieldInterface
     * @throws LocalizedException
     */
    protected function addNewFiled($field)
    {
        $codeKey = $this->getElementFieldCodeKey($field);
        $data = [
            HawkSearchFieldInterface::LABEL => $field[$codeKey] ?? '',
            HawkSearchFieldInterface::NAME => $field[$codeKey] ?? '',
        ];
        if (!empty($field[AttributesConfig::MAGENTO_ATTRIBUTE])) {
            try {
                $attribute = $this->productAttributeRepository
                    ->get($field[AttributesConfig::MAGENTO_ATTRIBUTE]);

                $data[HawkSearchFieldInterface::IS_SORT] = $attribute->getUsedForSortBy() ? true : false;
                $data[HawkSearchFieldInterface::IS_COMPARE] = $attribute->getIsComparable() ? true : false;
                $data[HawkSearchFieldInterface::IS_QUERY] = $attribute->getIsSearchable() ? true : false;
            } catch (NoSuchEntityException $e) {
                // do nothing
            }
        }

        try {
            $response = $this->instructionManagerPool
                ->get('hawksearch')->executeByCode('postField', $data)->get();

            if ($response[ClientInterface::RESPONSE_CODE] === 201) {
                $valueToSave[$data[HawkSearchFieldInterface::NAME] . FieldsManagement::FIELD_SUFFIX] = [
                    AttributesConfig::HAWK_ATTRIBUTE_CODE => $field[$codeKey] ?? '',
                    AttributesConfig::MAGENTO_ATTRIBUTE => $field[AttributesConfig::MAGENTO_ATTRIBUTE] ?? '',
                ];
            } else {
                throw new LocalizedException(
                    __(
                        'HawkSearch: ' . $response[ClientInterface::RESPONSE_MESSAGE]
                        . '. Field "' . $data[HawkSearchFieldInterface::NAME] . '". '
                        . $response[ClientInterface::RESPONSE_DATA]->getData('Message')
                    )
                );
            }

        } catch (InstructionException | NotFoundException $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return $response[ClientInterface::RESPONSE_DATA];
    }
}
