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
namespace HawkSearch\Datafeed\Model\Task\ScheduleDatafeed;

use HawkSearch\Datafeed\Model\Task\Datafeed\TaskScheduler as DatafeedTaskScheduler;
use HawkSearch\Datafeed\Model\Task\Exception\AlreadyScheduledException;
use HawkSearch\Datafeed\Model\Task\Exception\SchedulerException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskException;
use Magento\Cron\Model\Schedule;

class Task
{
    /** @var DatafeedTaskScheduler */
    private $datafeedTaskScheduler;

    /** @var TaskResultsFactory */
    private $taskResultsFactory;

    /**
     * @param TaskResultsFactory $taskResultsFactory
     * @param DatafeedTaskScheduler $datafeedTaskScheduler
     */
    public function __construct(
        TaskResultsFactory $taskResultsFactory,
        DatafeedTaskScheduler $datafeedTaskScheduler
    ) {
        $this->datafeedTaskScheduler = $datafeedTaskScheduler;
        $this->taskResultsFactory    = $taskResultsFactory;
    }

    /**
     * Task entry point.
     * Attempts to schedule datafeed generation.
     * @throws TaskException
     * @throws AlreadyScheduledException
     */
    public function execute() : TaskResults
    {
        try {
            $schedule = $this->datafeedTaskScheduler->schedule();
            return $this->createResults($schedule);
        } catch (SchedulerException $exception) {
            throw new TaskException('failed to schedule task: ' . $exception->getMessage());
        }
    }

    /**
     * Extracts results data from the schedule entity created during execution.
     * @param Schedule $schedule
     * @return TaskResults
     */
    private function createResults(Schedule $schedule) : TaskResults
    {
        /** @var TaskResults $results */
        $results = $this->taskResultsFactory->create();

        $results->setJobEntityId((int)$schedule->getId());
        $results->setCreatedAt($schedule->getCreatedAt());
        $results->setScheduledAt($schedule->getScheduledAt());

        return $results;
    }
}
