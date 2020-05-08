<?php


namespace HawkSearch\Datafeed\Console\Command;


use HawkSearch\Datafeed\Model\Task\GenerateDatafeed\Task;
use HawkSearch\Datafeed\Model\Task\GenerateDatafeed\TaskOptionsFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleDatafeed extends Command
{
    /** @var Task */
    private $task;

    /** @var TaskOptionsFactory */
    private $taskOptionsFactory;

    /**
     * @param Task $task
     * @param TaskOptionsFactory $taskOptionsFactory
     * @param string|null $name
     */
    public function __construct(
        Task $task,
        TaskOptionsFactory $taskOptionsFactory,
        string $name = null
    )
    {
        $this->task               = $task;
        $this->taskOptionsFactory = $taskOptionsFactory;
        parent::__construct( $name );
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName( 'hawksearch:datafeed:schedule-datafeed' )
            ->setDescription( 'Creates a cron schedule entry to generate datafeed in the next cron run.' );
        parent::configure();
    }

    /**
     * Console command entry point.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    )
    {

    }
}
