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
namespace HawkSearch\Datafeed\Model\Config\Backend;

class Cron extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Magento\Framework\View\Asset\MergeService
     */
    protected $_mergeService;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\View\Asset\MergeService $mergeService
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\View\Asset\MergeService $mergeService,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_mergeService = $mergeService;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate a base URL field value
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        try {
            if (!$this->isValidCronString($value)) {
                $this->_validateFullyQualifiedUrl($value);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $field = $this->getFieldConfig();
            $label = $field && is_array($field) ? $field['label'] : 'value';
            $msg = __('Invalid %1. %2', $label, $e->getMessage());
            $error = new \Magento\Framework\Exception\LocalizedException($msg, $e);
            throw $error;
        }
    }



	public function isValidCronString($string) {
		$e = preg_split('#\s+#', $string, null, PREG_SPLIT_NO_EMPTY);
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

	public function testCronPartSimple($p, $e) {
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

	public function testCronPart($p, $e) {

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
			if(count($ops) !== 2){
				return false;
			}
			if ($ops[0] > $ops[1] || $ops[0] < $range[$p][0] || $ops[0] > $range[$p][1] || $ops[1] < $range[$p][0] || $ops[1] > $range[$p][1]) {
				return false;
			}
		} else {
			
			$object_manager = \Magento\Framework\App\ObjectManager::getInstance();

			$a = $object_manager->get('Magento\Cron\Model\Schedule')->getNumeric($v);
			if($a < $range[$p][0] || $a > $range[$p][1]){
				return false;
			}
		}
		return true;
	}



    private function _validateFullyQualifiedUrl($value)
    {
        if (!$this->_isFullyQualifiedUrl($value)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Specify a fully qualified Cron Example: 0 * * * *.'));
        }
    }

    /**
     * Whether the provided value can be considered as a fully qualified URL
     *
     * @param string $value
     * @return bool
     */
    private function _isFullyQualifiedUrl($value)
    {
        $url = parse_url($value);
        return isset($url['scheme']) && isset($url['host']) && preg_match('/\/$/', $value);
    }

    /**
     * Clean compiled JS/CSS when updating url configuration settings
     *
     * @return $this
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            switch ($this->getPath()) {
                case \Magento\Store\Model\Store::XML_PATH_UNSECURE_BASE_URL:
                case \Magento\Store\Model\Store::XML_PATH_UNSECURE_BASE_MEDIA_URL:
                case \Magento\Store\Model\Store::XML_PATH_SECURE_BASE_URL:
                case \Magento\Store\Model\Store::XML_PATH_SECURE_BASE_MEDIA_URL:
                    $this->_mergeService->cleanMergedJsCss();
                    break;
            }
        }
        return parent::afterSave();
    }
}
