<?php
/**
 * Copyright (c) 2018 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace HawkSearch\Datafeed\Cron;

use HawkSearch\Datafeed\Helper\Data;
use HawkSearch\Datafeed\Model\Datafeed as DatafeedModel;
use HawkSearch\Datafeed\Model\EmailFactory;
use HawkSearch\Datafeed\Model\Task\Datafeed\Task;
use HawkSearch\Datafeed\Model\Task\Datafeed\TaskOptions;
use HawkSearch\Datafeed\Model\Task\Datafeed\TaskOptionsFactory;
use HawkSearch\Datafeed\Model\Task\Exception\TaskException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskLockException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskUnlockException;

class DataFeed
{
    public const JOB_CODE = 'hawksearch_datafeed';

    /** @var EmailFactory */
    private $emailFactory;

    /** @var Data */
    private $helper;

    /** @var Task */
    private $task;

    /** @var TaskOptionsFactory */
    private $taskOptionsFactory;

    /**
     * DataFeed constructor.
     * @param EmailFactory $emailFactory
     * @param Data $helper
     * @param Task $task
     * @param TaskOptionsFactory $taskOptionsFactory
     */
    public function __construct(
        EmailFactory $emailFactory,
        Data $helper,
        Task $task,
        TaskOptionsFactory $taskOptionsFactory
    ) {
        $this->emailFactory       = $emailFactory;
        $this->helper             = $helper;
        $this->task               = $task;
        $this->taskOptionsFactory = $taskOptionsFactory;
    }

    public function execute()
    {
        if (! $this->helper->getCronEnabled()) {
            return;
        }

        $message = $this->executeTask();

        $this->emailFactory->create()
            ->sendEmail([
                'jobTitle' => DatafeedModel::SCRIPT_NAME,
                'message'  => $message
            ]);
    }

    /**
     * Executes the underlying task, and returns execution message.
     * @return string
     */
    private function executeTask() : string
    {
        /** @var TaskOptions $taskOptions */
        $taskOptions = $this->taskOptionsFactory->create();

        try {
            $this->task->execute($taskOptions);
        } catch (TaskLockException $exception) {
            return 'Hawksearch is currently locked, not generating feed at this time.';
        } catch (TaskUnlockException $exception) {
            return 'HawkSearch Datafeed lock failed to release. Please verify that the job completed successfully.';
        } catch (TaskException $exception) {
            return sprintf('There has been an error: %s', $exception->getMessage());
        }

        return 'HawkSearch Datafeed Generated!';
    }
}
