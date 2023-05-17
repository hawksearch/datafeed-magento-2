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
namespace HawkSearch\Datafeed\Console\Command;

use HawkSearch\Datafeed\Model\Task\Exception\AlreadyScheduledException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskException;
use HawkSearch\Datafeed\Model\Task\ScheduleDatafeed\Task;
use HawkSearch\Datafeed\Model\Task\ScheduleDatafeed\TaskResults;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleDatafeed extends Command
{
    /** @var Task */
    private $task;

    /**
     * @param Task $task
     * @param string|null $name
     */
    public function __construct(
        Task $task,
        string $name = null
    ) {
        $this->task = $task;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('hawksearch:datafeed:schedule-datafeed')
            ->setDescription('Creates a cron schedule entry to generate datafeed in the next cron run.');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            /** @var TaskResults $results */
            $results = $this->task->execute();
            $this->reportSuccess($output, $results);
        } catch (AlreadyScheduledException $exception) {
            $output->writeln('Failed to schedule datafeed generation: a pending job already exists');
        } catch (TaskException $exception) {
            $output->writeln('Failed to schedule datafeed generation: ' . $exception->getMessage());
        }
    }

    /**
     * Prints results info to the console.
     * @param OutputInterface $output
     * @param TaskResults $results
     */
    private function reportSuccess(
        OutputInterface $output,
        TaskResults $results
    ) {
        $output->writeln('Job was scheduled successfully, and can be viewed in the cron_schedule table:');
        $output->writeln('schedule_id: ' . $results->getJobEntityId());
        $output->writeln('created_at: ' . $results->getCreatedAt() . ' UTC');
        $output->writeln('scheduled_at: ' . $results->getScheduledAt() . ' UTC');
    }
}
