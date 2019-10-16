<?php
/**
 * Copyright (c) 2019 Hawksearch (www.hawksearch.com) - All Rights Reserved
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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;


class CsvWriter
{
    private $finalDestinationPath;
    private $outputFile;
    private $outputOpen = false;
    private $delimiter;
    private $bufferSize;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function init($destFile, $delim, $buffSize = null)
    {
        $this->finalDestinationPath = $destFile;
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $writer->create(dirname($destFile));
        $writer->delete($destFile);

        $this->delimiter = $delim;
        $this->bufferSize = $buffSize;
        return $this;
    }

    public function __destruct()
    {
        $this->closeOutput();
    }


    public function appendRow(array $fields)
    {
        if (!$this->outputOpen) {
            $this->openOutput();
        }
        foreach ($fields as $k => $f) {
            $fields[$k] = strtr($f, array('\"' => '"'));
        }
        if (false === fputcsv($this->outputFile, $fields, $this->delimiter)) {
            throw new \Exception("CsvWriter: failed to write row.");
        }
    }

    public function openOutput()
    {
        if (false === ($this->outputFile = fopen($this->finalDestinationPath, 'a'))) {
            throw new \Exception("CsvWriter: Failed to open destination file '$this->finalDestinationPath'.");
        }
        if (!is_null($this->bufferSize)) {
            stream_set_write_buffer($this->outputFile, $this->bufferSize);
        }
        $this->outputOpen = true;
    }
    public function closeOutput()
    {
        if ($this->outputOpen) {
            if (false === fflush($this->outputFile)) {
                throw new \Exception(sprintf("CsvWriter: Failed to flush feed file: %s", $this->finalDestinationPath));
            }
            if (false === fclose($this->outputFile)) {
                throw new \Exception(sprintf("CsvWriter: Failed to close feed file: %s ", $this->finalDestinationPath));
            }
            $this->outputOpen = false;
        }
    }
}