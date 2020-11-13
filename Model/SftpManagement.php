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

use Exception;
use HawkSearch\Datafeed\Exception\SftpException;
use HawkSearch\Datafeed\Logger\DataFeedLogger;
use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Config\Sftp as ConfigSftp;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\Sftp;
use Zend_Filter_BaseName;

/**
 * Class SftpManagement
 * processes files to SFTP
 */
class SftpManagement
{
    /**
     * @var Sftp
     */
    private $sftp;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Zend_Filter_BaseName
     */
    private $baseName;

    /**
     * @var DataFeedLogger
     */
    private $logger;

    /**
     * @var ConfigFeed
     */
    private $feedConfigProvider;

    /**
     * @var ConfigSftp
     */
    private $sftpConfigProvider;

    /**
     * SftpManagement constructor.
     * @param Sftp $sftp
     * @param File $file
     * @param ConfigFeed $feedConfigProvider
     * @param ConfigSftp $sftpConfigProvider
     * @param Zend_Filter_BaseName $baseName
     * @param DataFeedLogger $logger
     */
    public function __construct(
        Sftp $sftp,
        File $file,
        ConfigFeed $feedConfigProvider,
        ConfigSftp $sftpConfigProvider,
        Zend_Filter_BaseName $baseName,
        DataFeedLogger $logger
    ) {
        $this->sftp = $sftp;
        $this->file = $file;
        $this->feedConfigProvider = $feedConfigProvider;
        $this->sftpConfigProvider = $sftpConfigProvider;
        $this->baseName = $baseName;
        $this->logger = $logger;
    }

    /**
     * @return array[]
     */
    public function processFilesToSftp()
    {
        if (!$this->sftpConfigProvider->isEnabled()) {
            return [];
        }

        try {
            $processedFiles = [
                'success' => [],
                'error' => [],
                'failed_to_remove' => []
            ];

            $filesToProcess = $this->prepareFilesData();
            $this->logger->debug('start Sftp');

            if ($filesToProcess) {
                $this->sftp->open(
                    [
                        'host' => $this->sftpConfigProvider->getHost(),
                        'username' => $this->sftpConfigProvider->getUsername(),
                        'password' => $this->sftpConfigProvider->getPassword(),
                    ]
                );

                $sftpFolderPath = $this->sftpConfigProvider->getFolder() ?: DIRECTORY_SEPARATOR;
                if (!$this->sftp->cd($sftpFolderPath)) {
                    $this->processDirectories($sftpFolderPath);
                }

                foreach ($filesToProcess as $storeCode => $filesData) {
                    if (!$this->sftp->cd($storeCode)) {
                        $this->processDirectories($storeCode);
                    }

                    foreach ($filesData as $files) {
                        if (isset($files['filename'], $files['source'])) {
                            $this->sftp->write($files['filename'], $files['source']) ?
                                $processedFiles['success'][] = $files['source'] :
                                $processedFiles['error'][] = $files['source'];
                        }
                    }
                    $this->sftp->cd('..');
                }
            }
        } catch (FileSystemException $e) {
            $this->logger->debug($e->getMessage());
        } catch (SftpException $e) {
            $this->logger->debug($e->getMessage());
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        } finally {
            $this->sftp->close();
        }

        $this->logger->debug('Succeed Files:' . PHP_EOL . implode(PHP_EOL, $processedFiles['success']));
        $this->logger->debug('Failed Files:' . PHP_EOL . implode(PHP_EOL, $processedFiles['error']));

        $processedFiles['failed_to_remove'] = $this->removeSucceedFiles($processedFiles['success']);

        $this->logger->debug('end Sftp');

        return $processedFiles;
    }

    /**
     * @param array $files
     * @return array
     */
    private function removeSucceedFiles(array $files)
    {
        $failedFiles = [];

        foreach ($files as $file) {
            try {
                $this->file->deleteFile($file);
            } catch (FileSystemException $e) {
                $this->logger->debug($e->getMessage());
                $failedFiles[] = $file;
            }
        }

        $this->logger->debug(
            'Failed to remove from Magento Feed path:' . PHP_EOL .
            implode(PHP_EOL, $failedFiles)
        );

        return $failedFiles;
    }

    /**
     * @param string $path
     * @throws SftpException
     */
    private function processDirectories(string $path)
    {
        $dirList = explode(DIRECTORY_SEPARATOR, $path);
        foreach ($dirList as $dir) {
            if (!$this->sftp->cd($dir)) {
                $this->sftp->mkdir($dir, 0777, false);
                if (!$this->sftp->cd($dir)) {
                    throw new SftpException(__('Wasn\'t able to create or navigate to SFTP directory: %1', $path));
                }
            }
        }
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    private function prepareFilesData()
    {
        $filesToProcess = [];

        $feedsPath = $this->feedConfigProvider->getPath();
        foreach ($this->file->readDirectory($feedsPath) as $path) {
            if ($this->file->isDirectory($path)) {
                $storeCode = $this->baseName->filter($path);
                foreach ($this->file->readDirectory($path) as $file) {
                    if ($this->file->isFile($file)) {
                        $filesToProcess[$storeCode][] = [
                            'filename' => $this->baseName->filter($file),
                            'source' => $file
                        ];
                    }
                }
            }
        }

        return $filesToProcess;
    }
}
