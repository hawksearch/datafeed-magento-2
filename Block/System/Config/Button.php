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

namespace HawkSearch\Datafeed\Block\System\Config;

use DateTime;
use Exception;
use HawkSearch\Datafeed\Model\Task\Datafeed\TaskScheduler;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Button extends Field
{
    /** @var TaskScheduler */
    private $taskScheduler;

    /** @var TimezoneInterface */
    private $timezone;

    /** @var Schedule */
    private $nextScheduled = null;

    /**
     * Button constructor.
     * @param Context $context
     * @param TaskScheduler $taskScheduler
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        TaskScheduler $taskScheduler,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->taskScheduler = $taskScheduler;
        $this->timezone      = $timezone;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (! $this->getTemplate()) {
            $this->setTemplate('HawkSearch_Datafeed::system/config/button/feedgenerate.phtml');
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $config = $element->getFieldConfig();
        $this->addData(
            [
                'button_label' => $config[ 'button_label' ],
                'generate_url' => $this->getUrl($config[ 'button_url' ]),
                'html_id'      => $element->getHtmlId(),
            ]
        );
        return $this->_toHtml();
    }

    /**
     * @return bool
     */
    public function isScheduledForNextRun() : bool
    {
        return $this->taskScheduler->isScheduledForNextRun();
    }

    /**
     * @return string
     */
    public function getNextScheduledTimestamp() : string
    {
        if ($this->nextScheduled === null) {
            $this->loadNextScheduled();
        }

        if ($this->nextScheduled === null) {
            return '';
        }

        try {
            $date = new DateTime($this->nextScheduled->getScheduledAt());
            return $this->timezone->date($date)->format(DateTime::RFC850);
        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getNextScheduledId() : string
    {
        if ($this->nextScheduled === null) {
            $this->loadNextScheduled();
        }

        return $this->nextScheduled
            ? (string)$this->nextScheduled->getId()
            : '';
    }

    /**
     * Loads the next scheduled datafeed generation cron job.
     */
    private function loadNextScheduled() : void
    {
        $this->nextScheduled = $this->taskScheduler->getNextScheduled();
    }
}
