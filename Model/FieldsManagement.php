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

use HawkSearch\Connector\Gateway\Instruction\InstructionManagerPool;
use HawkSearch\Connector\Gateway\InstructionException;
use HawkSearch\Datafeed\Api\Data\HawkSearchFieldInterface;
use HawkSearch\Datafeed\Api\FieldsManagementInterface;
use HawkSearch\Datafeed\Block\Adminhtml\System\Config\FieldsMapping;
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
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * FieldsManagement constructor.
     * @param InstructionManagerPool $instructionManagerPool
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseFactory
     * @param ConfigProvider $configProvider
     * @param Json $jsonSerializer
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        InstructionManagerPool $instructionManagerPool,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        ConfigProvider $configProvider,
        Json $jsonSerializer,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->instructionManagerPool = $instructionManagerPool;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->configProvider = $configProvider;
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
            $hawkFieldsResponse = $this->instructionManagerPool->get('hawksearch')
                ->executeByCode('getFields')->get();
            $currentMapping = $this->jsonSerializer->unserialize($this->configProvider->getMapping());
            if (is_array($hawkFieldsResponse)) {
                //prepare array fields
                $arrayFields = [];
                foreach ($hawkFieldsResponse as $field) {
                    if (isset(
                        $field[HawkSearchFieldInterface::NAME],
                        $field[HawkSearchFieldInterface::LABEL]
                    )) {
                        $arrayFields[$field[HawkSearchFieldInterface::NAME] . self::FIELD_SUFFIX] = [
                            FieldsMapping::HAWK_ATTRIBUTE_LABEL => $field[HawkSearchFieldInterface::LABEL],
                            FieldsMapping::HAWK_ATTRIBUTE_CODE => $field[HawkSearchFieldInterface::NAME],
                            FieldsMapping::MAGENTO_ATTRIBUTE => $currentMapping[
                                    $field[HawkSearchFieldInterface::NAME] . self::FIELD_SUFFIX
                                ][FieldsMapping::MAGENTO_ATTRIBUTE] ?? '',
                        ];
                    }
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
                        $row[FieldsMapping::MAGENTO_ATTRIBUTE]
                    )] = 'selected="selected"';
                    $result[$rowId] = $this->dataObjectFactory->create()->addData($row)->toJson();
                }

                $response->setResponseData($result);
            }

        } catch (InstructionException $e) {
            $this->logger->error($e->getMessage());
            $response->setStatus(self::STATUS_ERROR);
            $response->setMessage($e->getMessage());
        } catch (NotFoundException $e) {
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
}
