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
declare(strict_types=1);

namespace HawkSearch\Datafeed\Console\Command;

use HawkSearch\Datafeed\Model\Task\Datafeed\Task;
use HawkSearch\Datafeed\Model\Task\Datafeed\TaskOptionsFactory;
use HawkSearch\Datafeed\Model\Task\Exception\TaskException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskLockException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskUnlockException;
use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class DataFeed extends Command
{
    const FORCE_MODE = 'force';
    const INPUT_STORES = 'stores';

    /**
     * @var Task
     */
    private $task;

    /**
     * @var TaskOptionsFactory
     */
    private $taskOptionsFactory;

    /**
     * @var ConfigFeed
     */
    private $configFeed;

    /**
     * @var State
     */
    private $state;

    /**
     * @param Task $task
     * @param TaskOptionsFactory $taskOptionsFactory
     * @param State $state
     * @param null $name
     */
    public function __construct(
        Task $task,
        TaskOptionsFactory $taskOptionsFactory,
        State $state,
        ConfigFeed $configFeed,
        $name = null
    ) {
        parent::__construct($name);
        $this->task = $task;
        $this->taskOptionsFactory = $taskOptionsFactory;
        $this->configFeed = $configFeed;
        $this->state  = $state;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('hawksearch:generate-feed')
            ->setDescription('Generate the HawkSearch data feed')
            ->setDefinition($this->getInputList());
        parent::configure();
    }

    /**
     * Get a list of command arguments
     *
     * @return array
     */
    protected function getInputList()
    {
        return [
            new InputArgument(
                self::INPUT_STORES,
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Space-separated list of store codes or omit to run datafeed for all stores.'
            ),
            new InputOption(
                self::FORCE_MODE,
                [ '-f', '--force' ],
                InputOption::VALUE_NONE,
                'Force datafeed to run even if lock present.'
            )
        ];
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_CRONTAB);

            $options = $this->taskOptionsFactory->create();
            $stores = $input->getArgument(self::INPUT_STORES);
            if (!empty($stores)) {
                $this->configFeed->setStoresToFilter($stores);
            }

            if ($input->getOption(self::FORCE_MODE) === self::FORCE_MODE) {
                $options->setForceMode(true);
            }

            $this->task->execute($options);
            $output->writeln('Done');

            return Cli::RETURN_SUCCESS;
        } catch (TaskLockException $exception) {
            $output->writeln('Unable to acquire feed lock, feed not generating.');
        } catch (TaskUnlockException $exception) {
            $output->writeln(
                'HawkSearch Datafeed lock failed to release. Please verify that the job completed successfully.'
            );
        } catch (TaskException | \Exception $exception) {
            $output->writeln(sprintf('There has been an error: %s', $exception->getMessage()));
        }

        return Cli::RETURN_FAILURE;
    }
}
