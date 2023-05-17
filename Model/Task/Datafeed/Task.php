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
namespace HawkSearch\Datafeed\Model\Task\Datafeed;

use HawkSearch\Datafeed\Model\Datafeed;
use HawkSearch\Datafeed\Model\Task\Exception\TaskException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskLockException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskUnlockException;
use Magento\Framework\Exception\FileSystemException;

class Task
{
    /** @var Datafeed */
    private $datafeed;

    /** @var TaskLock */
    private $taskLock;

    /** @var TaskResultsFactory */
    private $taskResultsFactory;

    /**
     * @param Datafeed $datafeed
     * @param TaskLock $taskLock
     * @param TaskResultsFactory $taskResultsFactory
     */
    public function __construct(
        Datafeed $datafeed,
        TaskLock $taskLock,
        TaskResultsFactory $taskResultsFactory
    ) {
        $this->datafeed = $datafeed;
        $this->taskLock           = $taskLock;
        $this->taskResultsFactory = $taskResultsFactory;
    }

    /**
     * Task entry point. Every run requires a set of task options an produces a new set of task results.
     * @param TaskOptions $options
     * @return TaskResults
     * @throws TaskLockException
     * @throws TaskUnlockException
     * @throws TaskException
     */
    public function execute(TaskOptions $options) : TaskResults
    {
        $this->lock($options);

        try {
            $this->datafeed->generateFeed();
        } catch (FileSystemException $exception) {
            throw new TaskException($exception->getMessage());
        }

        $this->unlock($options);

        /** @var TaskResults $results */
        $results = $this->taskResultsFactory->create();
        $results->setOptionsUsed($options);
        return $results;
    }

    /**
     * @param TaskOptions $options
     * @throws TaskLockException
     */
    private function lock(TaskOptions $options) : void
    {
        if ($options->isForceMode()) {
            return;
        }

        $this->taskLock->lock();
    }

    /**
     * @param TaskOptions $options
     * @throws TaskUnlockException
     */
    private function unlock(TaskOptions $options) : void
    {
        if ($options->isForceMode()) {
            return;
        }

        $this->taskLock->unlock();
    }
}
