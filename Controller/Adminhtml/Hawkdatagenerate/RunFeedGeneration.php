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

namespace HawkSearch\Datafeed\Controller\Adminhtml\Hawkdatagenerate;

use DateTime;
use Exception;
use HawkSearch\Datafeed\Model\Task\Exception\AlreadyScheduledException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskException;
use HawkSearch\Datafeed\Model\Task\ScheduleDatafeed\Task;
use HawkSearch\Datafeed\Model\Task\ScheduleDatafeed\TaskResults;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class RunFeedGeneration extends Action
{
    /** @var Task */
    private $task;

    /** @var TimezoneInterface */
    private $timezone;

    /**
     * @param Context $context
     * @param Task $task
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        Task $task,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->task     = $task;
        $this->timezone = $timezone;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        try {
            /** @var TaskResults $taskResults */
            $taskResults = $this->task->execute();
            $this->reportSuccess($taskResults);
        } catch (AlreadyScheduledException $exception) {
            $this->messageManager->addErrorMessage(__('Feed Generation is already scheduled for the next CRON run.'));
        } catch (TaskException $exception) {
            $this->messageManager->addErrorMessage(__('An error occurred: ' . $exception->getMessage()));
        }

        // return to previous page
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
    }

    /**
     * @param TaskResults $results
     */
    private function reportSuccess(TaskResults $results) : void
    {
        try {
            $scheduledAt = $this->timezone
                ->date(new DateTime($results->getScheduledAt()))
                ->format(DateTime::RFC850);
            $this->messageManager->addSuccessMessage(__('Feed Generation successfully scheduled: ') . $scheduledAt);
        } catch (Exception $exception) {
            $this->messageManager->addSuccessMessage(__('Feed Generation successfully scheduled.'));
        }
    }
}
