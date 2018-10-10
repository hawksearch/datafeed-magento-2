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

namespace HawkSearch\Datafeed\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;

class Data
{


    protected $scopeConfig;
    protected $_storeManager;
    private $filesystem;


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

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->filesystem = $filesystem;
    }


    public function getConfigurationData($data) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($data, $storeScope);

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

    public function getFeedFilePath() {
        $relPath = $this->scopeConfig->getValue(self::CONFIG_FEED_PATH);
        if(!$relPath) {
            $relPath = self::DEFAULT_FEED_PATH;
        }
        $mediaRoot = $this->filesystem->getDirectoryWrite('media')->getAbsolutePath();

        if(strpos(strrev($mediaRoot), '/') !== 0) {
            $fullPath = implode(DIRECTORY_SEPARATOR, array($mediaRoot, $relPath));
        } else {
            $fullPath = $mediaRoot . $relPath;
        }

        if(!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        return $fullPath;

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

        fwrite($f, "$phpbin -d memory_limit=6144M $runfile -r $root -t $tmpfile\n");
        fclose($f);


        $cronlog = implode(DIRECTORY_SEPARATOR, array($this->getFeedFilePath(), $this->getCronLogFilename()));


        shell_exec("/bin/sh $tmpfile > $cronlog 2>&1 &");

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

        fwrite($f, "$phpbin -d memory_limit=6144M $runfile -i true -r $root -t $tmpfile\n");
        fclose($f);

        $cronlog = implode(DIRECTORY_SEPARATOR, array($this->getFeedFilePath(), $this->getCronLogFilename()));
        shell_exec("/bin/sh $tmpfile > $cronlog 2>&1 &");

    }

    public function isValidCronString($str) {
        $e = preg_split('#\s+#', $str, null, PREG_SPLIT_NO_EMPTY);
        if (sizeof($e) < 5 || sizeof($e) > 6) {
            return false;
        }
        $isValid = $this->testCronPartSimple(0, $e)
            && $this->testCronPartSimple(1, $e)
            && $this->testCronPartSimple(2, $e)
            && $this->testCronPartSimple(3, $e)
            && $this->testCronPartSimple(4, $e);

        if (!$isValid) {
            return false;
        }
        return true;

    }

    private function testCronPartSimple($p, $e) {
        if ($p === 0) {
            // we only accept a single numeric value for the minute and it must be in range
            if (!ctype_digit($e[$p])) {
                return false;
            }
            if ($e[0] < 0 || $e[0] > 59) {
                return false;
            }
            return true;
        }
        return $this->testCronPart($p, $e);
    }

    private function testCronPart($p, $e) {

        if ($e[$p] === '*') {
            return true;
        }

        foreach (explode(',', $e[$p]) as $v) {
            if (!$this->isValidCronRange($p, $v)) {
                return false;
            }
        }
        return true;
    }

    private function isValidCronRange($p, $v) {
        static $range = array(array(0, 59), array(0, 23), array(1, 31), array(1, 12), array(0, 6));
        //$n = Mage::getSingleton('cron/schedule')->getNumeric($v);

        // steps can be used with ranges
        if (strpos($v, '/') !== false) {
            $ops = explode('/', $v);
            if (count($ops) !== 2) {
                return false;
            }
            // step must be digit
            if (!ctype_digit($ops[1])) {
                return false;
            }
            $v = $ops[0];
        }
        if (strpos($v, '-') !== false) {
            $ops = explode('-', $v);
            if (count($ops) !== 2) {
                return false;
            }
            if ($ops[0] > $ops[1] || $ops[0] < $range[$p][0] || $ops[0] > $range[$p][1] || $ops[1] < $range[$p][0] || $ops[1] > $range[$p][1]) {
                return false;
            }
        } else {
            $a = Mage::getSingleton('cron/schedule')->getNumeric($v);
            if ($a < $range[$p][0] || $a > $range[$p][1]) {
                return false;
            }
        }
        return true;
    }

    public function triggerReindex(\Magento\Store\Model\Store $store) {
        $apiUrl = $this->getTriggerReindexUrl();
        $client = new \Zend_Http_Client();
        $client->setUri($apiUrl);
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setHeaders('X-HawkSearch-ApiKey', $this->getConfigurationData('hawksearch_proxy/proxy/hawksearch_api_key'));
        $client->setHeaders('Accept', 'application/json');

        //$response = $client->request();

        return isset($response) ? true : false;

    }
    private function getTriggerReindexUrl() {
        $trackingUrl = $this->getTrackingUrl();
        return $trackingUrl . 'api/v3/index';
    }
    public function getTrackingUrl() {
        $mode = $this->getConfigurationData(\HawkSearch\Proxy\Helper\Data::CONFIG_PROXY_MODE);
        $trackingUrl = $this->getConfigurationData($mode ? 'hawksearch_proxy/proxy/tracking_url_live' : 'hawksearch_proxy/proxy/tracking_url_staging');
        return rtrim($trackingUrl, "/") . '/';
    }
}