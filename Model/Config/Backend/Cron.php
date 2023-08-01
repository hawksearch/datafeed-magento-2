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

namespace HawkSearch\Datafeed\Model\Config\Backend;

/*
 * see vendor/magento/module-cron/Model/Config/Converter/Db.php to decipher
 * how cron config can be set in the DB.
 */
use HawkSearch\Datafeed\Model\Validator\CronString;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Cron extends \Magento\Framework\App\Config\Value
{
    const CRON_DATAFEED_CRON_EXPR = 'crontab/default/jobs/hawksearch_datafeed/schedule';

    private $resourceConfig;
    /**
     * @var CronString
     */
    private $cronString;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Config $resourceConfig,
        CronString $cronString,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->cronString = $cronString;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    protected function _getValidationRulesBeforeSave()
    {
        return $this->cronString;
    }

    public function afterSave()
    {
        if ($this->getPath() == "hawksearch_datafeed/feed/cron_string") {
            $this->resourceConfig->saveConfig(self::CRON_DATAFEED_CRON_EXPR, $this->getValue(), 'default', 0);
        }

        return parent::afterSave();
    }
}
