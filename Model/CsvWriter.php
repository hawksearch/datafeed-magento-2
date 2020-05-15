<?php
/**
 * Copyright (c) 2013 Hawksearch (www.hawksearch.com) - All Rights Reserved
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

use Magento\Framework\Filesystem\Io\File as ioFile;
use Composer\Util\Filesystem as UtilFileSystem;
use Magento\Framework\Filesystem\Driver\File;

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
     * CsvWriter constructor.
     *
     * @param ioFile         $fileDirectory
     * @param UtilFileSystem $utilFileSystem
     * @param File           $file
     */
    public function __construct(
        ioFile $fileDirectory,
        UtilFileSystem $utilFileSystem,
        File $file
    ) {
        $this->fileDirectory = $fileDirectory;
        $this->utilFileSystem = $utilFileSystem;
        $this->file = $file;
    }

    /**
     * @param  $destFile
     * @param  $delim
     * @param  null     $buffSize
     * @return $this
     * @throws \Exception
     */
    public function init($destFile, $delim, $buffSize = null)
    {
        $this->finalDestinationPath = $destFile;
        if ($this->fileDirectory->fileExists($this->finalDestinationPath)) {
            if (false === $this->utilFileSystem->unlink($this->finalDestinationPath)) {
                throw new \Exception("CsvWriteBuffer: unable to remove old file '$this->finalDestinationPath'");
            }
        }
        $this->delimiter = $delim;
        $this->bufferSize = $buffSize;
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->closeOutput();
    }

    /**
     * @param  array $fields
     * @throws \Exception
     */
    public function appendRow(array $fields)
    {
        if (!$this->outputOpen) {
            $this->openOutput();
        }
        foreach ($fields as $k => $f) {
            $fields[$k] = strtr($f, ['\"' => '"']);
        }
        if (false === fputcsv($this->outputFile, $fields, $this->delimiter)) {
            throw new \Exception("CsvWriter: failed to write row.");
        }
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function openOutput()
    {
        if (false === ($this->outputFile = $this->file->fileOpen($this->finalDestinationPath, 'a'))) {
            throw new \Exception("CsvWriter: Failed to open destination file '$this->finalDestinationPath'.");
        }
        if ($this->bufferSize !== null) {
            try {
                stream_set_write_buffer($this->outputFile, $this->bufferSize);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        $this->outputOpen = true;
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function closeOutput()
    {
        if ($this->outputOpen) {
            if (false === $this->file->fileFlush($this->outputFile)) {
                throw new \Exception(sprintf("CsvWriter: Failed to flush feed file: %s", $this->finalDestinationPath));
            }
            if (false === $this->file->fileClose($this->outputFile)) {
                throw new \Exception(sprintf("CsvWriter: Failed to close feed file ", $this->finalDestinationPath));
            }
            $this->outputOpen = false;
        }
    }
}
