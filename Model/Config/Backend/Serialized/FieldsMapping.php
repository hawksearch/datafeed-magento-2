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
use HawkSearch\Datafeed\Api\Data\HawkSearchFieldInterface;
use HawkSearch\Datafeed\Block\Adminhtml\System\Config\FieldsMapping as ConfigFieldsMapping;
use HawkSearch\Datafeed\Model\FieldsManagement;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
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
     * FieldsMapping constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param InstructionManagerPool $instructionManagerPool
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param ManagerInterface $message
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
                if (preg_match(self::NEW_ROW_PATTERN_ID, $id)) {
                    $newFields[$id] = $value;
                } else {
                    $valueToSave[$id] = $value;
                }
            }

            foreach ($newFields as $newField) {
                $data = [
                    HawkSearchFieldInterface::LABEL => $newField[ConfigFieldsMapping::HAWK_ATTRIBUTE_LABEL] ?? '',
                    HawkSearchFieldInterface::NAME => $newField[ConfigFieldsMapping::HAWK_ATTRIBUTE_CODE] ?? '',
                ];
                if (!empty($newField[ConfigFieldsMapping::MAGENTO_ATTRIBUTE])) {
                    try {
                        $attribute = $this->productAttributeRepository
                            ->get($newField[ConfigFieldsMapping::MAGENTO_ATTRIBUTE]);

                        $data[HawkSearchFieldInterface::IS_SORT] = $attribute->getUsedForSortBy() ? true : false;
                        $data[HawkSearchFieldInterface::IS_COMPARE] = $attribute->getIsComparable() ? true : false;
                        $data[HawkSearchFieldInterface::IS_QUERY] = $attribute->getIsSearchable() ? true : false;
                    } catch (NoSuchEntityException $e) {
                        $this->_logger->error($e->getMessage());
                        $errors[] = $e->getMessage();
                    }
                }

                try {
                    $response = $this->instructionManagerPool
                        ->get('hawksearch')->executeByCode('postField', $data)->get();

                    if (!isset($response['FieldId']) && isset($response['Message'])) {
                        $errors[] = 'HawkSearch: ' . $response['Message'] . ':' . $data[HawkSearchFieldInterface::NAME];
                    } else {
                        $valueToSave[$data[HawkSearchFieldInterface::NAME] . FieldsManagement::FIELD_SUFFIX] = [
                            ConfigFieldsMapping::HAWK_ATTRIBUTE_LABEL => $newField[
                                ConfigFieldsMapping::HAWK_ATTRIBUTE_LABEL] ?? '',
                            ConfigFieldsMapping::HAWK_ATTRIBUTE_CODE => $newField[
                                ConfigFieldsMapping::HAWK_ATTRIBUTE_CODE] ?? '',
                            ConfigFieldsMapping::MAGENTO_ATTRIBUTE => $newField[
                                ConfigFieldsMapping::MAGENTO_ATTRIBUTE] ?? '',
                        ];
                    }
                } catch (InstructionException $e) {
                    $this->_logger->error($e->getMessage());
                    $errors[] = $e->getMessage();
                } catch (NotFoundException $e) {
                    $this->_logger->error($e->getMessage());
                    $errors[] = $e->getMessage();
                }
            }
        }

        if ($errors) {
            $this->message->addErrorMessage(implode("; ", $errors));
        }

        $this->setValue($valueToSave);

        return parent::beforeSave();
    }
}
