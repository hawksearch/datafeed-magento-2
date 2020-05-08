<?php


namespace HawkSearch\Datafeed\Model\Task\GenerateDatafeed;


use HawkSearch\Datafeed\Model\Task\TaskLockException;

class Task
{
    /** @var TaskLock */
    private $taskLock;

    /** @var TaskResultsFactory */
    private $taskResultsFactory;

    /**
     * @param TaskLock $taskLock
     * @param TaskResultsFactory $taskResultsFactory
     */
    public function __construct(
        TaskLock $taskLock,
        TaskResultsFactory $taskResultsFactory
    )
    {
        $this->taskLock           = $taskLock;
        $this->taskResultsFactory = $taskResultsFactory;
    }

    /**
     * Task entry point. Every run requires a set of task options an produces a new set of task results.
     * @param TaskOptions $options
     * @return TaskResults
     * @throws TaskLockException
     */
    public function execute( TaskOptions $options ) : TaskResults
    {
        /** @var TaskResults $results */
        $results = $this->taskResultsFactory->create();
        $results->setOptionsUsed( $options );

        if ( ! $options->isForceMode() ) {
            $this->taskLock->lock();
        }

        // TODO not yet implemented

        return $results;
    }
}
