<?php
/**
 * Copyright (c) 2018 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
declare(strict_types=1);

namespace HawkSearch\Datafeed\Model;

use Exception;
use HawkSearch\Datafeed\Helper\Data;
use HawkSearch\Datafeed\Exception\SftpException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\Sftp;
use Zend_Filter_BaseName;

/**
 * Class SftpManagement
 * processes files to SFTP
 * @package HawkSearch\Datafeed\Model
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
     * @var Data
     */
    private $config;

    /**
     * @var Zend_Filter_BaseName
     */
    private $baseName;

    /**
     * SftpManagement constructor.
     * @param Sftp $sftp
     * @param File $file
     * @param Data $config
     * @param Zend_Filter_BaseName $baseName
     */
    public function __construct(
        Sftp $sftp,
        File $file,
        Data $config,
        Zend_Filter_BaseName $baseName
    ) {
        $this->sftp = $sftp;
        $this->file = $file;
        $this->config = $config;
        $this->baseName = $baseName;
    }

    /**
     * @return array[]
     */
    public function processFilesToSftp()
    {
        try {
            $processedFiles = [
                'success' => [],
                'error' => [],
                'failed_to_remove' => []
            ];

            $filesToProcess = $this->prepareFilesData();
            $this->config->log('start Sftp');

            if ($filesToProcess) {
                $this->sftp->open(
                    [
                        'host' => $this->config->getSftpHost(),
                        'username' => $this->config->getSftpUser(),
                        'password' => $this->config->getSftpPassword(),
                    ]
                );

                $sftpFolderPath = $this->config->getSftpFolder();
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
            $this->config->log($e->getMessage());
        } catch (SftpException $e) {
            $this->config->log($e->getMessage());
        } catch (Exception $e) {
            $this->config->log($e->getMessage());
        } finally {
            $this->sftp->close();
        }

        $this->config->log('Succeed Files:' . PHP_EOL . implode(PHP_EOL, $processedFiles['success']));
        $this->config->log('Failed Files:' . PHP_EOL . implode(PHP_EOL, $processedFiles['error']));

        $processedFiles['failed_to_remove'] = $this->removeSucceedFiles($processedFiles['success']);

        $this->config->log('end Sftp');

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
                $this->config->log($e->getMessage());
                $failedFiles[] = $file;
            }
        }

        $this->config->log('Failed to remove from Magento Feed path:' . PHP_EOL .
            implode(PHP_EOL, $failedFiles));

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

        $feedsPath = $this->config->getFeedFilePath();
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
