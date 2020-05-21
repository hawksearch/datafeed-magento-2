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

namespace HawkSearch\Datafeed\Model;

use Exception;
use HawkSearch\Datafeed\Helper\Data;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\Sftp;

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
     * SftpManagement constructor.
     * @param Sftp $sftp
     * @param File $file
     * @param Data $config
     */
    public function __construct(
        Sftp $sftp,
        File $file,
        Data $config
    ) {
        $this->sftp = $sftp;
        $this->file = $file;
        $this->config = $config;
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

                foreach ($filesToProcess as $store => $filesData) {
                    if (!$this->sftp->cd($store)) {
                        $this->processDirectories($store);
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
        } catch (Exception $e) {
            $this->config->log($e->getMessage());
        } finally {
            $this->sftp->close();
        }

        $this->config->log('SUCCEED FILES: ' . PHP_EOL . implode(PHP_EOL, $processedFiles['success']));
        $this->config->log('FAILED FILES: ' . PHP_EOL . implode(PHP_EOL, $processedFiles['error']));

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

        $this->config->log('FAILED TO REMOVE FROM MAGENTO FEED PATH: ' . PHP_EOL .
            implode(PHP_EOL, $failedFiles));

        return $failedFiles;
    }

    /**
     * @param string $path
     * @throws Exception
     */
    private function processDirectories(string $path)
    {
        $this->sftp->mkdir('/' . $path);
        if (!$this->sftp->cd($path)) {
            throw new Exception(__('Wasn\'t able to create or navigate to SFTP directory: %1', $path));
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
                $storeCode = basename($path);
                foreach ($this->file->readDirectory($path) as $file) {
                    if ($this->file->isFile($file)) {
                        $filesToProcess[$storeCode][] = [
                            'filename' => basename($file),
                            'source' => $file
                        ];
                    }
                }
            }
        }

        return $filesToProcess;
    }
}
