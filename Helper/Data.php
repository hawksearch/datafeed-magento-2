<?php
/**
 * Copyright (c) 2017 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace HawkSearch\Datafeed\Helper;

use Magento\Cron\Model\Schedule;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use HawkSearch\Proxy\Helper\Data as ProxyHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    protected $filesystem;
    /**
     * @var Schedule
     */
    private $cronSchedule;

    const DEFAULT_FEED_PATH = 'hawksearch/feeds';
    const CONFIG_LOCK_FILENAME = 'hawksearchFeedLock.lock';
    const CONFIG_SUMMARY_FILENAME = 'hawksearchFeedSummary.json';
    const CONFIG_FEED_PATH = 'hawksearch_datafeed/feed/feed_path';
    const CONFIG_MODULE_ENABLED = 'hawksearch_datafeed/general/enabled';
    const CONFIG_LOGGING_ENABLED = 'hawksearch_datafeed/general/logging_enabled';
    const CONFIG_INCLUDE_OOS = 'hawksearch_datafeed/feed/stockstatus';
    const CONFIG_BATCH_LIMIT = 'hawksearch_datafeed/feed/batch_limit';
    const CONFIG_IMAGE_WIDTH = 'hawksearch_datafeed/imagecache/image_width';
    const CONFIG_IMAGE_HEIGHT = 'hawksearch_datafeed/imagecache/image_height';
    const CONFIG_INCLUDE_DISABLED = 'hawksearch_datafeed/feed/itemstatus';
    const CONFIG_BUFFER_SIZE = '65536';
    const CONFIG_OUTPUT_EXTENSION = 'txt';
    const CONFIG_FIELD_DELIMITER = 'tab';
    const CONFIG_SELECTED_STORES = 'hawksearch_datafeed/feed/stores';
    const CONFIG_CRONLOG_FILENAME = 'hawksearchCronLog.log';
    const CONFIG_CRON_ENABLE = 'hawksearch_datafeed/feed/cron_enable';
    const CONFIG_CRON_EMAIL = 'hawksearch_datafeed/feed/cron_email';
    const CONFIG_CRON_IMAGECACHE_ENABLE = 'hawksearch_datafeed/imagecache/cron_enable';
    const CONFIG_CRON_IMAGECACHE_EMAIL = 'hawksearch_datafeed/imagecache/cron_email';
    const CONFIG_TRIGGER_REINDEX = 'hawksearch_datafeed/feed/reindex';
    /**
     * @var ProxyHelper
     */
    private $proxyHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param Filesystem $filesystem
     * @param Schedule $cronSchedule
     * @param StoreManagerInterface $storeManager
     * @param ProxyHelper $proxyHelper
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        Schedule $cronSchedule,
        StoreManagerInterface $storeManager,
        ProxyHelper $proxyHelper
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
        $this->cronSchedule = $cronSchedule;
        $this->proxyHelper = $proxyHelper;
        $this->storeManager = $storeManager;
    }

    public function getConfigurationData($data) {
        $storeScope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($data, $storeScope, $this->storeManager->getStore()->getCode());
    }

    public function getTriggerReindex() {
        return $this->scopeConfig->isSetFlag(self::CONFIG_TRIGGER_REINDEX, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getCode());
    }

    public function moduleIsEnabled() {
        return $this->getConfigurationData(self::CONFIG_MODULE_ENABLED);
    }

    public function loggingIsEnabled() {
        return $this->getConfigurationData(self::CONFIG_LOGGING_ENABLED);
    }

    public function includeOutOfStockItems() {
        return $this->getConfigurationData(self::CONFIG_INCLUDE_OOS);
    }

    public function includeDisabledItems() {
        return $this->getConfigurationData(self::CONFIG_INCLUDE_DISABLED);
    }

    public function getBatchLimit() {
        return $this->getConfigurationData(self::CONFIG_BATCH_LIMIT);
    }

    public function getGenEmail() {
        return $this->getConfigurationData('trans_email/ident_general/email');
    }

    public function getGenName() {
        return $this->getConfigurationData('trans_email/ident_general/name');
    }

    public function getImageWidth() {
        return $this->getConfigurationData(self::CONFIG_IMAGE_WIDTH);
    }

    public function getImageHeight() {
        return $this->getConfigurationData(self::CONFIG_IMAGE_HEIGHT);
    }

    public function getCronEmail() {
        return $this->getConfigurationData(self::CONFIG_CRON_EMAIL);
    }

    public function getFieldDelimiter() {
        if (strtolower(self::CONFIG_FIELD_DELIMITER) == 'tab') {
            return "\t";
        } else {
            return ",";
        }
    }

    public function getBufferSize() {
        $size = self::CONFIG_BUFFER_SIZE;
        return is_numeric($size) ? $size : null;
    }

    public function getOutputFileExtension() {
        return self::CONFIG_OUTPUT_EXTENSION;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFeedFilePath() {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_FEED_PATH);
        if(!$relPath) {
            $relPath = self::DEFAULT_FEED_PATH;
        }
        /** @var \Magento\Framework\Filesystem\Directory\Write $writer */
        $mediaWriter = $this->filesystem->getDirectoryWrite('media');
        $mediaWriter->create($relPath);

        return $mediaWriter->getAbsolutePath($relPath);
    }

    public function getLockFilename() {
        return self::CONFIG_LOCK_FILENAME;
    }

    public function getSummaryFilename() {
        return $this->getFeedFilePath() . DIRECTORY_SEPARATOR . self::CONFIG_SUMMARY_FILENAME;
    }

    public function getSelectedStores() {
        return explode(',', $this->getConfigurationData(self::CONFIG_SELECTED_STORES));
    }

    public function getCronEnabled() {
        return $this->getConfigurationData(self::CONFIG_CRON_ENABLE);
    }

    public function getCronLogFilename() {
        return self::CONFIG_CRONLOG_FILENAME;
    }

    public function getCronImagecacheEnable() {
        return $this->getConfigurationData(self::CONFIG_CRON_IMAGECACHE_ENABLE);
    }

    public function getCronImagecacheEmail() {
        return $this->getConfigurationData(self::CONFIG_CRON_IMAGECACHE_EMAIL);
    }

    public function isFeedLocked() {
        $lockfile = implode(DIRECTORY_SEPARATOR, array($this->getFeedFilePath(), $this->getLockFilename()));
        if (file_exists($lockfile)) {
            return true;
        }
        return false;
    }

    public function createFeedLocks($scriptName = '') {
        $lockfilename = implode(DIRECTORY_SEPARATOR, array($this->getFeedFilePath(), $this->getLockFilename()));
        return file_put_contents($lockfilename, json_encode(['date' => date('Y-m-d H:i:s'), 'script' => $scriptName]));
    }

    public function removeFeedLocks($kill = false) {
        $lockfilename = implode(DIRECTORY_SEPARATOR, array($this->getFeedFilePath(), $this->getLockFilename()));

        if (file_exists($lockfilename)) {
            $data = json_decode(file_get_contents($lockfilename));
            if(!empty($data) && isset($data->script) && $kill){
                $procs = shell_exec(sprintf('pgrep -af %s', preg_replace('/^(.)/', '[${1}]', $data->script)));
                if($procs) {
                    $procs = explode("\n", $procs);
                    foreach ($procs as $proc) {
                        $pid = explode(' ', $proc, 2)[0];
                        if(is_numeric($pid)){
                            exec(sprintf('kill %s', $pid));
                        }
                    }
                }
            }
            return unlink($lockfilename);
        }
        return false;
    }

    public function runDatafeed() {
        $tmppath = $this->filesystem->getDirectoryWrite('tmp')->getAbsolutePath();
        $tmpfile = tempnam($tmppath, 'hawkfeed_');

        $parts = explode(DIRECTORY_SEPARATOR, __FILE__);

        array_pop($parts);
        $parts[] = 'Runfeed.php';
        $runfile = implode(DIRECTORY_SEPARATOR, $parts);
        $root = BP;

        $f = fopen($tmpfile, 'w');

        $phpbin = PHP_BINDIR . DIRECTORY_SEPARATOR . "php";

        fwrite($f, "$phpbin -d memory_limit=-1 $runfile -r $root -t $tmpfile\n");
        fclose($f);

        $cronlog = implode(DIRECTORY_SEPARATOR, array($this->getFeedFilePath(), $this->getCronLogFilename()));

        shell_exec("/bin/sh $tmpfile > $cronlog 2>&1 &");
    }

    /**
     * @param $summary
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function writeSummary($summary) {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_FEED_PATH);
        if(!$relPath) {
            $relPath = self::DEFAULT_FEED_PATH;
        }

        $summaryFile = implode(DIRECTORY_SEPARATOR, [$relPath, self::CONFIG_SUMMARY_FILENAME]);
        $writer = $this->filesystem->getDirectoryWrite('media');
        $writer->writeFile($summaryFile, json_encode($summary, JSON_PRETTY_PRINT));
    }
    public function refreshImageCache() {
        $tmppath = $this->filesystem->getDirectoryWrite('tmp')->getAbsolutePath();
        $tmpfile = tempnam($tmppath, 'hawkimage_');

        $parts = explode(DIRECTORY_SEPARATOR, __FILE__);
        array_pop($parts);
        $parts[] = 'Runfeed.php';
        $runfile = implode(DIRECTORY_SEPARATOR, $parts);
        $root = BP;

        $f = fopen($tmpfile, 'w');

        $phpbin = PHP_BINDIR . DIRECTORY_SEPARATOR . "php";

        fwrite($f, "$phpbin -d memory_limit=-1 $runfile -i true -r $root -t $tmpfile\n");
        fclose($f);

        $cronlog = implode(DIRECTORY_SEPARATOR, array($this->getFeedFilePath(), $this->getCronLogFilename()));
        shell_exec("/bin/sh $tmpfile > $cronlog 2>&1 &");
    }

    /**
     * @param $basename
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getPathForFile($basename) {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_FEED_PATH);
        if(!$relPath) {
            $relPath = self::DEFAULT_FEED_PATH;
        }

        $dir = implode(DIRECTORY_SEPARATOR, [$relPath, $this->storeManager->getStore()->getCode()]);

        $mediaWriter = $this->filesystem->getDirectoryWrite('media');
        $mediaWriter->create($dir);

        return sprintf('%s%s%s.%s', $mediaWriter->getAbsolutePath($dir), DIRECTORY_SEPARATOR, $basename, $this->getOutputFileExtension());
    }

    /**
     * @return string
     */
    public function triggerReindex()
    {
        // TODO: this is a cross module dependency. remove somehow (create a combined module or emit an event...)
        if ($this->getTriggerReindex()) {
            $apiUrl = $this->proxyHelper->getApiUrl();
            $apiKey = $this->proxyHelper->getApiKey();

            $headers = [
                'Accept:application/json',
                'Cache-Control:no-cache',
                'X-HawkSearch-ApiKey:' . $apiKey,
                'Content-Length:0'
            ];

            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl . 'index');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, []);
                $result = curl_exec($ch);
                $response = json_encode($result);
            } catch (\Exception $e) {
                $response = json_encode(['error' => $e->getMessage()]);
            }
            return $response;
        } else {
            return null;
        }
    }
}
