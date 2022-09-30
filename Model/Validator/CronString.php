<?php
/**
 * Created by PhpStorm.
 * User: mageuser
 * Date: 4/6/18
 * Time: 10:47 AM
 */

namespace HawkSearch\Datafeed\Model\Validator;

use Magento\Cron\Model\Schedule;
use Magento\Framework\Validator\AbstractValidator;

class CronString extends AbstractValidator
{
    /**
     * @var Schedule
     */
    private $cronSchedule;
    private $messages;

    /**
     * CronString constructor.
     *
     * @param Schedule $cronSchedule
     */
    public function __construct(Schedule $cronSchedule)
    {
        $this->cronSchedule = $cronSchedule;
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        $this->messages = [];
        $e = preg_split('#\s+#', $value->getValue(), 0, PREG_SPLIT_NO_EMPTY);
        if (count($e) < 5 || count($e) > 6) {
            $this->messages['invalid_length'] = "Cron string should have exactly 5 parts";
        }
        $this->testCronPartSimple(0, $e);
        $this->testCronPartSimple(1, $e);
        $this->testCronPartSimple(2, $e);
        $this->testCronPartSimple(3, $e);
        $this->testCronPartSimple(4, $e);

        $this->_addMessages($this->messages);
        return empty($this->messages);
    }

    public function testCronPartSimple($p, $e)
    {
        if ($p === 0) {
            // we only accept a single numeric value for the minute and it must be in range
            if (!ctype_digit($e[$p])) {
                $this->messages['invalid_minute'] = 'Cron String: Minute part must be a single numeric value';
            }
            if ($e[0] < 0 || $e[0] > 59) {
                $this->messages['invalid_minute'] = 'Cron String: Minute part must be between 0 and 59';
            }
        } else {
            $this->testCronPart($p, $e);
        }
    }

    public function testCronPart($p, $e)
    {
        if ($e[$p] === '*') {
            return;
        }

        foreach (explode(',', (string)$e[$p]) as $v) {
            $this->isValidCronRange($p, $v);
        }
    }

    private function isValidCronRange($p, $v)
    {
        static $range = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 6]];
        // steps can be used with ranges
        if (strpos($v, '/') !== false) {
            $ops = explode('/', (string)$v);
            if (count($ops) !== 2) {
                $this->messages['invalid_range'] = sprintf('Cron String: Invalid range in part %d', $p + 1);
            }
            // step must be digit
            if (!ctype_digit($ops[1])) {
                $this->messages['invalid_step'] = sprintf('Cron String: Invalid step in part %d', $p + 1);
            }
            $v = $ops[0];
        }
        if (strpos($v, '-') !== false) {
            $ops = explode('-', (string)$v);
            if (count($ops) !== 2) {
                $this->messages['invalid_range'] = sprintf('Cron String: Invalid range in part %d', $p + 1);
            }
            if ($ops[0] > $ops[1]
                || $ops[0] < $range[$p][0]
                || $ops[0] > $range[$p][1] || $ops[1] < $range[$p][0] || $ops[1] > $range[$p][1]
            ) {
                $this->messages['invalid_range'] = sprintf('Cron String: Invalid range in part %d', $p + 1);
            }
        } else {
            $a = $this->cronSchedule->getNumeric($v);
            if ($a < $range[$p][0] || $a > $range[$p][1]) {
                $this->messages['invalid_range'] = sprintf('Cron String: Invalid range in part %d', $p + 1);
            }
        }
    }
}
