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

namespace HawkSearch\Datafeed\Model;

use HawkSearch\Connector\Gateway\Http\ClientInterface;
use HawkSearch\Connector\Gateway\Instruction\InstructionManagerPool;
use HawkSearch\Connector\Gateway\InstructionException;
use HawkSearch\Connector\Api\Data\HawkSearchFieldInterface;
use HawkSearch\Datafeed\Api\FieldsManagementInterface;
use HawkSearch\Datafeed\Model\Response\Response;
use HawkSearch\Datafeed\Model\Response\ResponseFactory;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class FieldsManagement implements FieldsManagementInterface
{
    /**#@+
     * Constants
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const FIELD_SUFFIX = '_field';
    const CONFIG_NAME = 'groups[attributes][fields][mapping][value][<%- _id %>][magento_attribute]';
    const OPTION_ID = '<%- _id %>_magento_attribute';
    /**#@-*/

    /**
     * @var InstructionManagerPool
     */
    private $instructionManagerPool;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var Config\Attributes
     */
    private $attributesConfigProvider;

    /**
     * FieldsManagement constructor.
     * @param InstructionManagerPool $instructionManagerPool
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseFactory
     * @param Config\Attributes $attributesConfigProvider
     * @param Json $jsonSerializer
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        InstructionManagerPool $instructionManagerPool,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Config\Attributes $attributesConfigProvider,
        Json $jsonSerializer,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->instructionManagerPool = $instructionManagerPool;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->attributesConfigProvider = $attributesConfigProvider;
        $this->jsonSerializer = $jsonSerializer;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @return Response
     */
    public function syncHawkSearchFields()
    {
        $response = $this->responseFactory->create();
        $response->setStatus(self::STATUS_SUCCESS);
        $response->setMessage('');
        $response->setResponseData([]);

        try {
            $hawkFields = $this->getHawkSearchFields();
            $currentMapping = $this->attributesConfigProvider->getMapping();

            //prepare array fields
            $arrayFields = [];
            /** @var HawkSearchFieldInterface $field */
            foreach ($hawkFields as $field) {
                $arrayFields[$field[HawkSearchFieldInterface::NAME] . self::FIELD_SUFFIX] = [
                    Config\Attributes::HAWK_ATTRIBUTE_LABEL => $field->getLabel(),
                    Config\Attributes::HAWK_ATTRIBUTE_CODE => $field->getName(),
                    Config\Attributes::MAGENTO_ATTRIBUTE => $currentMapping[$field->getName()] ?? '',
                ];
            }

            //prepare return data
            $result = [];
            foreach ($arrayFields as $rowId => $row) {
                $rowColumnValues = [];
                foreach ($row as $key => $value) {
                    $row[$key] = $value;
                    $rowColumnValues[$rowId . '_' . $key] = $row[$key];
                }
                $row['_id'] = $rowId;
                $row['column_values'] = $rowColumnValues;
                $row['option_extra_attrs']['option_' . $this->calcOptionHash(
                    $row[Config\Attributes::MAGENTO_ATTRIBUTE]
                )] = 'selected="selected"';
                $result[$rowId] = $this->dataObjectFactory->create()->addData($row)->toJson();
            }

            $response->setResponseData($result);
        } catch (InstructionException | NotFoundException $e) {
            $this->logger->error($e->getMessage());
            $response->setStatus(self::STATUS_ERROR);
            $response->setMessage($e->getMessage());
        }

        return $response;
    }

    /**
     * Calculate CRC32 hash for option value
     *
     * @param string $optionValue Value of the option
     * @return string
     */
    private function calcOptionHash($optionValue)
    {
        return sprintf('%u', crc32(self::CONFIG_NAME . self::OPTION_ID . $optionValue));
    }

    /**
     * @inheritDoc
     * @throws InstructionException
     * @throws NotFoundException
     */
    public function getHawkSearchFields()
    {
        $hawkFieldsResponse =  $this->instructionManagerPool->get('hawksearch')
            ->executeByCode('getFields')->get();

        if ($hawkFieldsResponse[ClientInterface::RESPONSE_CODE] === 200) {
            return is_array($hawkFieldsResponse[ClientInterface::RESPONSE_DATA])
                ? $hawkFieldsResponse[ClientInterface::RESPONSE_DATA]
                : [];
        } else {
            throw new InstructionException(__($hawkFieldsResponse[ClientInterface::RESPONSE_MESSAGE]));
        }
    }
}
