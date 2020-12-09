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
use HawkSearch\Datafeed\Api\Data\FeedSummaryInterface;
use HawkSearch\Datafeed\Api\Data\FeedSummaryInterfaceFactory;
use HawkSearch\Datafeed\Logger\DataFeedLogger;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;

class Datafeed
{
    /**#@+
     * Constants for keys of data array
     */
    const SCRIPT_NAME = 'Datafeed';
    /**#@-*/

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var CsvWriterFactory
     */
    private $csvWriterFactory;

    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var SftpManagement
     */
    private $sftpManagement;

    /**
     * @var InstructionManagerPool
     */
    private $instructionManagerPool;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var FeedSummaryInterfaceFactory
     */
    private $feedSummaryFactory;

    /**
     * @var DataFeedLogger
     */
    private $logger;

    /**
     * @var array
     */
    private $timeStampData = [];

    /**
     * @var Config\Feed
     */
    private $feedConfigProvider;

    /**
     * Datafeed constructor.
     * @param CsvWriterFactory $csvWriterFactory
     * @param Manager $eventManager
     * @param Emulation $emulation
     * @param DateTimeFactory $dateTimeFactory
     * @param SftpManagement $sftpManagement
     * @param Config\Feed $feedConfigProvider
     * @param InstructionManagerPool $instructionManagerPool
     * @param Filesystem $fileSystem
     * @param FeedSummaryInterfaceFactory $feedSummaryFactory
     * @param DataFeedLogger $logger
     */
    public function __construct(
        CsvWriterFactory $csvWriterFactory,
        Manager $eventManager,
        Emulation $emulation,
        DateTimeFactory $dateTimeFactory,
        SftpManagement $sftpManagement,
        Config\Feed $feedConfigProvider,
        InstructionManagerPool $instructionManagerPool,
        Filesystem $fileSystem,
        FeedSummaryInterfaceFactory $feedSummaryFactory,
        DataFeedLogger $logger
    ) {
        $this->csvWriterFactory = $csvWriterFactory;
        $this->eventManager = $eventManager;
        $this->emulation = $emulation;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->sftpManagement = $sftpManagement;
        $this->feedConfigProvider = $feedConfigProvider;
        $this->instructionManagerPool = $instructionManagerPool;
        $this->fileSystem = $fileSystem;
        $this->feedSummaryFactory = $feedSummaryFactory;
        $this->logger = $logger;
    }

    public function generateFeed()
    {
        $stores = $this->feedConfigProvider->getStores();

        $storeSummary = [];

        /** @var Store $store */
        foreach ($stores->getItems() as $store) {
            try {
                $this->log(sprintf('Starting environment for store %s', $store->getName()));

                $this->emulation->startEnvironmentEmulation($store->getId());

                $this->log(sprintf('Setting feed folder for store_code %s', $store->getCode()));

                $storeSummary[$store->getCode()]['start_time'] = date(DATE_ATOM);

                $this->timeStampData = [];
                $this->timeStampData[] = ['dataset', 'full'];

                // emit events to allow extended feeds
                $this->eventManager->dispatch(
                    'hawksearch_datafeed_generate_custom_feeds',
                    ['model' => $this, 'store' => $store]
                );

                //generate timestamp file
                $this->generateTimestamp($store->getCode());

                $this->sftpManagement->processFilesToSftp(); 

                // trigger reindex on HawkSearch side
                if ($this->feedConfigProvider->isReindex($store)) {
                    $response = $this->instructionManagerPool
                        ->get('hawksearch')->executeByCode('triggerHawkReindex')->get();

                    if ($response[ClientInterface::RESPONSE_CODE] === 201) {
                        $this->log('Reindex request has been created successfully');
                    } else {
                        $this->log(
                            'Response code: ' . $response[ClientInterface::RESPONSE_CODE]
                            . '; Message: ' . $response[ClientInterface::RESPONSE_MESSAGE]
                        );
                    }
                } else {
                    $this->log('Reindex is disabled.');
                }

                $storeSummary[$store->getCode()]['end_time'] = date(DATE_ATOM);

                // end emulation
                $this->emulation->stopEnvironmentEmulation();

                $this->log(
                    sprintf(
                        'going to write summary file %s',
                        $this->feedConfigProvider->getPath() . DIRECTORY_SEPARATOR
                        . $this->feedConfigProvider->getSummaryFilename()
                    )
                );

                /** @var FeedSummaryInterface $feedSummary */
                $feedSummary = $this->feedSummaryFactory->create();
                $feedSummary->setStores($storeSummary);
                $feedSummary->setComplete(date(DATE_ATOM));
                $this->writeSummary($feedSummary);

                $this->log('all done, goodbye');
            } catch (\Exception $e) {
                $this->log(
                    sprintf(
                        "General Exception %s at generateFeed() line %d, stack:\n%s",
                        $e->getMessage(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    )
                );
                throw $e;
            }
        }
    }

    /**
     * @return void
     * @var string $storeCode
     */
    private function generateTimestamp(string $storeCode)
    {
        try {
            $output = $this->initOutput('timestamp', $storeCode);
            $time = $this->dateTimeFactory->create();
            $output->appendRow([$time->gmtDate('c')]);
            foreach ($this->timeStampData as $argument) {
                $output->appendRow($argument);
            }
        } catch (FileSystemException $e) {
            $this->log('- ERROR');
            $this->log($e->getMessage());
        }
    }

    /**
     * @param FeedSummaryInterface $summary
     * @throws FileSystemException
     */
    private function writeSummary(FeedSummaryInterface $summary)
    {
        $summaryFile = implode(
            DIRECTORY_SEPARATOR,
            [$this->feedConfigProvider->getPath(), $this->feedConfigProvider->getSummaryFilename()]
        );
        $writer = $this->fileSystem->getDirectoryWrite('media');
        $writer->writeFile($summaryFile, json_encode($summary->getData(), JSON_PRETTY_PRINT));
    }

    /**
     * @param array $data
     * @return void
     */
    public function setTimeStampData(array $data)
    {
        $this->timeStampData[] = $data;
    }

    /**
     * @param string $filename
     * @param string $storeCode
     * @return CsvWriter
     * @throws FileSystemException
     */
    public function initOutput(string $filename, string $storeCode): CsvWriter
    {
        return $this->csvWriterFactory->create()->init(
            $this->getPathForFile($filename, $storeCode),
            $this->feedConfigProvider->getFieldDelimiter(),
            $this->feedConfigProvider->getBufferSize()
        );
    }

    /**
     * @param string $filename
     * @param string $storeCode
     * @return string
     * @throws FileSystemException
     */
    public function getPathForFile(string $filename, string $storeCode)
    {
        $dir = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->feedConfigProvider->getPath(),
                $storeCode
            ]
        );

        $mediaWriter = $this->fileSystem->getDirectoryWrite('media');
        $mediaWriter->create($dir);

        return sprintf(
            '%s%s%s.%s',
            $mediaWriter->getAbsolutePath($dir),
            DIRECTORY_SEPARATOR,
            $filename,
            $this->feedConfigProvider->getOutputFileExtension()
        );
    }

    /**
     * @param string $message
     * @return void
     */
    public function log(string $message)
    {
        $this->logger->debug($message);
    }
}
