<?php
/**
 * Copyright (c) 2023 Hawksearch (www.hawksearch.com) - All Rights Reserved
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

use Composer\Util\Filesystem as UtilFileSystem;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as ioFile;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class CsvWriter
 * @ HawkSearch\Datafeed\Model
 */
class CsvWriter
{
    /**
     * @var
     */
    private $finalDestinationPath;
    /**
     * @var
     */
    private $outputFile;
    /**
     * @var bool
     */
    private $outputOpen = false;
    /**
     * @var
     */
    private $delimiter;
    /**
     * @var
     */
    private $bufferSize;
    /**
     * @var ioFile
     */
    private $fileDirectory;
    /**
     * @var UtilFileSystem
     */
    private $utilFileSystem;
    /**
     * @var File
     */
    private $file;
    /**
     * @var Json
     */
    private $json;

    /**
     * CsvWriter constructor.
     *
     * @param ioFile         $fileDirectory
     * @param UtilFileSystem $utilFileSystem
     * @param File           $file
     * @param Json           $json
     */
    public function __construct(
        ioFile $fileDirectory,
        UtilFileSystem $utilFileSystem,
        File $file,
        Json $json
    ) {
        $this->fileDirectory = $fileDirectory;
        $this->utilFileSystem = $utilFileSystem;
        $this->file = $file;
        $this->json = $json;
    }

    /**
     * @param  $destFile
     * @param  $delimiter
     * @param  null     $buffSize
     * @return $this
     * @throws FileSystemException
     */
    public function init($destFile, $delimiter, $buffSize = null)
    {
        $this->finalDestinationPath = $destFile;
        if ($this->fileDirectory->fileExists($this->finalDestinationPath)) {
            if (false === $this->utilFileSystem->unlink($this->finalDestinationPath)) {
                throw new FileSystemException(
                    __("CsvWriteBuffer: unable to remove old file %1", $this->finalDestinationPath)
                );
            }
        }
        $this->delimiter = $delimiter;
        $this->bufferSize = $buffSize;
        return $this;
    }

    /**
     * @throws FileSystemException
     */
    public function __destruct()
    {
        $this->closeOutput();
    }

    /**
     * @param  array $fields
     * @throws FileSystemException
     */
    public function appendRow(array $fields)
    {
        if (!$this->outputOpen) {
            $this->openOutput();
        }

        foreach ($fields as $k => $f) {
            $fields[$k] = $this->prepareFieldValue($f);
        }

        if (false === fputcsv($this->outputFile, $fields, $this->delimiter)) {
            throw new FileSystemException(__("CsvWriter: failed to write row."));
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function prepareFieldValue($value): string
    {
        if (is_array($value)) {
            $value = $this->json->serialize($value);
        }

        return strtr((string)$value, ['\"' => '"']);
    }

    /**
     * @throws FileSystemException
     */
    public function openOutput()
    {
        if (false === ($this->outputFile = $this->file->fileOpen($this->finalDestinationPath, 'a'))) {
            throw new FileSystemException(
                __("CsvWriter: Failed to open destination file: %1", $this->finalDestinationPath)
            );
        }

        $this->outputOpen = true;
    }

    /**
     * @throws FileSystemException
     */
    public function closeOutput()
    {
        if ($this->outputOpen) {
            if (false === $this->file->fileFlush($this->outputFile)) {
                throw new FileSystemException(
                    __("CsvWriter: Failed to flush feed file: %1", $this->finalDestinationPath)
                );
            }
            if (false === $this->file->fileClose($this->outputFile)) {
                throw new FileSystemException(
                    __("CsvWriter: Failed to close feed file: %1", $this->finalDestinationPath)
                );
            }
            $this->outputOpen = false;
        }
    }
}
